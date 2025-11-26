<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use Illuminate\Http\JsonResponse;

class FirmProfileController extends Controller
{
    /**
     * Get the active AI firm profile for a firm
     */
    public function show(Firm $firm): JsonResponse
    {
        $profile = $firm->activeAiFirmProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'No active profile found. Please complete onboarding.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile_id' => $profile->id,
                'source' => $profile->source,
                'version' => $profile->version,
                'profile' => $profile->profile_json,
                'updated_at' => $profile->updated_at,
            ],
        ]);
    }
}
