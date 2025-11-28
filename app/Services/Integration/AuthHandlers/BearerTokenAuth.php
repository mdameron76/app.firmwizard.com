<?php

namespace App\Services\Integration\AuthHandlers;

class BearerTokenAuth extends BaseAuthHandler
{
    /**
     * Authenticate using bearer token
     */
    public function authenticate(array $input): array
    {
        return [
            'token' => $input['token'],
            'type' => 'bearer',
        ];
    }

    /**
     * Validate bearer token credentials
     */
    public function validate(array $credentials): bool
    {
        return !empty($credentials['token']);
    }

    /**
     * Get required fields
     */
    public function getRequiredFields(): array
    {
        return [
            [
                'name' => 'token',
                'label' => 'API Token',
                'type' => 'password',
                'placeholder' => 'Enter your API token',
                'required' => true,
            ],
        ];
    }
}
