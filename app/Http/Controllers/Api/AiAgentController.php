<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiJob;
use App\Jobs\ProcessAiJob;

class AiAgentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/ai-agents",
     *     summary="Get available AI agents",
     *     tags={"AI Agents"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of available AI agents"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $firm = $request->user()->firms()->first();
        
        if (!$firm) {
            return response()->json([
                'success' => false,
                'message' => 'No firm found for this user.'
            ], 404);
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

        return response()->json([
            'success' => true,
            'firm' => [
                'id' => $firm->id,
                'name' => $firm->name,
            ],
            'available_agents' => $availableAgents,
            'recent_jobs' => $recentJobs,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/ai-agents/trigger/{agentType}",
     *     summary="Trigger an AI agent",
     *     tags={"AI Agents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="agentType",
     *         in="path",
     *         required=true,
     *         description="Type of AI agent to trigger",
     *         @OA\Schema(
     *             type="string",
     *             enum={"blog_post", "competitor_analysis", "website_analysis", "gbp_analysis", "google_ads", "lsa_ads", "meta_ads", "backlinks", "analytics", "firm_profile"}
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="input_data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Agent triggered successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid agent type"
     *     )
     * )
     */
    public function trigger(Request $request, $agentType)
    {
        $firm = $request->user()->firms()->first();
        
        if (!$firm) {
            return response()->json([
                'success' => false,
                'message' => 'No firm found for this user.'
            ], 404);
        }

        // Validate agent type
        $validTypes = ['blog_post', 'competitor_analysis', 'website_analysis', 'gbp_analysis', 
                       'google_ads', 'lsa_ads', 'meta_ads', 'backlinks', 'analytics', 'firm_profile'];
        
        if (!in_array($agentType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid agent type.'
            ], 400);
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

        return response()->json([
            'success' => true,
            'message' => 'AI agent job has been queued!',
            'job' => $job,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/ai-agents/jobs/{job}",
     *     summary="Get AI job results",
     *     tags={"AI Agents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="job",
     *         in="path",
     *         required=true,
     *         description="AI Job ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job details and results"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function show(Request $request, AiJob $job)
    {
        $firm = $request->user()->firms()->first();
        
        if (!$firm || $job->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found or access denied.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'job' => $job,
        ]);
    }
}
