<?php

namespace App\Services\Agents;

use App\Models\AiFirmProfile;
use OpenAI;

class BlogPostAgent
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    /**
     * Generate blog post topics and outlines
     */
    public function execute(array $input): array
    {
        $firmProfile = $input['firm_profile'] ?? [];
        $competitorData = $input['competitor_data'] ?? [];

        $systemPrompt = "You are OZ, an AI marketing strategist for law firms. You receive a firm's structured profile "
            . "plus relevant marketing data. Your job is to return actionable, structured recommendations in JSON format "
            . "that can be directly rendered by a dashboard.";

        $userPrompt = "Using the firm profile and competitor data below, generate 8-12 blog post topics and outlines "
            . "optimized for SEO and for attracting the firm's ideal clients.\n\n"
            . "Firm Profile:\n" . json_encode($firmProfile, JSON_PRETTY_PRINT) . "\n\n"
            . "Competitor/Keyword Data:\n" . json_encode($competitorData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON using this shape:\n"
            . "{\n"
            . "  \"topics\": [\n"
            . "    {\n"
            . "      \"slug\": \"emergency-child-custody-in-jefferson-county\",\n"
            . "      \"title\": \"Emergency Child Custody in Jefferson County: What You Need to Know Today\",\n"
            . "      \"target_keyword\": \"emergency child custody jefferson county mo\",\n"
            . "      \"search_intent\": \"Informational\",\n"
            . "      \"priority\": \"high\",\n"
            . "      \"why_it_matters\": \"Addresses urgent, high-value cases\",\n"
            . "      \"outline\": [\n"
            . "        \"Intro: When every day counts\",\n"
            . "        \"What qualifies as an emergency\",\n"
            . "        \"...\"\n"
            . "      ]\n"
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
