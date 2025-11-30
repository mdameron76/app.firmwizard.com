<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\GbpSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationsController extends Controller
{
    /**
     * Display integrations page
     */
    public function index()
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return redirect()->route('dashboard')->with('error', 'No firm associated with your account.');
        }

        // Get all integrations for this firm
        $integrations = Integration::where('firm_id', $firm->id)->get();

        // Define available integration types
        $availableIntegrations = [
            'google_gbp' => [
                'title' => 'Google Business Profile',
                'description' => 'Sync insights, posts, and reviews from your GBP listing',
                'icon' => '📍',
                'auth_type' => 'oauth2',
                'status' => 'not_connected',
            ],
            'google_ads' => [
                'title' => 'Google Ads',
                'description' => 'Track campaigns, keywords, and ad performance',
                'icon' => '💰',
                'auth_type' => 'oauth2',
                'status' => 'not_connected',
            ],
            'google_lsa' => [
                'title' => 'Google Local Services Ads',
                'description' => 'Monitor LSA leads and rankings',
                'icon' => '🔧',
                'auth_type' => 'oauth2',
                'status' => 'not_connected',
            ],
            'google_analytics' => [
                'title' => 'Google Analytics',
                'description' => 'Connect GA4 for website traffic insights',
                'icon' => '📊',
                'auth_type' => 'oauth2',
                'status' => 'not_connected',
            ],
            'facebook_meta' => [
                'title' => 'Meta (Facebook & Instagram)',
                'description' => 'Manage Facebook Pages, Ads, and Instagram',
                'icon' => '📱',
                'auth_type' => 'oauth2',
                'status' => 'not_connected',
            ],
            'wordpress' => [
                'title' => 'WordPress',
                'description' => 'Connect your WordPress site for content publishing',
                'icon' => '📝',
                'auth_type' => 'api_key',
                'status' => 'not_connected',
            ],
        ];

        // Merge with existing integrations
        foreach ($integrations as $integration) {
            if (isset($availableIntegrations[$integration->platform])) {
                $availableIntegrations[$integration->platform]['status'] = $integration->status;
                $availableIntegrations[$integration->platform]['integration'] = $integration;
                $availableIntegrations[$integration->platform]['last_synced_at'] = $integration->last_synced_at;
            }
        }

        return view('integrations.index', compact('availableIntegrations', 'firm'));
    }

    /**
     * Manually trigger sync for an integration
     */
    public function sync(Request $request, int $id)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return response()->json(['success' => false, 'message' => 'No firm associated with your account.'], 403);
        }

        $integration = Integration::where('id', $id)
            ->where('firm_id', $firm->id)
            ->first();

        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found.'], 404);
        }

        // Route to appropriate sync service based on platform
        $result = match ($integration->platform) {
            'google_gbp' => app(GbpSyncService::class)->syncInsights($integration),
            default => ['success' => false, 'message' => 'Sync not implemented for this platform yet.'],
        };

        return response()->json($result);
    }

    /**
     * List active integrations (for n8n/external services)
     *
     * @OA\Get(
     *     path="/api/n8n/integrations/active",
     *     summary="List active integrations for a firm",
     *     description="Returns all active integrations for a specific firm. Requires API key authentication.",
     *     operationId="listActiveIntegrations",
     *     tags={"N8N Integrations"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Parameter(
     *         name="X-API-Key",
     *         in="header",
     *         description="N8N API Key for authentication",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="api_key",
     *         in="query",
     *         description="N8N API Key for authentication (alternative to header)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="firm_id",
     *         in="query",
     *         description="The ID of the firm to retrieve integrations for",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with list of active integrations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="firm_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="integrations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="platform", type="string", example="google_gbp"),
     *                     @OA\Property(property="name", type="string", example="Google Business Profile"),
     *                     @OA\Property(property="display_name", type="string", example="Google Business Profile"),
     *                     @OA\Property(property="auth_type", type="string", example="oauth2"),
     *                     @OA\Property(property="last_synced_at", type="string", format="date-time", example="2025-11-30T12:00:00+00:00", nullable=true),
     *                     @OA\Property(property="last_sync_status", type="string", example="success", nullable=true)
     *                 )
     *             ),
     *             @OA\Property(property="count", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - firm_id is required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="firm_id is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing API key",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function listActive(Request $request)
    {
        // Validate API key (you may want to implement API key authentication here)
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey || $apiKey !== config('app.n8n_api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get firm_id from request
        $firmId = $request->query('firm_id');

        if (!$firmId) {
            return response()->json(['error' => 'firm_id is required'], 400);
        }

        // Get all active integrations for the firm
        $integrations = Integration::where('firm_id', $firmId)
            ->where('status', 'active')
            ->get()
            ->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'platform' => $integration->platform,
                    'name' => $integration->name,
                    'display_name' => $integration->display_name,
                    'auth_type' => $integration->auth_type,
                    'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
                    'last_sync_status' => $integration->last_sync_status,
                ];
            });

        return response()->json([
            'firm_id' => (int) $firmId,
            'integrations' => $integrations,
            'count' => $integrations->count(),
        ]);
    }
}
