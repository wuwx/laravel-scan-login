<?php

namespace Wuwx\LaravelScanLogin\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Store;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Wuwx\LaravelScanLogin\Middleware\ScanLoginCsrfMiddleware;

class ScanLoginCsrfMiddlewareTest extends TestCase
{
    protected ScanLoginCsrfMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ScanLoginCsrfMiddleware();
    }

    public function test_allows_get_requests()
    {
        $request = Request::create('/scan-login/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getContent());
    }

    public function test_allows_head_requests()
    {
        $request = Request::create('/scan-login/test', 'HEAD');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_options_requests()
    {
        $request = Request::create('/scan-login/test', 'OPTIONS');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_api_routes()
    {
        $request = Request::create('/api/scan-login/test', 'POST');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_post_request_without_valid_token()
    {
        // Create a custom middleware instance that doesn't check testing environment
        $middleware = new class extends ScanLoginCsrfMiddleware {
            protected function isTestingEnvironment(): bool
            {
                return false;
            }
        };
        
        $request = Request::create('/scan-login/test', 'POST');
        $request->headers->set('Accept', 'application/json');
        
        // Mock session without token
        $session = $this->createMock(Store::class);
        $session->method('token')->willReturn('valid-token');
        $request->setLaravelSession($session);
        
        $response = $middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(419, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('CSRF_TOKEN_MISMATCH', $data['error']['code']);
    }

    public function test_allows_post_request_with_valid_token()
    {
        $token = 'valid-csrf-token';
        
        $request = Request::create('/scan-login/test', 'POST', ['_token' => $token]);
        
        // Mock session with matching token
        $session = $this->createMock(Store::class);
        $session->method('token')->willReturn($token);
        $request->setLaravelSession($session);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getContent());
    }

    public function test_allows_request_with_csrf_header()
    {
        $token = 'valid-csrf-token';
        
        $request = Request::create('/scan-login/test', 'POST');
        $request->headers->set('X-CSRF-TOKEN', $token);
        
        // Mock session with matching token
        $session = $this->createMock(Store::class);
        $session->method('token')->willReturn($token);
        $request->setLaravelSession($session);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getContent());
    }

    public function test_allows_requests_in_testing_environment()
    {
        app()->detectEnvironment(function () {
            return 'testing';
        });
        
        $request = Request::create('/scan-login/test', 'POST');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}