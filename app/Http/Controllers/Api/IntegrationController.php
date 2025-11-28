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
     * @OA\Get(
     *     path="/api/integrations/platforms",
     *     summary="Get available integration platforms",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of available platforms"
     *     )
     * )
     */
    public function platforms(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'platforms' => IntegrationManager::getAvailablePlatforms(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/integrations",
     *     summary="List all integrations for current firm",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of integrations"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/integrations",
     *     summary="Create a new integration",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"platform","name","auth_type","credentials"},
     *             @OA\Property(property="platform", type="string", example="wordpress"),
     *             @OA\Property(property="name", type="string", example="Main WordPress Site"),
     *             @OA\Property(property="auth_type", type="string", enum={"bearer_token","oauth2","jwt","openid"}),
     *             @OA\Property(property="credentials", type="object"),
     *             @OA\Property(property="config", type="object")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Integration created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
     * @OA\Get(
     *     path="/api/integrations/{integration}",
     *     summary="Get a single integration",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="integration",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Integration details"),
     *     @OA\Response(response=404, description="Integration not found")
     * )
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
     * @OA\Put(
     *     path="/api/integrations/{integration}",
     *     summary="Update an integration",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="integration",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="credentials", type="object"),
     *             @OA\Property(property="config", type="object"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive","error"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Integration updated"),
     *     @OA\Response(response=404, description="Integration not found")
     * )
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
     * @OA\Delete(
     *     path="/api/integrations/{integration}",
     *     summary="Delete an integration",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="integration",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Integration deleted"),
     *     @OA\Response(response=404, description="Integration not found")
     * )
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
     * @OA\Post(
     *     path="/api/integrations/{integration}/test",
     *     summary="Test integration connection",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="integration",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Connection test result"),
     *     @OA\Response(response=404, description="Integration not found")
     * )
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
     * @OA\Get(
     *     path="/api/integrations/auth-fields/{authType}",
     *     summary="Get auth fields for a specific auth type",
     *     tags={"Integrations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="authType",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", enum={"bearer_token","oauth2","jwt","openid"})
     *     ),
     *     @OA\Response(response=200, description="Auth fields"),
     *     @OA\Response(response=400, description="Invalid auth type")
     * )
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
