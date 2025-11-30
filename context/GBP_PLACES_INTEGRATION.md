# GBP to Google Places Integration

## Overview

This integration converts Google Business Profile (GBP) location IDs to Google Places IDs and syncs comprehensive business data from Google Places API.

## What Was Built

### 1. Database Schema

**Table:** `gbp_locations`

Stores complete business profile data tied to `firm_id`:

- Basic info: name, address, phone, website
- Hours: opening_hours JSON
- Categories: types array
- Ratings: rating (decimal), user_ratings_total
- Reviews: reviews JSON (top 5)
- Photos: photo_reference array (not URLs)
- Metadata: place_id, gbp_location_id, status, last_synced_at
- Raw data: complete Google Places API response

**Key Constraints:**
- Foreign key to `firms` table with cascade delete
- Unique constraint on `(firm_id, gbp_location_id)`

### 2. API Endpoint

**GET** `/api/gbp/{locationId}/sync`

**Authentication:** API Key (header: `X-API-Key` or query: `api_key`)

**Required Parameters:**
- `locationId` (path): GBP numeric location ID (e.g., "9393481967568809184")
- `firm_id` (query): Firm ID for multi-tenant support

**Response Format:**
```json
{
  "locationId": "9393481967568809184",
  "placeId": "ChIJr7uyr1-3dYgRhHY8ZJTAiF0",
  "data": {
    "name": "Business Name",
    "address": "123 Main St, City, State 12345",
    "phone": "(555) 123-4567",
    "website": "https://example.com",
    "hours": {...},
    "categories": ["lawyer", "legal_services"],
    "rating": 4.6,
    "total_reviews": 150,
    "reviews": [...],
    "photos": [...]
  },
  "synced_at": "2025-11-30T04:47:15+00:00"
}
```

### 3. Process Flow

The endpoint executes these steps:

**Step A:** Convert GBP location_id → place_id
- Calls `GbpSyncService::getPlaceIdFromGbpLocation()`
- Uses GBP Business Information API
- Extracts `metadata.placeId` from location data

**Step B:** Fetch Google Places data
- Calls `GooglePlacesService::getPlaceDetails()`
- Requests comprehensive business information
- Fields: name, address, phone, website, hours, photos, types, reviews, rating

**Step C:** Normalize & store in database
- Extracts photo_reference (not full URLs)
- Limits reviews to top 5
- Stores complete raw_data for debugging
- Updates or creates `GbpLocation` record

**Step D:** Return formatted JSON response

### 4. Services Created

**GbpSyncService** (`app/Services/GbpSyncService.php`)
- `getPlaceIdFromGbpLocation()`: Converts GBP ID to Places ID
- Uses existing OAuth token from Integration model
- Automatically refreshes tokens if needed

**GooglePlacesService** (`app/Services/GooglePlacesService.php`)
- `getPlaceDetails()`: Fetches full business data from Places API
- `normalizePlaceData()`: Formats data for database storage
- `getPhotoUrl()`: Converts photo_reference to URL when needed

### 5. Models

**GbpLocation** (`app/Models/GbpLocation.php`)
- Fillable fields for all business data
- Casts JSON fields automatically
- Relationship: `belongsTo(Firm::class)`
- Helper: `updateSyncTime()`

### 6. Security

✅ **API Key Authentication**
- Same n8n API key used for other endpoints
- Validates via header or query parameter

✅ **Multi-tenant Support**
- All records scoped to `firm_id`
- Validates firm has active GBP integration

✅ **Error Handling**
- Comprehensive try-catch blocks
- Logs all errors with context
- Returns meaningful error messages

### 7. Configuration Required

Add to `.env`:

```env
# Google Places API Key
GOOGLE_PLACES_API_KEY=your_api_key_here
```

**How to get API key:**
1. Go to Google Cloud Console
2. Enable "Places API"
3. Create API key
4. Restrict key to Places API only (recommended)

## Usage Examples

### n8n Workflow

```javascript
// HTTP Request Node
Method: GET
URL: https://oz.firmwizard.com/api/gbp/{{$node["Get Locations"].json["location_id"]}}/sync
Headers:
  X-API-Key: {{$credentials.n8n_api_key}}
Query Parameters:
  firm_id: {{$node["Get Firm"].json["id"]}}
```

