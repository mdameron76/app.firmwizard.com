# Integration Token Management

## How OAuth Token Refresh Works

### Automatic Refresh During Sync
The `GbpSyncService` automatically refreshes access tokens before each sync operation:

1. **Before every sync**, checks if the access token expires within 5 minutes
2. **If expired**, uses the refresh token to get a new access token from Google
3. **Updates** the integration with the new access token and expiration time
4. **Continues** with the sync operation

This means tokens will never expire during active use.

### Scheduled Token Refresh
A daily scheduled job runs at 2:00 AM to proactively refresh tokens:

```bash
php artisan integrations:refresh-tokens
```

**What it does:**
- Finds all active OAuth2 integrations
- Checks if tokens expire within 24 hours
- Refreshes tokens before they expire
- Marks integrations as "error" if refresh fails

**Schedule:** Daily at 2:00 AM (configured in `routes/console.php`)

## Google Refresh Token Behavior

### Token Lifespan
- **Access tokens**: Expire after 1 hour
- **Refresh tokens**: Do not expire (with offline access)
- **Exception**: User revokes access in Google account

### When Refresh Tokens Become Invalid

1. **User revokes access**: User disconnects app from their Google account
2. **Security event**: Google detects suspicious activity
3. **Account deletion**: User deletes their Google account

### Handling Invalid Refresh Tokens

When a refresh fails, the integration is marked with:
- `last_sync_status`: `'error'`
- `last_sync_message`: Error details

The user must re-authenticate via OAuth:
1. Go to Integrations page
2. Disconnect the integration
3. Reconnect via OAuth flow

## Manual Commands

### Refresh all tokens now
```bash
php artisan integrations:refresh-tokens
```

### Test a specific integration sync
```bash
# Via web interface
POST http://localhost:8000/integrations/{id}/sync

# Via command line (example)
php artisan tinker
$integration = App\Models\Integration::find(1);
app(App\Services\GbpSyncService::class)->syncInsights($integration);
```

## Production Setup

### Enable Laravel Scheduler
In production, ensure the Laravel scheduler runs every minute:

**Add to crontab:**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This will run the `integrations:refresh-tokens` command daily at 2:00 AM.

### Monitor Token Health
Set up alerts for integrations with `last_sync_status = 'error'`:

```sql
SELECT id, platform, display_name, last_sync_message, last_synced_at
FROM integrations
WHERE status = 'active'
  AND last_sync_status = 'error';
```

## Best Practices

1. **Sync regularly**: Sync data at least once per day to ensure tokens stay fresh
2. **Monitor errors**: Check for integrations with error status
3. **User notifications**: Alert users when re-authentication is needed
4. **Backup refresh tokens**: Store them securely (already encrypted in database)
5. **Log token refreshes**: Monitor logs for refresh failures

## Troubleshooting

### "No refresh token available"
**Cause**: Integration was created without offline access scope

**Solution**: Re-authenticate via OAuth with proper scopes

### "Token refresh failed: 400 Invalid Grant"
**Cause**: Refresh token has been revoked or is invalid

**Solution**: User must re-authenticate

### "The caller does not have permission"
**Cause**: Access token expired and refresh failed, or missing API scopes

**Solution**: Check OAuth scopes and re-authenticate if needed
