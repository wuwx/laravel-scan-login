<?php

namespace Wuwx\LaravelScanLogin\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConfigValidator
{
    /**
     * Validate and sanitize configuration values.
     *
     * @param array $config
     * @return array
     */
    public static function validate(array $config): array
    {
        $validatedConfig = [];
        $validationRules = $config['validation'] ?? [];

        foreach ($config as $key => $value) {
            if ($key === 'validation') {
                continue;
            }

            if (isset($validationRules[$key])) {
                $validatedConfig[$key] = self::validateConfigValue($key, $value, $validationRules[$key]);
            } else {
                $validatedConfig[$key] = $value;
            }
        }

        return $validatedConfig;
    }

    /**
     * Validate a single configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @param array $rules
     * @return mixed
     */
    private static function validateConfigValue(string $key, $value, array $rules)
    {
        $validator = Validator::make(
            [$key => $value],
            [$key => $rules]
        );

        if ($validator->fails()) {
            $defaultValue = self::getDefaultValue($key);
            
            Log::warning("Invalid scan-login configuration value for '{$key}': {$value}. Using default: {$defaultValue}", [
                'key' => $key,
                'value' => $value,
                'default' => $defaultValue,
                'errors' => $validator->errors()->first($key)
            ]);

            return $defaultValue;
        }

        return $value;
    }

    /**
     * Get default value for a configuration key.
     *
     * @param string $key
     * @return mixed
     */
    private static function getDefaultValue(string $key)
    {
        $defaults = [
            'token_expiry_minutes' => 5,
            'token_length' => 64,
            'polling_interval_seconds' => 3,
            'max_polling_duration_minutes' => 10,
            'qr_code_size' => 200,
            'qr_code_error_correction' => 'M',
            'rate_limit_per_minute' => 10,
            'max_login_attempts' => 3,
            'cleanup_interval_hours' => 24,
            'cleanup_batch_size' => 1000,
        ];

        return $defaults[$key] ?? null;
    }

    /**
     * Check if HTTPS is required and available.
     *
     * @return bool
     */
    public static function validateHttpsRequirement(): bool
    {
        $requireHttps = config('scan-login.require_https', false);
        
        if ($requireHttps && !request()->secure()) {
            Log::warning('Scan login requires HTTPS but current request is not secure', [
                'url' => request()->url(),
                'secure' => request()->secure()
            ]);
            
            return false;
        }

        return true;
    }

    /**
     * Get validated configuration values.
     *
     * @return array
     */
    public static function getValidatedConfig(): array
    {
        $config = config('scan-login', []);
        return self::validate($config);
    }
}