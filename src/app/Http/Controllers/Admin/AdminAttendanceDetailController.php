<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceDetailController extends Controller
{
    public function attendanceDetail($id)
    {
        $user = auth()->user();

        // 新しい記録の場合（new_YYYY-MM-DD形式）
        if (str_starts_with($id, 'new_')) {
            $date = str_replace('new_', '', $id);

            // 日付形式の検証
            try {
                $targetDate = Carbon::parse($date);
            } catch (\Exception $e) {
                return redirect('/attendance/list')->with('error', '無効な日付です');
            }

            // 該当日の既存記録を確認
            $existingWork = DB::table('works')
                ->where('user_id', $user->id)
                ->where('work_date', $targetDate->format('Y-m-d'))
                ->first();

            if ($existingWork) {
                // 既存記録がある場合は通常の詳細ページにリダイレクト
                return redirect('/attendance/detail/' . $existingWork->id);
            }

            // 新しい記録を作成
            $workId = DB::table('works')->insertGetId([
                'user_id' => $user->id,
                'work_date' => $targetDate->format('Y-m-d'),
                'start_time' => null,
                'end_time' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 作成した記録を取得
            $attendance = DB::table('works')
                ->join('users', 'works.user_id', '=', 'users.id')
                ->where('works.id', $workId)
                ->select('works.*', 'users.name as user_name')
                ->first();
        } else {
            // 既存記録の場合
            $attendance = DB::table('works')
                ->join('users', 'works.user_id', '=', 'users.id')
                ->where('works.id', $id)
                ->select('works.*', 'users.name as user_name')
                ->first();

            if (!$attendance) {
                return redirect('/attendance/list')->with('error', '勤怠記録が見つかりません');
            }
        }

        // 休憩時間を取得（適切にwork_idを使用）
        $workId = (str_starts_with($id, 'new_') ? $workId : $id);
        $breakTimes = DB::table('breakings')
            ->where('work_id', $workId)
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

        // 承認待ちの修正申請があるかチェック
        $pendingFix = DB::table('fixes')
            ->where('work_id', $workId)
            ->where('status', '承認待ち')
            ->first();

        $hasPendingFix = !is_null($pendingFix);

        $attendanceData = (object)[
            'id' => $attendance->id,
            'user_name' => $attendance->user_name,
            'work_date' => $attendance->work_date,
            'start_time' => $attendance->start_time,
            'end_time' => $attendance->end_time,
            'break_times' => $breakTimes,
            'total_break_time' => $totalBreakTime
        ];

        return view('admin.attendance_detail', compact('attendanceData', 'hasPendingFix', 'pendingFix'));
    }

    public function updateAttendanceDetail(Request $request, $id)
    {
        // バリデーション
        $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string',
        ], [
            'start_time.date_format' => '出勤時間は○○:○○の形式で入力してください',
            'end_time.date_format' => '退勤時間は○○:○○の形式で入力してください',
            'reason.required' => '備考を記入してください',
        ]);

        // 勤怠記録を取得
        $attendance = DB::table('works')->where('id', $id)->first();

        if (!$attendance) {
            return redirect('/admin/attendance/list')->with('error', '勤怠記録が見つかりません');
        }

        DB::beginTransaction();
        try {
            // 出勤・退勤時間の修正データを準備
            $workUpdates = [];
            if ($request->filled('start_time')) {
                $workUpdates['start_time'] = Carbon::parse($attendance->work_date . ' ' . $request->start_time)->toDateTimeString();
            }
            if ($request->filled('end_time')) {
                $workUpdates['end_time'] = Carbon::parse($attendance->work_date . ' ' . $request->end_time)->toDateTimeString();
            }

            // 出勤・退勤時間を更新
            if (!empty($workUpdates)) {
                $workUpdates['updated_at'] = now();
                DB::table('works')->where('id', $id)->update($workUpdates);
            }

            // 休憩時間の処理
            $this->updateBreakTimes($request, $id, $attendance->work_date);

            // 管理者による直接修正は承認不要で即座に反映
            DB::commit();
            return redirect('/admin/attendance/' . $id)->with('success', '勤怠記録を修正しました。');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect('/admin/attendance/' . $id)->with('error', '勤怠記録の修正に失敗しました。');
        }
    }

    private function updateBreakTimes(Request $request, $workId, $workDate)
    {
        // 既存の休憩時間を更新
        $existingBreaks = DB::table('breakings')->where('work_id', $workId)->get();

        foreach ($existingBreaks as $break) {
            $startInputName = 'break_start_' . $break->id;
            $endInputName = 'break_end_' . $break->id;

            $breakStart = $request->input($startInputName);
            $breakEnd = $request->input($endInputName);

            if ($breakStart && $breakEnd) {
                DB::table('breakings')->where('id', $break->id)->update([
                    'start_time' => Carbon::parse($workDate . ' ' . $breakStart)->toDateTimeString(),
                    'end_time' => Carbon::parse($workDate . ' ' . $breakEnd)->toDateTimeString(),
                    'updated_at' => now(),
                ]);
            } elseif (!$breakStart && !$breakEnd) {
                // 両方が空の場合は削除
                DB::table('breakings')->where('id', $break->id)->delete();
            }
        }

        // 新しい休憩時間を追加
        for ($i = 1; $i <= 10; $i++) {
            $breakStart = $request->input("break_start_{$i}");
            $breakEnd = $request->input("break_end_{$i}");

            if ($breakStart && $breakEnd) {
                DB::table('breakings')->insert([
                    'work_id' => $workId,
                    'start_time' => Carbon::parse($workDate . ' ' . $breakStart)->toDateTimeString(),
                    'end_time' => Carbon::parse($workDate . ' ' . $breakEnd)->toDateTimeString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
