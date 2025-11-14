<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class GetDateTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されているテスト
     */
    public function test_datetime_is_displayed_in_correct_format()
    {
        // ユーザーを作成してログイン
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => now(), // メール認証済み
        ]);
        $this->actingAs($user);

        // 現在時刻を固定（テストの一貫性のため）
        $now = Carbon::create(2025, 11, 11, 14, 30, 0);
        Carbon::setTestNow($now);

        // 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // 期待される日付表示形式: 2025年11月11日(月)
        $expectedDate = $now->format('Y年n月j日');
        $expectedDayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek];
        $expectedDateDisplay = "{$expectedDate}({$expectedDayOfWeek})";

        // 期待される時刻表示形式: 14:30
        $expectedTime = $now->format('H:i');

        // 画面に正しい日時が表示されていることを確認
        $response->assertSee($expectedDateDisplay, false);
        $response->assertSee($expectedTime, false);

        // テスト用の時刻設定を解除
        Carbon::setTestNow();
    }
}
