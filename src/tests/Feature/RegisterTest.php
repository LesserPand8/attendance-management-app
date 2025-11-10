<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前未入力時のバリデーションメッセージ表示テスト
     */
    public function test_register_validation_error_when_name_is_empty()
    {
        // 会員登録ページのURL（Fortifyの場合は /register）
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();

        // バリデーションメッセージの内容を確認
        $errors = session('errors');
        $this->assertTrue($errors->has('name'));
        $this->assertEquals('お名前を入力してください', $errors->first('name'));
    }

    /**
     * メールアドレス未入力時のバリデーションメッセージ表示テスト
     */
    public function test_register_validation_error_when_email_is_empty()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
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
    public function test_register_validation_error_when_password_is_empty()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();

        $errors = session('errors');
        $this->assertTrue($errors->has('password'));
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    /**
     * パスワードが7文字以下の場合のバリデーションメッセージ表示テスト
     */
    public function test_register_validation_error_when_password_is_too_short()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123', // 7文字
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();

        $errors = session('errors');
        $this->assertTrue($errors->has('password'));
        $this->assertEquals('パスワードは8文字以上で入力してください', $errors->first('password'));
    }

    /**
     * パスワードと確認用パスワードが一致しない場合のバリデーションメッセージ表示テスト
     */
    public function test_register_validation_error_when_password_confirmation_not_match()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();

        $errors = session('errors');
        $this->assertTrue($errors->has('password'));
        $this->assertEquals('パスワードと一致しません', $errors->first('password'));
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_register_success_and_data_saved_to_database()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // データベースに正しく保存されていることを確認
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'newuser@example.com',
        ]);

        // パスワードがハッシュ化されて保存されていることを確認
        $user = \App\Models\User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $user->password));
    }
}
