# FirmWizard Pixel Collect Endpoint Design  
`POST https://oz.firmwizard.com/api/pixel/collect`

This document describes how to build the **pixel collection endpoint**, how it integrates with the existing database tables (especially `ai_attribution_events`), what additional tables/schemas are recommended, and how to **secure** the endpoint against abuse and junk data.

---

## 1. High-Level Flow

1. Browser loads a page with the FirmWizard pixel installed.
2. Pixel script gathers:
   - `firm_id`
   - `fwid` (FirmWizard visitor ID)
   - current URL, referrer
   - UTM parameters
   - user agent, device info
   - timestamp
3. Pixel script sends a `POST` request to:

   ```text
   https://oz.firmwizard.com/api/pixel/collect
   ```

4. The API endpoint:
   - Validates payload + firm authorization
   - Normalizes data
   - Optionally queues event for async processing
   - Inserts into:
     - `fw_pixel_events_raw` (optional but recommended)
     - `ai_attribution_events` (normalized, attribution-friendly)

5. Attribution engine and analytics use `ai_attribution_events` as the **source of truth** for click & visit events.

---

## 2. Current Key Table: `ai_attribution_events`

Existing structure (simplified):

```sql
CREATE TABLE IF NOT EXISTS `ai_attribution_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint(20) unsigned DEFAULT NULL,
  `gclid` varchar(255) DEFAULT NULL,
  `fbclid` varchar(255) DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `crm_case_id` bigint(20) unsigned DEFAULT NULL,
  `matched_with` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_attribution_events_firm_id_index` (`firm_id`)
) ENGINE=InnoDB;
```

We will **extend usage** of this table to store **pixel events** as well, with a small schema extension.

### 2.1 Suggested Column Additions

Add columns:

```sql
ALTER TABLE `ai_attribution_events`
  ADD COLUMN `fwid` varchar(64) DEFAULT NULL AFTER `firm_id`,
  ADD COLUMN `event_type` varchar(64) DEFAULT NULL AFTER `matched_with`,
  ADD COLUMN `url` text DEFAULT NULL AFTER `utm_campaign`,
  ADD COLUMN `referrer` text DEFAULT NULL AFTER `url`,
  ADD COLUMN `user_agent` text DEFAULT NULL AFTER `referrer`,
  ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER `user_agent`;
```

**Notes:**
- `fwid` = FirmWizard visitor ID (from localStorage/cookie).
- `event_type` = 'pageview', 'gbp_click', 'ad_click', 'form_view', etc.
- `url`, `referrer` store landing page and referrer.
- `ip_address` and `user_agent` can help with fraud detection and bot filtering.

---

## 3. New Recommended Tables

### 3.1 `fw_pixel_events_raw` (Optional but Recommended)

A dedicated raw ingestion table to keep **unmodified event payloads** for debugging and audits.

