<?php

namespace App\Http\Controllers;

use App\Http\Requests\FixesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function updateAttendanceDetail(FixesRequest $request, $id)
    {
        // 勤怠記録を取得
        $attendance = DB::table('works')->where('id', $id)->first();

        if (!$attendance) {
            return redirect('/attendance/list')->with('error', '勤怠記録が見つかりません');
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

            // 出勤・退勤時間を更新（修正がある場合のみ）
            if (!empty($workUpdates)) {
                DB::table('works')->where('id', $id)->update($workUpdates);
            }

            // 既存の休憩時間の更新または削除
            $breaks = DB::table('breakings')->where('work_id', $id)->get();

            foreach ($breaks as $break) {
                $startInputName = 'break_start_' . $break->id;
                $endInputName = 'break_end_' . $break->id;

                $startTime = $request->input($startInputName);
                $endTime = $request->input($endInputName);

                // 両方の値が空の場合は休憩時間を削除
                if (empty($startTime) && empty($endTime)) {
                    DB::table('breakings')->where('id', $break->id)->delete();
                }
                // 両方の値がある場合は更新
                elseif ($startTime && $endTime) {
                    DB::table('breakings')
                        ->where('id', $break->id)
                        ->update([
                            'start_time' => Carbon::parse($attendance->work_date . ' ' . $startTime)->toDateTimeString(),
                            'end_time' => Carbon::parse($attendance->work_date . ' ' . $endTime)->toDateTimeString(),
                        ]);
                }
                // 片方だけ空の場合はエラーとして扱う（不正な状態）
            }

            // 新しい休憩時間の追加（break_start_1, break_start_2 など）
            for ($i = 1; $i <= 10; $i++) { // 最大10個まで確認
                $startFieldName = "break_start_{$i}";
                $endFieldName = "break_end_{$i}";

                if ($request->filled($startFieldName) && $request->filled($endFieldName)) {
                    $startTime = $request->input($startFieldName);
                    $endTime = $request->input($endFieldName);

                    // 既存の休憩時間と重複しないかチェック
                    $existingBreak = DB::table('breakings')
                        ->where('work_id', $id)
                        ->where('start_time', Carbon::parse($attendance->work_date . ' ' . $startTime)->toDateTimeString())
                        ->where('end_time', Carbon::parse($attendance->work_date . ' ' . $endTime)->toDateTimeString())
                        ->first();

                    if (!$existingBreak) {
                        DB::table('breakings')->insertGetId([
                            'user_id' => $attendance->user_id,
                            'work_id' => $id,
                            'start_time' => Carbon::parse($attendance->work_date . ' ' . $startTime)->toDateTimeString(),
                            'end_time' => Carbon::parse($attendance->work_date . ' ' . $endTime)->toDateTimeString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // fixesテーブルに修正申請を登録
            $fixData = [
                'user_id' => $attendance->user_id,
                'work_id' => $id,
                'fix_date' => now()->toDateString(), // 修正申請した日付（今日）
                'reason' => $request->input('reason', '修正申請'),
                'status' => '承認待ち',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('fixes')->insertGetId($fixData);

            DB::commit();
            return redirect('/attendance/detail/' . $id)->with('success', '修正申請が送信されました。承認待ち状態となります。');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Attendance update failed:', ['error' => $e->getMessage(), 'work_id' => $id]);
            return redirect('/attendance/detail/' . $id)->with('error', '修正申請の送信に失敗しました。');
        }
    }
}
