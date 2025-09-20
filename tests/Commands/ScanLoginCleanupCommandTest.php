<?php

namespace Wuwx\LaravelScanLogin\Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Carbon\Carbon;

class ScanLoginCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_command_with_no_expired_tokens()
    {
        // Create some pending tokens that are not expired
        ScanLoginToken::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5)
        ]);

        $this->artisan('scan-login:cleanup --force')
            ->expectsOutput('No expired tokens found.')
            ->assertExitCode(0);
    }

    public function test_cleanup_command_with_expired_tokens()
    {
        // Create expired tokens
        ScanLoginToken::factory()->count(3)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10)
        ]);

        // Create non-expired tokens
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5)
        ]);

        $this->artisan('scan-login:cleanup --force')
            ->expectsOutput('Found 3 expired tokens to clean up.')
            ->expectsOutput('Successfully deleted 3 expired tokens.')
            ->assertExitCode(0);

        // Verify expired tokens were deleted
        $this->assertEquals(0, ScanLoginToken::expired()->count());
        // Verify non-expired tokens remain
        $this->assertEquals(2, ScanLoginToken::pending()->count());
    }

    public function test_cleanup_command_dry_run()
    {
        // Create expired tokens
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10)
        ]);

        $this->artisan('scan-login:cleanup --dry-run')
            ->expectsOutput('Found 2 expired tokens to clean up.')
            ->expectsOutput('DRY RUN MODE: No tokens will be deleted.')
            ->assertExitCode(0);

        // Verify no tokens were deleted
        $this->assertEquals(2, ScanLoginToken::expired()->count());
    }

    public function test_cleanup_command_with_batch_size()
    {
        // Create many expired tokens
        ScanLoginToken::factory()->count(5)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10)
        ]);

        $this->artisan('scan-login:cleanup --force --batch-size=2')
            ->expectsOutput('Found 5 expired tokens to clean up.')
            ->expectsOutput('Successfully deleted 5 expired tokens.')
            ->assertExitCode(0);

        // Verify all expired tokens were deleted
        $this->assertEquals(0, ScanLoginToken::expired()->count());
    }

    public function test_cleanup_command_when_disabled()
    {
        config(['scan-login.enabled' => false]);

        $this->artisan('scan-login:cleanup')
            ->expectsOutput('Scan login is disabled in configuration.')
            ->assertExitCode(1);
    }

    public function test_cleanup_command_shows_statistics()
    {
        // Create expired tokens (pending but past expiry time)
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10) // expired
        ]);
        
        // Create used token (not expired - still valid expiry time)
        ScanLoginToken::factory()->count(1)->create([
            'status' => 'used',
            'user_id' => 1,
            'expires_at' => now()->addMinutes(5) // not expired
        ]);
        
        // Create pending token (not expired)
        ScanLoginToken::factory()->count(1)->create([
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5) // not expired
        ]);

        $this->artisan('scan-login:cleanup --force')
            ->expectsOutput('Found 2 expired tokens to clean up.')
            ->expectsOutput('Successfully deleted 2 expired tokens.')
            ->expectsOutput('Current Token Statistics:')
            ->assertExitCode(0);
    }

    public function test_cleanup_command_requires_confirmation_without_force()
    {
        ScanLoginToken::factory()->count(2)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10)
        ]);

        $this->artisan('scan-login:cleanup')
            ->expectsQuestion('Are you sure you want to delete 2 expired tokens?', false)
            ->expectsOutput('Cleanup cancelled.')
            ->assertExitCode(0);

        // Verify tokens were not deleted
        $this->assertEquals(2, ScanLoginToken::expired()->count());
    }

    public function test_cleanup_command_proceeds_with_confirmation()
    {
        ScanLoginToken::factory()->count(1)->create([
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10)
        ]);

        $this->artisan('scan-login:cleanup')
            ->expectsQuestion('Are you sure you want to delete 1 expired tokens?', true)
            ->expectsOutput('Successfully deleted 1 expired tokens.')
            ->assertExitCode(0);

        // Verify token was deleted
        $this->assertEquals(0, ScanLoginToken::expired()->count());
    }
}