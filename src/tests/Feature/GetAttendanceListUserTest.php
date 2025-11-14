<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAttendanceListUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されているテスト
     */
    public function test_all_user_attendance_records_displayed()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 複数の勤怠データを作成
        $today = \Carbon\Carbon::today();
        $workData = [];

        // 3日分の勤怠データを作成
        for ($i = 0; $i < 3; $i++) {
            $date = $today->copy()->subDays($i)->format('Y-m-d');
            $workData[] = [
                'user_id' => $user->id,
                'work_date' => $date,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        \Illuminate\Support\Facades\DB::table('works')->insert($workData);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 各勤怠データが表示されていることを確認
        foreach ($workData as $work) {
            // 日付が表示されていることを確認（m/d 形式）
            $displayDate = \Carbon\Carbon::parse($work['work_date'])->format('m/d');
            $response->assertSee($displayDate, false);

            // 出勤時刻が表示されていることを確認
            $displayStartTime = \Carbon\Carbon::parse($work['start_time'])->format('H:i');
            $response->assertSee($displayStartTime, false);

            // 退勤時刻が表示されていることを確認
            $displayEndTime = \Carbon\Carbon::parse($work['end_time'])->format('H:i');
            $response->assertSee($displayEndTime, false);
        }
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示されるテスト
     */
    public function test_current_month_displayed_on_attendance_list()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 現在の月が表示されていることを確認（Y/m 形式）
        $currentMonth = \Carbon\Carbon::now()->format('Y/m');
        $response->assertSee($currentMonth, false);
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示されるテスト
     */
    public function test_previous_month_displayed_when_clicking_previous_button()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 前月の勤怠データを作成
        $lastMonth = \Carbon\Carbon::now()->subMonth();
        $lastMonthDate = $lastMonth->format('Y-m-d');

        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $lastMonthDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 前月を指定して勤怠一覧画面にアクセス
        $previousMonth = \Carbon\Carbon::now()->subMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=' . $previousMonth);
        $response->assertStatus(200);

        // 前月が表示されていることを確認（Y/m 形式）
        $displayMonth = \Carbon\Carbon::parse($previousMonth)->format('Y/m');
        $response->assertSee($displayMonth, false);

        // 前月のデータが表示されていることを確認
        $displayDate = \Carbon\Carbon::parse($lastMonthDate)->format('m/d');
        $response->assertSee($displayDate, false);
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示されるテスト
     */
    public function test_next_month_displayed_when_clicking_next_button()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 翌月の勤怠データを作成
        $nextMonth = \Carbon\Carbon::now()->addMonth();
        $nextMonthDate = $nextMonth->format('Y-m-d');

        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $nextMonthDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 翌月を指定して勤怠一覧画面にアクセス
        $nextMonthParam = \Carbon\Carbon::now()->addMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=' . $nextMonthParam);
        $response->assertStatus(200);

        // 翌月が表示されていることを確認（Y/m 形式）
        $displayMonth = \Carbon\Carbon::parse($nextMonthParam)->format('Y/m');
        $response->assertSee($displayMonth, false);

        // 翌月のデータが表示されていることを確認
        $displayDate = \Carbon\Carbon::parse($nextMonthDate)->format('m/d');
        $response->assertSee($displayDate, false);
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移するテスト
     */
    public function test_detail_button_navigates_to_attendance_detail_page()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 勤怠データを作成
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 「詳細」リンクが表示されていることを確認
        $response->assertSee('詳細', false);

        // 詳細ページへのリンクを確認
        $detailUrl = '/attendance/detail/' . $workId;
        $response->assertSee($detailUrl, false);

        // 詳細ページにアクセス
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
    }
}
