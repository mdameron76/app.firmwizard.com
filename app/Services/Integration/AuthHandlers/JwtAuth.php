<?php

namespace App\Services\Integration\AuthHandlers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth extends BaseAuthHandler
{
    /**
     * Authenticate using JWT
     */
    public function authenticate(array $input): array
    {
        // Generate JWT from secret
        if (isset($input['secret'])) {
            $payload = [
                'iss' => $input['issuer'] ?? config('app.url'),
                'sub' => $input['subject'] ?? 'integration',
                'iat' => time(),
                'exp' => time() + ($input['expires_in'] ?? 3600),
            ];

            $token = JWT::encode($payload, $input['secret'], $input['algorithm'] ?? 'HS256');

            return [
                'token' => $token,
                'secret' => $input['secret'],
                'algorithm' => $input['algorithm'] ?? 'HS256',
                'expires_at' => now()->addSeconds($input['expires_in'] ?? 3600),
            ];
        }

        // Store pre-generated JWT
        return [
            'token' => $input['token'],
            'expires_at' => $input['expires_at'] ?? null,
        ];
    }

    /**
     * Validate JWT credentials
     */
    public function validate(array $credentials): bool
    {
        if (empty($credentials['token'])) {
            return false;
        }

        try {
            if (!empty($credentials['secret'])) {
                JWT::decode($credentials['token'], new Key($credentials['secret'], $credentials['algorithm'] ?? 'HS256'));
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Refresh JWT
     */
    public function refresh(array $credentials): ?array
    {
        if (empty($credentials['secret'])) {
            return null;
        }

        return $this->authenticate($credentials);
    }

    /**
     * Get required fields
     */
    public function getRequiredFields(): array
    {
        return [
            [
                'name' => 'token',
                'label' => 'JWT Token',
                'type' => 'textarea',
                'placeholder' => 'Paste your JWT token here',
                'required' => false,
                'help' => 'Provide either a token OR secret to generate one',
            ],
            [
                'name' => 'secret',
                'label' => 'JWT Secret',
                'type' => 'password',
                'placeholder' => 'Enter JWT secret key',
                'required' => false,
            ],
            [
                'name' => 'algorithm',
                'label' => 'Algorithm',
                'type' => 'select',
                'options' => ['HS256', 'HS384', 'HS512', 'RS256'],
                'default' => 'HS256',
                'required' => false,
            ],
        ];
    }
}
