<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppApi\AttendanceController as AppApiAttendanceController;
use App\Http\Controllers\AppApi\AdminController as AppApiAdminController;
use App\Http\Controllers\AppApi\AssetsController as AppApiAssetsController;
use App\Http\Controllers\AppApi\AuthController as AppApiAuthController;
use App\Http\Controllers\AppApi\ClaimsController as AppApiClaimsController;
use App\Http\Controllers\AppApi\DashboardController as AppApiDashboardController;
use App\Http\Controllers\AppApi\LeaveController as AppApiLeaveController;
use App\Http\Controllers\AppApi\ProfileController as AppApiProfileController;
use App\Http\Controllers\AppApi\ReportsController as AppApiReportsController;
use App\Http\Controllers\AppApi\TasksController as AppApiTasksController;
use App\Http\Controllers\AppApi\TeamController as AppApiTeamController;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkspacePageController;
use Illuminate\Support\Facades\Route;

Route::prefix('/app-api/auth')->name('app-api.auth.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/login', [AppApiAuthController::class, 'login'])->name('login');
        Route::post('/register', [AppApiAuthController::class, 'register'])->name('register');
        Route::post('/forgot-password', [AppApiAuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::get('/reset-password/check', [AppApiAuthController::class, 'checkResetToken'])->name('reset-password.check');
        Route::post('/reset-password', [AppApiAuthController::class, 'resetPassword'])->name('reset-password');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/me', [AppApiAuthController::class, 'me'])->name('me');
        Route::post('/logout', [AppApiAuthController::class, 'logout'])->name('logout');
    });
});

Route::prefix('/app-api/profile')->name('app-api.profile.')->middleware('auth')->group(function () {
    Route::get('/', [AppApiProfileController::class, 'show'])->name('show');
    Route::put('/', [AppApiProfileController::class, 'update'])->name('update');
    Route::put('/password', [AppApiProfileController::class, 'updatePassword'])->name('password');
    Route::post('/photo', [AppApiProfileController::class, 'uploadPhoto'])->name('photo');
});

Route::prefix('/app-api/leave')->name('app-api.leave.')->middleware('auth')->group(function () {
    Route::get('/balances', [AppApiLeaveController::class, 'balances'])->name('balances');
    Route::get('/requests', [AppApiLeaveController::class, 'index'])->name('requests.index');
    Route::post('/requests', [AppApiLeaveController::class, 'store'])->name('requests.store');
    Route::post('/requests/{leaveRequest}/status', [AppApiLeaveController::class, 'updateStatus'])->name('requests.status');
    Route::get('/allocations', [AppApiLeaveController::class, 'allocations'])->name('allocations.index');
    Route::post('/allocations', [AppApiLeaveController::class, 'saveAllocation'])->name('allocations.save');
    Route::post('/allocations/seed-defaults', [AppApiLeaveController::class, 'seedDefaults'])->name('allocations.seed-defaults');
});

Route::prefix('/app-api/attendance')->name('app-api.attendance.')->middleware('auth')->group(function () {
    Route::get('/entries', [AppApiAttendanceController::class, 'index'])->name('entries.index');
    Route::get('/status', [AppApiAttendanceController::class, 'status'])->name('status');
    Route::post('/event', [AppApiAttendanceController::class, 'event'])->name('event');
    Route::post('/entries/upsert', [AppApiAttendanceController::class, 'upsert'])->name('entries.upsert');
    Route::get('/admin/daily', [AppApiAttendanceController::class, 'adminList'])->name('admin.daily');
    Route::post('/admin/daily', [AppApiAttendanceController::class, 'adminUpdate'])->name('admin.daily.update');
});

Route::prefix('/app-api/dashboard')->name('app-api.dashboard.')->middleware('auth')->group(function () {
    Route::get('/overview', [AppApiDashboardController::class, 'overview'])->name('overview');
});

Route::prefix('/app-api/tasks')->name('app-api.tasks.')->middleware('auth')->group(function () {
    Route::get('/board', [AppApiTasksController::class, 'board'])->name('board');
    Route::post('/', [AppApiTasksController::class, 'store'])->name('store');
    Route::post('/move', [AppApiTasksController::class, 'move'])->name('move');
});

