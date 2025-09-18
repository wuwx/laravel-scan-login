<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

/**
 * Exception thrown when a scan login token has expired
 */
class TokenExpiredException extends ScanLoginException
{
    public function __construct(
        string $message = '登录令牌已过期，请刷新二维码',
        array $details = [],
        int $code = 410,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, 'TOKEN_EXPIRED', $details, $code, $previous);
    }
}