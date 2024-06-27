<?php

namespace App\Console\Commands;

use App\Helpers\MembershipStatus;
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

        $csv = array_map('str_getcsv', file($file));
        foreach ($csv as $row) {
            Member::create([
                'first_name' => $row[0],
                'middle_name' => $row[1],
                'last_name' => $row[2],
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
