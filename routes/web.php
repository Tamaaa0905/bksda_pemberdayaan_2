<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Services\DashboardDataService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (DashboardDataService $dashboardDataService) {
    return view('auth.login', [
        'dashboardPreview' => $dashboardDataService->forLanding(),
    ]);
});
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])->group(function () {
    Route::get('/peta-kelompok', [DashboardController::class, 'map'])->name('kelompok.map');
    Route::put('/desa/{desa}/koordinat', [DashboardController::class, 'updateDesaCoordinates'])
        ->name('desa.coordinates.update');

    Route::get('/kelompok/tambah', [DashboardController::class, 'createKelompok'])->name('kelompok.create');
    Route::post('/kelompok', [DashboardController::class, 'storeKelompok'])->name('kelompok.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/kelompok/{kelompok}/edit', [DashboardController::class, 'editKelompok'])->name('kelompok.edit');
    Route::put('/kelompok/{kelompok}', [DashboardController::class, 'updateKelompok'])->name('kelompok.update');
    Route::get('/kelompok/{kelompok}/status', [DashboardController::class, 'editGroupStatus'])
        ->name('kelompok.status.edit');
    Route::put('/kelompok/{kelompok}/status', [DashboardController::class, 'updateGroupStatus'])
        ->name('kelompok.status.update');
    Route::put('/kelompok/{kelompok}/koordinat', [DashboardController::class, 'updateGroupCoordinates'])
        ->name('kelompok.coordinates.update');

});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
