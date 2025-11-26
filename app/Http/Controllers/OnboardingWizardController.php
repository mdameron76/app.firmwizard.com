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
     * 
     * @OA\Get(
     *     path="/onboarding",
     *     summary="Get onboarding wizard",
     *     tags={"Onboarding"},
     *     @OA\Response(
     *         response=200,
     *         description="Display onboarding wizard"
     *     )
     * )
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
     * 
     * @OA\Get(
     *     path="/onboarding/step/{step}",
     *     summary="Get specific wizard step",
     *     tags={"Onboarding"},
     *     @OA\Parameter(
     *         name="step",
     *         in="path",
     *         required=true,
     *         description="Step number (1-5)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Display wizard step"
     *     )
     * )
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
     * Store wizard step data
     */
    public function store(Request $request, int $step)
    {
        $firm = $this->getCurrentFirm($request);
        
        if (!$firm) {
            return redirect()->route('dashboard')->with('error', 'No firm found.');
        }

        // Get or create session
        $session = $this->onboardingService->getCurrentSession($firm);
        
        if (!$session) {
            $session = $this->onboardingService->startOrResumeSession($firm, $request->user());
        }

        // Save step data
        $this->onboardingService->saveStep($session, $step, $request->all());

        // Redirect to next step or finalize
        if ($step < 5) {
            return redirect()->route('onboarding.step', $step + 1)
                ->with('success', 'Step ' . $step . ' saved successfully!');
        }

        // Finalize onboarding
        $this->onboardingService->finalizeOnboarding($session);

        return redirect()->route('dashboard')
            ->with('success', 'Onboarding completed! Your AI profile is being generated.');
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
