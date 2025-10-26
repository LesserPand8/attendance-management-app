<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAttendanceListController extends Controller
{
    public function attendanceList(Request $request)
    {
        $currentDay = $request->get('day', Carbon::now()->format('Y-m-d'));
        $targetDate = Carbon::parse($currentDay);

        // 全ユーザーを取得
        $users = DB::table('users')->get();

        $attendances = collect();

        foreach ($users as $user) {
            // 指定日の勤怠記録を取得
            $work = DB::table('works')
                ->where('user_id', $user->id)
                ->where('work_date', $targetDate->format('Y-m-d'))
                ->first();

            if ($work) {
                // 休憩時間を取得
                $breakTimes = DB::table('breakings')
                    ->where('work_id', $work->id)
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get();

                // 休憩時間の合計を計算
                $totalBreakMinutes = 0;
                foreach ($breakTimes as $break) {
                    $breakStart = Carbon::parse($break->start_time);
                    $breakEnd = Carbon::parse($break->end_time);
                    $totalBreakMinutes += $breakEnd->diffInMinutes($breakStart);
                }

                // 休憩時間合計を時:分形式にフォーマット
                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;
                $totalBreakTime = $totalBreakMinutes > 0 ? sprintf('%02d:%02d', $breakHours, $breakMinutes) : '';

                // 合計勤務時間を計算
                $totalTime = null;
                if ($work->start_time && $work->end_time) {
                    $start = Carbon::parse($work->start_time);
                    $end = Carbon::parse($work->end_time);
                    $totalMinutes = $end->diffInMinutes($start);

                    // 休憩時間を差し引く
                    $workMinutes = $totalMinutes - $totalBreakMinutes;
                    $hours = floor($workMinutes / 60);
                    $minutes = $workMinutes % 60;
                    $totalTime = sprintf('%02d:%02d', $hours, $minutes);
                }

                $attendances->push((object)[
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'work_id' => $work->id,
                    'date' => $targetDate->format('Y-m-d'),
                    'start_time' => $work->start_time,
                    'end_time' => $work->end_time,
                    'total_break_time' => $totalBreakTime,
                    'total_time' => $totalTime,
                    'status' => '出勤',
                ]);
            } else {
                // 勤怠記録がない場合
                $attendances->push((object)[
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'work_id' => null,
                    'date' => $targetDate->format('Y-m-d'),
                    'start_time' => null,
                    'end_time' => null,
                    'total_break_time' => '',
                    'total_time' => null,
                    'status' => '未出勤',
                ]);
            }
        }

        return view('admin.list', [
            'attendances' => $attendances,
            'currentDay' => $currentDay,
        ]);
    }
}
