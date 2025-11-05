@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/application-approval.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="detail">
        <h2 class="detail-title">勤怠詳細</h2>
        <form class="attendance-detail-form" action="/stamp_correction_request/approve/{{ $attendance_correct_request_id }}" method="post">
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
                        <div class="time-container">
                            <div class="time-display_start">{{ $attendanceData->start_time ? \Carbon\Carbon::parse($attendanceData->start_time)->format('H:i') : '' }}</div>
                            ～
                            <div class="time-display_end">{{ $attendanceData->end_time ? \Carbon\Carbon::parse($attendanceData->end_time)->format('H:i') : '' }}</div>
                        </div>
                    </td>
                </tr>
                @foreach($attendanceData->break_times as $break)
                <tr class="attendance-detail-row">
                    <th>休憩{{ $break['number'] > 1 ? $break['number'] : '' }}</th>
                    <td>
                        @if($pendingFix && $pendingFix->reason)
                        <div class="time-container">
                            <div class="time-display_start">{{ \Carbon\Carbon::parse($break['start'])->format('H:i') }}</div>
                            ～
                            <div class="time-display_end">{{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}</div>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr class="attendance-detail-row">
                    <th>休憩{{ count($attendanceData->break_times) + 1 }}</th>
                    <td>
                    </td>
                </tr>
                <tr class="comment-row">
                    <th>備考</th>
                    <td>
                        @if($pendingFix && $pendingFix->reason)
                        <div class="pending-reason-container">
                            <div class="pending-reason">{{ $pendingFix->reason }}</div>
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
            @if($hasPendingFix)
            <button type="submit" class="submit-button">承認</button>
            @else
            <div class="approved-button">承認済み</div>
            @endif
        </form>
    </div>
</div>
@endsection