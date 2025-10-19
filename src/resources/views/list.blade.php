@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="list">
        <h2 class="list-title">勤怠一覧</h2>
        <div class="list-month">
            <div class="month-navigation">
                <a href="/attendance/list?month={{ \Carbon\Carbon::parse($currentMonth)->subMonth()->format('Y-m') }}" class="nav-button">
                    ←前月</a>
                <span class="current-month">
                    <img src="{{ asset('storage/images/calendar.png') }}">
                    {{ \Carbon\Carbon::parse($currentMonth)->format('Y/n') }}
                </span>
                <a href="/attendance/list?month={{ \Carbon\Carbon::parse($currentMonth)->addMonth()->format('Y-m') }}" class="nav-button">翌月→</a>
            </div>
        </div>
        <table class="attendance-table">
            <tr class="table-header">
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach($attendances as $attendance)
            <tr class="table-row">
                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('m/d') }}({{ ['日', '月', '火', '水', '木', '金', '土'][\Carbon\Carbon::parse($attendance->date)->dayOfWeek] }})</td>
                <td>{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}</td>
                <td>{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}</td>
                <td>
                    @if($attendance->break_times && count($attendance->break_times) > 0)
                    @foreach($attendance->break_times as $break)
                    {{ \Carbon\Carbon::parse($break['start'])->format('H:i') }} ~ {{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}<br>
                    @endforeach
                    @else
                    {{ '' }}
                    @endif
                </td>
                <td>
                    {{ $attendance->total_time ?? '' }}
                </td>
                <td>
                    <!-- <button class="btn btn-primary">詳細</button> -->
                    <a class="detail-link" href="/attendance/detail/{{ $attendance->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>

</div>
@endsection