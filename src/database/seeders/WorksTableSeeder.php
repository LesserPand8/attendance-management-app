<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WorksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // user 1 / 2 の 2025-09 と 2025-10 を作成
        $users = [1, 2];
        $months = [
            ['start' => '2025-09-01', 'end' => '2025-09-30'],
            ['start' => '2025-10-01', 'end' => '2025-10-31'],
        ];

        foreach ($users as $userId) {
            foreach ($months as $m) {
                $period = CarbonPeriod::create($m['start'], $m['end']);
                $rows = [];
                foreach ($period as $date) {
                    $workDate = $date->format('Y-m-d');
                    $rows[] = [
                        'user_id'    => $userId,
                        'work_date'  => $workDate,
                        'start_time' => '09:00:00',
                        'end_time'   => '18:00:00',
                    ];
                }
                DB::table('works')->insert($rows);
            }
        }
    }
}
