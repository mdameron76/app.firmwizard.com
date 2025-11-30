# FirmWizard GBP Integration Context
**File:** `firmwizard_gbp_integration_context.md`  
**Scope:** Google Business Profile (GBP) / Business Profile Performance integration for `app.firmwizard.com`  
**Audience:** AI coding agents + human devs (Laravel/PHP + MySQL + n8n)

---

## 1. High-Level Goals

We want a **reliable, production-grade** integration between FirmWizard and **Google Business Profile** that:

1. **Authenticates** via Google OAuth 2.0 (already working).
2. **Discovers locations** for a firm’s GBP account.
3. **Pulls performance metrics** daily (and optionally hourly for some).
4. **Normalizes & stores** those metrics into MySQL tables.
5. **Exposes REST endpoints** for:
   - Internal analytics (dashboards inside FirmWizard).
   - n8n workflows (scheduled fetch + backfill).
6. **Keeps raw data** for debugging and future metric expansion.
7. **Is multi-tenant** and scoped by `firm_id`.

We are focusing on **GBP Insights / Performance** (impressions, website clicks, calls, directions, etc.), not reviews or posts (those can be separate integrations later).

---

## 2. Terminology and Key IDs

- **firm_id** – Primary key from `firms` table in `app_firmwizard` DB.
- **location_id** – GBP location resource identifier (from Google, usually the numeric `name` segment).
- **location_name** – Human-friendly location name (e.g., `"Ponder Bates Stewart Gonz Law Offices, LLC - Festus"`).
- **date** – Metrics are stored per day in FirmWizard **in UTC date** (YYYY-MM-DD).

Assumption: Google Business Profile Performance API is available via OAuth 2.0 and we have the proper scopes granted.

---

## 3. Database Structure

We will use **three related tables** for GBP:

1. `gbp_accounts` – One row per GBP account / profile per firm.  
2. `gbp_locations` – One row per GBP location (office) per firm.  
3. `gbp_insights` – Daily time-series metric table per firm + location.

### 3.1. `gbp_accounts`

```sql
CREATE TABLE IF NOT EXISTS gbp_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  firm_id BIGINT UNSIGNED NOT NULL,
  google_account_name VARCHAR(255) NOT NULL COMMENT 'Google resource name for account, e.g. accounts/123456789',
  account_display_name VARCHAR(255) NULL,
  -- OAuth linkage / reference into generic firm_integrations
  integration_id BIGINT UNSIGNED NULL COMMENT 'FK to firm_integrations row for google_gbp',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY gbp_accounts_firm_google_unique (firm_id, google_account_name),
  CONSTRAINT gbp_accounts_firm_id_foreign FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2. `gbp_locations`

```sql
CREATE TABLE IF NOT EXISTS gbp_locations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  firm_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  location_id VARCHAR(255) NOT NULL COMMENT 'GBP location name or ID, e.g. locations/9393481967568809184',
  location_name VARCHAR(255) NULL,
  primary_category VARCHAR(255) NULL,
  address_json JSON NULL,
  phone_number VARCHAR(50) NULL,
  website_url VARCHAR(255) NULL,
  timezone VARCHAR(100) NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_suspended TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY gbp_locations_firm_location_unique (firm_id, location_id),
  KEY gbp_locations_account_id_index (account_id),
  CONSTRAINT gbp_locations_firm_id_foreign FOREIGN KEY (firm_id) REFERENCES firms(id) ON DELETE CASCADE,
  CONSTRAINT gbp_locations_account_id_foreign FOREIGN KEY (account_id) REFERENCES gbp_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.3. `gbp_insights` (core time-series table)

> NOTE: This is based on what you already have, but tuned to align with the **Business Profile Performance** metric structure.

