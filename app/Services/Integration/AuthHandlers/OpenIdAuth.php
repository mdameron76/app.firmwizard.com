<?php

namespace App\Services\Integration\AuthHandlers;

class OpenIdAuth extends BaseAuthHandler
{
    /**
     * Authenticate using OpenID Connect
     */
    public function authenticate(array $input): array
    {
        // Similar to OAuth2 but with ID token
        return [
            'access_token' => $input['access_token'] ?? null,
            'id_token' => $input['id_token'],
            'refresh_token' => $input['refresh_token'] ?? null,
            'expires_at' => isset($input['expires_in']) ? now()->addSeconds($input['expires_in']) : null,
        ];
    }

    /**
     * Validate OpenID credentials
     */
    public function validate(array $credentials): bool
    {
        return !empty($credentials['id_token']);
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
                'placeholder' => 'Enter OpenID client ID',
                'required' => true,
            ],
            [
                'name' => 'client_secret',
                'label' => 'Client Secret',
                'type' => 'password',
                'placeholder' => 'Enter OpenID client secret',
                'required' => true,
            ],
            [
                'name' => 'issuer',
                'label' => 'Issuer URL',
                'type' => 'text',
                'placeholder' => 'https://example.com',
                'required' => true,
                'help' => 'The OpenID provider URL',
            ],
        ];
    }
}
