<?php

namespace Wuwx\LaravelScanLogin\Tests\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Wuwx\LaravelScanLogin\Middleware\ScanLoginRateLimitMiddleware;

class ScanLoginRateLimitMiddlewareTest extends TestCase
{
    protected ScanLoginRateLimitMiddleware $middleware;
    protected RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = app(RateLimiter::class);
        $this->middleware = new ScanLoginRateLimitMiddleware($this->limiter);
    }

    public function test_allows_request_within_rate_limit()
    {
        $request = Request::create('/scan-login/generate', 'POST');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['POST'], '/scan-login/generate', []);
            $route->bind($request);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        }, '5', '1');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getContent());
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('4', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_blocks_request_when_rate_limit_exceeded()
    {
        $request = Request::create('/scan-login/generate', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['POST'], '/scan-login/generate', []);
            $route->bind($request);
            return $route;
        });

        // Make requests to exceed the limit
        for ($i = 0; $i < 3; $i++) {
            $this->middleware->handle($request, function ($req) {
                return new Response('success');
            }, '2', '1');
        }

        // This request should be rate limited
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        }, '2', '1');

        $this->assertEquals(429, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $data['error']['code']);
        $this->assertArrayHasKey('retry_after', $data['error']['details']);
    }

    public function test_adds_rate_limit_headers()
    {
        $request = Request::create('/scan-login/generate', 'POST');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['POST'], '/scan-login/generate', []);
            $route->bind($request);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        }, '10', '1');

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertEquals('10', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_different_ips_have_separate_rate_limits()
    {
        // First IP
        $request1 = Request::create('/scan-login/generate', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $request1->setRouteResolver(function () use ($request1) {
            $route = new \Illuminate\Routing\Route(['POST'], '/scan-login/generate', []);
            $route->bind($request1);
            return $route;
        });

        // Second IP
        $request2 = Request::create('/scan-login/generate', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.2']);
        $request2->setRouteResolver(function () use ($request2) {
            $route = new \Illuminate\Routing\Route(['POST'], '/scan-login/generate', []);
            $route->bind($request2);
            return $route;
        });

        // Both should be allowed initially
        $response1 = $this->middleware->handle($request1, function ($req) {
            return new Response('success');
        }, '1', '1');

        $response2 = $this->middleware->handle($request2, function ($req) {
            return new Response('success');
        }, '1', '1');

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }
}