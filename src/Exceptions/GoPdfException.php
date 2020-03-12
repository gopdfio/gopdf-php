<?php

namespace GoPdf\Exceptions;

class GoPdfException extends \Exception
{
    private $body = null;

    public function __construct($message, $code, $body = null) {
        parent::__construct($message, $code);
        $this->body = $body;
    }

    public function getBody() {
        return $this->body;
    }
}
