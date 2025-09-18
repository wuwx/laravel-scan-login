<?php

namespace Wuwx\LaravelScanLogin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Support\Str;

class ScanLoginTokenFactory extends Factory
{
    protected $model = ScanLoginToken::class;

    public function definition(): array
    {
        return [
            'token' => Str::random(64),
            'status' => 'pending',
            'user_id' => null,
            'expires_at' => now()->addMinutes(5),
            'used_at' => null,
        ];
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    /**
     * Indicate that the token is used.
     */
    public function used(int $userId = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'used',
            'user_id' => $userId,
            'used_at' => now(),
        ]);
    }

    /**
     * Indicate that the token is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}