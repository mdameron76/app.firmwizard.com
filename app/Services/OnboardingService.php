<?php

namespace App\Services;

use App\Models\AiFirmProfile;
use App\Models\Firm;
use App\Models\FirmOnboardingSession;
use App\Models\FirmProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    /**
     * Start or resume onboarding session for a firm
     */
    public function startOrResumeSession(Firm $firm, User $user): FirmOnboardingSession
    {
        // Check for existing incomplete session
        $session = $firm->onboardingSessions()
            ->where('is_completed', false)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            $session = FirmOnboardingSession::create([
                'firm_id' => $firm->id,
                'user_id' => $user->id,
                'current_step' => 1,
                'completed_steps_json' => [],
                'wizard_data_json' => [],
            ]);
        }

        return $session;
    }

    /**
     * Get current session for a firm
     */
    public function getCurrentSession(Firm $firm): ?FirmOnboardingSession
    {
        return $firm->currentOnboardingSession;
    }

    /**
     * Save wizard step data
     */
    public function saveStep(FirmOnboardingSession $session, int $step, array $data): FirmOnboardingSession
    {
        $wizardData = $session->wizard_data_json ?? [];
        $completedSteps = $session->completed_steps_json ?? [];

        // Save step data
        $wizardData["step_{$step}"] = $data;

        // Mark step as completed
        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
        }

        // Update current step to next step if not already ahead
        $nextStep = min($step + 1, 5);
        if ($session->current_step < $nextStep) {
            $session->current_step = $nextStep;
        }

        $session->update([
            'wizard_data_json' => $wizardData,
            'completed_steps_json' => $completedSteps,
            'current_step' => $session->current_step,
        ]);

        // If all 5 steps completed, finalize onboarding
        if (count($completedSteps) >= 5) {
            $this->finalizeOnboarding($session);
        }

        return $session->fresh();
    }

    /**
     * Finalize onboarding and create AI firm profile
     */
    protected function finalizeOnboarding(FirmOnboardingSession $session): void
    {
        DB::transaction(function () use ($session) {
            $wizardData = $session->wizard_data_json;

            // Build firm profile JSON from wizard data
            $profileData = $this->buildProfileFromWizardData($wizardData);

            // Update firm profile table
            FirmProfile::updateOrCreate(
                ['firm_id' => $session->firm_id],
                [
                    'main_office_city' => $wizardData['step_4']['primary_location']['city'] ?? null,
                    'main_office_state' => $wizardData['step_4']['primary_location']['state'] ?? null,
                    'main_office_zip' => $wizardData['step_4']['primary_location']['zip'] ?? null,
                    'practice_areas_json' => $wizardData['step_2']['practice_areas'] ?? [],
                    'brand_voice_json' => $wizardData['step_1'] ?? [],
                    'target_demographics_json' => $wizardData['step_3'] ?? [],
                    'service_areas_json' => $wizardData['step_4'] ?? [],
                    'marketing_goals_json' => $wizardData['step_5'] ?? [],
                    'onboarding_completed_at' => now(),
                ]
            );

            // Deactivate any existing active profiles
            AiFirmProfile::where('firm_id', $session->firm_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create AI firm profile from onboarding data
            $aiFirmProfile = AiFirmProfile::create([
                'firm_id' => $session->firm_id,
                'source' => 'onboarding',
                'profile_json' => $profileData,
                'version' => 1,
                'is_active' => true,
            ]);

            // Mark session as completed
            $session->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // TODO: Trigger n8n webhook to start data collection
            // This would call POST /api/n8n/firms/{firmId}/profile
        });
    }

    /**
     * Build normalized profile JSON from wizard data
     */
    protected function buildProfileFromWizardData(array $wizardData): array
    {
        $step1 = $wizardData['step_1'] ?? [];
        $step2 = $wizardData['step_2'] ?? [];
        $step3 = $wizardData['step_3'] ?? [];
        $step4 = $wizardData['step_4'] ?? [];
        $step5 = $wizardData['step_5'] ?? [];

        return [
            'brand_voice' => [
                'tone' => $step1['tone'] ?? '',
                'style' => $step1['style'] ?? '',
                'values' => $step1['values'] ?? [],
                'messaging' => $step1['messaging'] ?? '',
            ],
            'practice_areas' => $step2['practice_areas'] ?? [],
            'target_demographics' => [
                'primary' => $step3['primary'] ?? '',
                'age_range' => $step3['age_range'] ?? '',
                'income_level' => $step3['income_level'] ?? '',
                'pain_points' => $step3['pain_points'] ?? [],
                'demographics' => $step3['demographics'] ?? '',
                'psychographics' => $step3['psychographics'] ?? [],
                'situational_triggers' => $step3['situational_triggers'] ?? [],
            ],
            'service_areas' => [
                'primary_location' => $step4['primary_location'] ?? [],
                'counties' => $step4['counties'] ?? [],
                'radius_miles' => $step4['radius_miles'] ?? 20,
                'zip_codes' => $step4['zip_codes'] ?? [],
                'nearby_cities' => $step4['nearby_cities'] ?? [],
            ],
            'marketing_goals' => [
                'monthly_budget_range' => $step5['monthly_budget_range'] ?? '',
                'primary_goals_ranked' => $step5['primary_goals_ranked'] ?? [],
                'lead_volume_estimate' => $step5['lead_volume_estimate'] ?? [],
            ],
            'created_at' => now()->toIso8601String(),
            'source' => 'onboarding_wizard',
        ];
    }

    /**
     * Validate step data
     */
    public function validateStepData(int $step, array $data): array
    {
        $rules = match ($step) {
            1 => [
                'tone' => 'required|string|max:255',
                'style' => 'required|string|max:255',
                'values' => 'required|array|min:1|max:5',
                'messaging' => 'required|string|max:500',
            ],
            2 => [
                'practice_areas' => 'required|array|min:1',
                'practice_areas.*' => 'string|max:255',
            ],
            3 => [
                'primary' => 'required|string|max:500',
                'age_range' => 'nullable|string|max:50',
                'income_level' => 'nullable|string|max:255',
                'pain_points' => 'nullable|array',
                'demographics' => 'nullable|string|max:1000',
                'psychographics' => 'nullable|array',
                'situational_triggers' => 'nullable|array',
            ],
            4 => [
                'primary_location' => 'required|array',
                'primary_location.city' => 'required|string|max:255',
                'primary_location.state' => 'required|string|max:2',
                'primary_location.zip' => 'required|string|max:10',
                'radius_miles' => 'nullable|integer|min:5|max:100',
                'counties' => 'nullable|array',
                'nearby_cities' => 'nullable|array',
                'zip_codes' => 'nullable|array',
            ],
            5 => [
                'monthly_budget_range' => 'required|string|max:50',
                'primary_goals_ranked' => 'required|array|min:1|max:5',
                'lead_volume_estimate' => 'nullable|array',
            ],
            default => [],
        };

        return $rules;
    }
}
