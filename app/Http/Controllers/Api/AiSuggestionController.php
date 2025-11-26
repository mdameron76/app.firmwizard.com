<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Services\OpenAiSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiSuggestionController extends Controller
{
    public function __construct(
        protected OpenAiSuggestionService $aiService
    ) {}

    /**
     * Generate brand voice options
     */
    public function brandVoice(Request $request, Firm $firm): JsonResponse
    {
        $firmData = $this->prepareFirmData($firm, $request->all());
        $suggestions = $this->aiService->generateBrandVoiceOptions($firmData);

        return response()->json([
            'success' => !isset($suggestions['error']),
            'data' => $suggestions,
        ]);
    }

    /**
     * Generate pain points
     */
    public function painPoints(Request $request, Firm $firm): JsonResponse
    {
        $firmData = $this->prepareFirmData($firm, $request->all());
        $suggestions = $this->aiService->generatePainPoints($firmData);

        return response()->json([
            'success' => !isset($suggestions['error']),
            'data' => $suggestions,
        ]);
    }

    /**
     * Generate psychographics
     */
    public function psychographics(Request $request, Firm $firm): JsonResponse
    {
        $firmData = $this->prepareFirmData($firm, $request->all());
        $suggestions = $this->aiService->generatePsychographics($firmData);

        return response()->json([
            'success' => !isset($suggestions['error']),
            'data' => $suggestions,
        ]);
    }

    /**
     * Generate situational triggers
     */
    public function situationalTriggers(Request $request, Firm $firm): JsonResponse
    {
        $firmData = $this->prepareFirmData($firm, $request->all());
        $suggestions = $this->aiService->generateSituationalTriggers($firmData);

        return response()->json([
            'success' => !isset($suggestions['error']),
            'data' => $suggestions,
        ]);
    }

    /**
     * Generate marketing goals
     */
    public function marketingGoals(Request $request, Firm $firm): JsonResponse
    {
        $firmData = $this->prepareFirmData($firm, $request->all());
        $suggestions = $this->aiService->generateMarketingGoals($firmData);

        return response()->json([
            'success' => !isset($suggestions['error']),
            'data' => $suggestions,
        ]);
    }

    /**
     * Estimate lead volume
     */
    public function leadEstimate(Request $request, Firm $firm): JsonResponse
    {
        $firmData = $this->prepareFirmData($firm, $request->all());
        $estimate = $this->aiService->estimateLeadVolume($firmData);

        return response()->json([
            'success' => !isset($estimate['error']),
            'data' => $estimate,
        ]);
    }

    /**
     * Prepare firm data for AI requests
     */
    protected function prepareFirmData(Firm $firm, array $additionalData = []): array
    {
        $firm->load('profile');

        return array_merge([
            'firm_name' => $firm->name,
            'website' => $firm->website_url,
            'location' => [
                'city' => $firm->profile?->main_office_city,
                'state' => $firm->profile?->main_office_state,
            ],
            'practice_areas' => $firm->profile?->practice_areas_json ?? [],
        ], $additionalData);
    }
}
