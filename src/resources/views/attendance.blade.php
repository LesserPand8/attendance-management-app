@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="content">
    <form class="attendance-form" action="/attendance" method="POST">
        @csrf
        <div class="attendance-condition">
            <div class="attendance-condition__tag active">{{ $workStatus }}</div>
        </div>
        <div class="date">
            {{ \Carbon\Carbon::now()->format('Y年n月j日') }}({{ ['日', '月', '火', '水', '木', '金', '土'][\Carbon\Carbon::now()->dayOfWeek] }})
            <input type="hidden" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
        </div>
        <div class="time">
            {{ \Carbon\Carbon::now()->format('H:i') }}
            <input type="hidden" name="time" value="{{ \Carbon\Carbon::now()->format('H:i') }}" class="time-input">
        </div>
        <div class="button-box">
            @if($buttonStatus === 'show_clockin')
            <button type="submit" name="action" value="clockin" class="submit-button">出勤</button>
            @elseif($buttonStatus === 'show_clockout_breakin')
            <button type="submit" name="action" value="clockout" class="submit-button">退勤</button>
            <button type="submit" name="action" value="breakin" class="break-button">休憩入</button>
            @elseif($buttonStatus === 'show_breakout')
            <button type="submit" name="action" value="breakout" class="break-button">休憩戻</button>
            @elseif($buttonStatus === 'show_thanks')
            <div class="thanks-comment">お疲れ様でした。</div>
            @endif
        </div>
    </form>
</div>
@endsection