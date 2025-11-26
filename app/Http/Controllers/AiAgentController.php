<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiJob;
use App\Jobs\ProcessAiJob;
use Illuminate\Support\Facades\Auth;

class AiAgentController extends Controller
{
    /**
     * Display the AI Agents Hub
     */
    public function index()
    {
        $user = Auth::user();
        $firm = $user->firms()->with('activeAiFirmProfile')->first();
        
        if (!$firm) {
            return redirect()->route('dashboard')->with('error', 'No firm found for this user.');
        }

        // Get recent AI jobs for this firm
        $recentJobs = AiJob::where('firm_id', $firm->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $availableAgents = [
            'blog_post' => [
                'name' => 'Blog Post Topics',
                'description' => 'Generate relevant blog post ideas based on your practice areas',
                'icon' => '📝',
            ],
            'competitor_analysis' => [
                'name' => 'Competitor Analysis',
                'description' => 'Analyze your competitors and identify opportunities',
                'icon' => '🔍',
            ],
            'website_analysis' => [
                'name' => 'Website Analysis',
                'description' => 'Get insights on your website performance and SEO',
                'icon' => '🌐',
            ],
            'gbp_analysis' => [
                'name' => 'Google Business Profile',
                'description' => 'Optimize your Google Business Profile listing',
                'icon' => '📍',
            ],
            'google_ads' => [
                'name' => 'Google Ads Strategy',
                'description' => 'Get recommendations for Google Ads campaigns',
                'icon' => '💰',
            ],
            'lsa_ads' => [
                'name' => 'Local Services Ads',
                'description' => 'Optimize your Local Services Ads performance',
                'icon' => '🎯',
            ],
            'meta_ads' => [
                'name' => 'Meta Ads Strategy',
                'description' => 'Get Facebook and Instagram ad recommendations',
                'icon' => '📱',
            ],
            'backlinks' => [
                'name' => 'Backlink Opportunities',
                'description' => 'Find valuable backlink opportunities for SEO',
                'icon' => '🔗',
            ],
            'analytics' => [
                'name' => 'Analytics Insights',
                'description' => 'Analyze your website traffic and user behavior',
                'icon' => '📊',
            ],
            'firm_profile' => [
                'name' => 'Firm Profile Review',
                'description' => 'Review and optimize your firm profile',
                'icon' => '🏢',
            ],
        ];

        return view('ai-agents.index', compact('firm', 'recentJobs', 'availableAgents'));
    }

    /**
     * Trigger an AI agent job
     */
    public function trigger(Request $request, $agentType)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();
        
        if (!$firm) {
            return redirect()->back()->with('error', 'No firm found for this user.');
        }

        // Validate agent type
        $validTypes = ['blog_post', 'competitor_analysis', 'website_analysis', 'gbp_analysis', 
                       'google_ads', 'lsa_ads', 'meta_ads', 'backlinks', 'analytics', 'firm_profile'];
        
        if (!in_array($agentType, $validTypes)) {
            return redirect()->back()->with('error', 'Invalid agent type.');
        }

        // Create AI job
        $job = AiJob::create([
            'firm_id' => $firm->id,
            'job_type' => $agentType,
            'status' => 'pending',
            'input_data' => $request->input('input_data', []),
        ]);

        // Dispatch to queue
        ProcessAiJob::dispatch($job);

        return redirect()->route('ai-agents.index')->with('success', 'AI agent job has been queued!');
    }

    /**
     * Show AI job results
     */
    public function show(AiJob $job)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();
        
        if (!$firm || $job->firm_id !== $firm->id) {
            abort(403);
        }

        return view('ai-agents.show', compact('job'));
    }
}
