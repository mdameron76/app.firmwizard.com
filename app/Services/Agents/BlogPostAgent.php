<?php

namespace App\Services\Agents;

use App\Models\AiFirmProfile;
use OpenAI;
use GuzzleHttp\Client as GuzzleClient;

class BlogPostAgent
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
     * Generate blog post topics and outlines
     */
    public function execute(array $input): array
    {
        $firmProfile = $input['firm_profile'] ?? [];
        $competitorData = $input['competitor_data'] ?? [];

        $systemPrompt = "You are OZ, an AI marketing strategist for law firms. Generate blog post topics optimized for SEO and client acquisition.";

        $userPrompt = "Using the firm profile and competitor data below, generate 8-10 blog post topics and outlines "
            . "optimized for SEO and for attracting the firm's ideal clients.\n\n"
            . "Firm Profile:\n" . json_encode($firmProfile, JSON_PRETTY_PRINT) . "\n\n"
            . "Competitor/Keyword Data:\n" . json_encode($competitorData, JSON_PRETTY_PRINT);

        $schema = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'blog_topics_response',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'topics' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'seo_score' => ['type' => 'integer'],
                                    'keywords' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string']
                                    ],
                                ],
                                'required' => ['title', 'description', 'seo_score', 'keywords'],
                                'additionalProperties' => false,
                            ],
                        ],
                    ],
                    'required' => ['topics'],
                    'additionalProperties' => false,
                ],
            ],
        ];

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-2024-08-06',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'response_format' => $schema,
                'temperature' => 0.7,
                'max_tokens' => 3000,
            ]);

            $content = $response->choices[0]->message->content;
            
            return json_decode($content, true) ?? ['error' => 'Failed to parse JSON response'];
            
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
