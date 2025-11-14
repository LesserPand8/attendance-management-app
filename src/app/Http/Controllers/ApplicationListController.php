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
        // 管理者または一般ユーザーの認証を確認
        $isAdmin = Auth::guard('admin')->check();
        $isUser = Auth::guard('web')->check();

        // どちらの認証もない場合はログインページにリダイレクト
        if (!$isAdmin && !$isUser) {
            // 管理者用のURLからアクセスされた場合は管理者ログインへ
            // それ以外は一般ユーザーログインへ
            return redirect('/login');
        }

        // 一般ユーザーの場合はメール認証を確認
        if ($isUser && !$isAdmin) {
            $user = Auth::user();
            if (is_null($user->email_verified_at)) {
                return redirect()->route('verification.notice');
            }
        }

        $tab = $request->get('tab', 'pending-approval'); // デフォルトは承認待ち

        // ステータスによるフィルタリング
        $statusFilter = '';
        if ($tab === 'pending-approval') {
            $statusFilter = '承認待ち';
        } elseif ($tab === 'approved') {
            $statusFilter = '承認済み';
        }

        $query = DB::table('fixes')
            ->join('works', 'fixes.work_id', '=', 'works.id')
            ->join('users', 'works.user_id', '=', 'users.id')
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

        // 管理者の場合は全ての申請を表示、一般ユーザーは自分の申請のみ
        if (!$isAdmin) {
            $user = Auth::user();
            $query->where('fixes.user_id', $user->id);
        }

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
                'fix_id' => $fix->fix_id,
                'user_name' => $fix->user_name,
                'date' => Carbon::parse($fix->work_date)->format('Y-m-d'),
                'reason' => $fix->reason,
                'status' => $fix->status,
                'fix_date' => Carbon::parse($fix->fix_date)->format('Y-m-d'),
            ]);
        }

        // 管理者と一般ユーザーで異なるビューを返す
        $viewName = $isAdmin ? 'admin.application-list' : 'application-list';
        return view($viewName, compact('applications', 'tab'));
    }
}
