<?php

namespace App\Services\Agents;

use OpenAI;

class WebsiteAnalysisAgent
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    /**
     * Analyze website and provide recommendations
     */
    public function execute(array $input): array
    {
        $firmProfile = $input['firm_profile'] ?? [];
        $websiteData = $input['website_data'] ?? [];

        $systemPrompt = "You are OZ, an AI marketing strategist for law firms. Analyze website performance "
            . "and provide actionable recommendations for conversion optimization and SEO.";

        $userPrompt = "Analyze this law firm's website and provide recommendations.\n\n"
            . "Firm Profile:\n" . json_encode($firmProfile, JSON_PRETTY_PRINT) . "\n\n"
            . "Website Data:\n" . json_encode($websiteData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"overall_score\": 75,\n"
            . "  \"categories\": {\n"
            . "    \"seo\": {\"score\": 70, \"issues\": [...], \"recommendations\": [...]},\n"
            . "    \"conversion\": {\"score\": 80, \"issues\": [...], \"recommendations\": [...]},\n"
            . "    \"mobile\": {\"score\": 85, \"issues\": [...], \"recommendations\": [...]},\n"
            . "    \"content\": {\"score\": 65, \"issues\": [...], \"recommendations\": [...]}\n"
            . "  },\n"
            . "  \"priority_actions\": [\n"
            . "    {\n"
            . "      \"action\": \"Add emergency custody landing page\",\n"
            . "      \"impact\": \"high\",\n"
            . "      \"effort\": \"medium\",\n"
            . "      \"rationale\": \"High search volume, low competition\"\n"
            . "    }\n"
            . "  ]\n"
            . "}";

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 2500,
            ]);

            $content = $response->choices[0]->message->content;
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            
            return json_decode($content, true) ?? ['error' => 'Failed to parse JSON response'];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
