<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

use Exception;

/**
 * Base exception class for scan login functionality
 */
class ScanLoginException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}