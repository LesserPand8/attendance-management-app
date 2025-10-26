<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


class AdminLoginController extends Controller
{
    // ログイン画面呼び出し
    public function showLoginPage(): View
    {
        return view('admin.auth.login');
    }

    // ログイン実行
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (Auth::guard('admin')->attempt($credentials)) {
            return redirect()->route('admin.list')->with([
                'login_msg' => 'ログインしました。',
            ]);
        }

        return back()->withErrors([
            'email' => ['ログイン情報が登録されていません'],
        ])->withInput($request->only('email'));
    }

    // ログアウト実行
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('logout_msg', 'ログアウトしました。');
    }
}
