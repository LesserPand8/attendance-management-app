<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


class AdminApprovalController extends Controller
{
    public function approvalList($attendance_correct_request_id)
    {
        // ここに勤怠修正申請の詳細表示ロジックを実装します
        return view('admin.approval-detail', ['attendance_correct_request_id' => $attendance_correct_request_id]);
    }
}
