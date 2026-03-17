<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserModerationController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::get('/terms-and-conditions', fn () => Inertia::render('TermsAndConditions'))->name('terms');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/farms', [FarmController::class, 'store'])->name('farms.store');
    Route::get('/farms/{farm}/edit', [FarmController::class, 'edit'])->name('farms.edit');
    Route::put('/farms/{farm}', [FarmController::class, 'update'])->name('farms.update');
    Route::delete('/farms/{farm}', [FarmController::class, 'destroy'])->name('farms.destroy');

    Route::post('/harvests', [HarvestController::class, 'store'])->name('harvests.store');
    Route::get('/harvests/{harvest}/edit', [HarvestController::class, 'edit'])->name('harvests.edit');
    Route::put('/harvests/{harvest}', [HarvestController::class, 'update'])->name('harvests.update');
    Route::delete('/harvests/{harvest}', [HarvestController::class, 'destroy'])->name('harvests.destroy');

    Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    Route::get('/equipment/{equipment}/edit', [EquipmentController::class, 'edit'])->name('equipment.edit');
    Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');

    Route::delete('/users/{user}', [UserModerationController::class, 'destroy'])->name('users.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
