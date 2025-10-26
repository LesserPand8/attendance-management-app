@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-list.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="staff-list">
        <h2 class="staff-list-title">スタッフ一覧</h2>
        <table class="staff-table">
            <tr class="staff-table-row">
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
            @foreach($staffs as $staff)
            <tr class="staff-table-row">
                <td>{{ $staff->name }}</td>
                <td>{{ $staff->email }}</td>
                <td>
                    <a class="detail-link" href="/admin/attendance/staff/{{ $staff->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection