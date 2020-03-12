<?php

namespace GoPdf\Exceptions;

class ServerException extends GoPdfException
{
    public function __construct($body)
    {
        parent::__construct('A fatal error occured.', 500, $body);
    }
}
