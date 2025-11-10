<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmailAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後、認証メールが送信されるテスト
     */
    public function test_verification_email_is_sent_after_registration()
    {
        \Illuminate\Support\Facades\Notification::fake();

        // 1. 会員登録
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $userData);
        $response->assertRedirect('/email/verify');

        // 2. 認証通知が送信されたことを検証
        $user = \App\Models\User::where('email', $userData['email'])->first();
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移するテスト
     */
    public function test_click_verify_button_redirects_to_verification_site()
    {
        // 1. 認証画面を表示
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user instanceof \Illuminate\Database\Eloquent\Model ? $user : $user->first());
        $response = $this->get('/email/verify');
        $response->assertStatus(200);

        // 2. 「認証はこちらから」ボタンのリンクを検証
        $response->assertSee('認証はこちらから');
        $this->assertStringContainsString('href="http://localhost:8025/"', $response->getContent());
    }

    /**
     * メール認証サイトで認証を完了するとプロフィール設定画面に遷移するテスト
     */
    public function test_complete_email_verification_redirects_to_profile_setting()
    {
        // 未認証ユーザー作成
        $user = \App\Models\User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user instanceof \Illuminate\Database\Eloquent\Model ? $user : $user->first());

        // メール認証完了リクエスト（idとhashは本来通知メールから取得だが、テスト用に生成）
        $id = $user->id;
        $hash = sha1($user->getEmailForVerification());
        $url = \Illuminate\Support\Facades\URL::signedRoute('verification.verify', ['id' => $id, 'hash' => $hash]);
        $response = $this->get($url);

        // プロフィール設定画面へリダイレクト
        $response->assertRedirect('/mypage/profile');
    }
}
