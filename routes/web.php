<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingWizardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Onboarding Wizard Routes
    Route::get('/onboarding', [OnboardingWizardController::class, 'index'])->name('onboarding.index');
    Route::get('/onboarding/step/{step}', [OnboardingWizardController::class, 'show'])->name('onboarding.step');
    
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
