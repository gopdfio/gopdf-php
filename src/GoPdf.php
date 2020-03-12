<?php

namespace GoPdf;

use GoPdf\Exceptions;

class GoPdf
{
    const WATERMARK_TEXT = 1;
    const WATERMARK_IMAGE = 2;
    const WATERMARK_PDF = 3;

    private static $apiKey = null;

    private static $apiBase = 'https://gopdf.dinacode.com/api/v1';

    public static function getApiKey()
    {
        return self::$apiKey;
    }

    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;
    }

    public static function convertTo($source, $options = [], $output = null)
    {
        $instance = new self($options);
        $instance->convert($source);

        if (is_null($output)) {
            return $instance->getData();
        }

        return $instance->save($output);
    }


    private static function _handleError($response, $statusCode)
    {
        $body = json_decode($response, true);
        if (is_null($body)) {
            throw new Exceptions\GoPdfException(
                'Invalid response from the server.',
                500
            );
        }

        switch ($statusCode) {
            case 400:
                if (!empty($body['message'])) {
                    throw new Exceptions\InvalidRequestException($body['message'], $body);
                }

                if (isset($body['error']) && is_string($body['error'])) {
                    throw new Exceptions\InvalidRequestException($body['error'], $body);
                }

                reset($body['errors']);
                $key = key($body['errors']);
                $message = $key.' : '.$body['errors'][$key][0];
                throw new Exceptions\InvalidRequestException($message, $body);
            case 401:
                throw new Exceptions\InvalidApiKeyException($body);
            case 403:
                throw new Exceptions\NoCreditsException($body);
            case 429:
                throw new Exceptions\RateLimitException($body);
            default:
                throw new Exceptions\ServerException($body);
        }
    }

    private $options = [];

    private $data = null;

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function __set($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }


    public function __get($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return null;
    }

    public function setMargin($margin)
    {
        $this->options['margin'] = [
            'top'    => (isset($margin['top']) ? $margin['top'] : null),
            'right'  => (isset($margin['right']) ? $margin['right'] : null),
            'bottom' => (isset($margin['bottom']) ? $margin['bottom'] : null),
            'left'   => (isset($margin['left']) ? $margin['left'] : null),
        ];
        return $this;
    }

    public function setAuth($username, $password)
    {
        $this->options['auth'] = [
            'username' => $username,
            'password' => $password
        ];
        return $this;

    }

    public function setCookies($cookies)
    {
        foreach ($cookies as $cookie) {
            $this->addCookie(
                $cookie['name'],
                (isset($cookie['value']) ? $cookie['value'] : null),
                (isset($cookie['secure']) ? $cookie['secure'] : false),
                (isset($cookie['httpOnly']) ? $cookie['httpOnly'] : false)
            );
        }
        return $this;

    }

    public function addCookie($name, $value = null, $secure = false, $httpOnly = false)
    {
        if (!isset($this->options['cookies'])) {
            $this->options['cookies'] = [];
        }

        $this->options['cookies'][] = [
            'name' => $name,
            'value' => $value,
            'secure' => $secure,
            'http_only' => $httpOnly
        ];
        return $this;

    }

    public function clearCookies()
    {
        $this->options['cookies'] = [];
        return $this;
    }

    public function setHTTPHeaders($headers)
    {
        foreach ($headers as $name=>$value) {
            $this->addHTTPHeader($name, $value);
        }
        return $this;

    }

    public function addHTTPHeader($name, $value = null)
    {
        if (!isset($this->options['http_headers'])) {
            $this->options['http_headers'] = [];
        }

        $this->options['http_headers'][$name] = $value;
        return $this;
    }

    public function clearHTTPHeaders()
    {
        $this->options['http_headers'] = [];
        return $this;
    }

    public function setHeader($source, $spacing = null)
    {
        $this->options['header'] = ['source' => $source, 'spacing' => $spacing];
        return $this;
    }

    public function setFooter($source, $spacing = null)
    {
        $this->options['footer'] = ['source' => $source, 'spacing' => $spacing];
        return $this;
    }

    public function protect($options)
    {
        $this->options['protection'] = [
            'author'         => (isset($options['author']) ? $options['author'] : null),
            'user_password'  => (isset($options['userPassword']) ? $options['userPassword'] : null),
            'owner_password' => (isset($options['ownerPassword']) ? $options['ownerPassword'] : null),
            'no_print'       => (isset($options['noPrint']) ? $options['noPrint'] : null),
            'no_copy'        => (isset($options['noCopy']) ? $options['noCopy'] : null),
            'no_modify'      => (isset($options['noModify']) ? $options['noModify'] : null)
        ];
        return $this;
    }

    public function watermark($options)
    {
        $this->options['watermark'] = $options;
        return $this;
    }

    public function convert($source)
    {
        $this->options['source'] = $source;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::$apiBase.'/go',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($this->options),
            CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
            CURLOPT_USERPWD => self::$apiKey.':'
        ]);
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);


        if ($statusCode === 200) {
            if (isset($this->options['filename'])) {
                /** 
                 * "filename" will save the resulting PDF to Amazon S3 for 2 days,
                 * and will return a JSON response
                 */
                $this->data = json_decode($response, true);
            } else {
                $this->data = $response;
            }
            return null;
        }

        return self::_handleError($response, $statusCode);
    }

    public function getData()
    {
        return $this->data;
    }

    public function save($filepath)
    {
        if (is_null($this->getData())) {
            throw new Exceptions\GoPdfException('A fatal error occured while trying to save the file to disk.', 500);
        }

        return file_put_contents($filepath, $this->getData());
    }
}
