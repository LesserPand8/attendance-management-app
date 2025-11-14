@extends('auth.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/auth/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <div class="login-form__heading">
        <h2>管理者ログイン</h2>
    </div>
    <form class="form" action="/admin/login" method="post">
        @csrf
        <div class="form__group">
            <div class="form__group-title">
                メールアドレス
            </div>
            <input class="form__group-content" type="email" name="email" value="{{ old('email') }}" />
            <div class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                パスワード
            </div>
            <input class="form__group-content" type="password" name="password" value="{{ old('password') }}" />
            <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">ログインする</button>
        </div>
    </form>
</div>
@endsection