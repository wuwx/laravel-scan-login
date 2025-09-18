<?php

namespace Wuwx\LaravelScanLogin\Tests\Middleware;

use Wuwx\LaravelScanLogin\Tests\TestCase;
use Wuwx\LaravelScanLogin\Middleware\ValidateTokenMiddleware;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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

    public function test_handles_valid_pending_token()
    {
        $token = 'valid-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('pending');

        $next = function ($request) {
            return new Response('success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('success', $response->getContent());
    }

    public function test_handles_token_not_found_json_request()
    {
        $token = 'non-existent-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('not_found');

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('TOKEN_NOT_FOUND', $data['error']['code']);
        $this->assertEquals('登录令牌不存在', $data['error']['message']);
    }

    public function test_handles_expired_token_json_request()
    {
        $token = 'expired-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('expired');

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(410, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('TOKEN_EXPIRED', $data['error']['code']);
        $this->assertEquals('登录令牌已过期，请刷新二维码', $data['error']['message']);
    }

    public function test_handles_used_token_json_request()
    {
        $token = 'used-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('used');

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(410, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('TOKEN_ALREADY_USED', $data['error']['code']);
        $this->assertEquals('登录令牌已被使用', $data['error']['message']);
    }

    public function test_extracts_token_from_query_parameter()
    {
        $token = 'query-token';
        $request = Request::create('/test?token=' . $token, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn(null);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('pending');

        $next = function ($request) {
            return new Response('success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('success', $response->getContent());
    }

    public function test_extracts_token_from_request_body()
    {
        $token = 'body-token';
        $request = Request::create('/test', 'POST', ['token' => $token]);
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn(null);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('pending');

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
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn(null);
            return $route;
        });

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('TOKEN_NOT_FOUND', $data['error']['code']);
    }

    public function test_handles_invalid_token_status()
    {
        $token = 'invalid-status-token';
        $request = Request::create('/test/' . $token, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () use ($token) {
            $route = Mockery::mock();
            $route->shouldReceive('parameter')->with('token', null)->andReturn($token);
            return $route;
        });

        $this->tokenManager
            ->shouldReceive('getStatus')
            ->with($token)
            ->once()
            ->andReturn('unknown_status');

        $next = function ($request) {
            return new Response('should not reach here');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('INVALID_TOKEN', $data['error']['code']);
        $this->assertEquals('登录令牌无效', $data['error']['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}