<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\ApplicationListController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminStaffListController;
use App\Http\Controllers\Admin\AdminAttendanceListController;
use App\Http\Controllers\Admin\AdminAttendanceDetailController;
use App\Http\Controllers\Admin\AdminStaffAttendanceListController;
use App\Http\Controllers\Admin\AdminApprovalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
|--------------------------------------------------------------------------
| 一般ユーザー用ルーティング
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/', [AttendanceController::class, 'attendance']);
    Route::get('/attendance', [AttendanceController::class, 'attendance']);
    Route::post('/attendance', [AttendanceController::class, 'registerAttendance']);
    Route::get('/attendance/list', [AttendanceListController::class, 'attendanceList']);
    Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'attendanceDetail']);
    Route::post('/attendance/detail/{id}', [AttendanceDetailController::class, 'updateAttendanceDetail']);
});

// 申請一覧は管理者・一般ユーザー共通パス（認証ミドルウェアで区別）
Route::get('/stamp_correction_request/list', [ApplicationListController::class, 'stampCorrectionRequestList']);

/*
|--------------------------------------------------------------------------
| 管理者用ルーティング
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'admin'], function () {
    // ログイン
    Route::get('login', [AdminLoginController::class, 'showLoginPage'])->name('admin.login');
    Route::post('login', [AdminLoginController::class, 'login']);

    // 以下の中は認証必須のエンドポイントとなる
    Route::middleware(['auth:admin'])->group(function () {
        // ログアウト
        Route::post('logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
        Route::get('attendance/list', [AdminAttendanceListController::class, 'attendanceList'])
            ->name('admin.attendance.list');
        Route::get('attendance/{id}', [AdminAttendanceDetailController::class, 'attendanceDetail']);

        Route::post('attendance/{id}', [AdminAttendanceDetailController::class, 'updateAttendanceDetail']);
        Route::get('staff/list', [AdminStaffListController::class, 'staffList']);
        Route::get('attendance/staff/{id}', [AdminStaffAttendanceListController::class, 'staffAttendanceList']);
    });
});

// 申請承認ページ（管理者のみアクセス可能）
Route::middleware(['auth:admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApprovalController::class, 'approvalDetail']);
});
