@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="detail">
        <h2 class="detail-title">勤怠詳細</h2>

        <!-- エラーメッセージの表示 -->
        @if ($errors->any())
        <div class="error-messages">
            @foreach ($errors->all() as $error)
            <div class="error-message">{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form class="attendance-detail-form" action="/attendance/detail/{{ $attendanceData->id }}" method="post">
            @csrf
            <table class="attendance-detail-table">
                <tr class="attendance-detail-row">
                    <th>名前</th>
                    <td class="name">{{ $attendanceData->user_name }}</td>
                </tr>
                <tr class="attendance-detail-row">
                    <th>日付</th>
                    <td>
                        <div class="year">
                            {{ \Carbon\Carbon::parse($attendanceData->work_date)->format('Y年') }}
                        </div>
                        <div class="month-day">
                            {{ \Carbon\Carbon::parse($attendanceData->work_date)->format('n月j日') }}
                        </div>
                    </td>
                </tr>
                <tr class="attendance-detail-row">
                    <th>出勤・退勤</th>
                    <td>
                        @if($hasPendingFix)
                        <div class="time-container">
                            <div class="time-display_start">{{ $attendanceData->start_time ? \Carbon\Carbon::parse($attendanceData->start_time)->format('H:i') : '' }}</div>
                            ～
                            <div class="time-display_end">{{ $attendanceData->end_time ? \Carbon\Carbon::parse($attendanceData->end_time)->format('H:i') : '' }}</div>
                        </div>
                        @else
                        <input class="start-time-input" type="text" name="start_time" value="{{ $attendanceData->start_time ? \Carbon\Carbon::parse($attendanceData->start_time)->format('H:i') : '' }}" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="end_time" value="{{ $attendanceData->end_time ? \Carbon\Carbon::parse($attendanceData->end_time)->format('H:i') : '' }}" placeholder="">
                        @endif
                    </td>
                </tr>
                @if($attendanceData->break_times && count($attendanceData->break_times) > 0)
                @foreach($attendanceData->break_times as $break)
                <tr class="attendance-detail-row">
                    <th>休憩{{ $break['number'] > 1 ? $break['number'] : '' }}</th>
                    <td>
                        @if($hasPendingFix)
                        <div class="time-container">
                            <div class="time-display_start">{{ \Carbon\Carbon::parse($break['start'])->format('H:i') }}</div>
                            ～
                            <div class="time-display_end">{{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}</div>
                        </div>
                        @else
                        <input class="start-time-input" type="text" name="break_start_{{ $break['number'] }}" value="{{ \Carbon\Carbon::parse($break['start'])->format('H:i') }}" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="break_end_{{ $break['number'] }}" value="{{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}" placeholder="">
                        @endif
                    </td>
                </tr>
                @endforeach
                @if(!$hasPendingFix)
                <!-- 新しい休憩時間入力フィールド（承認待ちでない場合のみ表示） -->
                <tr class="attendance-detail-row">
                    <th>休憩{{ count($attendanceData->break_times) + 1 }}</th>
                    <td>
                        <input class="start-time-input" type="text" name="break_start_{{ count($attendanceData->break_times) + 1 }}" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="break_end_{{ count($attendanceData->break_times) + 1 }}" placeholder="">
                    </td>
                </tr>
                @endif
                @else
                @if(!$hasPendingFix)
                <!-- 休憩記録がない場合は最初の入力フィールドを表示（承認待ちでない場合のみ） -->
                <tr class="attendance-detail-row">
                    <th>休憩</th>
                    <td>
                        <input class="start-time-input" type="text" name="break_start_1" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="break_end_1" placeholder="">
                    </td>
                </tr>
                @endif
                @endif
                <tr class="comment-row">
                    <th>備考</th>
                    <td>
                        @if($hasPendingFix)
                        <div class="pending-reason-container">
                            <div class="pending-reason">{{ $pendingFix->reason }}</div>
                        </div>
                        @else
                        <input class="comment-input" type="text" name="reason">
                        @endif
                    </td>
                </tr>
            </table>
            @if(!$hasPendingFix)
            <button type="submit" class="submit-button">修正</button>
            @else
            <div class="pending-message">*承認待ちのため修正できません。</div>
            @endif
        </form>
    </div>
</div>
@endsection