<?php

namespace Wuwx\LaravelScanLogin\Tests\Exceptions;

use Illuminate\Http\Request;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Wuwx\LaravelScanLogin\Exceptions\ScanLoginException;
use Wuwx\LaravelScanLogin\Exceptions\ScanLoginExceptionHandler;
use Wuwx\LaravelScanLogin\Exceptions\TokenExpiredException;

class ScanLoginExceptionHandlerTest extends TestCase
{
    public function test_to_json_response()
    {
        $exception = new TokenExpiredException('Token expired', ['token' => 'abc123']);
        
        $response = ScanLoginExceptionHandler::toJsonResponse($exception);
        
        $this->assertEquals(410, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('TOKEN_EXPIRED', $data['error']['code']);
        $this->assertEquals('Token expired', $data['error']['message']);
        $this->assertEquals(['token' => 'abc123'], $data['error']['details']);
    }

    public function test_get_ajax_error_response()
    {
        $exception = new ScanLoginException(
            'Test error',
            'TEST_ERROR',
            ['key' => 'value']
        );
        
        $response = ScanLoginExceptionHandler::getAjaxErrorResponse($exception);
        
        $expected = [
            'success' => false,
            'error' => [
                'code' => 'TEST_ERROR',
                'message' => 'Test error',
                'details' => ['key' => 'value'],
            ],
        ];
        
        $this->assertEquals($expected, $response);
    }

    public function test_handle_json_request()
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $exception = new TokenExpiredException();
        
        $response = ScanLoginExceptionHandler::handle($exception, $request);
        
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}