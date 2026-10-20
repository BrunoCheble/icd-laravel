<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Enums\VisitorStatus;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Dashboard\Models\SentMessage;
use App\Modules\Members\Models\Member;
use App\Modules\Prayers\Models\Prayer;
use App\Modules\Visitors\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Made-up data to try the system on a local computer (php artisan dev:data): members (active, pending with
 * details missing, inactive; couples, children; birthdays and wedding anniversaries this month and today),
 * visitors of the week, month, year and before, prayer requests and announcements. Every row is a "ZZ Teste" one,
 * so it is deleted by name (see clean()), never by id; real rows are never touched.
 */
class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->clean();
        $this->members();
        $this->visitors();
        $this->prayers();
        $this->announcements();
    }

    /**
     * Deletes the made-up rows (and the "sent" marks of the home page that point at them).
     */
    public function clean(): array
    {
        $members = Member::query()->where('last_name', 'like', 'ZZ Teste%');
        $visitors = Visitor::query()->where('created_by', 'ZZ Teste');
        SentMessage::query()->where('recipient_type', 'member')->whereIn('recipient_id', (clone $members)->pluck('id'))->delete();
        SentMessage::query()->where('recipient_type', 'visitor')->whereIn('recipient_id', (clone $visitors)->pluck('id'))->delete();

        return [
            'announcements' => Announcement::query()->where('title', 'like', 'ZZ Teste%')->delete(),
            'prayers' => Prayer::query()->where('name', 'like', 'ZZ Teste%')->delete(),
            'visitors' => $visitors->delete(),
            'members' => $members->delete(),
        ];
    }

    private function members(): void
    {
        $today = Carbon::today();
        $nif = 990000000;
        $make = function (string $first, string $last, array $data = []) use (&$nif, $today) {
            return Member::create($data + [
                'first_name' => $first,
                'last_name' => 'ZZ Teste ' . $last,
                'document_number' => (string) $nif++,
                'gender' => 'M',
                'marital_status' => 'S',
                'membership_status' => MembershipStatus::ACTIVED,
                'email' => strtolower($first) . '.' . strtolower($last) . '@example.com',
                'phone_number' => '91' . str_pad((string) ($nif % 10000000), 7, '0', STR_PAD_LEFT),
                'address' => 'Rua das Flores ' . ($nif % 90 + 1),
                'zip_code' => '1000-' . str_pad((string) ($nif % 999), 3, '0', STR_PAD_LEFT),
                'city' => ['Lisboa', 'Amadora', 'Sintra', 'Oeiras', 'Cascais'][$nif % 5],
                'date_of_birth' => $today->copy()->subYears(30 + $nif % 30)->subDays(40 + $nif % 200)->toDateString(),
            ]);
        };

        // Couples (the spouse linked both ways by the observer), with children
        $joao = $make('João', 'Almeida', ['marital_status' => 'M']);
        $make('Ana', 'Almeida', ['gender' => 'F', 'marital_status' => 'M', 'spouse_id' => (string) $joao->id, 'date_joined' => $today->copy()->subYears(16)->addDays(3)->toDateString()]);
        $make('Tiago', 'Almeida', ['father_id' => (string) $joao->id, 'date_of_birth' => $today->copy()->subYears(12)->addDays(9)->toDateString(), 'email' => null, 'phone_number' => null]);
        $pedro = $make('Pedro', 'Sousa', ['marital_status' => 'M']);
        $make('Carla', 'Sousa', ['gender' => 'F', 'marital_status' => 'M', 'spouse_id' => (string) $pedro->id, 'date_joined' => $today->copy()->subYears(28)->toDateString()]);
        $make('Rui', 'Martins', ['marital_status' => 'M', 'spouse_name' => 'Helena Martins', 'date_joined' => $today->copy()->subYears(9)->addDays(12)->toDateString()]);

        // Birthdays: today, this week, later this month, earlier this month
        $make('Sofia', 'Costa', ['gender' => 'F', 'date_of_birth' => $today->copy()->subYears(34)->toDateString()]);
        $make('Miguel', 'Lopes', ['date_of_birth' => $today->copy()->subYears(41)->addDays(2)->toDateString()]);
        $make('Inês', 'Gomes', ['gender' => 'F', 'date_of_birth' => $today->copy()->subYears(25)->addDays(5)->toDateString()]);
        $make('Paulo', 'Ribeiro', ['date_of_birth' => $today->copy()->subYears(60)->subDays(4)->toDateString()]);

        // Pending (registered on the site), with details missing
        $make('Marta', 'Pinto', ['gender' => 'F', 'membership_status' => MembershipStatus::PENDING, 'email' => null, 'date_of_birth' => null]);
        $make('Diogo', 'Carvalho', ['membership_status' => MembershipStatus::PENDING, 'marital_status' => 'M', 'address' => null, 'zip_code' => null]);
        $make('Rita', 'Teixeira', ['gender' => 'F', 'membership_status' => MembershipStatus::PENDING, 'phone_number' => '123']);
        // Active, with a detail missing (pending details for active members too)
        $make('Nuno', 'Ferreira', ['date_of_birth' => null]);

        // Inactive
        $make('Beatriz', 'Nunes', ['gender' => 'F', 'membership_status' => MembershipStatus::INACTIVED, 'notes' => 'Mudou de cidade.']);

        // More active members, for the lists and the pages
        foreach (['Joana', 'Bruno', 'Patrícia', 'André', 'Catarina', 'Ricardo', 'Mariana', 'Hugo'] as $i => $name) {
            $make($name, ['Rocha', 'Moreira', 'Cardoso', 'Pereira', 'Fonseca', 'Mendes', 'Barbosa', 'Correia'][$i], ['gender' => $i % 2 ? 'M' : 'F']);
        }
    }

    private function visitors(): void
    {
        $now = Carbon::now();
        foreach ([
            ['Lucas Andrade', 'M', 'youth', 'instagram', $now->copy()->subHours(2), null],
            ['Mariana Lopes', 'F', 'adult', 'João Almeida', $now->copy()->subDay(), null],
            ['Família Teixeira', 'M', 'couple', 'google', $now->copy()->startOfWeek()->addHours(10), null],
            ['Gabriel Rocha', 'M', 'teen', 'Ana Almeida', $now->copy()->startOfMonth()->addDays(1), null],
            ['Beatriz Santos', 'F', 'kid', null, $now->copy()->startOfMonth()->addDays(2), null],
            ['Tomás Faria', 'M', 'adult', 'facebook', $now->copy()->subMonths(2), null],
            ['Leonor Pires', 'F', 'adult', 'youtube', $now->copy()->subMonths(4), 'Voltou para o Brasil.'],
            ['Duarte Simões', 'M', 'youth', 'others', $now->copy()->subYear()->subDays(10), null],
        ] as [$name, $gender, $group, $invited, $at, $inactive]) {
            $visitor = Visitor::create([
                'name' => $name . ' ZZ Teste',
                'gender' => $gender,
                'group' => $group,
                'invited_by' => $invited,
                'city' => ['Lisboa', 'Porto', 'Sintra', 'Amadora'][strlen($name) % 4],
                'phone_number' => '93' . str_pad((string) (crc32($name) % 10000000), 7, '0', STR_PAD_LEFT),
                'created_by' => 'ZZ Teste',
                'status' => $inactive ? VisitorStatus::INACTIVED : VisitorStatus::ACTIVED,
                'observation' => $inactive,
            ]);
            $visitor->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }
    }

    private function prayers(): void
    {
        $now = Carbon::now();
        foreach ([
            ['ZZ Teste Ana Costa', "Família: pela saúde da minha mãe, que vai ser operada na quinta-feira.\nQue Deus guie as mãos dos médicos.", '912345678', 1],
            ['ZZ Teste Bruno', 'Trabalho: pelo meu novo emprego.', null, 3],
            ['ZZ Teste Família Sousa', null, null, 26],
            ['ZZ Teste Marta Lopes', 'Peço oração pela paz em casa.', '913456789', 50],
            ['ZZ Teste Rui', 'Gratidão: pela viagem de regresso.', null, 200],
            ['ZZ Teste Paulo', 'Direção: por uma decisão importante este mês.', '5511987654321', 24 * 40],
        ] as [$name, $request, $phone, $hours]) {
            $prayer = Prayer::create(['name' => $name, 'request' => $request, 'phone_number' => $phone]);
            $prayer->forceFill(['created_at' => $now->copy()->subHours($hours)])->save();
        }
    }

    private function announcements(): void
    {
        $authors = Member::query()->where('last_name', 'like', 'ZZ Teste%')->where('membership_status', MembershipStatus::ACTIVED)->pluck('id');
        foreach ([
            ['ZZ Teste Pintor profissional', 'service', true, '+5 months', 'Pintura de interiores e exteriores, massa corrida e pequenas reparações em paredes.', ['area' => 'construction', 'zone' => 'Amadora e Sintra', 'availability' => 'Dias úteis e sábados']],
            ['ZZ Teste Explicações de Matemática', 'lessons', true, '+4 months', 'Explicações para o 3º ciclo e secundário, presencial ou online.', ['subject' => 'Matemática', 'level' => 'secondary', 'mode' => 'both']],
            ['ZZ Teste Procuro trabalho em limpezas', 'resume', true, '+10 days', 'Experiência em limpezas domésticas e de escritórios. Disponível de manhã.', ['area' => 'cleaning', 'experience' => '1_3', 'availability' => 'part', 'driving' => false]],
            ['ZZ Teste Ajudante de cozinha', 'job', true, '-3 days', 'Restaurante procura ajudante de cozinha para o turno do almoço.', ['role' => 'Ajudante de cozinha', 'area' => 'restaurant', 'contract' => 'part', 'location' => 'Lisboa']],
            ['ZZ Teste Reparações elétricas', 'service', false, '+6 months', 'Pequenas reparações elétricas em casa.', ['area' => 'electricity', 'zone' => 'Lisboa']],
        ] as $i => [$title, $type, $approved, $expires, $text, $details]) {
            $announcement = Announcement::create([
                'member_id' => $authors[$i % max(1, $authors->count())],
                'title' => $title, 'type' => $type, 'details' => $details, 'description' => $text, 'contact' => '91234567' . $i,
                'is_approved' => $approved, 'approved_at' => $approved ? now() : null,
                'expires_at' => $expires ? now()->modify($expires)->endOfDay() : null,
            ]);
            // Members' comments: one shown on the site, one waiting for approval
            if ($i === 0) {
                $announcement->comments()->create(['member_id' => $authors->last(), 'body' => 'Pintou a minha sala, ficou impecável. Recomendo!', 'is_approved' => true, 'approved_at' => now()]);
                $announcement->comments()->create(['member_id' => $authors->first(), 'body' => 'Faz orçamentos sem compromisso?', 'is_approved' => false]);
            }
        }
    }
}
