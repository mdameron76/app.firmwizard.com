<?php

use App\Http\Controllers\Api\AiAgentController;
use App\Http\Controllers\Api\AiJobController;
use App\Http\Controllers\Api\AiSuggestionController;
use App\Http\Controllers\Api\CrawlerController;
use App\Http\Controllers\Api\FirmController;
use App\Http\Controllers\Api\FirmProfileController;
use App\Http\Controllers\Api\GbpLocationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\N8nWebhookController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PixelController;
use App\Http\Controllers\IntegrationsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public health check endpoint (no authentication required)
Route::get('/health', [HealthController::class, 'check']);

// Pixel collection endpoint (no authentication - uses API key validation)
Route::post('/pixel/collect', [PixelController::class, 'collect']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected API routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Firms
    Route::apiResource('firms', FirmController::class);
    
    // Firm Profile
    Route::get('firms/{firm}/profile', [FirmProfileController::class, 'show']);
    
    // Integrations
    Route::get('integrations/platforms', [IntegrationController::class, 'platforms']);
    Route::get('integrations/auth-fields/{authType}', [IntegrationController::class, 'authFields']);
    Route::get('integrations', [IntegrationController::class, 'index']);
    Route::post('integrations', [IntegrationController::class, 'store']);
    Route::get('integrations/{integration}', [IntegrationController::class, 'show']);
    Route::put('integrations/{integration}', [IntegrationController::class, 'update']);
    Route::delete('integrations/{integration}', [IntegrationController::class, 'destroy']);
    Route::post('integrations/{integration}/test', [IntegrationController::class, 'test']);
    
    // Onboarding Wizard
    Route::post('firms/{firm}/onboarding/start', [OnboardingController::class, 'start']);
    Route::get('firms/{firm}/onboarding/current', [OnboardingController::class, 'current']);
    Route::post('firms/{firm}/onboarding/step/{step}', [OnboardingController::class, 'saveStep']);
    
    // AI Suggestions for Wizard
    Route::post('firms/{firm}/ai-suggestions/brand-voice', [AiSuggestionController::class, 'brandVoice']);
    Route::post('firms/{firm}/ai-suggestions/pain-points', [AiSuggestionController::class, 'painPoints']);
    Route::post('firms/{firm}/ai-suggestions/psychographics', [AiSuggestionController::class, 'psychographics']);
    Route::post('firms/{firm}/ai-suggestions/situational-triggers', [AiSuggestionController::class, 'situationalTriggers']);
    Route::post('firms/{firm}/ai-suggestions/marketing-goals', [AiSuggestionController::class, 'marketingGoals']);
    Route::post('firms/{firm}/ai-suggestions/lead-estimate', [AiSuggestionController::class, 'leadEstimate']);
    
    // Metrics
    Route::get('firms/{firm}/metrics/summary', [MetricsController::class, 'summary']);
    Route::get('firms/{firm}/metrics/timeseries', [MetricsController::class, 'timeseries']);
    
    // AI Jobs
    Route::get('firms/{firm}/ai-jobs', [AiJobController::class, 'index']);
    Route::post('firms/{firm}/ai-jobs', [AiJobController::class, 'store']);
    Route::get('firms/{firm}/ai-jobs/{aiJob}', [AiJobController::class, 'show']);
    Route::patch('firms/{firm}/ai-jobs/{aiJob}', [AiJobController::class, 'update']);
    Route::delete('firms/{firm}/ai-jobs/{aiJob}', [AiJobController::class, 'destroy']);

    // AI Agents
    Route::get('ai-agents', [AiAgentController::class, 'index']);
    Route::post('ai-agents/trigger/{agentType}', [AiAgentController::class, 'trigger']);
    Route::get('ai-agents/jobs/{job}', [AiAgentController::class, 'show']);
});

// N8N Webhook routes (no auth - will use API key validation)
Route::post('n8n/firms/{firm}/profile', [N8nWebhookController::class, 'storeProfile']);
Route::patch('n8n/firms/{firm}/profile', [N8nWebhookController::class, 'updateProfile']);

// N8N Integrations API (no auth - uses API key validation)
Route::get('n8n/integrations/active', [IntegrationsController::class, 'listActive']);

// GBP Location Sync API (no auth - uses API key validation)
Route::get('gbp/{locationId}/sync', [GbpLocationController::class, 'sync']);

// Python Crawler API (no auth - uses API key validation)
Route::post('crawler/page', [CrawlerController::class, 'crawlPage']);
Route::get('crawler/pages/{competitorId}', [CrawlerController::class, 'getPages']);
Route::get('crawler/scripts', [CrawlerController::class, 'listScripts']);

// Competitor Finder API (no auth - uses API key validation)
Route::post('crawler/find-competitors', [CrawlerController::class, 'findCompetitors']);
Route::get('crawler/collections', [CrawlerController::class, 'getCollections']);
Route::post('crawler/collections', [CrawlerController::class, 'createCollection']);

// Firms and Locations for Crawler (no auth - uses API key validation)
Route::get('crawler/firms', [CrawlerController::class, 'getFirms']);
Route::get('crawler/firm-locations', [CrawlerController::class, 'getFirmLocations']);
Route::get('crawler/firm-profiles', [CrawlerController::class, 'getFirmProfiles']);
