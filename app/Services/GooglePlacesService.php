<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    /**
     * Fetch full business data from Google Places API
     */
    public function getPlaceDetails(string $placeId): array
    {
        $apiKey = config('services.google.places_api_key');

        if (!$apiKey) {
            throw new \Exception('Google Places API key not configured');
        }

        $http = Http::baseUrl('https://maps.googleapis.com/maps/api');

        // Disable SSL verification for local development
        if (config('app.env') === 'local') {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->get('/place/details/json', [
            'place_id' => $placeId,
            'fields' => implode(',', [
                'name',
                'formatted_address',
                'formatted_phone_number',
                'opening_hours',
                'photos',
                'types',
                'website',
                'reviews',
                'rating',
                'user_ratings_total',
            ]),
            'key' => $apiKey,
        ]);

        if (!$response->successful()) {
            Log::error('Google Places API failed', [
                'place_id' => $placeId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch place details: ' . $response->body());
        }

        $result = $response->json();

        if ($result['status'] !== 'OK') {
            throw new \Exception('Google Places API error: ' . $result['status']);
        }

        return $result['result'];
    }

    /**
     * Normalize Google Places API response for database storage
     */
    public function normalizePlaceData(array $placeData): array
    {
        // Extract photo references (not full URLs)
        $photos = [];
        if (isset($placeData['photos'])) {
            foreach ($placeData['photos'] as $photo) {
                $photos[] = [
                    'photo_reference' => $photo['photo_reference'],
                    'width' => $photo['width'] ?? null,
                    'height' => $photo['height'] ?? null,
                    'html_attributions' => $photo['html_attributions'] ?? [],
                ];
            }
        }

        // Limit reviews to most recent 5
        $reviews = [];
        if (isset($placeData['reviews'])) {
            $reviews = array_slice($placeData['reviews'], 0, 5);
        }

        return [
            'name' => $placeData['name'] ?? null,
            'formatted_address' => $placeData['formatted_address'] ?? null,
            'formatted_phone_number' => $placeData['formatted_phone_number'] ?? null,
            'website' => $placeData['website'] ?? null,
            'opening_hours' => $placeData['opening_hours'] ?? null,
            'types' => $placeData['types'] ?? [],
            'rating' => $placeData['rating'] ?? null,
            'user_ratings_total' => $placeData['user_ratings_total'] ?? null,
            'reviews' => $reviews,
            'photos' => $photos,
            'raw_data' => $placeData,
        ];
    }

    /**
     * Get photo URL from photo_reference
     */
    public function getPhotoUrl(string $photoReference, int $maxWidth = 400): string
    {
        $apiKey = config('services.google.places_api_key');

        return sprintf(
            'https://maps.googleapis.com/maps/api/place/photo?maxwidth=%d&photo_reference=%s&key=%s',
            $maxWidth,
            $photoReference,
            $apiKey
        );
    }
}
