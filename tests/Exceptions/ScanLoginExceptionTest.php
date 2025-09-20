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
        $exception = new ScanLoginException('Test message', 500);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    public function test_token_expired_exception()
    {
        $exception = new TokenExpiredException();

        $this->assertEquals('登录令牌已过期，请刷新二维码', $exception->getMessage());
        $this->assertEquals(410, $exception->getCode());
    }

    public function test_token_not_found_exception()
    {
        $exception = new TokenNotFoundException();

        $this->assertEquals('登录令牌不存在或无效', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function test_invalid_credentials_exception()
    {
        $exception = new InvalidCredentialsException();

        $this->assertEquals('用户名或密码错误', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }

    public function test_token_already_used_exception()
    {
        $exception = new TokenAlreadyUsedException();

        $this->assertEquals('登录令牌已被使用，请重新生成二维码', $exception->getMessage());
        $this->assertEquals(409, $exception->getCode());
    }

    public function test_custom_message()
    {
        $exception = new TokenExpiredException('Custom message', 400);

        $this->assertEquals('Custom message', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }
}