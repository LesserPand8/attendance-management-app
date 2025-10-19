@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="detail">
        <h2 class="detail-title">勤怠詳細</h2>
        <form class="attendance-detail-form" action="post">
            <table class="attendance-detail-table">
                <tr class="attendance-detail-row">
                    <th>名前</th>
                    <td>{{ $attendanceData->user_name }}</td>
                </tr>
                <tr class="attendance-detail-row">
                    <th>日付</th>
                    <td>{{ $attendanceData->work_date }}</td>
                </tr>
                <tr class="attendance-detail-row">
                    <th>出勤・退勤</th>
                    <td>
                        <input type="time" name="start_time" value="{{ $attendanceData->start_time ? \Carbon\Carbon::parse($attendanceData->start_time)->format('H:i') : '' }}">
                        ～
                        <input type="time" name="end_time" value="{{ $attendanceData->end_time ? \Carbon\Carbon::parse($attendanceData->end_time)->format('H:i') : '' }}">
                    </td>
                </tr>
                @if($attendanceData->break_times && count($attendanceData->break_times) > 0)
                @foreach($attendanceData->break_times as $break)
                <tr class="attendance-detail-row">
                    <th>休憩{{ $break['number'] > 1 ? $break['number'] : '' }}</th>
                    <td>
                        <input type="time" name="break_start_{{ $break['number'] }}" value="{{ \Carbon\Carbon::parse($break['start'])->format('H:i') }}">
                        ～
                        <input type="time" name="break_end_{{ $break['number'] }}" value="{{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}">
                    </td>
                </tr>
                @endforeach
                <!-- 新しい休憩時間入力フィールド -->
                <tr class="attendance-detail-row">
                    <th>休憩{{ count($attendanceData->break_times) + 1 }}</th>
                    <td>
                        <input type="time" name="break_start_{{ count($attendanceData->break_times) + 1 }}">
                        ～
                        <input type="time" name="break_end_{{ count($attendanceData->break_times) + 1 }}">
                    </td>
                </tr>
                @else
                <!-- 休憩記録がない場合は最初の入力フィールドを表示 -->
                <tr class="attendance-detail-row">
                    <th>休憩</th>
                    <td>
                        <input type="time" name="break_start_1">
                        ～
                        <input type="time" name="break_end_1">
                    </td>
                </tr>
                @endif
                <tr class="comment-row">
                    <th>備考</th>
                    <td>
                        <input type="text">
                    </td>
                </tr>
            </table>
            <button type="submit" class="submit-button">修正</button>
        </form>
    </div>
</div>
@endsection