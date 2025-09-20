<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

/**
 * Exception thrown when login credentials are invalid
 */
class InvalidCredentialsException extends ScanLoginException
{
    public function __construct(
        string $message = '用户名或密码错误',
        int $code = 401,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}