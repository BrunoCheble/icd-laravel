<?php

namespace App\Console\Commands;

use App\Services\Notifications\SendPushDigestService;
use Illuminate\Console\Command;

/**
 * Sends the app's notifications now (what production does once an hour), at any hour with --any-hour.
 */
class PushDigest extends Command
{
    protected $signature = 'push:digest {--any-hour : Also outside 8:00-22:00}';

    protected $description = 'Send the app notifications (birthdays of the day, what is new)';

    public function handle(SendPushDigestService $digest): int
    {
        $sent = $digest->execute((bool) $this->option('any-hour'));
        $this->info(count($sent) . ' notification(s) sent.');
        foreach ($sent as $notification) {
            $this->line("- {$notification['title']}: {$notification['body']}");
        }

        return self::SUCCESS;
    }
}
