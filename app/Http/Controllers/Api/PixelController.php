<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAttributionEvent;
use App\Models\FwPixelApiKey;
use App\Models\FwPixelEventRaw;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PixelController extends Controller
{
    /**
     * Collect pixel event from client browser
     */
    public function collect(Request $request): JsonResponse
    {
        // Validate minimal required fields
        $data = $request->validate([
            'firm_id'    => 'required|integer|exists:firms,id',
            'fwid'       => 'nullable|string|max:64',
            'url'        => 'required|string',
            'referrer'   => 'nullable|string',
            'utm'        => 'nullable|array',
            'gclid'      => 'nullable|string|max:255',
            'fbclid'     => 'nullable|string|max:255',
            'event_type' => 'nullable|string|max:64',
            'api_key'    => 'required|string'
        ]);

        // Validate API key belongs to firm_id and is active
        $apiKey = FwPixelApiKey::where('api_key', $data['api_key'])
            ->where('firm_id', $data['firm_id'])
            ->where('is_active', 1)
            ->first();

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        // Optional: Validate origin matches allowed domain
        $origin = $request->header('Origin') ?? $request->header('Referer');
        if ($apiKey->domain && $origin && !str_contains($origin, $apiKey->domain)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Origin mismatch'
            ], 403);
        }

        // Extract UTM params safely
        $utm = $data['utm'] ?? [];
        $utmSource = $utm['utm_source'] ?? null;
        $utmMedium = $utm['utm_medium'] ?? null;
        $utmCampaign = $utm['utm_campaign'] ?? null;

        // Get IP and User-Agent
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Store raw payload
        FwPixelEventRaw::create([
            'firm_id'      => $data['firm_id'],
            'fwid'         => $data['fwid'] ?? null,
            'payload_json' => $data,
            'ip_address'   => $ip,
            'user_agent'   => $userAgent,
        ]);

        // Apply simple bot filter
        if ($this->looksLikeBot($userAgent)) {
            return response()->json([
                'status' => 'ok',
                'ignored' => true
            ]);
        }

        // Insert normalized event into ai_attribution_events
        AiAttributionEvent::create([
            'firm_id'      => $data['firm_id'],
            'fwid'         => $data['fwid'] ?? null,
            'gclid'        => $data['gclid'] ?? null,
            'fbclid'       => $data['fbclid'] ?? null,
            'utm_source'   => $utmSource,
            'utm_medium'   => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'url'          => $data['url'],
            'referrer'     => $data['referrer'] ?? null,
            'user_agent'   => $userAgent,
            'ip_address'   => $ip,
            'event_type'   => $data['event_type'] ?? 'pageview',
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Simple bot detection helper
     */
    protected function looksLikeBot(?string $userAgent): bool
    {
        if (!$userAgent) {
            return true;
        }

        $botKeywords = [
            'bot',
            'crawl',
            'slurp',
            'spider',
            'curl',
            'wget',
            'python-requests',
            'AhrefsBot',
            'Googlebot',
            'crawler',
            'Bingbot',
            'SemrushBot',
            'PerplexityBot',
            'Twitterbot',
            'facebookexternalhit',
            'LinkedInBot',
            'Slackbot',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
        ];

        $ua = strtolower($userAgent);
        foreach ($botKeywords as $keyword) {
            if (str_contains($ua, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
