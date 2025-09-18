<?php

namespace Wuwx\LaravelScanLogin\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Wuwx\LaravelScanLogin\Exceptions\ScanLoginException;
use Wuwx\LaravelScanLogin\Exceptions\TokenExpiredException;
use Wuwx\LaravelScanLogin\Exceptions\TokenNotFoundException;
use Wuwx\LaravelScanLogin\Exceptions\InvalidCredentialsException;
use Wuwx\LaravelScanLogin\Exceptions\TokenAlreadyUsedException;

class ScanLoginExceptionTest extends TestCase
{
    public function test_base_exception_creation()
    {
        $exception = new ScanLoginException(
            'Test message',
            'TEST_ERROR',
            ['key' => 'value'],
            500
        );

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals('TEST_ERROR', $exception->getErrorCode());
        $this->assertEquals(['key' => 'value'], $exception->getDetails());
        $this->assertEquals(500, $exception->getCode());
    }

    public function test_base_exception_to_array()
    {
        $exception = new ScanLoginException(
            'Test message',
            'TEST_ERROR',
            ['key' => 'value']
        );

        $expected = [
            'success' => false,
            'error' => [
                'code' => 'TEST_ERROR',
                'message' => 'Test message',
                'details' => ['key' => 'value'],
            ],
        ];

        $this->assertEquals($expected, $exception->toArray());
    }

    public function test_token_expired_exception()
    {
        $exception = new TokenExpiredException();

        $this->assertEquals('登录令牌已过期，请刷新二维码', $exception->getMessage());
        $this->assertEquals('TOKEN_EXPIRED', $exception->getErrorCode());
        $this->assertEquals(410, $exception->getCode());
    }

    public function test_token_not_found_exception()
    {
        $exception = new TokenNotFoundException();

        $this->assertEquals('登录令牌不存在或无效', $exception->getMessage());
        $this->assertEquals('TOKEN_NOT_FOUND', $exception->getErrorCode());
        $this->assertEquals(404, $exception->getCode());
    }

    public function test_invalid_credentials_exception()
    {
        $exception = new InvalidCredentialsException();

        $this->assertEquals('用户名或密码错误', $exception->getMessage());
        $this->assertEquals('INVALID_CREDENTIALS', $exception->getErrorCode());
        $this->assertEquals(401, $exception->getCode());
    }

    public function test_token_already_used_exception()
    {
        $exception = new TokenAlreadyUsedException();

        $this->assertEquals('登录令牌已被使用，请重新生成二维码', $exception->getMessage());
        $this->assertEquals('TOKEN_ALREADY_USED', $exception->getErrorCode());
        $this->assertEquals(409, $exception->getCode());
    }

    public function test_custom_message_and_details()
    {
        $exception = new TokenExpiredException(
            'Custom message',
            ['token' => 'abc123', 'expired_at' => '2024-01-01 00:00:00']
        );

        $this->assertEquals('Custom message', $exception->getMessage());
        $this->assertEquals(['token' => 'abc123', 'expired_at' => '2024-01-01 00:00:00'], $exception->getDetails());
    }
}