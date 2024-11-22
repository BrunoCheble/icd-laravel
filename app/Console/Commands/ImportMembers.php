<?php

namespace App\Console\Commands;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Helpers\NameHelper;
use Illuminate\Console\Command;
use App\Models\Member;

class ImportMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:members {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        // Abrir o arquivo CSV
        $csv = array_map(function($line) {
            return str_getcsv($line, ';');
        }, file($file));

        foreach ($csv as $row) {

            $full_name = NameHelper::splitFullName($row[0]);

            Member::create([
                'first_name' => $full_name['first_name'],
                'middle_name' => $full_name['middle_name'],
                'last_name' => $full_name['last_name'],
                'gender' => $row[1],
                'marital_status' => $row[2],
                'email' => $row[3],
                'phone_number' => $row[4],
                'address' => $row[5],
                'city' => $row[6],
                'zip_code' => $row[7],
                'date_of_birth' => $row[8],
                'membership_status' => MembershipStatus::PENDING,
            ]);
        }

        $this->info('Members imported successfully.');
    }
}
