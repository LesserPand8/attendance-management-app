<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceDetailController extends Controller
{
    public function attendanceDetail($id)
    {
        // 勤怠記録を取得
        $attendance = DB::table('works')
            ->join('users', 'works.user_id', '=', 'users.id')
            ->where('works.id', $id)
            ->select('works.*', 'users.name as user_name')
            ->first();

        if (!$attendance) {
            return redirect('/attendance/list')->with('error', '勤怠記録が見つかりません');
        }

        // 休憩時間を取得
        $breakTimes = DB::table('breakings')
            ->where('work_id', $id)
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
            'id' => $attendance->id,
            'user_name' => $attendance->user_name,
            'work_date' => $attendance->work_date,
            'start_time' => $attendance->start_time,
            'end_time' => $attendance->end_time,
            'break_times' => $breakTimes,
            'total_break_time' => $totalBreakTime
        ];

        return view('detail', compact('attendanceData'));
    }
}