### cURL Example

```bash
curl -X GET "http://localhost:8000/api/gbp/9393481967568809184/sync?firm_id=1" \
  -H "X-API-Key: your_n8n_api_key"
```

### Testing with Postman

```
GET http://localhost:8000/api/gbp/9393481967568809184/sync?firm_id=1
Headers:
  X-API-Key: your_n8n_api_key
```

## Database Queries

### Get all synced locations for a firm

```sql
SELECT
  gbp_location_id,
  place_id,
  name,
  rating,
  user_ratings_total,
  last_synced_at,
  status
FROM gbp_locations
WHERE firm_id = 1
ORDER BY last_synced_at DESC;
```

### Get locations needing re-sync (older than 24 hours)

```sql
SELECT *
FROM gbp_locations
WHERE firm_id = 1
  AND (last_synced_at IS NULL OR last_synced_at < NOW() - INTERVAL 24 HOUR);
```

### Get photo URLs for a location

```php
use App\Services\GooglePlacesService;

$location = GbpLocation::find($id);
$placesService = new GooglePlacesService();

foreach ($location->photos as $photo) {
    $url = $placesService->getPhotoUrl($photo['photo_reference'], 800);
    echo $url;
}
```

## n8n Automation Ideas

### 1. Nightly Sync
- Trigger: Schedule (daily at 2 AM)
- Get all firms with active GBP integration
- For each firm, get all GBP locations
- Call sync endpoint for each location
- Store results in database
- Send summary email if errors occur

### 2. Review Monitoring
- Trigger: After GBP sync
- Compare new reviews vs. stored reviews
- If new reviews found, send notification
- Trigger sentiment analysis
- Update review dashboard

### 3. Profile Completeness Check
- Trigger: After GBP sync
- Check for missing fields (photos, hours, website)
- Calculate completeness score
- Generate AI recommendations
- Send action items to firm

### 4. Competitor Tracking
- Trigger: Weekly
- Sync firm's GBP locations
- Sync competitor GBP locations
- Compare ratings, reviews, photos
- Generate competitive analysis report

## Files Created/Modified

### New Files
- `app/Services/GooglePlacesService.php`
- `app/Models/GbpLocation.php`
- `app/Http/Controllers/Api/GbpLocationController.php`
- `database/migrations/2025_11_30_044715_create_gbp_locations_table.php`
- `GBP_PLACES_INTEGRATION.md` (this file)

### Modified Files
- `app/Services/GbpSyncService.php` - Added `getPlaceIdFromGbpLocation()`
- `routes/api.php` - Added GBP sync route
- `config/services.php` - Added Google Places API key config

## Swagger Documentation

The new endpoint is automatically documented in Swagger at:
`http://localhost:8000/api/documentation`

Look for the "GBP Locations" tag.

## Next Steps

1. **Add Google Places API key** to `.env`
2. **Test endpoint** with a real GBP location ID
3. **Set up n8n workflow** for nightly syncing
4. **Monitor logs** for API errors
5. **Implement rate limiting** if needed (currently uses basic API key auth)

## Troubleshooting

### "Google Places API key not configured"
- Add `GOOGLE_PLACES_API_KEY` to `.env`
- Run `php artisan config:clear`

### "No active Google Business Profile integration found"
- Ensure firm has connected GBP via OAuth
- Check `integrations` table: `platform='google_gbp'` and `status='active'`

### "Failed to fetch GBP location"
- Verify location ID is correct
- Check OAuth token hasn't expired (auto-refreshes)
- Ensure GBP API is enabled in Google Cloud Console

### "Google Places API error: REQUEST_DENIED"
- Enable Places API in Google Cloud Console
- Check API key restrictions
- Verify billing is enabled (Places API requires billing)

## Cost Considerations

**Google Places API Pricing:**
- Place Details: $0.017 per request
- Photo requests: $0.007 per request

**Example monthly cost for 100 firms with 2 locations each:**
- Daily syncs: 200 locations × 30 days = 6,000 requests
- Cost: 6,000 × $0.017 = $102/month

**Optimization tips:**
- Only sync when needed (check `last_synced_at`)
- Use Basic Data fields only if full data not needed
- Implement caching for frequently accessed data
