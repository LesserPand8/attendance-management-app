<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LeaveWorkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能するテスト
     */
    public function test_clock_out_button_works_correctly()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 出勤中の状態を作成（本日の勤務データで出勤済み、退勤未完了）
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => null, // 退勤時刻なし = 出勤中
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠打刻画面にアクセス（出勤中の状態）
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 「退勤」ボタンが表示されていることを確認
        $response->assertSee('退勤', false);

        // 「出勤中」ステータスが表示されていることを確認
        $response->assertSee('出勤中', false);

        // 退勤処理を実行
        $clockOutTime = \Carbon\Carbon::now()->format('H:i:s');

        $response = $this->post('/attendance', [
            'date' => $today,
            'time' => $clockOutTime,
            'action' => 'clockout',
        ]);

        // リダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // データベースに退勤時刻が記録されていることを確認
        $this->assertDatabaseHas('works', [
            'id' => $workId,
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
        ]);

        // end_timeが記録されていることを確認
        $work = \Illuminate\Support\Facades\DB::table('works')
            ->where('id', $workId)
            ->first();
        $this->assertNotNull($work);
        $this->assertNotNull($work->end_time);

        // 再度画面にアクセスして「退勤済」ステータスを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済', false);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できるテスト
     */
    public function test_clock_out_time_displayed_in_attendance_list()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $today = \Carbon\Carbon::today()->format('Y-m-d');

        // 出勤処理を実行
        $clockInTime = '09:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $clockInTime,
            'action' => 'clockin',
        ]);

        // 退勤処理を実行
        $clockOutTime = '18:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $clockOutTime,
            'action' => 'clockout',
        ]);

        // データベースに出勤・退勤記録が保存されていることを確認
        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => $clockInTime,
            'end_time' => $clockOutTime,
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 退勤時刻が表示されていることを確認（秒なしの形式 H:i で表示される想定）
        $displayClockOutTime = \Carbon\Carbon::parse($clockOutTime)->format('H:i');
        $response->assertSee($displayClockOutTime, false);

        // 勤務日付が表示されていることを確認
        $displayDate = \Carbon\Carbon::parse($today)->format('m/d');
        $response->assertSee($displayDate, false);
    }
}
