<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiJob;
use App\Models\AiJob;
use App\Models\Firm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiJobController extends Controller
{
    /**
     * Display a listing of AI jobs for a firm
     */
    public function index(Firm $firm): JsonResponse
    {
        $jobs = $firm->aiJobs()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Create a new AI job
     */
    public function store(Request $request, Firm $firm): JsonResponse
    {
        $validated = $request->validate([
            'job_type' => 'required|string|in:blog_post,competitor_analysis,website_analysis,gbp_analysis,google_ads,lsa_ads,meta_ads,backlinks,analytics,firm_profile',
            'input_json' => 'nullable|array',
        ]);

        $job = AiJob::create([
            'firm_id' => $firm->id,
            'job_type' => $validated['job_type'],
            'input_json' => $validated['input_json'] ?? [],
            'status' => 'pending',
        ]);

        // Dispatch job to queue for processing
        ProcessAiJob::dispatch($job);

        return response()->json([
            'success' => true,
            'message' => 'AI job created successfully',
            'data' => $job,
        ], 201);
    }

    /**
     * Display the specified AI job
     */
    public function show(Firm $firm, AiJob $aiJob): JsonResponse
    {
        if ($aiJob->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $aiJob,
        ]);
    }

    /**
     * Update the specified AI job
     */
    public function update(Request $request, Firm $firm, AiJob $aiJob): JsonResponse
    {
        if ($aiJob->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,running,completed,failed',
            'output_json' => 'nullable|array',
            'error_message' => 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'running' && !$aiJob->started_at) {
            $validated['started_at'] = now();
        }

        if (isset($validated['status']) && in_array($validated['status'], ['completed', 'failed'])) {
            $validated['finished_at'] = now();
        }

        $aiJob->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully',
            'data' => $aiJob->fresh(),
        ]);
    }

    /**
     * Remove the specified AI job
     */
    public function destroy(Firm $firm, AiJob $aiJob): JsonResponse
    {
        if ($aiJob->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        $aiJob->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully',
        ]);
    }
}
