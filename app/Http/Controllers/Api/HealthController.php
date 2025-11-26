<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring and n8n workflows
     */
    public function check(): JsonResponse
    {
        $healthy = true;
        $checks = [];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $healthy = false;
        }

        // Check cache
        try {
            cache()->put('health_check', true, 10);
            $checks['cache'] = cache()->get('health_check') ? 'ok' : 'error';
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
        ], $healthy ? 200 : 503);
    }
}
