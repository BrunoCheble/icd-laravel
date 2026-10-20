<?php

namespace App\Console\Commands;

use Database\Seeders\DevDataSeeder;
use Illuminate\Console\Command;

/**
 * Made-up "ZZ Teste" data to try the system on a local computer (members, visitors, prayer requests,
 * announcements), made again on every run; --clean only deletes it. Refuses anywhere but the local Docker database.
 */
class DevData extends Command
{
    protected $signature = 'dev:data {--clean : Only delete the made-up data}';

    protected $description = 'Made-up "ZZ Teste" data to try the system locally (or --clean to delete it)';

    public function handle(DevDataSeeder $seeder): int
    {
        $connection = config('database.connections.' . config('database.default'));
        if (! app()->environment('local') || ($connection['host'] ?? null) !== 'db') {
            $this->error('Only on a local computer (local environment, the Docker database "db").');
            return self::FAILURE;
        }

        if ($this->option('clean')) {
            $deleted = $seeder->clean();
            $this->info('Deleted: ' . collect($deleted)->map(fn ($count, $what) => "{$count} {$what}")->implode(', ') . '.');
            return self::SUCCESS;
        }

        $seeder->run();
        $this->info('Made-up data created in "' . $connection['database'] . '" (delete it with: php artisan dev:data --clean).');
        return self::SUCCESS;
    }
}
