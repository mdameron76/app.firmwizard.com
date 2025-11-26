<?php

namespace App\Services\Agents;

use OpenAI;
use GuzzleHttp\Client as GuzzleClient;

class CompetitorAnalysisAgent
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::factory()
            ->withApiKey(config('services.openai.api_key'))
            ->withHttpClient(new GuzzleClient(['verify' => false]))
            ->make();
    }

    /**
     * Analyze competitors and identify opportunities
     */
    public function execute(array $input): array
    {
        $firmProfile = $input['firm_profile'] ?? [];
        $competitorList = $input['competitor_list'] ?? [];
        $serpData = $input['serp_data'] ?? [];

        $systemPrompt = "You are OZ, an AI marketing strategist for law firms. Analyze competitive landscape "
            . "and identify opportunities. Return structured JSON with competitor analysis and actionable recommendations.";

        $userPrompt = "Analyze the competitive landscape for this firm and identify opportunities.\n\n"
            . "Firm Profile:\n" . json_encode($firmProfile, JSON_PRETTY_PRINT) . "\n\n"
            . "Competitors:\n" . json_encode($competitorList, JSON_PRETTY_PRINT) . "\n\n"
            . "SERP Data:\n" . json_encode($serpData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"competitor_overview\": [\n"
            . "    {\n"
            . "      \"name\": \"Competitor Firm A\",\n"
            . "      \"city\": \"City\",\n"
            . "      \"primary_practice\": \"Family Law\",\n"
            . "      \"strengths\": [\"Strong GBP presence\", \"High review count\"],\n"
            . "      \"weaknesses\": [\"Weak content on custody\"],\n"
            . "      \"visibility_score\": 82\n"
            . "    }\n"
            . "  ],\n"
            . "  \"opportunities\": [\n"
            . "    {\n"
            . "      \"priority\": \"high\",\n"
            . "      \"description\": \"Own emergency custody search results\",\n"
            . "      \"why_it_matters\": \"High-intent, urgent cases\",\n"
            . "      \"first_step\": \"Create landing page for emergency custody\"\n"
            . "    }\n"
            . "  ],\n"
            . "  \"gaps\": [\n"
            . "    \"Content gap in custody modifications\",\n"
            . "    \"Weak presence in nearby cities\"\n"
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
                'max_tokens' => 3000,
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
