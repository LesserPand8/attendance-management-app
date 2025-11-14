<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAttendanceDetailUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっているテスト
     */
    public function test_attendance_detail_displays_logged_in_user_name()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'name' => '山田太郎',
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

        // 勤怠詳細画面にアクセス
        $response = $this->get('/attendance/detail/' . $workId);
        $response->assertStatus(200);

        // ログインユーザーの名前が表示されていることを確認
        $response->assertSee($user->name, false);
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっているテスト
     */
    public function test_attendance_detail_displays_selected_date()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 特定の日付で勤怠データを作成
        $selectedDate = '2025-11-01';
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $selectedDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get('/attendance/detail/' . $workId);
        $response->assertStatus(200);

        // 選択した日付が表示されていることを確認
        // 年が表示されている（"2025年"）
        $year = \Carbon\Carbon::parse($selectedDate)->format('Y') . '年';
        $response->assertSee($year, false);

        // 月日が表示されている（"11月1日"）
        $monthDay = \Carbon\Carbon::parse($selectedDate)->format('n月j日');
        $response->assertSee($monthDay, false);
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致しているテスト
     */
    public function test_attendance_detail_displays_correct_work_times()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 勤怠データを作成
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $startTime = '09:30:00';
        $endTime = '18:45:00';

        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get('/attendance/detail/' . $workId);
        $response->assertStatus(200);

        // 出勤時刻が表示されていることを確認（H:i 形式）
        $displayStartTime = \Carbon\Carbon::parse($startTime)->format('H:i');
        $response->assertSee($displayStartTime, false);

        // 退勤時刻が表示されていることを確認（H:i 形式）
        $displayEndTime = \Carbon\Carbon::parse($endTime)->format('H:i');
        $response->assertSee($displayEndTime, false);
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致しているテスト
     */
    public function test_attendance_detail_displays_correct_break_times()
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

        // 休憩データを作成
        $breakStartTime = '12:00:00';
        $breakEndTime = '13:00:00';

        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user->id,
            'work_id' => $workId,
            'start_time' => $breakStartTime,
            'end_time' => $breakEndTime,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get('/attendance/detail/' . $workId);
        $response->assertStatus(200);

        // 休憩開始時刻が表示されていることを確認（H:i 形式）
        $displayBreakStartTime = \Carbon\Carbon::parse($breakStartTime)->format('H:i');
        $response->assertSee($displayBreakStartTime, false);

        // 休憩終了時刻が表示されていることを確認（H:i 形式）
        $displayBreakEndTime = \Carbon\Carbon::parse($breakEndTime)->format('H:i');
        $response->assertSee($displayBreakEndTime, false);
    }
}
