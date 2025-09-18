<?php

namespace Wuwx\LaravelScanLogin\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Wuwx\LaravelScanLogin\Middleware\SecurityMiddleware;

class SecurityMiddlewareTest extends TestCase
{
    protected SecurityMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityMiddleware();
    }

    public function test_adds_security_headers()
    {
        $request = Request::create('https://example.com/scan-login/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('test content');
        });

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_adds_hsts_header_for_https()
    {
        $request = Request::create('https://example.com/scan-login/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('test content');
        });

        $this->assertEquals('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_adds_csp_header_for_scan_login_routes()
    {
        $request = Request::create('https://example.com/scan-login/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('test content');
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_requires_https_when_configured()
    {
        config(['scan-login.require_https' => true]);
        
        // Create a custom middleware instance that doesn't check testing environment
        $middleware = new class extends SecurityMiddleware {
            protected function isTestingEnvironment(): bool
            {
                return false;
            }
        };
        
        $request = Request::create('http://example.com/scan-login/test', 'GET');
        
        $response = $middleware->handle($request, function ($req) {
            return new Response('test content');
        });

        $this->assertEquals(426, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('HTTPS_REQUIRED', $data['error']['code']);
    }

    public function test_allows_http_when_https_not_required()
    {
        config(['scan-login.require_https' => false]);
        
        $request = Request::create('http://example.com/scan-login/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('test content');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test content', $response->getContent());
    }

    public function test_allows_http_in_testing_environment()
    {
        config(['scan-login.require_https' => true]);
        app()->detectEnvironment(function () {
            return 'testing';
        });
        
        $request = Request::create('http://example.com/scan-login/test', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('test content');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}