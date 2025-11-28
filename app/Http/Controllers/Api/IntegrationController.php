<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\Integration\IntegrationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    protected $manager;

    public function __construct(IntegrationManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get available integration platforms
     */
    public function platforms(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'platforms' => IntegrationManager::getAvailablePlatforms(),
        ]);
    }

    /**
     * List all integrations for a firm
     */
    public function index(Request $request): JsonResponse
    {
        $firm = $request->user()->firms()->first();

        if (!$firm) {
            return response()->json([
                'success' => false,
                'message' => 'No firm found',
            ], 404);
        }

        $integrations = $firm->integrations()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'integrations' => $integrations,
        ]);
    }

    /**
     * Create a new integration
     */
    public function store(Request $request): JsonResponse
    {
        $firm = $request->user()->firms()->first();

        if (!$firm) {
            return response()->json([
                'success' => false,
                'message' => 'No firm found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'platform' => 'required|string',
            'name' => 'required|string|max:255',
            'auth_type' => 'required|string|in:bearer_token,oauth2,jwt,openid',
            'credentials' => 'required|array',
            'config' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $integration = $this->manager->create($firm->id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Integration created successfully',
                'integration' => $integration,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single integration
     */
    public function show(Request $request, Integration $integration): JsonResponse
    {
        $firm = $request->user()->firms()->first();

        if (!$firm || $integration->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Integration not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'integration' => $integration,
        ]);
    }

    /**
     * Update an integration
     */
    public function update(Request $request, Integration $integration): JsonResponse
    {
        $firm = $request->user()->firms()->first();

        if (!$firm || $integration->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Integration not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'credentials' => 'sometimes|array',
            'config' => 'sometimes|array',
            'status' => 'sometimes|string|in:active,inactive,error',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $integration = $this->manager->update($integration, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Integration updated successfully',
                'integration' => $integration,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an integration
     */
    public function destroy(Request $request, Integration $integration): JsonResponse
    {
        $firm = $request->user()->firms()->first();

        if (!$firm || $integration->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Integration not found',
            ], 404);
        }

        $integration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Integration deleted successfully',
        ]);
    }

    /**
     * Test integration connection
     */
    public function test(Request $request, Integration $integration): JsonResponse
    {
        $firm = $request->user()->firms()->first();

        if (!$firm || $integration->firm_id !== $firm->id) {
            return response()->json([
                'success' => false,
                'message' => 'Integration not found',
            ], 404);
        }

        $result = $this->manager->testConnection($integration);

        if ($result['success']) {
            $integration->updateSyncTime();
        }

        return response()->json($result);
    }

    /**
     * Get auth fields for a specific auth type
     */
    public function authFields(Request $request, string $authType): JsonResponse
    {
        try {
            $handler = IntegrationManager::getAuthHandler($authType);
            
            return response()->json([
                'success' => true,
                'fields' => $handler->getRequiredFields(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
