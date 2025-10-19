<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;

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

Route::get('/', [AttendanceController::class, 'attendance'])->middleware('auth');
Route::get('/attendance', [AttendanceController::class, 'attendance'])->middleware('auth');
Route::post('/attendance', [AttendanceController::class, 'registerAttendance']);
Route::get('/attendance/list', [AttendanceListController::class, 'attendanceList'])->middleware('auth');
Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'attendanceDetail'])->middleware('auth');
