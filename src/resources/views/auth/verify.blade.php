@extends('auth.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify.css') }}">
@endsection

@section('content')
<div class="verify-container">
    <div class="verify-title">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </div>
    <a href="http://localhost:8025/" target="_blank" class="verify-btn">認証はこちらから</a>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="verify-link">認証メールを再送する</button>
    </form>
</div>
@endsection