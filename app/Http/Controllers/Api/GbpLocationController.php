<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GbpLocation;
use App\Models\Integration;
use App\Services\GbpSyncService;
use App\Services\GooglePlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GbpLocationController extends Controller
{
    public function __construct(
        private GbpSyncService $gbpSyncService,
        private GooglePlacesService $placesService
    ) {}

    /**
     * Sync GBP location with Google Places API
     *
     * @OA\Get(
     *     path="/api/gbp/{locationId}/sync",
     *     summary="Sync GBP location with Google Places data",
     *     description="Converts GBP location_id to place_id and syncs full business data from Google Places API",
     *     operationId="syncGbpLocation",
     *     tags={"GBP Locations"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="GBP location ID (numeric string)",
     *         required=true,
     *         @OA\Schema(type="string", example="9393481967568809184")
     *     ),
     *     @OA\Parameter(
     *         name="firm_id",
     *         in="query",
     *         description="Firm ID for multi-tenant support",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully synced location data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="locationId", type="string", example="9393481967568809184"),
     *             @OA\Property(property="placeId", type="string", example="ChIJr7uyr1-3dYgRhHY8ZJTAiF0"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="website", type="string"),
     *                 @OA\Property(property="hours", type="object"),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="rating", type="number", example=4.6),
     *                 @OA\Property(property="reviews", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="photos", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="GBP integration not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function sync(Request $request, string $locationId): JsonResponse
    {
        // Validate firm_id
        $firmId = $request->query('firm_id');
        if (!$firmId) {
            return response()->json(['error' => 'firm_id is required'], 400);
        }

        try {
            // Step A: Find active GBP integration for this firm
            $integration = Integration::where('firm_id', $firmId)
                ->where('platform', 'google_gbp')
                ->where('status', 'active')
                ->first();

            if (!$integration) {
                return response()->json([
                    'error' => 'No active Google Business Profile integration found for this firm'
                ], 404);
            }

            // Step A: Convert GBP location_id to place_id
            $placeId = $this->gbpSyncService->getPlaceIdFromGbpLocation($integration, $locationId);

            // Step B: Fetch data from Google Places API
            $placeData = $this->placesService->getPlaceDetails($placeId);

            // Step C: Normalize and store data
            $normalizedData = $this->placesService->normalizePlaceData($placeData);

            $gbpLocation = GbpLocation::updateOrCreate(
                [
                    'firm_id' => $firmId,
                    'gbp_location_id' => $locationId,
                ],
                array_merge($normalizedData, [
                    'place_id' => $placeId,
                    'last_synced_at' => now(),
                    'status' => 'connected',
                ])
            );

            // Log the sync
            Log::info('GBP location synced successfully', [
                'firm_id' => $firmId,
                'location_id' => $locationId,
                'place_id' => $placeId,
            ]);

            // Step D: Return formatted response
            return response()->json([
                'locationId' => $locationId,
                'placeId' => $placeId,
                'data' => [
                    'name' => $gbpLocation->name,
                    'address' => $gbpLocation->formatted_address,
                    'phone' => $gbpLocation->formatted_phone_number,
                    'website' => $gbpLocation->website,
                    'hours' => $gbpLocation->opening_hours,
                    'categories' => $gbpLocation->types,
                    'rating' => $gbpLocation->rating,
                    'total_reviews' => $gbpLocation->user_ratings_total,
                    'reviews' => $gbpLocation->reviews,
                    'photos' => $gbpLocation->photos,
                ],
                'synced_at' => $gbpLocation->last_synced_at->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('GBP location sync failed', [
                'firm_id' => $firmId,
                'location_id' => $locationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
