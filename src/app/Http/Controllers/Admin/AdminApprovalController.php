<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminApprovalController extends Controller
{
    public function approvalDetail($attendance_correct_request_id)
    {
        // 修正申請の詳細を取得
        $fix = DB::table('fixes')
            ->join('works', 'fixes.work_id', '=', 'works.id')
            ->join('users', 'works.user_id', '=', 'users.id')
            ->where('fixes.id', $attendance_correct_request_id)
            ->select(
                'fixes.*',
                'works.*',
                'users.name as user_name',
                'fixes.id as fix_id',
                'works.id as work_id'
            )
            ->first();

        if (!$fix) {
            return redirect('/admin/stamp_correction_request/list')->with('error', '申請が見つかりません');
        }

        // 休憩時間を取得
        $breakTimes = DB::table('breakings')
            ->where('work_id', $fix->work_id)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('start_time')
            ->get()
            ->map(function ($break, $index) {
                return [
                    'number' => $index + 1,
                    'start' => $break->start_time,
                    'end' => $break->end_time,
                    'duration' => Carbon::parse($break->end_time)->diffInMinutes(Carbon::parse($break->start_time))
                ];
            });

        // 休憩時間の合計を計算
        $totalBreakMinutes = $breakTimes->sum('duration');
        $breakHours = floor($totalBreakMinutes / 60);
        $breakMinutes = $totalBreakMinutes % 60;
        $totalBreakTime = $totalBreakMinutes > 0 ? sprintf('%02d:%02d', $breakHours, $breakMinutes) : '00:00';

        $attendanceData = (object)[
            'id' => $fix->work_id,
            'user_name' => $fix->user_name,
            'work_date' => $fix->work_date,
            'start_time' => $fix->start_time,
            'end_time' => $fix->end_time,
            'break_times' => $breakTimes,
            'total_break_time' => $totalBreakTime
        ];

        // 承認待ちかどうか
        $hasPendingFix = $fix->status === '承認待ち';
        $pendingFix = $hasPendingFix ? $fix : null;

        return view('admin.application-approval', compact('attendance_correct_request_id', 'attendanceData', 'hasPendingFix', 'pendingFix'));
    }
}
