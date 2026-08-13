<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\FinancialDocumentController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CustomerPortalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () { Route::get('/login',[AuthenticatedSessionController::class,'create'])->name('login'); Route::post('/login',[AuthenticatedSessionController::class,'store'])->middleware('throttle:5,1')->name('login.store'); });
Route::middleware(['auth','active'])->group(function () { Route::get('/dashboard',DashboardController::class)->name('dashboard'); Route::middleware('role:admin')->group(function () { Route::get('/users',[UserController::class,'index'])->name('users.index'); Route::get('/users/create',[UserController::class,'create'])->name('users.create'); Route::post('/users',[UserController::class,'store'])->name('users.store'); }); Route::post('/logout',[AuthenticatedSessionController::class,'destroy'])->name('logout'); });

Route::middleware(['auth','active'])->prefix('management/{resource}')->name('management.')->group(function () {
    Route::get('/', [ManagementController::class, 'index'])->name('index');
    Route::get('/create', [ManagementController::class, 'create'])->name('create');
    Route::post('/', [ManagementController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [ManagementController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth','active','role:admin|project_manager'])->group(function () {
    Route::get('/financial', [FinancialDocumentController::class,'index'])->name('financial.index');
    Route::get('/financial/create', [FinancialDocumentController::class,'create'])->name('financial.create');
    Route::post('/financial', [FinancialDocumentController::class,'store'])->name('financial.store');
    Route::get('/financial/{document}', [FinancialDocumentController::class,'show'])->name('financial.show');
    Route::post('/financial/{document}/entries', [FinancialDocumentController::class,'addEntry'])->name('financial.entries.store');
    Route::post('/financial/{document}/allocations', [FinancialDocumentController::class,'allocate'])->name('financial.allocations.store');
});
Route::middleware(['auth','active','role:admin'])->group(function () {
    Route::post('/financial/{document}/void', [FinancialDocumentController::class,'void'])->name('financial.void');
    Route::get('/transfers/create', [TransferController::class,'create'])->name('transfers.create');
    Route::post('/transfers', [TransferController::class,'store'])->name('transfers.store');
    Route::get('/checks', [CheckController::class,'index'])->name('checks.index');
    Route::get('/checks/create', [CheckController::class,'create'])->name('checks.create');
    Route::post('/checks', [CheckController::class,'store'])->name('checks.store');
    Route::patch('/checks/{check}/status', [CheckController::class,'status'])->name('checks.status');
    Route::get('/audit-log',[ReportController::class,'audit'])->name('audit.index');
    Route::patch('/projects/{project}/customer-permission',[CustomerPortalController::class,'permission'])->name('customer.permission');
});
Route::middleware(['auth','active','role:admin|project_manager'])->group(function () {
    Route::get('/reports/{type?}',[ReportController::class,'index'])->name('reports.index');
    Route::get('/reports/{type}/export',[ReportController::class,'export'])->name('reports.export');
    Route::get('/reports/{type}/print',[ReportController::class,'print'])->name('reports.print');
});
Route::middleware(['auth','active','role:customer'])->group(function () {
    Route::get('/customer',[CustomerPortalController::class,'index'])->name('customer.index');
    Route::get('/customer/projects/{project}',[CustomerPortalController::class,'show'])->name('customer.show');
});
Route::middleware(['auth','active'])->group(function () {
    Route::get('/projects/{project}/portal',[CustomerPortalController::class,'show'])->name('customer.preview');
    Route::post('/projects/{project}/comments',[CustomerPortalController::class,'comment'])->name('customer.comment');
    Route::post('/projects/{project}/attachments',[CustomerPortalController::class,'upload'])->name('customer.upload');
    Route::get('/attachments/{attachment}',[CustomerPortalController::class,'download'])->name('attachments.download');
});
