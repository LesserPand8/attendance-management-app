<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能するテスト
     */
    public function test_clock_in_button_works_correctly()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 勤怠打刻画面にアクセス（勤務外の状態）
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 「出勤」ボタンが表示されていることを確認
        $response->assertSee('出勤', false);

        // 出勤処理を実行
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $time = \Carbon\Carbon::now()->format('H:i:s'); // 秒まで含める

        $response = $this->post('/attendance', [
            'date' => $today,
            'time' => $time,
            'action' => 'clockin',
        ]);

        // リダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // データベースに出勤記録が保存されていることを確認
        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'work_date' => $today,
        ]);

        // start_timeが記録されていることを確認（秒まで一致させる必要があるため別途確認）
        $work = \Illuminate\Support\Facades\DB::table('works')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();
        $this->assertNotNull($work);
        $this->assertNotNull($work->start_time);

        // 再度画面にアクセスして「出勤中」ステータスを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中', false);
    }

    /**
     * 出勤は一日一回のみできるテスト
     */
    public function test_clock_in_button_not_displayed_after_clock_out()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 退勤済の勤務データを作成（本日の勤務で既に退勤済み）
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00', // 退勤時刻が記録されている = 退勤済
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 「出勤」ボタンが表示されないことを確認
        $response->assertDontSee('出勤', false);

        // 「退勤済」ステータスが表示されることを確認
        $response->assertSee('退勤済', false);
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できるテスト
     */
    public function test_clock_in_time_displayed_in_attendance_list()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 出勤処理を実行
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $clockInTime = '09:30:00';

        $this->post('/attendance', [
            'date' => $today,
            'time' => $clockInTime,
            'action' => 'clockin',
        ]);

        // データベースに出勤記録が保存されていることを確認
        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => $clockInTime,
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 出勤時刻が表示されていることを確認（秒なしの形式 H:i で表示される想定）
        $displayTime = \Carbon\Carbon::parse($clockInTime)->format('H:i');
        $response->assertSee($displayTime, false);

        // 勤務日付が表示されていることを確認（11/11(火) の形式で表示される）
        $displayDate = \Carbon\Carbon::parse($today)->format('m/d');
        $response->assertSee($displayDate, false);
    }
}
