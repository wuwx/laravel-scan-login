<?php

namespace Wuwx\LaravelScanLogin\Exceptions;

/**
 * Exception thrown when attempting to use a token that has already been used
 */
class TokenAlreadyUsedException extends ScanLoginException
{
    public function __construct(
        string $message = '登录令牌已被使用，请重新生成二维码',
        array $details = [],
        int $code = 409,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, 'TOKEN_ALREADY_USED', $details, $code, $previous);
    }
}