```sql
CREATE TABLE IF NOT EXISTS gbp_insights (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  firm_id BIGINT UNSIGNED NOT NULL,
  location_id VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GBP Location ID',
  location_name VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  date DATE NOT NULL COMMENT 'UTC date for metrics',

  -- Aggregated metrics (desktop + mobile)
  views_search INT NOT NULL DEFAULT 0 COMMENT 'Search impressions (desktop + mobile)',
  views_maps INT NOT NULL DEFAULT 0 COMMENT 'Maps impressions (desktop + mobile)',

  actions_website INT NOT NULL DEFAULT 0 COMMENT 'Website clicks',
  actions_phone INT NOT NULL DEFAULT 0 COMMENT 'Phone call clicks',
  actions_driving_directions INT NOT NULL DEFAULT 0 COMMENT 'Direction requests',

  photos_count_merchant INT NOT NULL DEFAULT 0 COMMENT 'Business photos (count if available)',
  photos_count_customers INT NOT NULL DEFAULT 0 COMMENT 'Customer photos (count if available)',
  photos_views_merchant INT NOT NULL DEFAULT 0 COMMENT 'Views of business photos',
  photos_views_customers INT NOT NULL DEFAULT 0 COMMENT 'Views of customer photos',

  search_queries JSON DEFAULT NULL COMMENT 'Top search query phrases, if we store them',
  raw_data JSON DEFAULT NULL COMMENT 'Raw metric payload from Google for this day/location',

  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY gbp_insights_firm_id_location_id_date_unique (firm_id, location_id, date),
  KEY gbp_insights_firm_id_date_index (firm_id, date),
  KEY gbp_insights_location_id_index (location_id),
  CONSTRAINT gbp_insights_firm_id_foreign FOREIGN KEY (firm_id) REFERENCES firms (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Important:** The **schema is intentionally opinionated**: we aggregate search and maps impressions from granular metrics and keep the granular raw data in `raw_data`.

---

## 4. GBP Metrics Mapping (Business Profile Performance)

We are using Google’s **Business Profile Performance API** (successor to classic Insights). It returns metrics identified by enum names like:

- `BUSINESS_IMPRESSIONS_DESKTOP_SEARCH`
- `BUSINESS_IMPRESSIONS_MOBILE_SEARCH`
- `BUSINESS_IMPRESSIONS_DESKTOP_MAPS`
- `BUSINESS_IMPRESSIONS_MOBILE_MAPS`
- `CALL_CLICKS`
- `WEBSITE_CLICKS`
- `DRIVING_DIRECTIONS`
- (Optional) `BUSINESS_MESSAGES`, `BOOKINGS`, etc.

### 4.1. FirmWizard Column ↔ Metric Mapping

**FirmWizard aggregated columns:**

- `views_search`
  - Sum of:
    - `BUSINESS_IMPRESSIONS_DESKTOP_SEARCH`
    - `BUSINESS_IMPRESSIONS_MOBILE_SEARCH`
- `views_maps`
  - Sum of:
    - `BUSINESS_IMPRESSIONS_DESKTOP_MAPS`
    - `BUSINESS_IMPRESSIONS_MOBILE_MAPS`
- `actions_website`
  - `WEBSITE_CLICKS`
- `actions_phone`
  - `CALL_CLICKS`
- `actions_driving_directions`
  - `DRIVING_DIRECTIONS`
- `photos_*` fields  
  - If the Performance API gives photo metrics, map them; if not available, keep zeros or enhance later via other endpoints.

**`raw_data` column:**  
Should store a JSON document like:

```json
{
  "metrics": {
    "BUSINESS_IMPRESSIONS_DESKTOP_SEARCH": 12,
    "BUSINESS_IMPRESSIONS_MOBILE_SEARCH": 20,
    "BUSINESS_IMPRESSIONS_DESKTOP_MAPS": 5,
    "BUSINESS_IMPRESSIONS_MOBILE_MAPS": 8,
    "CALL_CLICKS": 2,
    "WEBSITE_CLICKS": 3,
    "DRIVING_DIRECTIONS": 1
  },
  "source": "business_profile_performance_api",
  "retrieved_at": "2025-11-30T08:00:10Z"
}
```

**`search_queries` column:**  
Optionally store an array of top queries per day/location if we fetch them, e.g.:

```json
[
  { "query": "divorce lawyer festus", "impressions": 32 },
  { "query": "family law attorney near me", "impressions": 18 }
]
```

---

## 5. API Endpoints (Backend – Laravel Style)

Base URL pattern:

- App-facing: `/api/gbp/*`
- n8n-facing (service key): `/api/n8n/gbp/*`

> All routes must be scoped by `firm_id` and protected by either:
> - Authenticated user token (for app), or  
> - n8n service Bearer token (for automation).

### 5.1. List GBP locations for a firm

**Endpoint:**
```http
GET /api/gbp/{firm_id}/locations
```

**Returns:** All `gbp_locations` rows for firm with basic info and last sync date:

```json
[
  {
    "id": 1,
    "location_id": "locations/9393481967568809184",
    "location_name": "Ponder Bates Stewart Gonz Law Offices, LLC - Festus",
    "primary_category": "Family law attorney",
    "website_url": "https://example.com",
    "phone_number": "+1-555-555-5555",
    "timezone": "America/Chicago",
    "is_verified": true,
    "is_suspended": false,
    "last_insights_date": "2025-11-29"
  }
]
```

### 5.2. Sync GBP locations metadata for a firm

**Endpoint:**
```http
POST /api/gbp/{firm_id}/locations/sync
```

**Behavior:**
- Uses the **google_gbp** `firm_integrations` row for the firm.
- Calls Google’s list-accounts and list-locations endpoints.
- Upserts into `gbp_accounts` and `gbp_locations`.
- Returns summary:

```json
{
  "firm_id": 1,
  "accounts_processed": 1,
  "locations_created": 1,
  "locations_updated": 0
}
```

### 5.3. Sync GBP insights for a single location + date range

**Endpoint:**
```http
POST /api/gbp/{firm_id}/locations/{location_id}/insights/sync
```

**Body (optional):**
```json
{
  "start_date": "2025-11-01",
  "end_date": "2025-11-30"
}
```

**Behavior:**
- Calls Business Profile Performance API for that `location_id` and date range.
- Maps metrics → `gbp_insights` (per day).
- Upserts by unique key `(firm_id, location_id, date)`.
- Returns row counts:

```json
{
  "firm_id": 1,
  "location_id": "locations/9393481967568809184",
  "start_date": "2025-11-01",
  "end_date": "2025-11-30",
  "days_processed": 30,
  "rows_inserted": 25,
  "rows_updated": 5
}
```

### 5.4. Sync GBP insights for all locations (per firm)

**Endpoint:**
```http
POST /api/gbp/{firm_id}/insights/sync
```

**Body:**
```json
{
  "start_date": "2025-11-01",
  "end_date": "2025-11-30",
  "mode": "incremental"  // or "backfill"
}
```

**Behavior:**
- Iterates over all `gbp_locations` for `firm_id`.
- Calls the **single location sync** internally for each.
- Use **incremental mode** to only fetch from the last known date → today - 1.
- Backfill mode might fetch up to 18 months historically.

### 5.5. Get insights for a location (for dashboards)

**Endpoint:**
```http
GET /api/gbp/{firm_id}/locations/{location_id}/insights
```

**Query parameters:**
- `start_date`
- `end_date`

**Response:**
```json
{
  "firm_id": 1,
  "location_id": "locations/9393481967568809184",
  "location_name": "Ponder Bates Stewart Gonz Law Offices, LLC - Festus",
  "metrics": [
    {
      "date": "2025-11-23",
      "views_search": 24,
      "views_maps": 12,
      "actions_website": 3,
      "actions_phone": 1,
      "actions_driving_directions": 2
    },
    {
      "date": "2025-11-24",
      "views_search": 18,
      "views_maps": 9,
      "actions_website": 2,
      "actions_phone": 0,
      "actions_driving_directions": 1
    }
  ]
}
```

---

## 6. ETL Logic (Metric Parsing & Insert) – Laravel / PHP Pseudocode

Below is a **canonical mapping** from GBP metrics → `gbp_insights` row.

### 6.1. Example GBP Performance response shape (simplified)

You should expect something roughly like:

```json
{
  "timeSeries": [
    {
      "startDate": "2025-11-23",
      "endDate": "2025-11-24",
      "metricValues": [
        {
          "metric": "BUSINESS_IMPRESSIONS_DESKTOP_SEARCH",
          "value": { "integerValue": "12" }
        },
        {
          "metric": "BUSINESS_IMPRESSIONS_MOBILE_SEARCH",
          "value": { "integerValue": "20" }
        },
        {
          "metric": "BUSINESS_IMPRESSIONS_DESKTOP_MAPS",
          "value": { "integerValue": "5" }
        },
        {
          "metric": "BUSINESS_IMPRESSIONS_MOBILE_MAPS",
          "value": { "integerValue": "8" }
        },
        {
          "metric": "CALL_CLICKS",
          "value": { "integerValue": "2" }
        },
        {
          "metric": "WEBSITE_CLICKS",
          "value": { "integerValue": "3" }
        },
        {
          "metric": "DRIVING_DIRECTIONS",
          "value": { "integerValue": "1" }
        }
      ]
    }
  ]
}
```

### 6.2. Laravel-style mapping pseudocode

```php
function syncGbpInsightsForLocation(int $firmId, string $locationId, string $startDate, string $endDate, array $googleResponse, string $locationName = null)
{
    foreach ($googleResponse['timeSeries'] as $series) {

        // GBP often uses [startDate, endDate) but if we requested daily, treat startDate as the day.
        $date = $series['startDate']; // "YYYY-MM-DD"

        $viewsSearch = 0;
        $viewsMaps = 0;
        $actionsWebsite = 0;
        $actionsPhone = 0;
        $actionsDriving = 0;

        $rawMetrics = [];

        foreach ($series['metricValues'] as $metricValue) {
            $metricName = $metricValue['metric'];
            $value = (int) ($metricValue['value']['integerValue'] ?? 0);
            $rawMetrics[$metricName] = $value;

            switch ($metricName) {
                case 'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH':
                case 'BUSINESS_IMPRESSIONS_MOBILE_SEARCH':
                    $viewsSearch += $value;
                    break;

                case 'BUSINESS_IMPRESSIONS_DESKTOP_MAPS':
                case 'BUSINESS_IMPRESSIONS_MOBILE_MAPS':
                    $viewsMaps += $value;
                    break;

                case 'WEBSITE_CLICKS':
                    $actionsWebsite += $value;
                    break;

                case 'CALL_CLICKS':
                    $actionsPhone += $value;
                    break;

                case 'DRIVING_DIRECTIONS':
                    $actionsDriving += $value;
                    break;

                // Other metrics can be added here and stored in raw_data only for now
            }
        }

        // Upsert row
        \DB::table('gbp_insights')->updateOrInsert(
            [
                'firm_id'     => $firmId,
                'location_id' => $locationId,
                'date'        => $date,
            ],
            [
                'location_name'              => $locationName,
                'views_search'               => $viewsSearch,
                'views_maps'                 => $viewsMaps,
                'actions_website'            => $actionsWebsite,
                'actions_phone'              => $actionsPhone,
                'actions_driving_directions' => $actionsDriving,
                'raw_data'                   => json_encode([
                    'metrics'      => $rawMetrics,
                    'source'       => 'business_profile_performance_api',
                    'retrieved_at' => now()->toIso8601String(),
                ]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
```

**Key points:**

- Do **not** just store one made-up field like `{"views_search": 4}` in `raw_data`.  
  Always store the full metrics object so we can extend later.
- Make sure `date` is normalized to **UTC date** consistent across the system.

---

## 7. n8n Integration

We will use n8n for **scheduled syncing**.

### 7.1. n8n → FirmWizard service endpoints

**Endpoint to get all firms with GBP connected:**

```http
GET /api/n8n/gbp/firms-with-gbp
Authorization: Bearer {N8N_SERVICE_TOKEN}
```

**Returns:**
```json
[
  {
    "firm_id": 1,
    "integration_id": 10,
    "has_gbp_locations": true
  }
]
```

**Endpoint to sync all locations for a firm:**

```http
POST /api/n8n/gbp/{firm_id}/insights/sync
Authorization: Bearer {N8N_SERVICE_TOKEN}
Content-Type: application/json

{
  "mode": "incremental",      // or "backfill"
  "days_back": 7              // only used when mode=incremental
}
```

Server-side behavior for `incremental` mode:

1. Look up latest `date` in `gbp_insights` for firm.
2. If none found, default to `today - 30 days`.
3. Call GBP API for `[last_date+1, today-1]` range.
4. Store data per location.

### 7.2. n8n workflow outline

**Workflow: "Daily GBP Insights Sync"**

1. **Cron Node** – run daily at ~3:00 AM (off-peak).
2. **HTTP Node – Get firms with GBP**  
   - GET `/api/n8n/gbp/firms-with-gbp`  
3. **Split In Batches Node** – iterate each firm.
4. **HTTP Node – Sync insights for that firm**  
   - POST `/api/n8n/gbp/{{firm_id}}/insights/sync`  
   - Body `{ "mode": "incremental", "days_back": 7 }`
5. Optional: Slack/email summary of counts + failures.

---

## 8. Security & Multi-Tenancy Rules

1. All **app-facing** GBP endpoints must:
   - Require an authenticated user.
   - Confirm that user belongs to `firm_id` they’re requesting.
2. All **n8n-facing** endpoints must:
   - Require `Authorization: Bearer N8N_SERVICE_TOKEN` header.
   - The token is configured in `.env` and validated by middleware.
3. Tokens/OAuth:
   - Actual OAuth tokens are stored in `firm_integrations` table (type: `google_gbp`).
   - They must be encrypted using Laravel’s `encrypt()` helper or equivalent.

---

## 9. Sanity Checks & QA Plan

When implementing, verify:

1. **GBP UI vs FirmWizard data**  
   - Pick a 7-day range in Google Business Profile UI.  
   - Compare totals for search views, maps views, website clicks, directions, and calls with sums from `gbp_insights`.
2. **Edge cases**  
   - No data days → should result in either no row or a row with all zeros.  
   - Suspended or unverified locations → skip or mark clearly.
3. **Performance**  
   - Use batching in GBP API to not hammer quotas.  
   - Use incremental sync for daily jobs to avoid redundant requests.
4. **Error handling**  
   - Log any Google API errors with enough context (firm_id, location_id, date range, HTTP status, error code/message).  
   - Optionally store last error in `firm_integrations.last_sync_message` for quick debugging.

---

## 10. Summary

This document defines:

- The **GBP database model** (`gbp_accounts`, `gbp_locations`, `gbp_insights`).  
- The **exact metric mapping** from Business Profile Performance API → FirmWizard columns.  
- The **ETL logic** for aggregating and storing metrics.  
- The **REST endpoints** required for app and n8n.  
- A **sane scheduling & sync pattern** that keeps data fresh without overloading APIs.  
- **Security and multi-tenant rules** to keep firms isolated and protected.

Any AI coding agent using this file should be able to:

1. Generate migrations.  
2. Implement Laravel controllers/services.  
3. Wire up n8n workflows.  
4. Pass manual sanity checks against the GBP UI.

---

# END OF FILE
