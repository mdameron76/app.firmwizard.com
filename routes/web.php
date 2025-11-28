<?php

use App\Http\Controllers\AiAgentController;
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
    Route::post('/onboarding/step/{step}', [OnboardingWizardController::class, 'store'])->name('onboarding.step.store');
    
    // AI Agents Routes
    Route::prefix('ai-agents')->name('ai-agents.')->group(function () {
        Route::get('/', [AiAgentController::class, 'index'])->name('index');
        Route::post('/{agentType}/trigger', [AiAgentController::class, 'trigger'])->name('trigger');
        Route::get('/jobs/{job}', [AiAgentController::class, 'show'])->name('show');
        
        // Individual Agent Pages
        Route::get('/blog-posts', function() { return view('ai-agents.blog-posts'); })->name('blog-posts');
        Route::get('/competitor-analysis', function() { return view('ai-agents.competitor-analysis'); })->name('competitor-analysis');
        Route::get('/website-analysis', function() { return view('ai-agents.website-analysis'); })->name('website-analysis');
        Route::get('/gbp', function() { return view('ai-agents.gbp'); })->name('gbp');
        Route::get('/google-ads', function() { return view('ai-agents.google-ads'); })->name('google-ads');
        Route::get('/lsa', function() { return view('ai-agents.lsa'); })->name('lsa');
        Route::get('/meta-ads', function() { return view('ai-agents.meta-ads'); })->name('meta-ads');
        Route::get('/backlinks', function() { return view('ai-agents.backlinks'); })->name('backlinks');
        Route::get('/analytics', function() { return view('ai-agents.analytics'); })->name('analytics');
    });
    
    // Integrations Routes
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', function() { return view('integrations.index'); })->name('index');
    });
    
    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function() { return view('settings.index'); })->name('index');
    });
    
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
