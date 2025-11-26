<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    public function __construct(
        protected OnboardingService $onboardingService
    ) {}

    /**
     * Start or resume onboarding session
     */
    public function start(Request $request, Firm $firm): JsonResponse
    {
        $session = $this->onboardingService->startOrResumeSession($firm, $request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'current_step' => $session->current_step,
                'completed_steps' => $session->completed_steps_json,
                'wizard_data' => $session->wizard_data_json,
            ],
        ]);
    }

    /**
     * Get current onboarding session
     */
    public function current(Firm $firm): JsonResponse
    {
        $session = $this->onboardingService->getCurrentSession($firm);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active onboarding session found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'current_step' => $session->current_step,
                'completed_steps' => $session->completed_steps_json,
                'wizard_data' => $session->wizard_data_json,
                'is_completed' => $session->is_completed,
                'completed_at' => $session->completed_at,
            ],
        ]);
    }

    /**
     * Save wizard step data
     */
    public function saveStep(Request $request, Firm $firm, int $step): JsonResponse
    {
        if ($step < 1 || $step > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid step number. Must be between 1 and 5.',
            ], 400);
        }

        // Get or create session
        $session = $this->onboardingService->getCurrentSession($firm);
        
        if (!$session) {
            $session = $this->onboardingService->startOrResumeSession($firm, $request->user());
        }

        // Validate step data
        $rules = $this->onboardingService->validateStepData($step, $request->all());
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Save step
        $session = $this->onboardingService->saveStep($session, $step, $request->all());

        return response()->json([
            'success' => true,
            'message' => "Step {$step} saved successfully",
            'data' => [
                'session_id' => $session->id,
                'current_step' => $session->current_step,
                'completed_steps' => $session->completed_steps_json,
                'is_completed' => $session->is_completed,
            ],
        ]);
    }
}
