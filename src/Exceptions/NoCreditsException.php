<?php

namespace GoPdf\Exceptions;


class NoCreditsException extends GoPdfException
{
    public function __construct($body)
    {
        parent::__construct('No remaining credits left.', 403, $body);
    }
}
