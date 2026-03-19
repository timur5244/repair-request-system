<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dispatcher\DispatcherDashboardController;
use App\Http\Controllers\Master\MasterDashboardController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('requests.index')
        : redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dispatcher/dashboard', [DispatcherDashboardController::class, 'index'])
        ->middleware('dispatcher')
        ->name('dispatcher.dashboard');

    Route::post('/dispatcher/requests/{request}/assign', [DispatcherDashboardController::class, 'assign'])
        ->middleware('dispatcher')
        ->name('dispatcher.requests.assign');

    Route::post('/dispatcher/requests/{request}/cancel', [DispatcherDashboardController::class, 'cancel'])
        ->middleware('dispatcher')
        ->name('dispatcher.requests.cancel');

    Route::get('/master/dashboard', [MasterDashboardController::class, 'index'])
        ->middleware('master')
        ->name('master.dashboard');

    Route::post('/master/requests/{request}/take', [MasterDashboardController::class, 'take'])
        ->middleware('master')
        ->name('master.requests.take');

    Route::post('/master/requests/{request}/finish', [MasterDashboardController::class, 'finish'])
        ->middleware('master')
        ->name('master.requests.finish');

    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{request}', [RequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{request}/assign', [RequestController::class, 'assign'])->name('requests.assign');
    Route::post('/requests/{request}/status', [RequestController::class, 'setStatus'])->name('requests.status');
});
