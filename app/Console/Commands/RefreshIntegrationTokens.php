<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\GbpSyncService;
use Illuminate\Console\Command;

class RefreshIntegrationTokens extends Command
{
    protected $signature = 'integrations:refresh-tokens';
    protected $description = 'Refresh OAuth tokens for all active integrations';

    public function handle(): int
    {
        $this->info('Refreshing OAuth tokens for active integrations...');

        // Get all active OAuth2 integrations
        $integrations = Integration::where('status', 'active')
            ->where('auth_type', 'oauth2')
            ->get();

        $refreshed = 0;
        $failed = 0;

        foreach ($integrations as $integration) {
            try {
                // Check if token needs refresh (expires within 1 day)
                if (!$integration->token_expires_at ||
                    $integration->token_expires_at->subDay()->isPast()) {

                    $this->info("Refreshing token for {$integration->platform} (ID: {$integration->id})");

                    // Use the appropriate service to refresh
                    match ($integration->platform) {
                        'google_gbp' => $this->refreshGoogleToken($integration),
                        default => $this->warn("No refresh handler for platform: {$integration->platform}"),
                    };

                    $refreshed++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to refresh {$integration->platform} (ID: {$integration->id}): {$e->getMessage()}");

                // Mark integration as error if refresh fails
                $integration->update([
                    'last_sync_status' => 'error',
                    'last_sync_message' => 'Token refresh failed: ' . $e->getMessage(),
                ]);

                $failed++;
            }
        }

        $this->info("✓ Refreshed {$refreshed} token(s)");

        if ($failed > 0) {
            $this->warn("✗ Failed to refresh {$failed} token(s)");
        }

        return Command::SUCCESS;
    }

    private function refreshGoogleToken(Integration $integration): void
    {
        if (!$integration->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $http = \Illuminate\Support\Facades\Http::asForm();

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
            throw new \Exception('Token refresh failed: ' . $response->body());
        }

        $tokens = $response->json();

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
