<?php

namespace App\Services;

use App\Models\GbpInsight;
use App\Models\Integration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GbpSyncService
{
    /**
     * Sync GBP insights for a given integration
     */
    public function syncInsights(Integration $integration, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        if ($integration->platform !== 'google_gbp') {
            return [
                'success' => false,
                'message' => 'Integration is not a Google Business Profile integration.',
            ];
        }

        // Default to last 30 days if no dates provided
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now()->subDay();

        try {
            // Refresh token if needed
            $this->refreshTokenIfNeeded($integration);

            // Get account locations
            $locations = $this->getLocations($integration);

            if (empty($locations)) {
                return [
                    'success' => false,
                    'message' => 'No locations found for this GBP account.',
                ];
            }

            $syncedCount = 0;
            $errors = [];

            // Sync insights for each location
            foreach ($locations as $location) {
                try {
                    $this->syncLocationInsights($integration, $location, $startDate, $endDate);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Location {$location['name']}: " . $e->getMessage();
                    Log::error('GBP location sync failed', [
                        'location' => $location['name'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update integration sync status
            $integration->update([
                'last_synced_at' => now(),
                'last_sync_status' => $syncedCount > 0 ? 'success' : 'error',
                'last_sync_message' => $syncedCount > 0
                    ? "Synced {$syncedCount} location(s) successfully"
                    : 'No locations synced: ' . implode('; ', $errors),
            ]);

            return [
                'success' => $syncedCount > 0,
                'message' => "Synced insights for {$syncedCount} location(s)",
                'synced_count' => $syncedCount,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            Log::error('GBP sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            $integration->update([
                'last_synced_at' => now(),
                'last_sync_status' => 'error',
                'last_sync_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Google Places place_id from GBP location_id
     */
    public function getPlaceIdFromGbpLocation(Integration $integration, string $locationId): string
    {
        $http = Http::withToken($integration->access_token);

        // Disable SSL verification for local development
        if (config('app.env') === 'local') {
            $http = $http->withOptions(['verify' => false]);
        }

        $locationName = "locations/{$locationId}";
        $response = $http->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$locationName}", [
            'readMask' => 'name,metadata',
        ]);

        if (!$response->successful()) {
            Log::error('Failed to fetch GBP location for place_id', [
                'location_id' => $locationId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch GBP location: ' . $response->body());
        }

        $location = $response->json();
        $placeId = $location['metadata']['placeId'] ?? null;

        if (!$placeId) {
            throw new \Exception("No place_id found for GBP location: {$locationId}");
        }

        Log::info('Retrieved place_id from GBP location', [
            'location_id' => $locationId,
            'place_id' => $placeId,
        ]);

        return $placeId;
    }

    /**
     * Get all locations for the GBP account
     */
    private function getLocations(Integration $integration): array
    {
        $http = Http::withToken($integration->access_token);

        // Disable SSL verification for local development
        if (config('app.env') === 'local') {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->get('https://mybusinessbusinessinformation.googleapis.com/v1/accounts');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch GBP accounts: ' . $response->body());
        }

        $accounts = $response->json('accounts', []);

        if (empty($accounts)) {
            return [];
        }

        // Get locations for the first account (most businesses have one account)
        $accountName = $accounts[0]['name'];

        $httpLocations = Http::withToken($integration->access_token);

        // Disable SSL verification for local development
        if (config('app.env') === 'local') {
            $httpLocations = $httpLocations->withOptions(['verify' => false]);
        }

        $locationsResponse = $httpLocations->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations", [
            'readMask' => 'name,title,storefrontAddress,phoneNumbers,websiteUri,categories,openInfo',
        ]);

        if (!$locationsResponse->successful()) {
            throw new \Exception('Failed to fetch locations: ' . $locationsResponse->body());
        }

        return $locationsResponse->json('locations', []);
    }

    /**
     * Sync insights for a specific location
     */
    private function syncLocationInsights(Integration $integration, array $location, Carbon $startDate, Carbon $endDate): void
    {
        $locationName = $location['name'];
        $locationId = basename($locationName);

        // Fetch insights from GBP API
        $insights = $this->fetchInsightsFromApi($integration, $locationName, $startDate, $endDate);

        // Process and store each day's insights
        foreach ($insights as $date => $metrics) {
            GbpInsight::updateOrCreate(
                [
                    'firm_id' => $integration->firm_id,
                    'location_id' => $locationId,
                    'date' => $date,
                ],
                [
                    'location_name' => $location['title'] ?? $location['locationName'] ?? 'Unknown',
                    'views_search' => $metrics['views_search'] ?? 0,
                    'views_maps' => $metrics['views_maps'] ?? 0,
                    'actions_website' => $metrics['actions_website'] ?? 0,
                    'actions_phone' => $metrics['actions_phone'] ?? 0,
                    'actions_driving_directions' => $metrics['actions_driving_directions'] ?? 0,
                    'photos_count_merchant' => $metrics['photos_count_merchant'] ?? 0,
                    'photos_count_customers' => $metrics['photos_count_customers'] ?? 0,
                    'photos_views_merchant' => $metrics['photos_views_merchant'] ?? 0,
                    'photos_views_customers' => $metrics['photos_views_customers'] ?? 0,
                    'search_queries' => $metrics['search_queries'] ?? null,
                    'raw_data' => $metrics['raw_data'] ?? $metrics, // Use nested raw_data if available, otherwise full metrics
                ]
            );
        }
    }

    /**
     * Fetch insights from Google My Business API
     */
    private function fetchInsightsFromApi(Integration $integration, string $locationName, Carbon $startDate, Carbon $endDate): array
    {
        $http = Http::withToken($integration->access_token);

        // Disable SSL verification for local development
        if (config('app.env') === 'local') {
            $http = $http->withOptions(['verify' => false]);
        }

        // Try the newer Business Profile Performance API first
        try {
            return $this->fetchFromPerformanceAPI($http, $locationName, $startDate, $endDate);
        } catch (\Exception $e) {
            Log::warning('Performance API failed, trying legacy getDailyMetricsTimeSeries', [
                'error' => $e->getMessage(),
            ]);

            // Fall back to getDailyMetricsTimeSeries endpoint
            return $this->fetchFromDailyMetricsAPI($http, $locationName, $startDate, $endDate);
        }
    }

    /**
     * Fetch from Business Profile Performance API (newer)
     */
    private function fetchFromPerformanceAPI($http, string $locationName, Carbon $startDate, Carbon $endDate): array
    {
        // Define metrics to fetch - API only returns one metric per request
        $metrics = [
            'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
            'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH',
            'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
            'BUSINESS_IMPRESSIONS_MOBILE_SEARCH',
            'BUSINESS_CONVERSATIONS',
            'BUSINESS_DIRECTION_REQUESTS',
            'CALL_CLICKS',
            'WEBSITE_CLICKS',
        ];

        $allMetricsData = [];

        // Fetch each metric separately since API only returns one metric per request
        foreach ($metrics as $metric) {
            $queryParams = [];
            $queryParams[] = 'dailyMetric=' . $metric;
            $queryParams[] = 'dailyRange.startDate.year=' . $startDate->year;
            $queryParams[] = 'dailyRange.startDate.month=' . $startDate->month;
            $queryParams[] = 'dailyRange.startDate.day=' . $startDate->day;
            $queryParams[] = 'dailyRange.endDate.year=' . $endDate->year;
            $queryParams[] = 'dailyRange.endDate.month=' . $endDate->month;
            $queryParams[] = 'dailyRange.endDate.day=' . $endDate->day;

            $queryString = implode('&', $queryParams);
            $url = "https://businessprofileperformance.googleapis.com/v1/{$locationName}:getDailyMetricsTimeSeries?{$queryString}";

            try {
                $response = $http->get($url);

                if (!$response->successful()) {
                    Log::warning("Performance API failed for metric {$metric}", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue; // Skip this metric but continue with others
                }

                $jsonResponse = $response->json();

                // Store this metric's data
                $timeSeries = $jsonResponse['timeSeries'] ?? null;
                if ($timeSeries) {
                    $datedValues = $timeSeries['datedValues'] ?? [];

                    foreach ($datedValues as $dateValue) {
                        $date = $dateValue['date'] ?? null;
                        if (!$date) continue;

                        $dateString = sprintf('%04d-%02d-%02d', $date['year'], $date['month'], $date['day']);

                        if (!isset($allMetricsData[$dateString])) {
                            $allMetricsData[$dateString] = [
                                'metrics' => [],
                                'retrieved_at' => now()->toIso8601String(),
                            ];
                        }

                        $allMetricsData[$dateString]['metrics'][$metric] = intval($dateValue['value'] ?? 0);
                    }
                }

                Log::info("Fetched metric {$metric}", ['data_points' => count($datedValues ?? [])]);

            } catch (\Exception $e) {
                Log::warning("Exception fetching metric {$metric}", ['error' => $e->getMessage()]);
                continue;
            }
        }

        Log::info('✅ SUCCESS! All Performance API metrics fetched', [
            'location' => $locationName,
            'dates' => count($allMetricsData),
            'sample_date' => array_key_first($allMetricsData) ?? 'none',
        ]);

        return $this->parsePerformanceAPIMetrics($allMetricsData);
    }

    /**
     * Fetch from getDailyMetricsTimeSeries (fallback)
     */
    private function fetchFromDailyMetricsAPI($http, string $locationName, Carbon $startDate, Carbon $endDate): array
    {
        $response = $http->post("https://mybusiness.googleapis.com/v4/{$locationName}/locations:reportInsights", [
            'locationNames' => [$locationName],
            'basicRequest' => [
                'metricRequests' => [
                    ['metric' => 'QUERIES_DIRECT'],
                    ['metric' => 'QUERIES_INDIRECT'],
                    ['metric' => 'VIEWS_MAPS'],
                    ['metric' => 'VIEWS_SEARCH'],
                    ['metric' => 'ACTIONS_WEBSITE'],
                    ['metric' => 'ACTIONS_PHONE'],
                    ['metric' => 'ACTIONS_DRIVING_DIRECTIONS'],
                    ['metric' => 'PHOTOS_VIEWS_MERCHANT'],
                    ['metric' => 'PHOTOS_VIEWS_CUSTOMERS'],
                    ['metric' => 'PHOTOS_COUNT_MERCHANT'],
                    ['metric' => 'PHOTOS_COUNT_CUSTOMERS'],
                ],
                'timeRange' => [
                    'startTime' => $startDate->toIso8601String(),
                    'endTime' => $endDate->toIso8601String(),
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('GBP insights API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch insights: ' . $response->body());
        }

        return $this->parseInsightsResponse($response->json());
    }

    /**
     * Parse and aggregate Performance API metrics
     * Properly maps metrics according to firmwizard_gbp_integration_context.md section 4.1
     */
    private function parsePerformanceAPIMetrics(array $allMetricsData): array
    {
        $insights = [];

        foreach ($allMetricsData as $dateString => $dayData) {
            $metrics = $dayData['metrics'] ?? [];

            // Aggregate metrics according to spec:
            // views_search = DESKTOP_SEARCH + MOBILE_SEARCH
            // views_maps = DESKTOP_MAPS + MOBILE_MAPS
            // actions_website = WEBSITE_CLICKS
            // actions_phone = CALL_CLICKS
            // actions_driving_directions = BUSINESS_DIRECTION_REQUESTS

            $insights[$dateString] = [
                'views_search' => ($metrics['BUSINESS_IMPRESSIONS_DESKTOP_SEARCH'] ?? 0)
                                + ($metrics['BUSINESS_IMPRESSIONS_MOBILE_SEARCH'] ?? 0),
                'views_maps' => ($metrics['BUSINESS_IMPRESSIONS_DESKTOP_MAPS'] ?? 0)
                              + ($metrics['BUSINESS_IMPRESSIONS_MOBILE_MAPS'] ?? 0),
                'actions_website' => $metrics['WEBSITE_CLICKS'] ?? 0,
                'actions_phone' => $metrics['CALL_CLICKS'] ?? 0,
                'actions_driving_directions' => $metrics['BUSINESS_DIRECTION_REQUESTS'] ?? 0,
                'raw_data' => [
                    'metrics' => $metrics,
                    'source' => 'business_profile_performance_api',
                    'retrieved_at' => $dayData['retrieved_at'],
                ],
            ];
        }

        Log::info('Parsed and aggregated insights from Performance API', [
            'count' => count($insights),
            'sample_date' => array_key_first($insights) ?? 'none',
            'sample_metrics' => isset($insights[array_key_first($insights) ?? ''])
                ? array_keys($insights[array_key_first($insights)]['raw_data']['metrics'])
                : [],
        ]);

        return $insights;
    }

    /**
     * Parse GBP insights API response
     */
    private function parseInsightsResponse(array $response): array
    {
        $insights = [];

        $locationMetrics = $response['locationMetrics'][0] ?? [];
        $metricValues = $locationMetrics['metricValues'] ?? [];

        foreach ($metricValues as $metricValue) {
            $metric = $metricValue['metric'] ?? '';
            $dimensionalValues = $metricValue['dimensionalValues'] ?? [];

            foreach ($dimensionalValues as $dimensional) {
                $date = isset($dimensional['time'])
                    ? Carbon::parse($dimensional['time'])->format('Y-m-d')
                    : now()->format('Y-m-d');

                if (!isset($insights[$date])) {
                    $insights[$date] = [];
                }

                $value = $dimensional['value'] ?? 0;

                // Map API metrics to our database fields
                switch ($metric) {
                    case 'VIEWS_SEARCH':
                        $insights[$date]['views_search'] = $value;
                        break;
                    case 'VIEWS_MAPS':
                        $insights[$date]['views_maps'] = $value;
                        break;
                    case 'ACTIONS_WEBSITE':
                        $insights[$date]['actions_website'] = $value;
                        break;
                    case 'ACTIONS_PHONE':
                        $insights[$date]['actions_phone'] = $value;
                        break;
                    case 'ACTIONS_DRIVING_DIRECTIONS':
                        $insights[$date]['actions_driving_directions'] = $value;
                        break;
                    case 'PHOTOS_COUNT_MERCHANT':
                        $insights[$date]['photos_count_merchant'] = $value;
                        break;
                    case 'PHOTOS_COUNT_CUSTOMERS':
                        $insights[$date]['photos_count_customers'] = $value;
                        break;
                    case 'PHOTOS_VIEWS_MERCHANT':
                        $insights[$date]['photos_views_merchant'] = $value;
                        break;
                    case 'PHOTOS_VIEWS_CUSTOMERS':
                        $insights[$date]['photos_views_customers'] = $value;
                        break;
                }
            }
        }

        return $insights;
    }

    /**
     * Refresh access token if expired
     */
    private function refreshTokenIfNeeded(Integration $integration): void
    {
        // Check if token is expired or about to expire (within 5 minutes)
        if (!$integration->token_expires_at || $integration->token_expires_at->subMinutes(5)->isPast()) {
            $this->refreshAccessToken($integration);
        }
    }

    /**
     * Refresh the access token using refresh token
     */
    private function refreshAccessToken(Integration $integration): void
    {
        if (!$integration->refresh_token) {
            throw new \Exception('No refresh token available. Please reconnect the integration.');
        }

        $http = Http::asForm();

        // Disable SSL verification for local development
        if (config('app.env') === 'local') {
            $http = $http->withOptions(['verify' => false]);
        }

        $response = $http->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $integration->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('Token refresh failed', [
                'integration_id' => $integration->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh access token: ' . $response->body());
        }

        $tokens = $response->json();

        // Update integration with new access token
        $integration->update([
            'access_token' => $tokens['access_token'],
            'token_expires_at' => isset($tokens['expires_in'])
                ? now()->addSeconds($tokens['expires_in'])
                : null,
        ]);

        // Also update credentials JSON
        $credentials = $integration->credentials;
        $credentials['access_token'] = $tokens['access_token'];
        $integration->credentials = $credentials;
        $integration->save();
    }
}
