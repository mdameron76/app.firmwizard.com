<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IntegrationsOAuthController extends Controller
{
    /**
     * Generate Google OAuth URL and redirect
     */
    public function googleAuth(Request $request, string $platform)
    {
        $user = Auth::user();
        $firm = $user->firms()->first();

        if (!$firm) {
            return redirect()->route('integrations.index')->with('error', 'No firm associated with your account.');
        }

        // Validate platform
        if (!in_array($platform, ['google_gbp', 'google_ads', 'google_lsa', 'google_analytics'])) {
            return redirect()->route('integrations.index')->with('error', 'Invalid integration platform: ' . $platform);
        }

        // Define Google OAuth scopes based on platform
        $scopes = $this->getGoogleScopes($platform);

        // Generate state token for CSRF protection
        $state = Str::random(40);
        session(['oauth_state' => $state, 'oauth_platform' => $platform, 'oauth_firm_id' => $firm->id]);

        // Build OAuth URL
        $params = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('integrations.oauth.google.callback'),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(Request $request)
    {
        // Validate state token
        if (!$request->has('state') || $request->state !== session('oauth_state')) {
            return redirect()->route('integrations.index')->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            return redirect()->route('integrations.index')->with('error', 'OAuth authorization failed: ' . $request->error);
        }

        if (!$request->has('code')) {
            return redirect()->route('integrations.index')->with('error', 'No authorization code received.');
        }

        $platform = session('oauth_platform');
        $firmId = session('oauth_firm_id');

        // Exchange code for tokens
        $tokens = $this->exchangeCodeForTokens($request->code);

        if (!$tokens) {
            return redirect()->route('integrations.index')->with('error', 'Failed to exchange authorization code for tokens.');
        }

        // Create or update integration
        $integration = Integration::updateOrCreate(
            [
                'firm_id' => $firmId,
                'platform' => $platform,
            ],
            [
                'name' => $this->getPlatformName($platform),
                'display_name' => $this->getPlatformName($platform),
                'auth_type' => 'oauth2',
                'status' => 'active',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in']) ? now()->addSeconds($tokens['expires_in']) : null,
                'credentials' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                ],
            ]
        );

        // Clear session
        session()->forget(['oauth_state', 'oauth_platform', 'oauth_firm_id']);

        return redirect()->route('integrations.index')->with('success', $this->getPlatformName($platform) . ' connected successfully!');
    }

    /**
     * Exchange authorization code for access/refresh tokens
     */
    private function exchangeCodeForTokens(string $code): ?array
    {
        try {
            $http = \Illuminate\Support\Facades\Http::asForm();

            // Disable SSL verification for local development
            if (config('app.env') === 'local') {
                $http = $http->withOptions(['verify' => false]);
            }

            $response = $http->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('integrations.oauth.google.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Google OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            \Log::error('Google OAuth token exchange exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Google OAuth scopes for platform
     */
    private function getGoogleScopes(string $platform): array
    {
        $baseScopes = [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ];

        $platformScopes = [
            'google_gbp' => [
                'https://www.googleapis.com/auth/business.manage',
            ],
            'google_ads' => [
                'https://www.googleapis.com/auth/adwords',
            ],
            'google_lsa' => [
                'https://www.googleapis.com/auth/localservices',
            ],
            'google_analytics' => [
                'https://www.googleapis.com/auth/analytics.readonly',
            ],
        ];

        return array_merge($baseScopes, $platformScopes[$platform] ?? []);
    }

    /**
     * Get platform display name
     */
    private function getPlatformName(string $platform): string
    {
        $names = [
            'google_gbp' => 'Google Business Profile',
            'google_ads' => 'Google Ads',
            'google_lsa' => 'Google Local Services Ads',
            'google_analytics' => 'Google Analytics',
        ];

        return $names[$platform] ?? $platform;
    }
}
