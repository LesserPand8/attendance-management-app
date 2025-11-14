<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetAttendanceListAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できるテスト
     */
    public function test_all_users_attendance_is_displayed_accurately()
    {
        // 管理者を作成してログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $today = \Carbon\Carbon::today();

        // 複数のユーザーを作成
        /** @var \App\Models\User $user1 */
        $user1 = \App\Models\User::factory()->create(['name' => 'テストユーザー1']);
        /** @var \App\Models\User $user2 */
        $user2 = \App\Models\User::factory()->create(['name' => 'テストユーザー2']);

        // ユーザー1の勤怠データを作成
        $workId1 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user1->id,
            'work_date' => $today->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ユーザー1の休憩時間を作成
        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user1->id,
            'work_id' => $workId1,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ユーザー2の勤怠データを作成
        $workId2 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user2->id,
            'work_date' => $today->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ユーザー2の休憩時間を作成
        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user2->id,
            'work_id' => $workId2,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 管理者の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // ユーザー1の勤怠情報が表示されていることを確認
        $response->assertSee('テストユーザー1', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);
        $response->assertSee('01:00', false); // 休憩時間

        // ユーザー2の勤怠情報が表示されていることを確認
        $response->assertSee('テストユーザー2', false);
        $response->assertSee('10:00', false);
        $response->assertSee('19:00', false);
        $response->assertSee('01:00', false); // 休憩時間
    }

    /**
     * 勤怠一覧画面に現在の日付が表示されることのテスト
     */
    public function test_attendance_list_shows_current_date_for_admin()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $today = \Carbon\Carbon::today();
        $expectedTitleDate = $today->format('Y年n月d日');
        $expectedSpanDate = $today->format('Y/n/d');

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // タイトル部の日付
        $response->assertSee($expectedTitleDate, false);
        // 現在日表示部の日付
        $response->assertSee($expectedSpanDate, false);
    }

    /**
     * 「前日」ボタン押下で前日の勤怠情報が表示されるテスト
     */
    public function test_attendance_list_shows_previous_day_when_prev_button_clicked()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $today = \Carbon\Carbon::today();
        $yesterday = $today->copy()->subDay();

        // 前日の勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $yesterday->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
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

        // 「前日」ボタン押下時のリクエスト（dayパラメータで前日指定）
        $response = $this->get('/admin/attendance/list?day=' . $yesterday->format('Y-m-d'));
        $response->assertStatus(200);

        // 前日の日付と勤怠情報が表示されること
        $response->assertSee($yesterday->format('Y年n月d日'), false);
        $response->assertSee('テストユーザー', false);
        $response->assertSee('08:00', false);
        $response->assertSee('17:00', false);
        $response->assertSee('01:00', false); // 休憩時間
    }

    /**
     * 「翌日」ボタン押下で翌日の勤怠情報が表示されるテスト
     */
    public function test_attendance_list_shows_next_day_when_next_button_clicked()
    {
            // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $today = \Carbon\Carbon::today();
        $tomorrow = $today->copy()->addDay();

        // 翌日の勤怠データ作成
        $user = \App\Models\User::factory()->create(['name' => 'テストユーザー']);
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $tomorrow->format('Y-m-d'),
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user->id,
            'work_id' => $workId,
            'start_time' => '12:30:00',
            'end_time' => '13:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 「翌日」ボタン押下時のリクエスト（dayパラメータで翌日指定）
        $response = $this->get('/admin/attendance/list?day=' . $tomorrow->format('Y-m-d'));
        $response->assertStatus(200);

        // 翌日の日付と勤怠情報が表示されること
        $response->assertSee($tomorrow->format('Y年n月d日'), false);
        $response->assertSee('テストユーザー', false);
        $response->assertSee('08:30', false);
        $response->assertSee('17:30', false);
        $response->assertSee('01:00', false); // 休憩時間
    }
}
