<?php

namespace Wuwx\LaravelScanLogin\Tests\Services;

use Illuminate\Support\Facades\Log;
use Wuwx\LaravelScanLogin\Services\ConfigValidator;
use Wuwx\LaravelScanLogin\Tests\TestCase;

class ConfigValidatorTest extends TestCase
{
    public function test_validates_valid_configuration()
    {
        $config = [
            'token_expiry_minutes' => 5,
            'token_length' => 64,
            'polling_interval_seconds' => 3,
            'qr_code_size' => 200,
            'validation' => [
                'token_expiry_minutes' => ['integer', 'min:1', 'max:60'],
                'token_length' => ['integer', 'min:32', 'max:128'],
                'polling_interval_seconds' => ['integer', 'min:1', 'max:30'],
                'qr_code_size' => ['integer', 'min:100', 'max:500'],
            ]
        ];

        $result = ConfigValidator::validate($config);

        $this->assertEquals(5, $result['token_expiry_minutes']);
        $this->assertEquals(64, $result['token_length']);
        $this->assertEquals(3, $result['polling_interval_seconds']);
        $this->assertEquals(200, $result['qr_code_size']);
    }

    public function test_uses_default_values_for_invalid_configuration()
    {
        Log::shouldReceive('warning')->times(3);

        $config = [
            'token_expiry_minutes' => 100, // Invalid: max is 60
            'token_length' => 20, // Invalid: min is 32
            'polling_interval_seconds' => 50, // Invalid: max is 30
            'qr_code_size' => 200, // Valid
            'validation' => [
                'token_expiry_minutes' => ['integer', 'min:1', 'max:60'],
                'token_length' => ['integer', 'min:32', 'max:128'],
                'polling_interval_seconds' => ['integer', 'min:1', 'max:30'],
                'qr_code_size' => ['integer', 'min:100', 'max:500'],
            ]
        ];

        $result = ConfigValidator::validate($config);

        $this->assertEquals(5, $result['token_expiry_minutes']); // Default value
        $this->assertEquals(64, $result['token_length']); // Default value
        $this->assertEquals(3, $result['polling_interval_seconds']); // Default value
        $this->assertEquals(200, $result['qr_code_size']); // Valid value
    }

    public function test_validates_qr_code_error_correction_level()
    {
        $config = [
            'qr_code_error_correction' => 'M',
            'validation' => [
                'qr_code_error_correction' => ['string', 'in:L,M,Q,H'],
            ]
        ];

        $result = ConfigValidator::validate($config);
        $this->assertEquals('M', $result['qr_code_error_correction']);
    }

    public function test_uses_default_for_invalid_error_correction_level()
    {
        Log::shouldReceive('warning')->once();

        $config = [
            'qr_code_error_correction' => 'X', // Invalid
            'validation' => [
                'qr_code_error_correction' => ['string', 'in:L,M,Q,H'],
            ]
        ];

        $result = ConfigValidator::validate($config);
        $this->assertEquals('M', $result['qr_code_error_correction']); // Default value
    }

    public function test_validates_https_requirement_when_secure()
    {
        $this->app['request']->server->set('HTTPS', 'on');
        
        $this->assertTrue(ConfigValidator::validateHttpsRequirement());
    }

    public function test_validates_https_requirement_when_not_required()
    {
        config(['scan-login.require_https' => false]);
        
        $this->assertTrue(ConfigValidator::validateHttpsRequirement());
    }

    public function test_fails_https_validation_when_required_but_not_secure()
    {
        Log::shouldReceive('warning')->once();
        
        config(['scan-login.require_https' => true]);
        $this->app['request']->server->set('HTTPS', null);
        
        $this->assertFalse(ConfigValidator::validateHttpsRequirement());
    }

    public function test_gets_validated_config()
    {
        config([
            'scan-login' => [
                'token_expiry_minutes' => 5,
                'polling_interval_seconds' => 3,
                'validation' => [
                    'token_expiry_minutes' => ['integer', 'min:1', 'max:60'],
                    'polling_interval_seconds' => ['integer', 'min:1', 'max:30'],
                ]
            ]
        ]);

        $result = ConfigValidator::getValidatedConfig();

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['token_expiry_minutes']);
        $this->assertEquals(3, $result['polling_interval_seconds']);
    }
}