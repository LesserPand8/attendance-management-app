<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function attendance()
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        // 今日の勤務記録を取得
        $todayWork = DB::table('works')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        // 勤務状態を判定
        $workStatus = $this->getWorkStatus($todayWork);

        // ボタンの表示状態を判定
        $buttonStatus = $this->getButtonStatus($todayWork);

        return view('attendance', compact('workStatus', 'buttonStatus'));
    }

    private function getWorkStatus($todayWork)
    {
        // worksテーブルに今日の記録がない場合
        if (!$todayWork) {
            return '勤務外';
        }

        // end_timeが入っている場合（退勤済み）
        if ($todayWork->end_time) {
            return '退勤済み';
        }

        // start_timeが入っている場合、休憩中かどうかをチェック
        if ($todayWork->start_time) {
            // 現在休憩中かどうかをチェック
            $currentBreak = DB::table('breakings')
                ->where('work_id', $todayWork->id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->first();

            if ($currentBreak) {
                return '休憩中';
            }

            return '出勤中';
        }

        // その他の場合
        return '勤務外';
    }

    private function getButtonStatus($todayWork)
    {
        // worksテーブルに今日の記録がない場合 → 出勤ボタンを表示
        if (!$todayWork) {
            return 'show_clockin';
        }

        // end_timeが入っている場合 → お疲れ様でした。を表示
        if ($todayWork->end_time) {
            return 'show_thanks';
        }

        // start_timeが入っていてend_timeがない場合
        if ($todayWork->start_time && !$todayWork->end_time) {
            // 現在休憩中かどうかをチェック
            $currentBreak = DB::table('breakings')
                ->where('work_id', $todayWork->id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->first();

            if ($currentBreak) {
                // 休憩中 → 休憩戻ボタンを表示
                return 'show_breakout';
            } else {
                // 出勤中 → 退勤ボタン、休憩入ボタンを表示
                return 'show_clockout_breakin';
            }
        }

        // その他の場合 → 出勤ボタンを表示
        return 'show_clockin';
    }

    public function registerAttendance()
    {
        $user = Auth::user();
        $date = request('date');
        $time = request('time');
        $action = request('action'); // どのボタンが押されたかを識別

        // 今日の勤務記録を取得
        $todayWork = DB::table('works')
            ->where('user_id', $user->id)
            ->where('work_date', $date)
            ->first();

        switch ($action) {
            case 'clockin':
                // 出勤処理
                if (!$todayWork) {
                    DB::table('works')->insert([
                        'user_id' => $user->id,
                        'work_date' => $date,
                        'start_time' => $time,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                break;

            case 'clockout':
                // 退勤処理
                if ($todayWork && !$todayWork->end_time) {
                    DB::table('works')
                        ->where('id', $todayWork->id)
                        ->update([
                            'end_time' => $time,
                            'updated_at' => now(),
                        ]);
                }
                break;

            case 'breakin':
                // 休憩入り処理
                if ($todayWork && $todayWork->start_time && !$todayWork->end_time) {
                    // 現在進行中の休憩がないかチェック
                    $currentBreak = DB::table('breakings')
                        ->where('work_id', $todayWork->id)
                        ->whereNotNull('start_time')
                        ->whereNull('end_time')
                        ->first();

                    if (!$currentBreak) {
                        DB::table('breakings')->insert([
                            'user_id' => $user->id,
                            'work_id' => $todayWork->id,
                            'start_time' => $time,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                break;

            case 'breakout':
                // 休憩戻り処理
                if ($todayWork) {
                    $currentBreak = DB::table('breakings')
                        ->where('work_id', $todayWork->id)
                        ->whereNotNull('start_time')
                        ->whereNull('end_time')
                        ->first();

                    if ($currentBreak) {
                        DB::table('breakings')
                            ->where('id', $currentBreak->id)
                            ->update([
                                'end_time' => $time,
                                'updated_at' => now(),
                            ]);
                    }
                }
                break;
        }

        return redirect('/attendance');
    }
}
