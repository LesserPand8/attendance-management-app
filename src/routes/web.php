<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\ApplicationListController;

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

Route::middleware(['auth'])->group(function () {
    Route::get('/', [AttendanceController::class, 'attendance']);
    Route::get('/attendance', [AttendanceController::class, 'attendance']);
    Route::post('/attendance', [AttendanceController::class, 'registerAttendance']);
    Route::get('/attendance/list', [AttendanceListController::class, 'attendanceList']);
    Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'attendanceDetail']);
    Route::post('/attendance/detail/{id}', [AttendanceDetailController::class, 'updateAttendanceDetail']);
    Route::get('/stamp_correction_request/list', [ApplicationListController::class, 'stampCorrectionRequestList']);
});
