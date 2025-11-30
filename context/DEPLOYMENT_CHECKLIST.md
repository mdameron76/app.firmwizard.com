# Production Deployment Checklist

## Date: 2025-11-30
## Features: GBP Integration + Google Places API Sync

---

## ⚠️ CRITICAL: Database Migrations to Run in Production

Run these migrations in **exact order** on production server:

```bash
# 1. Run all pending migrations
php artisan migrate

# Specifically, these new migrations:
# - 2025_11_30_003807_add_oauth_fields_to_integrations_table.php
# - 2025_11_30_044715_create_gbp_locations_table.php
```

### Migration Details:

**Migration 1:** `add_oauth_fields_to_integrations_table`
- Adds OAuth fields to existing `integrations` table
- Fields: `display_name`, `access_token`, `refresh_token`, `token_expires_at`, `last_sync_message`
- **Safe**: Adds nullable columns, no data loss risk

**Migration 2:** `create_gbp_locations_table`
- Creates new `gbp_locations` table
- Stores Google Places business data
- Has foreign key to `firms` table (must exist)
- **Safe**: New table, no existing data affected

---

## 🔑 Environment Variables to Add

Add these to production `.env`:

```env
# Google OAuth (already have these, verify they're set)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret

# NEW: Google Places API Key
GOOGLE_PLACES_API_KEY=your_places_api_key_here

# N8N API Key (verify this exists)
N8N_API_KEY=your_n8n_api_key
```

### How to Get Google Places API Key:
1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Select your project (same one with OAuth credentials)
3. Navigate to "APIs & Services" → "Library"
4. Search for "Places API" and enable it
5. Go to "Credentials" → "Create Credentials" → "API Key"
6. **Important**: Restrict the key to "Places API" only
7. **Important**: Enable billing (Places API requires it)

---

## 📝 Post-Deployment Commands

Run these on production server after deploying code:

```bash
# 1. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 2. Run migrations
php artisan migrate

# 3. Regenerate Swagger docs
php artisan l5-swagger:generate

# 4. Test the integration refresh command
php artisan integrations:refresh-tokens

# 5. Verify scheduled tasks are set up
php artisan schedule:list
```

---

## ✅ New Features Deployed

### 1. GBP OAuth Integration
- **Route**: `/integrations/oauth/google/google_gbp`
- **Callback**: `/integrations/oauth/google/callback`
- **Page**: `http://oz.firmwizard.com/integrations`

### 2. GBP Insights Sync
- **Endpoint**: `POST /integrations/{id}/sync`
- **Fetches**: All 8 metrics from Google Business Profile Performance API
- **Stores**: 30 days of insights in `gbp_insights` table

### 3. Google Places Sync API
- **Endpoint**: `GET /api/gbp/{locationId}/sync?firm_id={id}`
- **Auth**: API Key (header: `X-API-Key` or query: `api_key`)
- **Purpose**: Converts GBP location to Places ID and syncs full business data

### 4. Token Refresh Scheduler
- **Command**: `integrations:refresh-tokens`
- **Schedule**: Daily at 2:00 AM
- **Purpose**: Automatically refreshes OAuth tokens before expiry

---

## 🔍 Post-Deployment Testing

### Test 1: OAuth Connection
```bash
# Go to: https://oz.firmwizard.com/integrations
# Click "Connect" on Google Business Profile
# Should redirect to Google OAuth
# After auth, should show "Connected" status
```

### Test 2: Insights Sync
```bash
# In integrations page, click "Sync Now" on connected GBP
# Should fetch metrics successfully
# Check database:
SELECT COUNT(*) FROM gbp_insights WHERE firm_id = 1;
# Should show 30+ records (30 days × locations)
```

### Test 3: Places API Sync
```bash
curl -X GET "https://oz.firmwizard.com/api/gbp/9393481967568809184/sync?firm_id=1" \
  -H "X-API-Key: your_n8n_api_key"

# Should return JSON with business data
# Check database:
SELECT * FROM gbp_locations WHERE firm_id = 1;
```

### Test 4: Token Refresh
```bash
# SSH to production
php artisan integrations:refresh-tokens

# Should output:
# "Refreshing OAuth tokens for active integrations..."
# "✓ Refreshed X token(s)"
```

### Test 5: Swagger Docs
```bash
# Go to: https://oz.firmwizard.com/api/documentation
# Should see new endpoints:
# - POST /integrations/{id}/sync
# - GET /api/gbp/{locationId}/sync
```

---

## 🐛 Rollback Plan (if needed)

If something goes wrong:

```bash
# 1. Rollback last migration
php artisan migrate:rollback --step=1

# 2. If need to rollback both migrations
php artisan migrate:rollback --step=2

# 3. Redeploy previous code version
git checkout <previous-commit-hash>
composer install --no-dev
php artisan config:clear
```

---

## 📊 Database Table Summary

### New Tables Created:
1. **`gbp_locations`** (new)
   - Stores Google Places business data
   - Tied to `firm_id`
   - Includes: name, address, phone, hours, reviews, photos, etc.

### Modified Tables:
1. **`integrations`** (5 new columns)
   - `display_name` - Friendly name for integration
   - `access_token` - OAuth access token
   - `refresh_token` - OAuth refresh token
   - `token_expires_at` - Token expiration timestamp
   - `last_sync_message` - Last sync error/success message

### Existing Tables (unchanged):
- `gbp_insights` (already exists, no changes)
- `firms` (no changes)
- `integrations` (only new columns added)

---

## 🔐 Security Checklist

- [ ] Verify API keys are in production `.env` (not committed to git)
- [ ] Confirm Google Places API key is **restricted** to Places API only
- [ ] Verify billing is enabled for Google Places API
- [ ] Test API key authentication on `/api/gbp/{locationId}/sync`
- [ ] Confirm OAuth tokens are encrypted in database
- [ ] Check logs for any exposed credentials

---

## 💰 Cost Monitoring

**Google Places API Pricing:**
- Place Details: $0.017 per request
- Photo requests: $0.007 per request

**Expected usage for 100 firms:**
- Daily syncs: ~200 locations
- Monthly cost: ~$102 (200 × 30 × $0.017)

**Monitor in Google Cloud Console:**
1. Go to "APIs & Services" → "Dashboard"
2. Click on "Places API"
3. View "Quotas & System Limits"
4. Check daily usage

---

## 📞 Support Contacts

**If issues occur:**
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check server error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
3. Check Google Cloud Console for API errors

**Common Issues:**

**Issue**: "Google Places API key not configured"
- **Fix**: Add `GOOGLE_PLACES_API_KEY` to `.env` and run `php artisan config:clear`

**Issue**: "REQUEST_DENIED" from Places API
- **Fix**: Enable Places API in Google Cloud Console + enable billing

**Issue**: "No active Google Business Profile integration found"
- **Fix**: Reconnect GBP integration via `/integrations` page

---

## ✅ Final Checklist

Before marking deployment complete:

- [ ] Migrations run successfully
- [ ] Environment variables added
- [ ] Caches cleared
- [ ] OAuth connection tested
- [ ] Insights sync tested
- [ ] Places API sync tested
- [ ] Token refresh command tested
- [ ] Swagger docs regenerated
- [ ] Scheduled tasks verified
- [ ] Logs checked for errors
- [ ] n8n workflows updated (if applicable)

---

## 📝 Notes for Next Deployment

Remember to document:
- Any issues encountered
- Performance metrics
- API usage costs
- User feedback
