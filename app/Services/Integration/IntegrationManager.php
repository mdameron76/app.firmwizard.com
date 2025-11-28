<?php

namespace App\Services\Integration;

use App\Models\Integration;
use App\Services\Integration\AuthHandlers\BearerTokenAuth;
use App\Services\Integration\AuthHandlers\JwtAuth;
use App\Services\Integration\AuthHandlers\OAuth2Auth;
use App\Services\Integration\AuthHandlers\OpenIdAuth;

class IntegrationManager
{
    /**
     * Get available integration platforms
     */
    public static function getAvailablePlatforms(): array
    {
        return [
            'wordpress' => [
                'name' => 'WordPress',
                'description' => 'Connect to WordPress via REST API',
                'icon' => '📝',
                'auth_types' => ['bearer_token', 'jwt'],
                'default_auth' => 'bearer_token',
            ],
            'google_ads' => [
                'name' => 'Google Ads',
                'description' => 'Connect to Google Ads API',
                'icon' => '💰',
                'auth_types' => ['oauth2'],
                'default_auth' => 'oauth2',
            ],
            'google_business' => [
                'name' => 'Google Business Profile',
                'description' => 'Connect to Google My Business API',
                'icon' => '📍',
                'auth_types' => ['oauth2'],
                'default_auth' => 'oauth2',
            ],
            'meta_ads' => [
                'name' => 'Meta Ads',
                'description' => 'Connect to Facebook & Instagram Ads',
                'icon' => '📱',
                'auth_types' => ['bearer_token', 'oauth2'],
                'default_auth' => 'oauth2',
            ],
            'google_analytics' => [
                'name' => 'Google Analytics',
                'description' => 'Connect to Google Analytics 4',
                'icon' => '📊',
                'auth_types' => ['oauth2'],
                'default_auth' => 'oauth2',
            ],
        ];
    }

    /**
     * Get auth handler for given type
     */
    public static function getAuthHandler(string $authType)
    {
        return match ($authType) {
            'bearer_token' => new BearerTokenAuth(),
            'oauth2' => new OAuth2Auth(),
            'jwt' => new JwtAuth(),
            'openid' => new OpenIdAuth(),
            default => throw new \InvalidArgumentException("Unknown auth type: {$authType}"),
        };
    }

    /**
     * Create a new integration
     */
    public function create(int $firmId, array $data): Integration
    {
        $handler = self::getAuthHandler($data['auth_type']);
        
        $credentials = $handler->authenticate($data['credentials'] ?? []);

        return Integration::create([
            'firm_id' => $firmId,
            'platform' => $data['platform'],
            'name' => $data['name'],
            'auth_type' => $data['auth_type'],
            'credentials' => $credentials,
            'config' => $data['config'] ?? [],
            'status' => 'active',
        ]);
    }

    /**
     * Update an integration
     */
    public function update(Integration $integration, array $data): Integration
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['config'])) {
            $updateData['config'] = $data['config'];
        }

        if (isset($data['credentials'])) {
            $handler = self::getAuthHandler($integration->auth_type);
            $updateData['credentials'] = $handler->authenticate($data['credentials']);
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $integration->update($updateData);

        return $integration->fresh();
    }

    /**
     * Test integration connection
     */
    public function testConnection(Integration $integration): array
    {
        try {
            $handler = self::getAuthHandler($integration->auth_type);
            
            if (!$handler->validate($integration->credentials)) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                ];
            }

            // Platform-specific connection test
            $tester = $this->getPlatformTester($integration->platform);
            
            if ($tester) {
                return $tester->test($integration);
            }

            return [
                'success' => true,
                'message' => 'Credentials are valid',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get platform-specific connection tester
     */
    protected function getPlatformTester(string $platform): ?object
    {
        // Return platform-specific testers when implemented
        return null;
    }

    /**
     * Refresh integration credentials if needed
     */
    public function refreshCredentials(Integration $integration): bool
    {
        try {
            $handler = self::getAuthHandler($integration->auth_type);
            
            $newCredentials = $handler->refresh($integration->credentials);
            
            if ($newCredentials) {
                $integration->update(['credentials' => $newCredentials]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error("Failed to refresh credentials for integration {$integration->id}: " . $e->getMessage());
            return false;
        }
    }
}
