<?php

use App\Http\Controllers\AiAgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\IntegrationsOAuthController;
use App\Http\Controllers\OnboardingWizardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/analytics', function () {
    return view('pages.dashboards.analytics');
})->middleware(['auth', 'verified'])->name('analytics');

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
        Route::get('/', [IntegrationsController::class, 'index'])->name('index');
        Route::post('/{id}/sync', [IntegrationsController::class, 'sync'])->name('sync');

        // OAuth routes - IMPORTANT: callback route must come BEFORE the dynamic {platform} route
        Route::get('/oauth/google/callback', [IntegrationsOAuthController::class, 'googleCallback'])->name('oauth.google.callback');
        Route::get('/oauth/google/{platform}', [IntegrationsOAuthController::class, 'googleAuth'])->name('oauth.google');
    });
    
    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/profile-picture', [SettingsController::class, 'updateProfilePicture'])->name('profile-picture.update');
        Route::post('/firm', [SettingsController::class, 'updateFirm'])->name('firm.update');
        Route::post('/pixel/regenerate', [SettingsController::class, 'regeneratePixelKey'])->name('pixel.regenerate');
        Route::post('/locations', [SettingsController::class, 'storeLocation'])->name('locations.store');
        Route::put('/locations/{id}', [SettingsController::class, 'updateLocation'])->name('locations.update');
        Route::delete('/locations/{id}', [SettingsController::class, 'deleteLocation'])->name('locations.delete');
    });
    
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
