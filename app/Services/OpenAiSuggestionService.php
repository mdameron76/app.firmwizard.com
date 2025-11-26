<?php

namespace App\Services;

use OpenAI;

class OpenAiSuggestionService
{
    protected $client;
    protected string $systemPrompt;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        
        $this->systemPrompt = "You are an expert legal marketing strategist specializing in family law and small law firms. "
            . "The user is an attorney or law firm staff member filling out a smart onboarding form. "
            . "They do NOT want to type paragraphs. Your job is to turn their existing answers into a small set of 3–5 clear, "
            . "pre-written options they can choose from.\n\n"
            . "Rules:\n"
            . "- Always return STRICT JSON.\n"
            . "- Keep each option short, specific, and non-generic.\n"
            . "- Tailor wording to the data provided (practice areas, service locations, target clients, brand voice).\n"
            . "- Never ask questions, just provide options.\n"
            . "- Avoid jargon and buzzwords; use plain language a stressed-out client would understand.\n"
            . "- Do not include any explanations outside of JSON.";
    }

    /**
     * Generate brand voice options based on firm data
     */
    public function generateBrandVoiceOptions(array $firmData): array
    {
        $prompt = "Based on the following firm information, generate 4 brand voice options. "
            . "Each option should specify: tone, style, core_values (3-5 bullet points), and a short core_message tagline.\n\n"
            . "Firm data:\n" . json_encode($firmData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return JSON with this shape:\n"
            . "{\n"
            . "  \"brand_voice_options\": [\n"
            . "    {\n"
            . "      \"id\": \"option_1\",\n"
            . "      \"tone\": \"...\",\n"
            . "      \"style\": \"...\",\n"
            . "      \"core_values\": [\"...\"],\n"
            . "      \"core_message\": \"...\"\n"
            . "    }\n"
            . "  ]\n"
            . "}";

        return $this->makeRequest($prompt);
    }

    /**
     * Generate target pain points based on practice areas and location
     */
    public function generatePainPoints(array $firmData): array
    {
        $prompt = "The firm primarily handles the following practice areas and works in these locations. "
            . "Suggest 6-8 realistic client pain points (phrased from the client's perspective) for this firm.\n\n"
            . "Data:\n" . json_encode($firmData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"pain_point_options\": [\n"
            . "    \"...\",\n"
            . "    \"...\"\n"
            . "  ]\n"
            . "}";

        return $this->makeRequest($prompt);
    }

    /**
     * Generate psychographic profiles
     */
    public function generatePsychographics(array $firmData): array
    {
        $prompt = "Based on the firm's practice areas and target demographics, suggest 4-6 psychographic characteristics "
            . "that describe their ideal clients' values, attitudes, and behaviors.\n\n"
            . "Firm data:\n" . json_encode($firmData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"psychographic_options\": [\n"
            . "    \"...\",\n"
            . "    \"...\"\n"
            . "  ]\n"
            . "}";

        return $this->makeRequest($prompt);
    }

    /**
     * Generate situational triggers
     */
    public function generateSituationalTriggers(array $firmData): array
    {
        $prompt = "Based on the firm's practice areas, suggest 5-7 life situations or events that would trigger "
            . "someone to need their services urgently.\n\n"
            . "Firm data:\n" . json_encode($firmData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"situational_trigger_options\": [\n"
            . "    \"...\",\n"
            . "    \"...\"\n"
            . "  ]\n"
            . "}";

        return $this->makeRequest($prompt);
    }

    /**
     * Generate marketing goal suggestions
     */
    public function generateMarketingGoals(array $firmData): array
    {
        $prompt = "Given this firm profile and their approximate budget range, suggest 5-7 prioritized marketing goals "
            . "for family law marketing. Focus on measurable outcomes.\n\n"
            . "Firm profile:\n" . json_encode($firmData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"goal_options\": [\n"
            . "    \"Increase qualified family law leads from Google Search\",\n"
            . "    \"Improve Google Maps rankings in top 5 target cities\",\n"
            . "    \"...\"\n"
            . "  ]\n"
            . "}";

        return $this->makeRequest($prompt);
    }

    /**
     * Estimate lead volume based on budget
     */
    public function estimateLeadVolume(array $firmData): array
    {
        $prompt = "Based on the following firm data and budget, provide a realistic monthly lead volume estimate "
            . "for family law leads in their service area. Consider practice areas and competition level.\n\n"
            . "Firm data:\n" . json_encode($firmData, JSON_PRETTY_PRINT) . "\n\n"
            . "Return strictly JSON:\n"
            . "{\n"
            . "  \"lead_estimate\": {\n"
            . "    \"min_leads\": 10,\n"
            . "    \"max_leads\": 20,\n"
            . "    \"confidence\": 0.75,\n"
            . "    \"rationale\": \"Brief explanation\"\n"
            . "  }\n"
            . "}";

        return $this->makeRequest($prompt);
    }

    /**
     * Make OpenAI API request
     */
    protected function makeRequest(string $userPrompt): array
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            $content = $response->choices[0]->message->content;
            
            // Extract JSON from markdown code blocks if present
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
