<?php

namespace App\Modules\Dashboard\Services;

use App\Enums\MembershipStatus;
use App\Enums\VisitorStatus;
use App\Helpers\WhatsAppHelper;
use App\Modules\Dashboard\Models\SentMessage;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Services\GetMemberPendingInformationService;
use App\Modules\Members\Services\GetRegistrationLinkService;
use App\Modules\Visitors\Models\Visitor;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GetTodayMessagesService
{
    // Visitors of the last days get a welcome; a pending member is reminded at most once in these days.
    public const WELCOME_DAYS = 7;
    public const REMIND_EVERY_DAYS = 7;
    public const UPCOMING_DAYS = 7;

    public function __construct(private GetMemberPendingInformationService $pending, private GetRegistrationLinkService $registrationLink)
    {
    }

    /**
     * The messages of the day, each ready to send by WhatsApp (see item()), in sections: "birthdays", "weddings"
     * (one per couple), "welcomes" (visitors of the last days, until welcomed), "reminders" (members with missing
     * details, pending ones first, not reminded in the last days); and "upcoming": the birthdays and wedding anniversaries of the next days.
     */
    public function execute(CarbonInterface $today): array
    {
        $today = Carbon::parse($today->toDateString());
        $sent = SentMessage::query()->with('user')
            ->where(fn ($q) => $q->whereIn('period', [(string) $today->year, 'once', $today->toDateString()])
                ->orWhere(fn ($q) => $q->where('kind', SentMessage::PENDING)->where('created_at', '>=', $today->copy()->subDays(self::REMIND_EVERY_DAYS - 1))))
            ->get();
        $sentOn = fn (string $kind, int $id) => $sent->first(fn ($m) => $m->key() === "{$kind}|{$id}|" . SentMessage::periodOf($kind, $today));

        $birthdays = $this->onDay(Member::query(), 'date_of_birth', [$today])->get()
            ->map(fn (Member $member) => $this->item(SentMessage::BIRTHDAY, $member->id, $member->first_and_last_name, [$member], $sentOn(SentMessage::BIRTHDAY, $member->id), [
                'subtitle' => ($years = $this->years($member->date_of_birth, $today)) ? __('Turns :count', ['count' => $years]) : __('Birthday'),
                'text' => "Feliz aniversário, {$this->sibling($member)} {$member->first_name}! 🎉 Que Deus te abençoe muito neste novo ano de vida!",
            ]));

        $weddings = $this->couples($this->onDay(Member::query()->with('spouse'), 'date_joined', [$today])->get())
            ->map(function (Collection $couple) use ($today, $sentOn) {
                $years = $this->years($couple[0]->date_joined, $today);
                $names = $couple->pluck('first_name')->implode(' e ');
                return $this->item(SentMessage::WEDDING, $couple->min('id'), $couple->pluck('first_name')->implode(' & ') . ' ' . $couple[0]->last_name, $couple->all(), $sentOn(SentMessage::WEDDING, $couple->min('id')), [
                    'subtitle' => $years ? trans_choice(':count year married|:count years married', $years, ['count' => $years]) : __('Wedding anniversary'),
                    'text' => "Feliz aniversário de casamento, {$names}! 💍" . ($years ? " São {$years} anos de bênçãos." : '') . ' Que Deus continue abençoando a vossa família!',
                ]);
            });

        $welcomed = $sent->where('kind', SentMessage::WELCOME)->keyBy('recipient_id');
        $welcomes = Visitor::query()
            ->where('status', VisitorStatus::ACTIVED)
            ->where('created_at', '>=', $today->copy()->subDays(self::WELCOME_DAYS - 1))
            ->orderBy('created_at')
            ->get()
            // Welcomed ones stay listed (as sent) only on the day they were welcomed.
            ->filter(fn (Visitor $visitor) => ! isset($welcomed[$visitor->id]) || $welcomed[$visitor->id]->created_at->isSameDay($today))
            ->map(fn (Visitor $visitor) => $this->item(SentMessage::WELCOME, $visitor->id, $visitor->name, [$visitor], $welcomed[$visitor->id] ?? null, [
                'subtitle' => collect([
                    __('Visit') . ' ' . $visitor->created_at->format('d/m'),
                    $visitor->invited_by_name ? __('Invited by') . ' ' . $visitor->invited_by_name : null,
                ])->filter()->implode(' · '),
                'text' => 'A paz, ' . Str::before(trim($visitor->name), ' ') . '! Foi uma alegria ter você conosco. Volte sempre, que Deus te abençoe! 🙏',
            ]));

        $reminded = $sent->where('kind', SentMessage::PENDING)->groupBy('recipient_id');
        $reminders = Member::query()
            ->where('membership_status', '!=', MembershipStatus::INACTIVED)
            ->orderByRaw('membership_status = ? DESC', [MembershipStatus::PENDING])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Member $member) => [$member, $this->pending->execute($member)])
            ->filter(fn ($pair) => count($pair[1]) > 0)
            // Reminded in the last days: listed (as sent) only on the day of the reminder.
            ->filter(fn ($pair) => ! isset($reminded[$pair[0]->id]) || $reminded[$pair[0]->id]->contains(fn ($m) => $m->period === $today->toDateString()))
            ->map(fn ($pair) => $this->item(SentMessage::PENDING, $pair[0]->id, $pair[0]->first_and_last_name, [$pair[0]], $sentOn(SentMessage::PENDING, $pair[0]->id), [
                'subtitle' => trans_choice(':count pending item|:count pending items', count($pair[1]), ['count' => count($pair[1])]),
                'text' => self::reminderText($this->sibling($pair[0]), $pair[0]->first_name, $pair[1], $this->registrationLink->execute($pair[0]), $pair[0]->isPending()),
            ]));

        return [
            'today' => $today,
            'birthdays' => $birthdays->values(),
            'weddings' => $weddings->values(),
            'welcomes' => $welcomes->values(),
            'reminders' => $reminders->values(),
            'upcoming' => $this->upcoming($today),
        ];
    }

    /**
     * The message that asks a member for what is missing, with the link to fill it in on their own: to complete the
     * registration (pending) or to bring it up to date (active).
     */
    public static function reminderText(string $sibling, string $firstName, array $pending, string $link, bool $registering = true): string
    {
        $why = $registering ? 'Para concluir o seu registo como membro da igreja' : 'Para mantermos o seu registo na igreja atualizado';

        return "A paz, {$sibling} {$firstName}! Tudo bem ? {$why}, faltam-nos alguns dados:\n"
            . collect($pending)->map(fn ($item) => "- {$item};")->implode("\n")
            . "\n\nPode preenchê-los aqui, é rápido: {$link}";
    }

    /**
     * When and by whom a message was marked as sent, for the page: ["by" => "Bruno", "at" => "09:12"], or null.
     */
    public static function sentInfo(?SentMessage $sent): ?array
    {
        return $sent ? ['by' => $sent->user?->name, 'at' => $sent->created_at->timezone(config('app.timezone'))->format('H:i')] : null;
    }

    // One message of the page: who it is for (with their photo or initials and WhatsApp), the text, and if it was sent.
    private function item(string $kind, int $recipientId, string $title, array $people, ?SentMessage $sent, array $extra): array
    {
        return $extra + [
            'kind' => $kind,
            'recipientId' => $recipientId,
            'title' => $title,
            'people' => collect($people)->map(fn ($person) => $person instanceof Member
                ? ['name' => $person->first_name, 'photo' => $person->url_photo, 'initials' => null, 'url' => route('members.show', $person), 'whatsapp' => WhatsAppHelper::url($person->phone_number)]
                : ['name' => Str::before(trim($person->name), ' '), 'photo' => null, 'initials' => $person->initials, 'url' => route('visitors.edit', $person), 'whatsapp' => WhatsAppHelper::url($person->phone_number)])
                ->values()->all(),
            'sent' => self::sentInfo($sent),
        ];
    }

    // Members (not inactive) whose date falls on one of the days; 29 February falls on the 28th in other years.
    private function onDay(Builder $query, string $column, array $days): Builder
    {
        $monthDays = collect($days)->flatMap(fn (Carbon $day) => $day->format('m-d') === '02-28' && ! $day->isLeapYear() ? ['02-28', '02-29'] : [$day->format('m-d')]);

        return $query
            ->where('membership_status', '!=', MembershipStatus::INACTIVED)
            ->whereNotNull($column)
            ->whereIn(DB::raw("DATE_FORMAT({$column}, '%m-%d')"), $monthDays->all());
    }

    // Both spouses keep the wedding date: one entry per couple (the husband first), or the member alone.
    private function couples(Collection $members): Collection
    {
        return $members
            ->map(fn (Member $member) => collect([$member, $member->spouse])
                ->filter(fn ($person) => $person && $person->membership_status !== MembershipStatus::INACTIVED)
                ->sortBy(fn ($person) => $person->gender === 'M' ? 0 : 1)->values())
            ->filter(fn (Collection $couple) => $couple->isNotEmpty())
            ->unique(fn (Collection $couple) => $couple->pluck('id')->sort()->implode('-'))
            ->values();
    }

    // The birthdays and wedding anniversaries of the next days: ["date", "kind", "title", "years"].
    private function upcoming(Carbon $today): Collection
    {
        $days = collect(range(1, self::UPCOMING_DAYS))->map(fn ($n) => $today->copy()->addDays($n));
        $dayOf = fn ($date) => $days->first(fn (Carbon $day) => $day->format('m-d') === Carbon::parse($date)->format('m-d'))
            ?? $days->first(fn (Carbon $day) => $day->format('m-d') === '02-28');

        $birthdays = $this->onDay(Member::query(), 'date_of_birth', $days->all())->get()
            ->map(fn (Member $member) => ['date' => $dayOf($member->date_of_birth), 'kind' => SentMessage::BIRTHDAY, 'title' => $member->first_and_last_name, 'years' => $this->years($member->date_of_birth, $dayOf($member->date_of_birth))]);
        $weddings = $this->couples($this->onDay(Member::query()->with('spouse'), 'date_joined', $days->all())->get())
            ->map(fn (Collection $couple) => ['date' => $dayOf($couple[0]->date_joined), 'kind' => SentMessage::WEDDING, 'title' => $couple->pluck('first_name')->implode(' & ') . ' ' . $couple[0]->last_name, 'years' => $this->years($couple[0]->date_joined, $dayOf($couple[0]->date_joined))]);

        return $birthdays->concat($weddings)->sortBy(fn ($entry) => [$entry['date']->toDateString(), $entry['title']])->values();
    }

    // Whole years from the date to the day (null without a real year).
    private function years($date, Carbon $day): ?int
    {
        $year = Carbon::parse($date)->year;
        return $year > 1900 && $year < $day->year ? $day->year - $year : null;
    }

    private function sibling(Member $member): string
    {
        return $member->gender === 'M' ? 'irmão' : 'irmã';
    }
}