```sql
CREATE TABLE IF NOT EXISTS `fw_pixel_events_raw` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint(20) unsigned NOT NULL,
  `fwid` varchar(64) DEFAULT NULL,
  `payload_json` json NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fw_pixel_events_raw_firm_id_index` (`firm_id`),
  KEY `fw_pixel_events_raw_fwid_index` (`fwid`)
) ENGINE=InnoDB;
```

Purpose:
- Capture exactly what the pixel sent
- Assist with debugging, forensics, and training future models

### 3.2 `fw_pixel_api_keys`

For securing which sites/firms can send events.

```sql
CREATE TABLE IF NOT EXISTS `fw_pixel_api_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint(20) unsigned NOT NULL,
  `api_key` varchar(128) NOT NULL,
  `domain` varchar(255) DEFAULT NULL, -- optional: restrict per domain
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fw_pixel_api_keys_api_key_unique` (`api_key`),
  KEY `fw_pixel_api_keys_firm_id_index` (`firm_id`)
) ENGINE=InnoDB;
```

These keys will be used to verify that incoming pixel requests are valid and belong to an existing firm.

---

## 4. Pixel Payload Contract

### 4.1 Expected JSON Body (Client → Server)

The pixel should send:

```json
{
  "firm_id": 12,
  "fwid": "fw_84ab129f",
  "url": "https://gonzfirm.com/divorce-attorney?utm_source=GBP&utm_medium=organic",
  "referrer": "https://www.google.com/maps",
  "utm": {
    "utm_source": "GBP",
    "utm_medium": "organic",
    "utm_campaign": "google-my-business"
  },
  "gclid": null,
  "fbclid": null,
  "event_type": "pageview",
  "api_key": "FWPXL_xxx_your_secret_pixel_key"
}
```

Server will enrich with:
- IP address (from request)
- User agent (from headers)
- Timestamp (server time)

---

## 5. Building the Endpoint: `/api/pixel/collect`

Here is a **framework-agnostic** step-by-step implementation outline.  
Assume Laravel-style pseudocode, but the logic is portable.

### 5.1 Route Definition

```php
Route::post('/api/pixel/collect', [PixelController::class, 'collect']);
```

### 5.2 Controller Logic (High-Level)

```php
public function collect(Request $request)
{
    // 1. Basic throttle / rate-limit
    // (use Laravel's built-in rate limiter or a custom middleware)

    // 2. Validate minimal required fields
    $data = $request->validate([
        'firm_id'   => 'required|integer|exists:firms,id',
        'fwid'      => 'nullable|string|max:64',
        'url'       => 'required|string',
        'referrer'  => 'nullable|string',
        'utm'       => 'nullable|array',
        'gclid'     => 'nullable|string|max:255',
        'fbclid'    => 'nullable|string|max:255',
        'event_type'=> 'nullable|string|max:64',
        'api_key'   => 'required|string'
    ]);

    // 3. Validate API key belongs to firm_id and is active
    $apiKey = FwPixelApiKey::where('api_key', $data['api_key'])
                ->where('firm_id', $data['firm_id'])
                ->where('is_active', 1)
                ->first();

    if (!$apiKey) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    // 3b. Optional: Validate origin matches allowed domain
    $origin = $request->header('Origin') ?? $request->header('Referer');
    if ($apiKey->domain && $origin && !str_contains($origin, $apiKey->domain)) {
        return response()->json(['status' => 'error', 'message' => 'Origin mismatch'], 403);
    }

    // 4. Extract UTM params safely
    $utm = $data['utm'] ?? [];
    $utm_source   = $utm['utm_source']   ?? null;
    $utm_medium   = $utm['utm_medium']   ?? null;
    $utm_campaign = $utm['utm_campaign'] ?? null;

    // 5. Get IP and User-Agent
    $ip         = $request->ip();
    $userAgent  = $request->userAgent();

    // 6. Store raw payload
    FwPixelEventRaw::create([
        'firm_id'     => $data['firm_id'],
        'fwid'        => $data['fwid'] ?? null,
        'payload_json'=> json_encode($data),
        'ip_address'  => $ip,
        'user_agent'  => $userAgent,
    ]);

    // 7. Optionally apply simple bot filters
    if ($this->looksLikeBot($userAgent)) {
        return response()->json(['status' => 'ok', 'ignored' => true]);
    }

    // 8. Insert normalized event into ai_attribution_events
    AiAttributionEvent::create([
        'firm_id'      => $data['firm_id'],
        'fwid'         => $data['fwid'] ?? null,
        'gclid'        => $data['gclid'] ?? null,
        'fbclid'       => $data['fbclid'] ?? null,
        'utm_source'   => $utm_source,
        'utm_medium'   => $utm_medium,
        'utm_campaign' => $utm_campaign,
        'url'          => $data['url'],
        'referrer'     => $data['referrer'] ?? null,
        'user_agent'   => $userAgent,
        'ip_address'   => $ip,
        'event_type'   => $data['event_type'] ?? 'pageview',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    return response()->json(['status' => 'ok']);
}
```

### 5.3 Example Bot Filter Helper

```php
protected function looksLikeBot(?string $userAgent): bool
{
    if (!$userAgent) return true;

    $botKeywords = [
        'bot', 'crawl', 'slurp', 'spider', 'curl', 'wget', 'python-requests', 'spider', 'crawler', 'AhrefsBot', 'Googlebot', 'crawler', 'Bingbot', 'SemrushBot', 'PerplexityBot', 'Twitterbot'
    ];

    $ua = strtolower($userAgent);
    foreach ($botKeywords as $keyword) {
        if (str_contains($ua, $keyword)) {
            return true;
        }
    }

    return false;
}
```

---

## 6. Security Considerations

### 6.1 Pixel API Keys

- Each **firm** gets one or more **pixel API keys**.
- Keys are **long random strings**, e.g., `FWPXL_` + 40–60 chars.
- Stored hashed or at least in a locked-down table.
- Rotatable and revocable.

The pixel JS includes the key:

```js
body: JSON.stringify({
  firm_id: FIRM_ID,
  fwid: fwid,
  url: window.location.href,
  referrer: document.referrer || null,
  utm: utmParams,
  gclid: gclid,
  fbclid: fbclid,
  event_type: eventType,
  api_key: 'FWPXL_xxx_your_secret_pixel_key'
})
```

> **Note:** Anyone can see the key in front-end JS, so it should not be used as a *secret* in the traditional sense.  
> It is an **identifier**, not full security. Real security comes from **rate limiting**, **domain checking**, and **anomaly detection**.

### 6.2 Origin / Domain Checking

Using the `fw_pixel_api_keys.domain` field:

- Only accept requests where `Origin` / `Referer` contain the allowed domain (e.g. `gonzfirm.com`).
- Reject all others with `403`.

This prevents random third-party sites from sending fake events (unless they spoof headers, which is harder in a browser context).

### 6.3 Rate Limiting

Implement rate limits:

- Per IP
- Per `firm_id`
- Per `fwid` (optional)

Examples:
- Max 50 pixel hits per minute per IP
- Max 5k pixel hits per hour per `firm_id` (tunable)

In Laravel you could use the `ThrottleRequests` middleware or a Redis-based limiter.

### 6.4 Payload Validation & Sanitization

- Use strict validation rules on expected payload.
- Drop any unexpected fields.
- Limit string lengths.
- Ensure `firm_id` must exist in `firms` table.
- Ensure `fwid` follows expected pattern (`fw_...`).

### 6.5 Audit Logs & Monitoring

- Log unusual spikes per firm.
- Track number of events per domain.
- Alert if a domain not tied to a firm starts sending traffic (should never happen).

---

## 7. Integration with Existing Tables

### 7.1 Relationship to `firms` Table

- `ai_attribution_events.firm_id` references `firms.id`.
- `fw_pixel_api_keys.firm_id` references `firms.id`.
- `fw_pixel_events_raw.firm_id` references `firms.id`.

This allows **per-firm** filtering for analytics and attribution.

### 7.2 Relationship to CRM Leads and Cases

Later, when a **lead** is created in the CRM:

- The CRM should capture `fwid` from a hidden input in forms.
- Later, when the case is created, the CRM lead ID is linked to the case.

Then a background job can:
- Find all `ai_attribution_events` where `fwid = lead.fwid AND firm_id = lead.firm_id`
- Update those rows with:
  - `crm_lead_id = lead.id`
  - When case is opened: `crm_case_id = case.id`

This gives a **joined timeline** from first click → case.

---

## 8. Example End-to-End Event

1. User clicks GBP link to `https://gonzfirm.com/gbp`
2. GBP redirect endpoint logs `event_type = gbp_click` and redirects with UTMs
3. Pixel fires on `/gbp` and `/` homepage
4. Browser sends:

   ```json
   {
     "firm_id": 12,
     "fwid": "fw_84ab129f",
     "url": "https://gonzfirm.com/?utm_source=GBP&utm_medium=organic&utm_campaign=google-my-business",
     "referrer": "https://www.google.com/maps",
     "utm": {
       "utm_source": "GBP",
       "utm_medium": "organic",
       "utm_campaign": "google-my-business"
     },
     "event_type": "pageview",
     "api_key": "FWPXL_..."
   }
   ```

5. `/api/pixel/collect`:
   - Validates firm_id + api_key
   - Stores raw payload in `fw_pixel_events_raw`
   - Inserts normalized row into `ai_attribution_events`

6. Later, user fills out a form:
   - Form posts `fwid` into CRM
   - CRM lead creation process updates `ai_attribution_events` for that `fwid` with `crm_lead_id`

7. Once case opens:
   - `crm_case_id` is set on those same events

Now the attribution engine can generate **per-firm**, **per-channel** ROI.

---

## 9. Summary

By implementing the `/api/pixel/collect` endpoint with:

- **firm_id-aware pixel payloads**
- **normalized storage in `ai_attribution_events`**
- **raw backup in `fw_pixel_events_raw`**
- **secured API keys per firm**
- **origin validation, rate limiting, and bot filtering**

FirmWizard gains:

- Robust, secure pixel-based tracking
- Per-firm, per-channel attribution
- Clean event data for AI-driven recommendations
- A clear path to multi-touch attribution and ROI reporting at scale.
