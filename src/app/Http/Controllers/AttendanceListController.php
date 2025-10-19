<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceListController extends Controller
{
    public function attendanceList()
    {
        $user = Auth::user();
        $currentMonth = request('month', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($currentMonth);

        // 今月の勤怠データを取得
        $workRecords = DB::table('works')
            ->where('user_id', $user->id)
            ->whereYear('work_date', $targetDate->year)
            ->whereMonth('work_date', $targetDate->month)
            ->get()
            ->keyBy('work_date'); // 日付をキーにした配列に変換

        // 月の全日程を生成
        $attendances = collect();
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();

        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $work = $workRecords->get($dateStr);

            if ($work) {
                // 休憩時間を取得
                $breakTimes = DB::table('breakings')
                    ->where('work_id', $work->id)
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get()
                    ->map(function ($break) {
                        return [
                            'start' => $break->start_time,
                            'end' => $break->end_time
                        ];
                    });

                // 休憩時間の合計を計算
                $totalBreakMinutes = 0;
                foreach ($breakTimes as $break) {
                    $breakStart = Carbon::parse($break['start']);
                    $breakEnd = Carbon::parse($break['end']);
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
                    'id' => $work->id,
                    'date' => $dateStr,
                    'start_time' => $work->start_time,
                    'end_time' => $work->end_time,
                    'break_times' => $breakTimes,
                    'total_break_time' => $totalBreakTime,
                    'total_time' => $totalTime,
                ]);
            } else {
                // 勤怠記録がない日
                $attendances->push((object)[
                    'id' => null,
                    'date' => $dateStr,
                    'start_time' => null,
                    'end_time' => null,
                    'break_times' => collect(),
                    'total_break_time' => '',
                    'total_time' => null,
                ]);
            }
        }

        return view('list', compact('attendances', 'currentMonth'));
    }
}
