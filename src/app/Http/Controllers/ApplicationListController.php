<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApplicationListController extends Controller
{
    public function stampCorrectionRequestList(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'pending-approval'); // デフォルトは承認待ち

        // ステータスによるフィルタリング
        $statusFilter = '';
        if ($tab === 'pending-approval') {
            $statusFilter = '承認待ち';
        } elseif ($tab === 'approved') {
            $statusFilter = '承認済み';
        }

        // ログインユーザーの修正申請一覧を取得
        $query = DB::table('fixes')
            ->join('works', 'fixes.work_id', '=', 'works.id')
            ->join('users', 'works.user_id', '=', 'users.id')
            ->where('fixes.user_id', $user->id)
            ->select(
                'fixes.id as fix_id',
                'fixes.work_id',
                'fixes.fix_date',
                'fixes.reason',
                'fixes.status',
                'works.work_date',
                'works.start_time',
                'works.end_time',
                'users.name as user_name'
            );

        // ステータスでフィルタリング
        if ($statusFilter) {
            $query->where('fixes.status', $statusFilter);
        }

        $fixes = $query->orderBy('fixes.fix_date', 'desc')->get();

        // 各修正申請に休憩時間情報を追加
        $applications = collect();

        foreach ($fixes as $fix) {
            $applications->push((object)[
                'id' => $fix->work_id, // 勤怠詳細ページへのリンク用
                'user_name' => $fix->user_name,
                'date' => Carbon::parse($fix->work_date)->format('Y-m-d'),
                'reason' => $fix->reason,
                'status' => $fix->status,
                'fix_date' => Carbon::parse($fix->fix_date)->format('Y-m-d'),
            ]);
        }

        return view('application-list', compact('applications', 'tab'));
    }
}
