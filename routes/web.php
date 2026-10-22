<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\FirstRunController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\ControlStudioController;
use App\Http\Controllers\PluginStateController;
use App\Http\Controllers\PluginInstallController;
use App\Http\Controllers\PluginUninstallController;
use App\Http\Controllers\PluginRestoreController;
use App\Http\Controllers\CoreSettingsController;
use App\Http\Controllers\GuidedTourController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PluginExtensionController;

Route::view('/', 'welcome')->name('home');
// First-run installer is unavailable until a 32+ character secret is set in .env.
Route::get('/setup', [FirstRunController::class, 'show'])->middleware('throttle:10,1')->name('setup.show');
Route::post('/setup', [FirstRunController::class, 'store'])->middleware('throttle:3,1')->name('setup.store');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
});
Route::middleware(['auth','auth.session'])->group(function (): void {
    Route::get('/dashboard', ControlStudioController::class)->middleware('can:core.view-dashboard')->name('dashboard');
    Route::get('/dashboard/settings', [CoreSettingsController::class, 'show'])->middleware('can:core.manage-security')->name('settings.show');
    Route::post('/dashboard/settings/cpanel', [CoreSettingsController::class, 'saveCpanel'])->middleware(['can:core.manage-security','throttle:5,1'])->name('settings.cpanel');
    Route::post('/dashboard/settings/switch', [CoreSettingsController::class, 'switch'])->middleware(['can:core.manage-security','throttle:20,1'])->name('settings.switch');
    Route::post('/dashboard/settings/permission', [CoreSettingsController::class, 'permission'])->middleware(['can:core.manage-security','throttle:20,1'])->name('settings.permission');
    Route::post('/dashboard/settings/disconnect', [CoreSettingsController::class, 'disconnect'])->middleware(['can:core.manage-security','throttle:5,1'])->name('settings.disconnect');
    Route::post('/dashboard/tour/complete', [GuidedTourController::class, 'complete'])->middleware(['can:core.manage-security','throttle:10,1'])->name('tour.complete');
    Route::post('/dashboard/plugins/install', PluginInstallController::class)
        ->middleware(['can:core.manage-plugins','throttle:5,1'])->name('plugins.install');
    Route::post('/dashboard/plugins/quarantine/{snapshot}/restore', PluginRestoreController::class)
        ->middleware(['can:core.manage-plugins','throttle:3,1'])->name('plugins.restore');
    Route::post('/dashboard/plugins/{slug}/uninstall', PluginUninstallController::class)
        ->middleware(['can:core.manage-plugins','throttle:3,1'])->name('plugins.uninstall');
    Route::post('/dashboard/plugins/{slug}/state', PluginStateController::class)
        ->middleware(['can:core.manage-plugins','throttle:20,1'])->name('plugins.state');
    Route::get('/account/security', [ChangePasswordController::class, 'edit'])->middleware('can:core.manage-security')->name('password.edit');
    Route::post('/account/security', [ChangePasswordController::class, 'update'])->middleware(['can:core.manage-security','throttle:5,1'])->name('password.update');
    Route::match(['GET', 'POST'], '/dashboard/extensions/{slug}/{action?}', PluginExtensionController::class)
        ->where(['slug' => '[a-z][a-z0-9-]{1,63}', 'action' => '[a-z][a-z0-9-]{0,31}'])
        ->middleware(['can:core.manage-plugins', 'throttle:30,1'])->name('plugins.extension');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
