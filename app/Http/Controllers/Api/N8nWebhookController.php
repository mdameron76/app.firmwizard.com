<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiFirmProfile;
use App\Models\AiFirmProfileVersion;
use App\Models\Firm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class N8nWebhookController extends Controller
{
    /**
     * Store initial profile from onboarding (called by wizard completion)
     */
    public function storeProfile(Request $request, Firm $firm): JsonResponse
    {
        // TODO: Add API key validation middleware
        
        $validated = $request->validate([
            'profile_data' => 'required|array',
            'source' => 'nullable|string',
        ]);

        // Deactivate existing profiles
        AiFirmProfile::where('firm_id', $firm->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create new profile
        $profile = AiFirmProfile::create([
            'firm_id' => $firm->id,
            'source' => $validated['source'] ?? 'n8n',
            'profile_json' => $validated['profile_data'],
            'version' => 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile stored successfully',
            'data' => [
                'profile_id' => $profile->id,
                'version' => $profile->version,
            ],
        ], 201);
    }

    /**
     * Update profile with collected data (called by n8n after data collection)
     */
    public function updateProfile(Request $request, Firm $firm): JsonResponse
    {
        // TODO: Add API key validation middleware
        
        $validated = $request->validate([
            'profile_data' => 'required|array',
            'merge_strategy' => 'nullable|string|in:replace,merge',
        ]);

        $activeProfile = $firm->activeAiFirmProfile;

        if (!$activeProfile) {
            return response()->json([
                'success' => false,
                'message' => 'No active profile found',
            ], 404);
        }

        // Save version history
        AiFirmProfileVersion::create([
            'firm_id' => $firm->id,
            'ai_firm_profile_id' => $activeProfile->id,
            'profile_json' => $activeProfile->profile_json,
        ]);

        // Merge or replace profile data
        $mergeStrategy = $validated['merge_strategy'] ?? 'merge';
        $newProfileData = $mergeStrategy === 'merge'
            ? array_merge($activeProfile->profile_json, $validated['profile_data'])
            : $validated['profile_data'];

        $activeProfile->update([
            'profile_json' => $newProfileData,
            'version' => $activeProfile->version + 1,
            'source' => 'collector',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'profile_id' => $activeProfile->id,
                'version' => $activeProfile->version,
            ],
        ]);
    }
}
