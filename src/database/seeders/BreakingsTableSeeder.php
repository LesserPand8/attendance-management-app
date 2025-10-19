<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BreakingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert breakings for September (1..30) for user_id = 1
        $userId = 1;
        for ($day = 1; $day <= 30; $day++) {
            $workId = $day;
            $date = sprintf('2025-09-%02d', $day);
            $param = [
                'user_id' => $userId,
                'work_id' => $workId,
                'start_time' => $date . ' 12:00:00',
                'end_time' => $date . ' 13:00:00',
            ];

            DB::table('breakings')->insert($param);
        }
    }
}
