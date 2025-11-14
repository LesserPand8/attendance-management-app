<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header-utilities">
                <a class="header__logo" href="/attendance">
                    <img src="{{ asset('storage/images/logo.svg') }}" alt="coachtech">
                </a>
                <div class="header-nav">
                    <div class="header-nav__item">
                        <a class="header-nav__link" href="/attendance">勤怠</a>
                    </div>
                    <div class="header-nav__item">
                        <a class="header-nav__link" href="/attendance/list">勤怠一覧</a>
                    </div>
                    <div class="header-nav__item">
                        <a class="header-nav__link" href="/stamp_correction_request/list">申請</a>
                    </div>
                    @if (!Auth::check())
                    <div class="header-nav__item">
                        <a class="header-nav__button-login" href="/login">ログイン</a>
                    </div>
                    @endif
                    @if (Auth::check())
                    <div class="header-nav__item">
                        <form class="form" action="/logout" method="post">
                            @csrf
                            <button class="header-nav__button-logout">ログアウト</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>