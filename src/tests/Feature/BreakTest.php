<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 休憩ボタンが正しく機能するテスト
     */
    public function test_break_in_button_works_correctly()
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

        // 「休憩入」ボタンが表示されていることを確認
        $response->assertSee('休憩入', false);

        // 「出勤中」ステータスが表示されていることを確認
        $response->assertSee('出勤中', false);

        // 休憩入処理を実行
        $breakInTime = \Carbon\Carbon::now()->format('H:i:s');

        $response = $this->post('/attendance', [
            'date' => $today,
            'time' => $breakInTime,
            'action' => 'breakin',
        ]);

        // リダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // データベースに休憩記録が保存されていることを確認
        $this->assertDatabaseHas('breakings', [
            'work_id' => $workId,
        ]);

        // 休憩開始時刻が記録されていることを確認
        $breaking = \Illuminate\Support\Facades\DB::table('breakings')
            ->where('work_id', $workId)
            ->first();
        $this->assertNotNull($breaking);
        $this->assertNotNull($breaking->start_time); // start_time が記録されている
        $this->assertNull($breaking->end_time); // まだ休憩終了していない

        // 再度画面にアクセスして「休憩中」ステータスを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中', false);
    }

    /**
     * 休憩は一日に何回でもできるテスト
     */
    public function test_multiple_breaks_allowed_per_day()
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

        // 1回目の休憩入処理を実行
        $breakInTime1 = '12:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakInTime1,
            'action' => 'breakin',
        ]);

        // 1回目の休憩戻処理を実行
        $breakOutTime1 = '13:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakOutTime1,
            'action' => 'breakout',
        ]);

        // データベースに1回目の休憩記録が完了していることを確認
        $this->assertDatabaseHas('breakings', [
            'work_id' => $workId,
            'start_time' => $breakInTime1,
            'end_time' => $breakOutTime1,
        ]);

        // 休憩戻後、画面にアクセスして「出勤中」ステータスと「休憩入」ボタンを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中', false);
        $response->assertSee('休憩入', false); // 2回目の休憩が可能であることを確認
    }

    /**
     * 休憩戻ボタンが正しく機能するテスト
     */
    public function test_break_out_button_works_correctly()
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

        // 休憩入処理を実行
        $breakInTime = '12:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakInTime,
            'action' => 'breakin',
        ]);

        // 休憩中の状態を確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中', false);

        // 「休憩戻」ボタンが表示されていることを確認
        $response->assertSee('休憩戻', false);

        // 休憩戻処理を実行
        $breakOutTime = '13:00:00';
        $response = $this->post('/attendance', [
            'date' => $today,
            'time' => $breakOutTime,
            'action' => 'breakout',
        ]);

        // リダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // データベースに休憩終了時刻が記録されていることを確認
        $this->assertDatabaseHas('breakings', [
            'work_id' => $workId,
            'start_time' => $breakInTime,
            'end_time' => $breakOutTime,
        ]);

        // 再度画面にアクセスして「出勤中」ステータスを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中', false);
    }

    /**
     * 休憩戻は一日に何回でもできるテスト
     */
    public function test_multiple_break_outs_allowed_per_day()
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

        // 1回目の休憩入処理を実行
        $breakInTime1 = '12:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakInTime1,
            'action' => 'breakin',
        ]);

        // 1回目の休憩戻処理を実行
        $breakOutTime1 = '13:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakOutTime1,
            'action' => 'breakout',
        ]);

        // 2回目の休憩入処理を実行
        $breakInTime2 = '15:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakInTime2,
            'action' => 'breakin',
        ]);

        // 休憩中の状態を確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中', false);

        // 「休憩戻」ボタンが表示されることを確認（2回目の休憩戻が可能）
        $response->assertSee('休憩戻', false);
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できるテスト
     */
    public function test_break_time_displayed_in_attendance_list()
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
            'end_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩入処理を実行
        $breakInTime = '12:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakInTime,
            'action' => 'breakin',
        ]);

        // 休憩戻処理を実行
        $breakOutTime = '13:00:00';
        $this->post('/attendance', [
            'date' => $today,
            'time' => $breakOutTime,
            'action' => 'breakout',
        ]);

        // データベースに休憩記録が保存されていることを確認
        $this->assertDatabaseHas('breakings', [
            'work_id' => $workId,
            'start_time' => $breakInTime,
            'end_time' => $breakOutTime,
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 休憩時間が表示されていることを確認
        // 休憩時間は合計時間として表示される想定（13:00 - 12:00 = 01:00）
        $breakDuration = '01:00';
        $response->assertSee($breakDuration, false);

        // 勤務日付が表示されていることを確認
        $displayDate = \Carbon\Carbon::parse($today)->format('m/d');
        $response->assertSee($displayDate, false);
    }
}
