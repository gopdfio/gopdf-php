<?php

namespace GoPdf\Exceptions;


class RateLimitException extends GoPdfException
{
    public function __construct($body)
    {
        parent::__construct('Please indicate a valid API Key.', 401, $body);
    }
}
