<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_is_displayed_as_off_duty()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 勤怠打刻画面にアクセス（まだ出勤していない状態）
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 画面に「勤務外」のステータスが表示されていることを確認
        $response->assertSee('勤務外', false);
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_is_displayed_as_on_duty()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 今日の日付で出勤済みの勤務レコードを作成（start_timeあり、end_timeなし）
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => null, // 退勤していない
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 画面に「出勤中」のステータスが表示されていることを確認
        $response->assertSee('出勤中', false);
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_is_displayed_as_on_break()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 今日の日付で出勤済みの勤務レコードを作成（start_timeあり、end_timeなし）
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => null, // 退勤していない
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩中のレコードを作成（start_timeあり、end_timeなし）
        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user->id,
            'work_id' => $workId,
            'start_time' => '12:00:00',
            'end_time' => null, // 休憩戻りしていない
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 画面に「休憩中」のステータスが表示されていることを確認
        $response->assertSee('休憩中', false);
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示されるテスト
     */
    public function test_status_is_displayed_as_clocked_out()
    {
        // ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // 今日の日付で退勤済みの勤務レコードを作成（start_timeあり、end_timeあり）
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00', // 退勤済み
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 画面に「退勤済」のステータスが表示されていることを確認
        $response->assertSee('退勤済', false);
    }
}
