<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Production runs with Laravel's caches (config, routes, events, views), which make every request lighter. The
 * deploy only pulls the code and the server has no terminal, so the app rebuilds them itself: when the deployed
 * commit or the .env file changed since they were built. Right after a pull OPcache may still hold the old PHP files
 * for a while (bootstrap/providers.php…), and caches built then would miss what is new: so first they are only
 * cleared (the app works without them, only slower) and they are built once the change is SETTLE_SECONDS old. On a
 * failure the caches are cleared and tried again on the next change.
 */
class RefreshDeployCachesService
{
    public const SETTLE_SECONDS = 90;

    public function __construct(private ?string $marker = null, private int $settleSeconds = self::SETTLE_SECONDS)
    {
        $this->marker ??= storage_path('framework/deploy-caches');
    }

    /** How long ago the code or the .env changed (the checkout's last write). */
    public function changedSecondsAgo(): int
    {
        $times = array_filter([@filemtime(base_path('.git/index')), @filemtime(base_path('.git/HEAD')), @filemtime(base_path('.env'))]);

        return $times ? max(0, time() - max($times)) : PHP_INT_MAX;
    }

    /** What the caches are built from: the commit checked out and the time .env was last changed. */
    public function signature(): ?string
    {
        $head = @file_get_contents(base_path('.git/HEAD'));
        if ($head === false) {
            return null;
        }
        $head = trim($head);
        if (str_starts_with($head, 'ref: ')) {
            $ref = substr($head, 5);
            $commit = @file_get_contents(base_path(".git/{$ref}"));
            if ($commit === false) {
                // After a "git gc" the branch's commit is in packed-refs.
                preg_match('/^([0-9a-f]{40}) ' . preg_quote($ref, '/') . '$/m', (string) @file_get_contents(base_path('.git/packed-refs')), $match);
                $commit = $match[1] ?? '';
            }
            $head = trim($commit);
        }

        return $head === '' ? null : $head . '|' . (int) @filemtime(base_path('.env'));
    }

    public function isStale(): bool
    {
        $signature = $this->signature();

        return $signature !== null && $signature !== @file_get_contents($this->marker);
    }

    /** Rebuilds the caches when they are stale; one request at a time (the others go on with what is there). */
    public function execute(): void
    {
        $lock = @fopen($this->marker . '.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            return;
        }
        try {
            $signature = $this->signature();
            if ($signature === null || $signature === @file_get_contents($this->marker)) {
                return;
            }
            // Too soon after the change: only cleared (once), built on a later request.
            if ($this->changedSecondsAgo() < $this->settleSeconds) {
                if (@file_get_contents($this->marker . '.cleared') !== $signature) {
                    Artisan::call('optimize:clear');
                    file_put_contents($this->marker . '.cleared', $signature);
                }

                return;
            }
            $this->forgetCompiledCode();
            try {
                Artisan::call('optimize');
            } catch (Throwable $error) {
                Artisan::call('optimize:clear');
                report($error);
            }
            file_put_contents($this->marker, $signature);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** Drops the app's own PHP files from OPcache, so they are compiled again from what was pulled. */
    private function forgetCompiledCode(): void
    {
        if (! function_exists('opcache_invalidate')) {
            return;
        }
        foreach (['app', 'bootstrap', 'config', 'routes'] as $folder) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($folder), \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    @opcache_invalidate($file->getPathname(), true);
                }
            }
        }
    }
}
