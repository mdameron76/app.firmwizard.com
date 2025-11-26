<?php

if (!function_exists('openai_client')) {
    /**
     * Create OpenAI client with SSL verification disabled for local development
     */
    function openai_client(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey(config('services.openai.api_key'))
            ->withHttpClient(new \GuzzleHttp\Client([
                'verify' => false, // Disable SSL verification for local dev
            ]))
            ->make();
    }
}
