<?php

use App\Http\Controllers\Backoffice\DashboardController as BackofficeDashboardController;
use App\Http\Controllers\Backoffice\DynamicRouteController;
use App\Http\Controllers\Backoffice\RoleController;
use App\Http\Controllers\Backoffice\UserController as BackofficeUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public welcome page (no password required)
Route::get('/', function () {
    return view('welcome');
});

// Dashboard - Backoffice for all authenticated users
Route::get('/dashboard', [BackofficeDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// User Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin welcome page - redirects to dashboard if authenticated
Route::get('/admin', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('admin.welcome');
})->name('admin.welcome');

// Backoffice Routes (auth required) - under /backoffice prefix
Route::middleware(['auth', 'verified'])->prefix('backoffice')->name('backoffice.')->group(function () {
    Route::resource('users', BackofficeUserController::class);
    Route::resource('roles', RoleController::class);

    // Dynamic Routes Management
    Route::resource('routes', DynamicRouteController::class);
    Route::post('routes/{route}/toggle', [DynamicRouteController::class, 'toggle'])->name('routes.toggle');
    Route::post('routes/clear-cache', [DynamicRouteController::class, 'clearCache'])->name('routes.clear-cache');
});

require __DIR__.'/auth.php';
