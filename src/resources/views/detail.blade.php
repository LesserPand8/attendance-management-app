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
                        <input class="start-time-input" type="text" name="start_time" value="{{ $attendanceData->start_time ? \Carbon\Carbon::parse($attendanceData->start_time)->format('H:i') : '' }}" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="end_time" value="{{ $attendanceData->end_time ? \Carbon\Carbon::parse($attendanceData->end_time)->format('H:i') : '' }}" placeholder="">
                    </td>
                </tr>
                @if($attendanceData->break_times && count($attendanceData->break_times) > 0)
                @foreach($attendanceData->break_times as $break)
                <tr class="attendance-detail-row">
                    <th>休憩{{ $break['number'] > 1 ? $break['number'] : '' }}</th>
                    <td>
                        <input class="start-time-input" type="text" name="break_start_{{ $break['number'] }}" value="{{ \Carbon\Carbon::parse($break['start'])->format('H:i') }}" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="break_end_{{ $break['number'] }}" value="{{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}" placeholder="">
                    </td>
                </tr>
                @endforeach
                <!-- 新しい休憩時間入力フィールド -->
                <tr class="attendance-detail-row">
                    <th>休憩{{ count($attendanceData->break_times) + 1 }}</th>
                    <td>
                        <input class="start-time-input" type="text" name="break_start_{{ count($attendanceData->break_times) + 1 }}" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="break_end_{{ count($attendanceData->break_times) + 1 }}" placeholder="">
                    </td>
                </tr>
                @else
                <!-- 休憩記録がない場合は最初の入力フィールドを表示 -->
                <tr class="attendance-detail-row">
                    <th>休憩</th>
                    <td>
                        <input class="start-time-input" type="text" name="break_start_1" placeholder="">
                        ～
                        <input class="end-time-input" type="text" name="break_end_1" placeholder="">
                    </td>
                </tr>
                @endif
                <tr class="comment-row">
                    <th>備考</th>
                    <td>
                        <input class="comment-input" type="text" name="reason">
                    </td>
                </tr>
            </table>
            <button type="submit" class="submit-button">修正</button>
        </form>
    </div>
</div>
@endsection