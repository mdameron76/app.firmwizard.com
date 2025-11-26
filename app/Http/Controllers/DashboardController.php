<?php

namespace App\Http\Controllers;

use App\Models\Firm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard
     */
    public function index(Request $request): View
    {
        $firm = $this->getCurrentFirm($request);
        
        if (!$firm) {
            return view('dashboard.no-firm');
        }

        $firm->load('profile', 'currentOnboardingSession', 'activeAiFirmProfile');

        // Check if onboarding is complete
        $onboardingComplete = $firm->profile?->onboarding_completed_at !== null;

        // Get latest metrics
        $metrics = [
            'leads_monthly' => $firm->metricsMonthly()
                ->where('metric_type', 'leads_monthly')
                ->where('month', now()->format('Y-m'))
                ->first(),
            'visibility_score' => $firm->metricsDaily()
                ->where('metric_type', 'visibility_score')
                ->latest('date')
                ->first(),
        ];

        // Get recent AI jobs
        $recentJobs = $firm->aiJobs()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'firm' => $firm,
            'onboardingComplete' => $onboardingComplete,
            'metrics' => $metrics,
            'recentJobs' => $recentJobs,
        ]);
    }

    /**
     * Get current firm for user
     */
    protected function getCurrentFirm(Request $request): ?Firm
    {
        $firmId = session('current_firm_id');
        
        if ($firmId) {
            return $request->user()->firms()->find($firmId);
        }

        $firm = $request->user()->firms()->first();
        
        if ($firm) {
            session(['current_firm_id' => $firm->id]);
        }

        return $firm;
    }
}
