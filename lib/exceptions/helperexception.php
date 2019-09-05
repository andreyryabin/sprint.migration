<?php

namespace Sprint\Migration\Exceptions;

use Exception;
use Throwable;

class HelperException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $message = is_array($message) ? implode(', ', $message) : $message;

        parent::__construct($message, $code, $previous);
    }


}