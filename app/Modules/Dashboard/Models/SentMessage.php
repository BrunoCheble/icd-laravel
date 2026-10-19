<?php

namespace App\Modules\Dashboard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message of the day marked as sent on the home page. See the sent_messages migration.
 */
class SentMessage extends Model
{
    public const BIRTHDAY = 'birthday';
    public const WEDDING = 'wedding';
    public const WELCOME = 'welcome';
    public const PENDING = 'pending';

    // Who each kind of message goes to.
    public const RECIPIENTS = [
        self::BIRTHDAY => 'member',
        self::WEDDING => 'member',
        self::WELCOME => 'visitor',
        self::PENDING => 'member',
    ];

    protected $fillable = ['kind', 'recipient_type', 'recipient_id', 'period', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * When a message of this kind counts for, on the day given: anniversaries once a year, a welcome once, a reminder
     * once a day.
     */
    public static function periodOf(string $kind, \Carbon\CarbonInterface $day): string
    {
        return match ($kind) {
            self::BIRTHDAY, self::WEDDING => (string) $day->year,
            self::WELCOME => 'once',
            default => $day->toDateString(),
        };
    }

    public function key(): string
    {
        return "{$this->kind}|{$this->recipient_id}|{$this->period}";
    }
}
