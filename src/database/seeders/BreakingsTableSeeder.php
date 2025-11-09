<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class BreakingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 課題: 以前の実装では work_id を「日付=連番」と仮定していたため、
        // works テーブルに user1 -> user1(10月) -> user2 ... の順でIDが採番される現状では
        // 全て user1 9月のレコード (id 1..30) に紐づいてしまっていた。
        // 対策: user_id と work_date から正しい work_id を検索して紐付ける。

        $months = [
            ['start' => '2025-09-01', 'end' => '2025-09-30'],
            ['start' => '2025-10-01', 'end' => '2025-10-31'],
        ];
        $users = [1, 2];

        $inserts = [];
        foreach ($users as $userId) {
            foreach ($months as $m) {
                $period = CarbonPeriod::create($m['start'], $m['end']);
                foreach ($period as $date) {
                    $workDate = $date->format('Y-m-d');
                    $workId = DB::table('works')
                        ->where('user_id', $userId)
                        ->where('work_date', $workDate)
                        ->value('id');

                    if (!$workId) {
                        // works が存在しない場合はスキップ（Seeder 実行順の問題を避ける）
                        continue;
                    }

                    $inserts[] = [
                        'user_id'    => $userId,
                        'work_id'    => $workId,
                        'start_time' => Carbon::createFromFormat('Y-m-d H:i:s', $workDate . ' 12:00:00')->format('H:i:s'),
                        'end_time'   => Carbon::createFromFormat('Y-m-d H:i:s', $workDate . ' 13:00:00')->format('H:i:s'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // バルクインサート（件数が増える場合チャンク化を検討）
        if (!empty($inserts)) {
            DB::table('breakings')->insert($inserts);
        }
    }
}
