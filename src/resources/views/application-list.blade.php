@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/application-list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="application-list">
        <h2 class="application-list-title">申請一覧</h2>

        <div class="tab-menu">
            <a class="tab-pending-approval {{ ($tab ?? 'pending-approval') === 'pending-approval' ? 'active' : '' }}" href="/stamp_correction_request/list?tab=pending-approval">承認待ち</a>
            <a class="tab-approved {{ ($tab ?? '') === 'approved' ? 'active' : '' }}" href="/stamp_correction_request/list?tab=approved">承認済み</a>
        </div>
        <div class="application-list-content">
            <table class="application-table">
                <tr class="application-table-row">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @foreach ($applications as $application)
                <tr class="application-table-row">
                    <td>{{ $application->status }}</td>
                    <td>{{ $application->user_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($application->date)->format('Y/m/d') }}</td>
                    <td>{{ $application->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($application->fix_date)->format('Y/m/d') }}</td>
                    <td><a class="detail-link" href="/attendance/detail/{{ $application->id }}">詳細</a></td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    @endsection