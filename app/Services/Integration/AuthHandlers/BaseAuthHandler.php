<?php

namespace App\Services\Integration\AuthHandlers;

abstract class BaseAuthHandler
{
    /**
     * Authenticate and return credentials
     */
    abstract public function authenticate(array $input): array;

    /**
     * Validate credentials
     */
    abstract public function validate(array $credentials): bool;

    /**
     * Refresh credentials if needed
     */
    public function refresh(array $credentials): ?array
    {
        return null; // Override in subclasses if refresh is supported
    }

    /**
     * Get required fields for this auth type
     */
    abstract public function getRequiredFields(): array;
}
