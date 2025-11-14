<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetUserListAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が全一般ユーザーの氏名とメールアドレスを確認できるテスト
     */
    public function test_admin_can_view_all_users_name_and_email()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 複数の一般ユーザーを作成
        $user1 = \App\Models\User::factory()->create([
            'name' => 'テストユーザー1',
            'email' => 'test1@example.com',
        ]);
        $user2 = \App\Models\User::factory()->create([
            'name' => 'テストユーザー2',
            'email' => 'test2@example.com',
        ]);
        $user3 = \App\Models\User::factory()->create([
            'name' => 'テストユーザー3',
            'email' => 'test3@example.com',
        ]);

        // スタッフ一覧ページにアクセス
        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        // 全ての一般ユーザーの氏名とメールアドレスが表示されていることを確認
        $response->assertSee('テストユーザー1', false);
        $response->assertSee('test1@example.com', false);
        $response->assertSee('テストユーザー2', false);
        $response->assertSee('test2@example.com', false);
        $response->assertSee('テストユーザー3', false);
        $response->assertSee('test3@example.com', false);
    }

    /**
     * 管理者が選択したユーザーの勤怠情報を正しく確認できるテスト
     */
    public function test_admin_can_view_user_attendance_information()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーを作成
        $user = \App\Models\User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        // 勤怠データを作成
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 休憩時間を作成
        \Illuminate\Support\Facades\DB::table('breakings')->insert([
            'user_id' => $user->id,
            'work_id' => $workId,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ユーザーの勤怠一覧ページにアクセス
        $response = $this->get('/admin/attendance/staff/' . $user->id);
        $response->assertStatus(200);

        // 勤怠情報が正確に表示されることを確認
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);
        $response->assertSee('01:00', false); // 休憩時間の合計
    }

    /**
     * 前月ボタンを押下した時に前月の情報が表示されるテスト
     */
    public function test_previous_month_button_shows_previous_month_data()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーを作成
        $user = \App\Models\User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        // 前日の勤怠データを作成
        $previousDay = \Carbon\Carbon::now()->subDay();
        $previousDayDate = $previousDay->format('Y-m-d');

        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $previousDayDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠一覧ページを開く
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 前日のパラメータを付けてアクセス
        $response = $this->get('/admin/attendance/list?day=' . $previousDayDate);
        $response->assertStatus(200);

        // 前日の日付が表示されていることを確認
        $response->assertSee($previousDay->format('Y年m月d日'), false);
    }

    /**
     * 翌月ボタンを押下した時に翌月の情報が表示されるテスト
     */
    public function test_next_month_button_shows_next_month_data()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーを作成
        $user = \App\Models\User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        // 翌日の勤怠データを作成
        $nextDay = \Carbon\Carbon::now()->addDay();
        $nextDayDate = $nextDay->format('Y-m-d');

        \Illuminate\Support\Facades\DB::table('works')->insert([
            'user_id' => $user->id,
            'work_date' => $nextDayDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠一覧ページを開く
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 翌日のパラメータを付けてアクセス
        $response = $this->get('/admin/attendance/list?day=' . $nextDayDate);
        $response->assertStatus(200);

        // 翌日の日付が表示されていることを確認
        $response->assertSee($nextDay->format('Y年m月d日'), false);
    }

    /**
     * 詳細ボタンを押下すると勤怠詳細画面に遷移するテスト
     */
    public function test_detail_button_navigates_to_attendance_detail_page()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーを作成
        $user = \App\Models\User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        // 勤怠データを作成
        $workDate = \Carbon\Carbon::today()->format('Y-m-d');
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 勤怠一覧ページを開く
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 詳細リンクが表示されていることを確認
        $response->assertSee('/admin/attendance/' . $workId, false);

        // 詳細ページに遷移
        $detailResponse = $this->get('/admin/attendance/' . $workId);
        $detailResponse->assertStatus(200);

        // 勤怠詳細ページの内容が表示されていることを確認
        $detailResponse->assertSee('テストユーザー', false);
        $detailResponse->assertSee('09:00', false);
        $detailResponse->assertSee('18:00', false);
    }
}
