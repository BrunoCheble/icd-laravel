<?php

namespace App\Services\Notifications;

use Throwable;

/**
 * Something new came in (a prayer request, an announcement to approve, a comment, a chord suggestion, a member who
 * registered): the app's notification goes once the request's answer was sent (nobody waits), once per request.
 * Not from the console (made-up data, imports), except in the tests.
 */
class NotifyNews
{
    public static function soon(): void
    {
        // Once per request (the mark lives in this request's app).
        if (app()->bound('notify-news') || (app()->runningInConsole() && ! app()->runningUnitTests())) {
            return;
        }
        app()->instance('notify-news', true);
        app()->terminating(function () {
            app()->forgetInstance('notify-news');
            try {
                app(SendPushDigestService::class)->sendNews();
            } catch (Throwable $error) {
                report($error);
            }
        });
    }
}
