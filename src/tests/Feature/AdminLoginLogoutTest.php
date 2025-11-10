<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminLoginLogoutTest extends TestCase
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
        $response = $this->post('/admin/login', [
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
        $response = $this->post('/admin/login', [
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
        $response = $this->post('/admin/login', [
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
        // 事前に管理者を作成
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.attendance.list'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * ログアウトができるテスト
     */
    public function test_logout_success()
    {
        // 事前に管理者を作成しログイン
        /** @var \App\Models\Admin $admin */
        $admin = \App\Models\Admin::factory()->create([
            'email' => 'logoutadmin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($admin, 'admin');

        $response = $this->post('/admin/logout');

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }
}
