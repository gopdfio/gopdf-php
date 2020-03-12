<?php

namespace GoPdf\Exceptions;


class InvalidApiKeyException extends GoPdfException
{
    public function __construct($body = null)
    {
        parent::__construct('Please indicate a valid API Key.', 401, $body);
    }
}
