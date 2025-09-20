<?php

namespace Wuwx\LaravelScanLogin\Tests\Middleware;

use Wuwx\LaravelScanLogin\Tests\TestCase;
use Wuwx\LaravelScanLogin\Middleware\ValidateTokenMiddleware;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Mockery;

class ValidateTokenMiddlewareTest extends TestCase
{
    private ValidateTokenMiddleware $middleware;
    private TokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tokenManager = Mockery::mock(TokenManager::class);
        $this->middleware = new ValidateTokenMiddleware($this->tokenManager);
    }

    public function test_handles_valid_token()
    {
        $token = 'valid-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('exists')
            ->with($token)
            ->once()
            ->andReturn(true);

        $next = function ($request) {
            return new Response('success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('success', $response->getContent());
    }

    public function test_handles_missing_token()
    {
        $request = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn(null);
            return $route;
        });

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('登录令牌不存在或已失效');

        $this->middleware->handle($request, $next);
    }

    public function test_handles_invalid_token()
    {
        $token = 'invalid-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('exists')
            ->with($token)
            ->once()
            ->andReturn(false);

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('登录令牌不存在或已失效');

        $this->middleware->handle($request, $next);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}