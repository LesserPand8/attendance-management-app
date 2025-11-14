<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FixAttendanceDetailUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されるテスト
     */
    public function test_validation_error_when_start_time_is_after_end_time()
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

        // 修正申請を送信（出勤時間が退勤時間より後）
        $response = $this->post('/attendance/detail/' . $workId, [
            'start_time' => '19:00', // 退勤時間より後
            'end_time' => '18:00',
            'reason' => '時間を修正します',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors();

        // 「出勤時間もしくは退勤時間が不適切な値です」というエラーメッセージが表示されることを確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('start_time'));
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('start_time'));
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示されるテスト
     */
    public function test_validation_error_when_break_start_time_is_after_end_time()
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

        // 修正申請を送信（休憩開始時間が退勤時間より後）
        $response = $this->post('/attendance/detail/' . $workId, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start_1' => '19:00', // 退勤時間より後
            'break_end_1' => '20:00',
            'reason' => '休憩時間を修正します',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors();

        // 「休憩時間が不適切な値です」というエラーメッセージが表示されることを確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('break_start'));
        $this->assertEquals('休憩時間が不適切な値です', $errors->first('break_start'));
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されるテスト
     */
    public function test_validation_error_when_break_end_time_is_after_end_time()
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

        // 修正申請を送信（休憩終了時間が退勤時間より後）
        $response = $this->post('/attendance/detail/' . $workId, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start_1' => '12:00',
            'break_end_1' => '19:00', // 退勤時間より後
            'reason' => '休憩時間を修正します',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors();

        // 「休憩時間もしくは退勤時間が不適切な値です」というエラーメッセージが表示されることを確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('break_end'));
        $this->assertEquals('休憩時間もしくは退勤時間が不適切な値です', $errors->first('break_end'));
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示されるテスト
     */
    public function test_validation_error_when_reason_is_empty()
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

        // 修正申請を送信（備考欄が未入力）
        $response = $this->post('/attendance/detail/' . $workId, [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'reason' => '', // 備考欄が未入力
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertSessionHasErrors();

        // 「備考を記入してください」というエラーメッセージが表示されることを確認
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('reason'));
        $this->assertEquals('備考を記入してください', $errors->first('reason'));
    }

    /**
     * 修正申請処理が実行されるテスト
     */
    public function test_fix_request_is_submitted_and_displayed_for_admin()
    {
        // 一般ユーザーを作成してログイン（メール認証済み）
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

        // 修正申請を送信
        $response = $this->post('/attendance/detail/' . $workId, [
            'start_time' => '09:30',
            'end_time' => '18:30',
            'reason' => '出勤時刻と退勤時刻を修正します',
        ]);

        // リダイレクトされることを確認
        $response->assertRedirect();

        // データベースに修正申請が保存されていることを確認
        $this->assertDatabaseHas('fixes', [
            'user_id' => $user->id,
            'work_id' => $workId,
            'fix_date' => $today,
            'reason' => '出勤時刻と退勤時刻を修正します',
        ]);

        // 管理者を作成してログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 管理者の申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 修正申請が表示されていることを確認
        $response->assertSee($user->name, false);
        $response->assertSee('出勤時刻と退勤時刻を修正します', false);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていることのテスト
     */
    public function test_pending_requests_are_all_displayed_for_logged_in_user()
    {
        // 一般ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $today = \Carbon\Carbon::today();

        // 複数の勤怠データを作成
        $workId1 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workId2 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today->copy()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1つ目の修正申請を送信
        $this->post('/attendance/detail/' . $workId1, [
            'start_time' => '09:30',
            'end_time' => '18:30',
            'reason' => '1つ目の修正申請',
        ]);

        // 2つ目の修正申請を送信
        $this->post('/attendance/detail/' . $workId2, [
            'start_time' => '10:30',
            'end_time' => '19:30',
            'reason' => '2つ目の修正申請',
        ]);

        // 申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 自分の申請が全て表示されていることを確認
        $response->assertSee('1つ目の修正申請', false);
        $response->assertSee('2つ目の修正申請', false);
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されているテスト
     */
    public function test_approved_requests_are_all_displayed_after_admin_approval()
    {
        // 一般ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $today = \Carbon\Carbon::today();

        // 複数の勤怠データを作成
        $workId1 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workId2 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today->copy()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1つ目の修正申請を送信
        $this->post('/attendance/detail/' . $workId1, [
            'start_time' => '09:30',
            'end_time' => '18:30',
            'reason' => '承認される申請1',
        ]);

        // 2つ目の修正申請を送信
        $this->post('/attendance/detail/' . $workId2, [
            'start_time' => '10:30',
            'end_time' => '19:30',
            'reason' => '承認される申請2',
        ]);

        // データベースから作成された修正申請のIDを取得
        $fixId1 = \Illuminate\Support\Facades\DB::table('fixes')
            ->where('work_id', $workId1)
            ->value('id');
        $fixId2 = \Illuminate\Support\Facades\DB::table('fixes')
            ->where('work_id', $workId2)
            ->value('id');

        // 管理者を作成してログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 1つ目の修正申請を承認
        $this->post('/stamp_correction_request/approve/' . $fixId1);

        // 2つ目の修正申請を承認
        $this->post('/stamp_correction_request/approve/' . $fixId2);

        // 一般ユーザーとして再ログイン
        $this->actingAs($user);

        // 申請一覧画面の「承認済み」タブにアクセス
        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        // 承認済みの申請が全て表示されていることを確認
        $response->assertSee('承認される申請1', false);
        $response->assertSee('承認される申請2', false);
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移するテスト
     */
    public function test_detail_button_navigates_to_attendance_detail_page()
    {
        // 一般ユーザーを作成してログイン（メール認証済み）
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $today = \Carbon\Carbon::today();

        // 勤怠データを作成
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 修正申請を送信
        $this->post('/attendance/detail/' . $workId, [
            'start_time' => '09:30',
            'end_time' => '18:30',
            'reason' => '詳細画面テスト用申請',
        ]);

        // 申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 詳細リンクが存在することを確認
        $response->assertSee('/attendance/detail/' . $workId, false);

        // 詳細リンクをクリック（勤怠詳細画面に遷移）
        $detailResponse = $this->get('/attendance/detail/' . $workId);
        $detailResponse->assertStatus(200);

        // 勤怠詳細画面の内容が表示されていることを確認
        $detailResponse->assertSee($user->name, false);
        $detailResponse->assertSee('詳細画面テスト用申請', false);
    }
}
