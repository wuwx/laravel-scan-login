<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

/**
 * Exception thrown when a scan login token is not found
 */
class TokenNotFoundException extends ScanLoginException
{
    public function __construct(
        string $message = '登录令牌不存在或无效',
        int $code = 404,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}