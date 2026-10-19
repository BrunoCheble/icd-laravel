<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Dashboard\Models\SentMessage;

class MarkMessageSentService
{
    /**
     * Marks today's message of this kind to this person as sent by the user (once: marking it again keeps the first
     * mark), or unmarks it ($sent false).
     */
    public function execute(string $kind, int $recipientId, User $user, bool $sent = true): ?SentMessage
    {
        $keys = [
            'kind' => $kind,
            'recipient_type' => SentMessage::RECIPIENTS[$kind],
            'recipient_id' => $recipientId,
            'period' => SentMessage::periodOf($kind, now()),
        ];

        if (! $sent) {
            SentMessage::query()->where($keys)->delete();
            return null;
        }

        return SentMessage::query()->firstOrCreate($keys, ['user_id' => $user->id])->load('user');
    }
}
