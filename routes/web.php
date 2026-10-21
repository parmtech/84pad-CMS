<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\ControlStudioController;
use App\Http\Controllers\PluginStateController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
});
Route::middleware(['auth','auth.session'])->group(function (): void {
    Route::get('/dashboard', ControlStudioController::class)->name('dashboard');
    Route::post('/dashboard/plugins/{slug}/state', PluginStateController::class)
        ->middleware('throttle:20,1')->name('plugins.state');
    Route::get('/account/security', [ChangePasswordController::class, 'edit'])->name('password.edit');
    Route::post('/account/security', [ChangePasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
