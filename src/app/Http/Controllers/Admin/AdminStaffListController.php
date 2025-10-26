<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AdminStaffListController extends Controller
{
    public function staffList()
    {
        // 全ユーザーを取得（管理者以外）
        $staffs = DB::table('users')
            ->select('id', 'name', 'email', 'created_at')
            ->orderBy('name')
            ->get();

        return view('admin.staff-list', compact('staffs'));
    }
}
