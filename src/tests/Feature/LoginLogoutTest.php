<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    /**
     * メールアドレス未入力時のバリデーションメッセージ表示テスト
     */
    public function test_login_validation_error_when_email_is_empty()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();

        $errors = session('errors');
        $this->assertTrue($errors->has('email'));
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    /**
     * パスワード未入力時のバリデーションメッセージ表示テスト
     */
    public function test_login_validation_error_when_password_is_empty()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();

        $errors = session('errors');
        $this->assertTrue($errors->has('password'));
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    /**
     * 入力情報が間違っている場合のバリデーションメッセージ表示テスト
     */
    public function test_login_validation_error_when_credentials_are_invalid()
    {
        $response = $this->post('/login', [
            'email' => 'notfound@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();

        $errors = session('errors');
        $this->assertTrue($errors->has('email'));
        $this->assertEquals('ログイン情報が登録されていません', $errors->first('email'));
    }

    /**
     * 正しい情報が入力された場合、ログイン処理が実行されるテスト
     */
    public function test_login_success_with_valid_credentials()
    {
        // 事前にユーザーを作成
        $user = \App\Models\User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'loginuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/attendance'); // ログイン後のリダイレクト先（必要に応じて修正）
        $this->assertAuthenticatedAs($user);
    }

    /**
     * ログアウトができるテスト
     */
    public function test_logout_success()
    {
        // 事前にユーザーを作成しログイン
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create([
            'email' => 'logoutuser@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/'); // ログアウト後のリダイレクト先（必要に応じて修正）
        $this->assertGuest();
    }
}
