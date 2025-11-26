<?php

namespace App\Http\Controllers;

use App\Models\Firm;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingWizardController extends Controller
{
    public function __construct(
        protected OnboardingService $onboardingService
    ) {}

    /**
     * Show onboarding wizard landing/current step
     */
    public function index(Request $request): View
    {
        $firm = $this->getCurrentFirm($request);
        
        if (!$firm) {
            return view('onboarding.no-firm');
        }

        $session = $this->onboardingService->getCurrentSession($firm);
        
        if (!$session) {
            $session = $this->onboardingService->startOrResumeSession($firm, $request->user());
        }

        // Redirect to current step
        return view('onboarding.index', [
            'firm' => $firm,
            'session' => $session,
            'currentStep' => $session->current_step,
        ]);
    }

    /**
     * Show specific wizard step
     */
    public function show(Request $request, int $step): View
    {
        $firm = $this->getCurrentFirm($request);
        
        if (!$firm) {
            return view('onboarding.no-firm');
        }

        $session = $this->onboardingService->getCurrentSession($firm);
        
        if (!$session) {
            $session = $this->onboardingService->startOrResumeSession($firm, $request->user());
        }

        return view("onboarding.steps.step{$step}", [
            'firm' => $firm,
            'session' => $session,
            'step' => $step,
            'wizardData' => $session->wizard_data_json ?? [],
        ]);
    }

    /**
     * Get current firm for user
     */
    protected function getCurrentFirm(Request $request): ?Firm
    {
        // Check if firm_id is in session
        $firmId = session('current_firm_id');
        
        if ($firmId) {
            return $request->user()->firms()->find($firmId);
        }

        // Get first firm for user
        $firm = $request->user()->firms()->first();
        
        if ($firm) {
            session(['current_firm_id' => $firm->id]);
        }

        return $firm;
    }
}
