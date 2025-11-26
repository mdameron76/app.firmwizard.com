<?php

use App\Http\Controllers\Api\AiJobController;
use App\Http\Controllers\Api\FirmController;
use App\Http\Controllers\Api\FirmProfileController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\N8nWebhookController;
use App\Http\Controllers\Api\OnboardingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected API routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Firms
    Route::apiResource('firms', FirmController::class);
    
    // Firm Profile
    Route::get('firms/{firm}/profile', [FirmProfileController::class, 'show']);
    
    // Onboarding Wizard
    Route::post('firms/{firm}/onboarding/start', [OnboardingController::class, 'start']);
    Route::get('firms/{firm}/onboarding/current', [OnboardingController::class, 'current']);
    Route::post('firms/{firm}/onboarding/step/{step}', [OnboardingController::class, 'saveStep']);
    
    // Metrics
    Route::get('firms/{firm}/metrics/summary', [MetricsController::class, 'summary']);
    Route::get('firms/{firm}/metrics/timeseries', [MetricsController::class, 'timeseries']);
    
    // AI Jobs
    Route::post('firms/{firm}/ai-jobs', [AiJobController::class, 'store']);
    Route::get('firms/{firm}/ai-jobs/{aiJob}', [AiJobController::class, 'show']);
});

// N8N Webhook routes (no auth - will use API key validation)
Route::post('n8n/firms/{firm}/profile', [N8nWebhookController::class, 'storeProfile']);
Route::patch('n8n/firms/{firm}/profile', [N8nWebhookController::class, 'updateProfile']);
