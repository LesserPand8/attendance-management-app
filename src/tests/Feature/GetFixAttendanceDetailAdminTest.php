<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetFixAttendanceDetailAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっているテスト
     */
    public function test_attendance_detail_shows_selected_data_for_admin()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // ユーザーと勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user->id,
            'work_id' => $workId,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 既存の勤怠詳細ページに直接アクセス (/admin/attendance/{id})
        $response = $this->get('/admin/attendance/' . $workId);
        $response->assertStatus(200);

        // 詳細画面の内容が選択した情報と一致すること
        $response->assertSee('テストユーザー', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);
        $response->assertSee('12:00', false);
        $response->assertSee('13:00', false);
    }

    /**
     * 出勤時間が退勤時間より後の場合、エラーメッセージが表示されるテスト
     */
    public function test_error_message_shown_when_start_time_is_after_end_time()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 不正な出勤・退勤時間で修正申請 (POST /admin/attendance/{id})
        $response = $this->post('/admin/attendance/' . $workId, [
            'start_time' => '19:00', // 退勤時間より後
            'end_time' => '18:00',
            'reason' => 'テスト',
        ]);

        // バリデーションエラー確認
        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('start_time'));
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('start_time'));
    }

    /**
     * 休憩開始時間が退勤時間より後の場合、エラーメッセージが表示されるテスト
     */
    public function test_error_message_shown_when_break_start_time_is_after_end_time()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩開始時間を退勤時間より後に設定して修正申請
        $response = $this->post('/admin/attendance/' . $workId, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start_1' => '19:00', // 退勤時間より後
            'break_end_1' => '20:00',
            'reason' => 'テスト',
        ]);

        // バリデーションエラー確認
        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('break_start') || $errors->has('break_end'));
        $errorMessage = $errors->first('break_start') ?: $errors->first('break_end');
        $this->assertEquals('休憩時間が不適切な値です', $errorMessage);
    }

    /**
     * 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示されるテスト
     */
    public function test_error_message_shown_when_break_end_time_is_after_end_time()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩終了時間を退勤時間より後に設定して修正申請
        $response = $this->post('/admin/attendance/' . $workId, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start_1' => '12:00',
            'break_end_1' => '19:00', // 退勤時間より後
            'reason' => 'テスト',
        ]);

        // バリデーションエラー確認
        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('break_end'));
        $this->assertEquals('休憩時間もしくは退勤時間が不適切な値です', $errors->first('break_end'));
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示されるテスト
     */
    public function test_error_message_shown_when_reason_is_empty()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 備考欄を未入力で修正申請
        $response = $this->post('/admin/attendance/' . $workId, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => '', // 備考欄が未入力
        ]);

        // バリデーションエラー確認
        $response->assertSessionHasErrors();
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('reason'));
        $this->assertEquals('備考を記入してください', $errors->first('reason'));
    }
}
