<?php

namespace Tests\Feature;

use App\Services\RefreshDeployCachesService;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;

class DeployCachesTest extends TestCase
{
    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marker = sys_get_temp_dir() . '/deploy-caches-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        @unlink($this->marker);
        @unlink($this->marker . '.lock');
        @unlink($this->marker . '.cleared');
        @unlink($this->marker . '.status');
        parent::tearDown();
    }

    public function test_the_signature_is_the_commit_and_the_env_time(): void
    {
        $signature = (new RefreshDeployCachesService($this->marker, 0))->signature();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}\|\d+$/', (string) $signature);
    }

    public function test_the_caches_are_built_once_per_change(): void
    {
        Artisan::shouldReceive('call')->once()->with('optimize');
        $caches = new RefreshDeployCachesService($this->marker, 0);

        $this->assertTrue($caches->isStale());
        $caches->execute();
        $this->assertFalse($caches->isStale());
        $caches->execute();

        $this->assertSame($caches->signature(), file_get_contents($this->marker));
    }

    public function test_a_change_builds_them_again(): void
    {
        Artisan::shouldReceive('call')->once()->with('optimize');
        file_put_contents($this->marker, 'an-older-commit|0');

        (new RefreshDeployCachesService($this->marker, 0))->execute();
    }

    public function test_a_failure_clears_them_and_waits_for_the_next_change(): void
    {
        Artisan::shouldReceive('call')->once()->with('optimize')->andThrow(new RuntimeException('route cache failed'));
        Artisan::shouldReceive('call')->once()->with('optimize:clear');
        $caches = new RefreshDeployCachesService($this->marker, 0);

        $caches->execute();

        $this->assertFalse($caches->isStale());
        $this->assertStringStartsWith('failed:RuntimeException@', $caches->status());
    }

    public function test_right_after_a_change_they_are_only_cleared_once(): void
    {
        Artisan::shouldReceive('call')->once()->with('optimize:clear');
        Artisan::shouldReceive('call')->never()->with('optimize');
        $caches = new RefreshDeployCachesService($this->marker, PHP_INT_MAX);

        $caches->execute();
        $caches->execute();

        // Still to build, on a later request.
        $this->assertTrue($caches->isStale());
    }

    public function test_outside_production_requests_never_build_them(): void
    {
        Artisan::shouldReceive('call')->never();
        $this->app->instance(RefreshDeployCachesService::class, new RefreshDeployCachesService($this->marker, 0));

        $this->get('/up')->assertOk();

        $this->assertFileDoesNotExist($this->marker);
    }
}
