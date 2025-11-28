<?php

namespace App\Services\Integration\AuthHandlers;

use Illuminate\Support\Facades\Http;

class OAuth2Auth extends BaseAuthHandler
{
    /**
     * Authenticate using OAuth2
     */
    public function authenticate(array $input): array
    {
        // This will be called after OAuth callback with authorization code
        if (isset($input['code'])) {
            return $this->exchangeCodeForToken($input);
        }

        // Return authorization URL for redirect
        return [
            'auth_url' => $this->getAuthorizationUrl($input),
        ];
    }

    /**
     * Exchange authorization code for access token
     */
    protected function exchangeCodeForToken(array $input): array
    {
        $response = Http::asForm()->post($input['token_url'], [
            'grant_type' => 'authorization_code',
            'code' => $input['code'],
            'client_id' => $input['client_id'],
            'client_secret' => $input['client_secret'],
            'redirect_uri' => $input['redirect_uri'],
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to exchange authorization code for token');
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
            'token_type' => $data['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * Get OAuth2 authorization URL
     */
    protected function getAuthorizationUrl(array $input): string
    {
        $params = http_build_query([
            'client_id' => $input['client_id'],
            'redirect_uri' => $input['redirect_uri'],
            'response_type' => 'code',
            'scope' => $input['scope'] ?? '',
            'state' => $input['state'] ?? bin2hex(random_bytes(16)),
        ]);

        return $input['auth_url'] . '?' . $params;
    }

    /**
     * Refresh OAuth2 token
     */
    public function refresh(array $credentials): ?array
    {
        if (empty($credentials['refresh_token'])) {
            return null;
        }

        $response = Http::asForm()->post($credentials['token_url'], [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials['refresh_token'],
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $credentials['refresh_token'],
            'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
            'token_type' => $data['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * Validate OAuth2 credentials
     */
    public function validate(array $credentials): bool
    {
        return !empty($credentials['access_token']);
    }

    /**
     * Get required fields
     */
    public function getRequiredFields(): array
    {
        return [
            [
                'name' => 'client_id',
                'label' => 'Client ID',
                'type' => 'text',
                'placeholder' => 'Enter OAuth2 client ID',
                'required' => true,
            ],
            [
                'name' => 'client_secret',
                'label' => 'Client Secret',
                'type' => 'password',
                'placeholder' => 'Enter OAuth2 client secret',
                'required' => true,
            ],
            [
                'name' => 'auth_url',
                'label' => 'Authorization URL',
                'type' => 'text',
                'placeholder' => 'https://example.com/oauth/authorize',
                'required' => true,
            ],
            [
                'name' => 'token_url',
                'label' => 'Token URL',
                'type' => 'text',
                'placeholder' => 'https://example.com/oauth/token',
                'required' => true,
            ],
            [
                'name' => 'scope',
                'label' => 'Scopes',
                'type' => 'text',
                'placeholder' => 'read write (space-separated)',
                'required' => false,
            ],
        ];
    }
}
