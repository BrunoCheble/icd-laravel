<?php

namespace App\Modules\Members\Services;

use App\Modules\Members\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ListAnniversariesService
{
    /**
     * The birthdays and the wedding anniversaries of a month, in day order: each birthday is
     * ["member", "date" (this year), "years" (the age it turns, null without the year)]; each wedding is
     * ["members" (the couple, or one member when the spouse is not a member), "date", "years" (of marriage)].
     */
    public function execute(int $month, ?int $year = null): array
    {
        $year ??= now()->year;

        $birthdays = Member::query()->whereMonth('date_of_birth', $month)->get()
            ->map(fn (Member $member) => $this->entry(Carbon::parse($member->date_of_birth), $year) + ['member' => $member])
            ->sortBy(fn ($entry) => [$entry['date']->day, $entry['member']->first_name])
            ->values();

        // Both spouses keep the wedding date: one entry per couple.
        $weddings = Member::query()->with('spouse')->whereMonth('date_joined', $month)->get()
            ->unique(fn (Member $member) => $member->spouse ? min($member->id, $member->spouse->id) . '-' . max($member->id, $member->spouse->id) : 'm' . $member->id)
            ->map(fn (Member $member) => $this->entry(Carbon::parse($member->date_joined), $year) + [
                'members' => collect([$member, $member->spouse])->filter()->sortBy(fn ($person) => $person->gender === 'M' ? 0 : 1)->values(),
            ])
            ->sortBy(fn ($entry) => $entry['date']->day)
            ->values();

        return ['birthdays' => $birthdays, 'weddings' => $weddings];
    }

    // The day it falls on in the year given (29 February falls on the 28th in other years), and how many years.
    private function entry(Carbon $date, int $year): array
    {
        $day = min($date->day, Carbon::create($year, $date->month)->daysInMonth);

        return [
            'date' => Carbon::create($year, $date->month, $day),
            'years' => $date->year > 1900 && $date->year < $year ? $year - $date->year : null,
        ];
    }
}