Route::prefix('/app-api/team')->name('app-api.team.')->middleware('auth')->group(function () {
    Route::get('/my', [AppApiTeamController::class, 'my'])->name('my');
    Route::post('/', [AppApiTeamController::class, 'store'])->name('store');
    Route::post('/link', [AppApiTeamController::class, 'link'])->name('link');
    Route::get('/member-options', [AppApiTeamController::class, 'memberOptions'])->name('member-options');
});

Route::prefix('/app-api/assets')->name('app-api.assets.')->middleware('auth')->group(function () {
    Route::get('/', [AppApiAssetsController::class, 'index'])->name('index');
    Route::post('/', [AppApiAssetsController::class, 'store'])->name('store');
    Route::post('/{asset}/status', [AppApiAssetsController::class, 'updateStatus'])->name('status');
});

Route::prefix('/app-api/reports')->name('app-api.reports.')->middleware('auth')->group(function () {
    Route::get('/summary', [AppApiReportsController::class, 'summary'])->name('summary');
    Route::get('/attendance', [AppApiReportsController::class, 'attendance'])->name('attendance');
    Route::get('/leave', [AppApiReportsController::class, 'leaves'])->name('leave');
    Route::get('/claims', [AppApiReportsController::class, 'claims'])->name('claims');
});

Route::prefix('/app-api/admin')->name('app-api.admin.')->middleware('auth')->group(function () {
    Route::get('/overview', [AppApiAdminController::class, 'overview'])->name('overview');
    Route::get('/users', [AppApiAdminController::class, 'users'])->name('users');
    Route::get('/approvals', [AppApiAdminController::class, 'approvals'])->name('approvals');
    Route::post('/approvals', [AppApiAdminController::class, 'saveApprovals'])->name('approvals.save');
    Route::get('/assets', [AppApiAdminController::class, 'assets'])->name('assets');
    Route::get('/settings', [AppApiAdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AppApiAdminController::class, 'saveSettings'])->name('settings.save');
    Route::post('/link-person', [AppApiAdminController::class, 'linkPerson'])->name('link-person');
});

Route::prefix('/app-api/claims')->name('app-api.claims.')->middleware('auth')->group(function () {
    Route::get('/', [AppApiClaimsController::class, 'index'])->name('index');
    Route::post('/', [AppApiClaimsController::class, 'store'])->name('store');
    Route::post('/receipt-extract', [AppApiClaimsController::class, 'extractReceipt'])->name('receipt-extract');
    Route::get('/{claim}', [AppApiClaimsController::class, 'show'])->name('show');
    Route::put('/{claim}', [AppApiClaimsController::class, 'update'])->name('update');
    Route::post('/{claim}/submit', [AppApiClaimsController::class, 'submit'])->name('submit');
    Route::post('/{claim}/action', [AppApiClaimsController::class, 'action'])->name('action');
    Route::post('/{claim}/attachments', [AppApiClaimsController::class, 'uploadAttachment'])->name('attachments.store');
    Route::delete('/{claim}/attachments/{attachment}', [AppApiClaimsController::class, 'deleteAttachment'])->name('attachments.delete');
});

Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::get('/register', [AuthPageController::class, 'register'])->name('register');
Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('forgot-password');
Route::get('/reset-password', [AuthPageController::class, 'resetPassword'])->name('reset-password');
Route::get('/logout', [AuthPageController::class, 'logout'])->name('logout');

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/dashboard', DashboardController::class);
Route::get('/admin', AdminController::class)->name('admin');
Route::get('/attendance', AttendanceController::class)->name('attendance');
Route::get('/leave', [WorkspacePageController::class, 'leave'])->name('leave');
Route::get('/claims', [WorkspacePageController::class, 'claims'])->name('claims');
Route::get('/profile', [WorkspacePageController::class, 'profile'])->name('profile');
Route::get('/tasks', [WorkspacePageController::class, 'tasks'])->name('tasks');
Route::get('/team', [WorkspacePageController::class, 'team'])->name('team');
Route::get('/assets', [WorkspacePageController::class, 'assets'])->name('assets');
Route::get('/report', [WorkspacePageController::class, 'reports'])->name('report');
