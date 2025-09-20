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
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'claimer_id' => null,
            'consumer_id' => null,
            'expires_at' => now()->addMinutes(5),
            'claimed_at' => null,
            'consumed_at' => null,
            'cancelled_at' => null,
            // 生成二维码时的设备信息
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
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
     * Indicate that the token is consumed.
     */
    public function consumed(int $userId = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed',
            'consumer_id' => $userId,
            'consumed_at' => now(),
        ]);
    }

    /**
     * Indicate that the token is claimed.
     */
    public function claimed(int $claimerId = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed',
            'claimer_id' => $claimerId,
            'claimed_at' => now(),
        ]);
    }

    /**
     * Indicate that the token is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled',
            'cancelled_at' => now(),
        ]);
    }

}