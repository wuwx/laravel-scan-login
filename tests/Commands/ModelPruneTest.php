<?php

namespace Wuwx\LaravelScanLogin\Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Carbon\Carbon;

class ModelPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_prune_removes_expired_tokens()
    {
        // Create expired tokens
        ScanLoginToken::factory()->count(3)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->subMinutes(10)
        ]);

        // Create non-expired tokens
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->addMinutes(5)
        ]);

        // Create used tokens (not expired)
        ScanLoginToken::factory()->count(1)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed',
            'consumer_id' => 1,
            'expires_at' => now()->addMinutes(5)
        ]);

        // Run model:prune command
        $this->artisan('model:prune', [
            '--model' => [ScanLoginToken::class]
        ])
        ->assertExitCode(0);

        // Verify expired tokens were deleted
        $this->assertEquals(0, ScanLoginToken::expired()->count());
        
        // Verify non-expired tokens remain
        $this->assertEquals(2, ScanLoginToken::issued()->count());
        $this->assertEquals(1, ScanLoginToken::where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed')->count());
    }

    public function test_model_prune_removes_all_expired_tokens()
    {
        // Create many expired tokens
        ScanLoginToken::factory()->count(5)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->subMinutes(10)
        ]);

        // Run model:prune command
        $this->artisan('model:prune', [
            '--model' => [ScanLoginToken::class]
        ])
        ->assertExitCode(0);

        // Verify all expired tokens were deleted
        $this->assertEquals(0, ScanLoginToken::expired()->count());
    }

    public function test_model_prune_dry_run()
    {
        // Create expired tokens
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->subMinutes(10)
        ]);

        // Run model:prune in dry run mode
        $this->artisan('model:prune', [
            '--model' => [ScanLoginToken::class],
            '--pretend' => true
        ])
        ->assertExitCode(0);

        // Verify no tokens were deleted
        $this->assertEquals(2, ScanLoginToken::expired()->count());
    }

    public function test_model_prune_with_expired_status()
    {
        // Create tokens with expired status
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired',
            'expires_at' => now()->addMinutes(5) // Future expiry but status is expired
        ]);

        // Create tokens past expiry time
        ScanLoginToken::factory()->count(1)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->subMinutes(10)
        ]);

        // Run model:prune command
        $this->artisan('model:prune', [
            '--model' => [ScanLoginToken::class]
        ])
        ->assertExitCode(0);

        // Verify all expired tokens were deleted
        $this->assertEquals(0, ScanLoginToken::expired()->count());
    }

    public function test_model_prune_with_no_expired_tokens()
    {
        // Create only non-expired tokens
        ScanLoginToken::factory()->count(3)->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->addMinutes(5)
        ]);

        // Run model:prune command
        $this->artisan('model:prune', [
            '--model' => [ScanLoginToken::class]
        ])
        ->assertExitCode(0);

        // Verify no tokens were deleted
        $this->assertEquals(3, ScanLoginToken::count());
    }

    public function test_prunable_method_returns_correct_query()
    {
        // Create test tokens
        ScanLoginToken::factory()->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired',
            'expires_at' => now()->addMinutes(5)
        ]);
        
        ScanLoginToken::factory()->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->subMinutes(10)
        ]);
        
        ScanLoginToken::factory()->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->addMinutes(5)
        ]);

        // Test the prunable query
        $prunableTokens = (new ScanLoginToken)->prunable()->get();
        
        // Should return 2 tokens (1 with expired status + 1 past expiry time)
        $this->assertEquals(2, $prunableTokens->count());
    }

    public function test_pruning_method_is_called()
    {
        // Create a mock to track if pruning method is called
        $token = ScanLoginToken::factory()->create([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'expires_at' => now()->subMinutes(10)
        ]);

        // Run model:prune command
        $this->artisan('model:prune', [
            '--model' => [ScanLoginToken::class]
        ])
        ->assertExitCode(0);

        // Verify token was deleted
        $this->assertNull(ScanLoginToken::find($token->id));
    }
}