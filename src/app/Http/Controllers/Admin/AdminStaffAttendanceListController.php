<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStaffAttendanceListController extends Controller
{
    public function staffAttendanceList($id)
    {
        // スタッフ情報を取得
        $staff = DB::table('users')->where('id', $id)->first();

        if (!$staff) {
            abort(404, 'スタッフが見つかりません');
        }
        $currentMonth = request('month', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($currentMonth);

        // 今月の勤怠データを取得
        $workRecords = DB::table('works')
            ->where('user_id', $id)
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
                    'id' => 'new_' . $dateStr . '_' . $id, // 日付とスタッフIDを含む特別な識別子
                    'date' => $dateStr,
                    'start_time' => null,
                    'end_time' => null,
                    'break_times' => collect(),
                    'total_break_time' => '',
                    'total_time' => null,
                ]);
            }
        }

        return view('admin.staff-attendance-list', compact('attendances', 'currentMonth', 'staff'));
    }

    public function exportCsv($id)
    {
        // スタッフ情報を取得
        $staff = DB::table('users')->where('id', $id)->first();

        if (!$staff) {
            abort(404, 'スタッフが見つかりません');
        }

        $currentMonth = request('month', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($currentMonth);

        // 今月の勤怠データを取得（staffAttendanceListメソッドと同じロジック）
        $workRecords = DB::table('works')
            ->where('user_id', $id)
            ->whereYear('work_date', $targetDate->year)
            ->whereMonth('work_date', $targetDate->month)
            ->get()
            ->keyBy('work_date');

        // CSVデータを生成
        $csvData = [];
        $csvData[] = ['日付', '曜日', '出勤時間', '退勤時間', '休憩時間', '合計勤務時間']; // ヘッダー

        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();

        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $work = $workRecords->get($dateStr);

            $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

            if ($work) {
                // 休憩時間の合計を計算
                $breakTimes = DB::table('breakings')
                    ->where('work_id', $work->id)
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get();

                $totalBreakMinutes = 0;
                foreach ($breakTimes as $break) {
                    $breakStart = Carbon::parse($break->start_time);
                    $breakEnd = Carbon::parse($break->end_time);
                    $totalBreakMinutes += $breakEnd->diffInMinutes($breakStart);
                }

                $totalBreakTime = $totalBreakMinutes > 0 ? sprintf('%02d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60) : '';

                // 合計勤務時間を計算
                $totalTime = '';
                if ($work->start_time && $work->end_time) {
                    $start = Carbon::parse($work->start_time);
                    $end = Carbon::parse($work->end_time);
                    $workMinutes = $end->diffInMinutes($start) - $totalBreakMinutes;
                    $totalTime = sprintf('%02d:%02d', floor($workMinutes / 60), $workMinutes % 60);
                }

                $csvData[] = [
                    $date->format('m/d'),
                    $dayOfWeek,
                    $work->start_time ? Carbon::parse($work->start_time)->format('H:i') : '',
                    $work->end_time ? Carbon::parse($work->end_time)->format('H:i') : '',
                    $totalBreakTime,
                    $totalTime
                ];
            } else {
                // 勤怠記録がない日
                $csvData[] = [
                    $date->format('m/d'),
                    $dayOfWeek,
                    '',
                    '',
                    '',
                    ''
                ];
            }
        }

        // CSVファイル名を生成
        $filename = $staff->name . '_' . $targetDate->format('Y年m月') . '_勤怠一覧.csv';

        // CSVレスポンスを生成
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            // BOM付きUTF-8で出力（Excelで文字化けしないように）
            fwrite($file, "\xEF\xBB\xBF");

            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
