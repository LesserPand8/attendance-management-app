@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="list">
        <h2 class="list-title">{{ \Carbon\Carbon::parse($currentDay ?? now())->format('Y年n月d日') }}の勤怠</h2>
        <div class="list-day">
            <div class="day-navigation">
                <a href="/admin/attendance/list?day={{ \Carbon\Carbon::parse($currentDay ?? now())->subDay()->format('Y-m-d') }}" class="nav-button">
                    ←前日</a>
                <span class="current-day">
                    <img class="calendar-icon" src="{{ asset('storage/images/calendar.png') }}">
                    {{ \Carbon\Carbon::parse($currentDay ?? now())->format('Y/n/d') }}
                </span>
                <a href="/admin/attendance/list?day={{ \Carbon\Carbon::parse($currentDay ?? now())->addDay()->format('Y-m-d') }}" class="nav-button">翌日→</a>
            </div>
        </div>
        <table class="attendance-table">
            <tr class="table-header">
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach($attendances ?? [] as $attendance)
            <tr class="table-row">
                <td>{{ $attendance->user_name }}</td>
                <td>{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}</td>
                <td>{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}</td>
                <td>
                    {{ $attendance->total_break_time ?? '' }}
                </td>
                <td>
                    {{ $attendance->total_time ?? '' }}
                </td>
                <td>
                    <a class="detail-link" href="/admin/attendance/{{ $attendance->work_id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>

    </div>
</div>
@endsection