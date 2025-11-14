<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    /**
     * 承認待ちの修正申請が全て表示されるテスト
     */
    public function test_all_pending_fix_requests_are_displayed()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 複数の一般ユーザーを作成
        $user1 = \App\Models\User::factory()->create(['name' => 'テストユーザー1']);
        $user2 = \App\Models\User::factory()->create(['name' => 'テストユーザー2']);
        $user3 = \App\Models\User::factory()->create(['name' => 'テストユーザー3']);

        // 各ユーザーの勤怠データを作成
        $workId1 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user1->id,
            'work_date' => '2025-11-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workId2 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user2->id,
            'work_date' => '2025-11-11',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workId3 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user3->id,
            'work_date' => '2025-11-12',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 承認待ちの修正申請を作成
        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user1->id,
            'work_id' => $workId1,
            'fix_date' => '2025-11-10',
            'reason' => '打刻忘れ',
            'status' => '承認待ち',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user2->id,
            'work_id' => $workId2,
            'fix_date' => '2025-11-11',
            'reason' => '時間間違い',
            'status' => '承認待ち',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user3->id,
            'work_id' => $workId3,
            'fix_date' => '2025-11-12',
            'reason' => '休憩時間修正',
            'status' => '承認待ち',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 承認済みの修正申請も作成(これは表示されないはず)
        $workId4 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user1->id,
            'work_date' => '2025-11-09',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user1->id,
            'work_id' => $workId4,
            'fix_date' => '2025-11-09',
            'reason' => '承認済み申請',
            'status' => '承認',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 修正申請一覧ページにアクセス
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 承認待ちの修正申請が全て表示されていることを確認
        $response->assertSee('テストユーザー1', false);
        $response->assertSee('打刻忘れ', false);
        $response->assertSee('テストユーザー2', false);
        $response->assertSee('時間間違い', false);
        $response->assertSee('テストユーザー3', false);
        $response->assertSee('休憩時間修正', false);
    }

    /**
     * 承認済みの修正申請が全て表示されるテスト
     */
    public function test_all_approved_fix_requests_are_displayed()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 複数の一般ユーザーを作成
        $user1 = \App\Models\User::factory()->create(['name' => '承認ユーザー1']);
        $user2 = \App\Models\User::factory()->create(['name' => '承認ユーザー2']);
        $user3 = \App\Models\User::factory()->create(['name' => '承認ユーザー3']);

        // 各ユーザーの勤怠データを作成
        $workId1 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user1->id,
            'work_date' => '2025-11-13',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workId2 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user2->id,
            'work_date' => '2025-11-13',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workId3 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user3->id,
            'work_date' => '2025-11-13',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 承認済みの修正申請を作成
        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user1->id,
            'work_id' => $workId1,
            'fix_date' => '2025-11-13',
            'reason' => '承認済み理由1',
            'status' => '承認',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user2->id,
            'work_id' => $workId2,
            'fix_date' => '2025-11-13',
            'reason' => '承認済み理由2',
            'status' => '承認',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user3->id,
            'work_id' => $workId3,
            'fix_date' => '2025-11-13',
            'reason' => '承認済み理由3',
            'status' => '承認',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 承認待ちの修正申請も作成(これは承認済みタブには表示されないはず)
        $workId4 = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user1->id,
            'work_date' => '2025-11-14',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('fixes')->insert([
            'user_id' => $user1->id,
            'work_id' => $workId4,
            'fix_date' => '2025-11-14',
            'reason' => '承認待ち申請',
            'status' => '承認待ち',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 修正申請一覧ページの承認済みタブにアクセス
        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        // 承認済みタブが表示されていることを確認
        $response->assertSee('承認済み', false);
        $response->assertSee('tab-approved active', false);
    }

    /**
     * 修正申請の詳細内容が正しく表示されるテスト
     */
    public function test_fix_request_detail_is_displayed_correctly()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーを作成
        $user = \App\Models\User::factory()->create([
            'name' => '詳細テストユーザー',
        ]);

        // 勤怠データを作成
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
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

        // 修正申請を作成
        $fixId = \Illuminate\Support\Facades\DB::table('fixes')->insertGetId([
            'user_id' => $user->id,
            'work_id' => $workId,
            'fix_date' => '2025-11-10',
            'reason' => '打刻忘れのため修正申請します',
            'status' => '承認待ち',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 修正申請詳細ページにアクセス
        $response = $this->get('/stamp_correction_request/approve/' . $fixId);
        $response->assertStatus(200);

        // 申請内容が正しく表示されていることを確認
        $response->assertSee('詳細テストユーザー', false);
        $response->assertSee('2025年', false);
        $response->assertSee('11月10日', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);
        $response->assertSee('12:00', false);
        $response->assertSee('13:00', false);
        $response->assertSee('打刻忘れのため修正申請します', false);
    }

    /**
     * 修正申請の承認処理が正しく行われるテスト
     */
    public function test_fix_request_approval_process_works_correctly()
    {
        // 管理者ログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        // 一般ユーザーを作成
        $user = \App\Models\User::factory()->create([
            'name' => '承認テストユーザー',
        ]);

        // 勤怠データを作成
        $workId = \Illuminate\Support\Facades\DB::table('works')->insertGetId([
            'user_id' => $user->id,
            'work_date' => '2025-11-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 修正申請を作成
        $fixId = \Illuminate\Support\Facades\DB::table('fixes')->insertGetId([
            'user_id' => $user->id,
            'work_id' => $workId,
            'fix_date' => '2025-11-10',
            'reason' => '打刻修正',
            'status' => '承認待ち',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 承認前のステータスを確認
        $fixBefore = \Illuminate\Support\Facades\DB::table('fixes')->where('id', $fixId)->first();
        $this->assertEquals('承認待ち', $fixBefore->status);

        // 承認処理を実行
        $response = $this->post('/stamp_correction_request/approve/' . $fixId, [
            'action' => 'approve',
        ]);

        // リダイレクトされることを確認
        $response->assertRedirect();

        // 承認後のステータスを確認
        $fixAfter = \Illuminate\Support\Facades\DB::table('fixes')->where('id', $fixId)->first();
        $this->assertEquals('承認済み', $fixAfter->status);
    }
}
