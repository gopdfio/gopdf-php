<?php

namespace GoPdf\Exceptions;


class InvalidRequestException extends GoPdfException
{
    public function __construct($message, $body)
    {
        parent::__construct($message, 400, $body);
    }
}
