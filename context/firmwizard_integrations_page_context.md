
# FirmWizard Integrations System — Full Context Doc  
**File:** firmwizard_integrations_page_context.md  
**Purpose:** Provides full instructions for developers and AI coding agents to build the Integrations Page, DB tables, API routes, n8n scheduler endpoints, and fetch pipelines for app.firmwizard.com.

---

# 1. Overview

We are adding a full **Integrations Management System** to  
**`app.firmwizard.com`** so each **firm_id** can connect marketing, analytics, and website data sources.

Integrations required:

- Google Business Profile (GBP) 
- Google Ads   
- Google Local Services Ads (LSA)  
- Google Analytics (UA/GA4)  
- Facebook / Meta (Pages, Ads, IG)  
- WordPress REST API  
- (Future) Clio, MyCase, CallRail, Ruby Receptionists, Calendly, Zoom, etc.

The system must:

✔ Store integration metadata  
✔ Store authentication tokens (encrypted)  
✔ Allow Connect / Disconnect / Refresh  
✔ Trigger n8n schedulers per integration  
✔ Provide REST API endpoints for app + automations  
✔ Allow raw data retrieval for future dashboards and AI models  

---

# 2. Database Schema

## Main Table: `firm_integrations`

```sql
CREATE TABLE firm_integrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firm_id BIGINT UNSIGNED NOT NULL,
    integration_type VARCHAR(100) NOT NULL,
    display_name VARCHAR(255) NULL,

    status ENUM('not_connected','active','expired','error')
        DEFAULT 'not_connected',

    auth_type ENUM('oauth2','api_key','manual','none') DEFAULT 'oauth2',

    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    api_key TEXT NULL,
    settings_json JSON NULL,

    last_synced_at DATETIME NULL,
    last_sync_status VARCHAR(50) NULL,
    last_sync_message TEXT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_firm (firm_id),
    INDEX idx_type (integration_type)
);
```

### Allowed `integration_type` values:

| Type | Meaning |
|------|---------|
| google_ads | Google Ads |
| google_gbp | Business Profile |
| google_lsa | LSA / Local Services |
| google_analytics | GA4 / UA |
| facebook_meta | Meta Pages + Ads |
| wordpress | WordPress REST API |

---

# 3. Integrations Page UI

Page:  
```
app.firmwizard.com/app/settings/integrations
```

## Grid of cards

Each card shows:

- Icon  
- Name  
- Status badge  
- Last sync time  
- Button: Connect / Reconnect / Manage

## Status colors

- **Green — active**  
- **Yellow — expired**  
- **Red — error**  
- **Gray — not connected**

---

# 4. Connect UI Flows

## Google Ads / GBP / LSA / Analytics

Use OAuth2:

1. User clicks “Connect Google Ads”
2. Frontend calls:

```
GET /api/integrations/google/auth-url?firm_id=X&integration=google_ads
```

3. Redirect to Google OAuth  
4. After login, Google returns to:

```
GET /api/integrations/google/callback
```

5. Backend saves tokens → marks integration ACTIVE  
6. Redirect back to Integrations page

---

## Facebook / Meta

Same OAuth2 flow using Meta Login.

---

## WordPress

Manual fields:

- WordPress Base URL  
- Username  
- App Password  
- Optional: Security nonce bypass

---

# 5. API Endpoints (Backend)

Base path:

```
/api/integrations
```

## List for a firm
```
GET /api/integrations/{firm_id}
```

## Get single integration
```
GET /api/integrations/{firm_id}/{integration_type}
```

## Save settings
```
POST /api/integrations/{firm_id}/{integration_type}/save
```

Example:
```json
{
  "settings_json": {
    "base_url": "https://lawfirm.com",
    "username": "api",
    "app_password": "XXXX"
  }
}
```

## Get Google OAuth URL
```
GET /api/integrations/google/auth-url
```

## Google OAuth Callback
```
GET /api/integrations/google/callback
```

## Force Sync
```
POST /api/integrations/{firm_id}/{integration_type}/sync
```

Used by app and n8n.

---

# 6. n8n Integration Pipelines

## Scheduler workflow

1. n8n calls:
```
GET /api/n8n/integrations/list-active
```

Returns all active tokens/settings:

```json
[
  {
    "firm_id": 3,
    "integration_type": "google_ads",
    "access_token": "xxxx",
    "refresh_token": "xxxx",
    "settings_json": { ... }
  }
]
```

2. For each integration:

```
POST /api/integrations/{firm_id}/{integration_type}/sync
```

Backend logic handles:

- Refresh tokens  
- Pull API data  
- Save to DB tables  
- Update last_synced_at  

---

# 7. Where Data is Stored (per Integration)

## Google Ads  
Table: `google_ads_stats`

Fields:  
- campaign_id  
- date  
- impressions  
- clicks  
- cost  
- conversions  
- cpc  
- ctr  

## Google GBP  
Table: `gbp_insights`

## Google LSA  
Tables:  
- `lsa_leads`  
- `lsa_keywords`  
- `lsa_rankings`  

## Google Analytics  
Table: `google_analytics_stats`

## Facebook Meta  
Tables:  
- `facebook_ads_stats`  
- `facebook_leads`  
- `facebook_pages_insights`  

## WordPress  
Tables:  
- `wp_posts`  
- `wp_pages`  
- `wp_forms`  

---

# 8. Security Architecture

### 1. Tokens must be AES-encrypted:
```
encrypt()
decrypt()
```

### 2. All `/api/integrations/*` routes require:

- Bearer token  
- firm_id ownership validation  
- IP throttling  

### 3. n8n must authenticate using:
```
Authorization: Bearer N8N_SERVICE_TOKEN
```

### 4. OAuth state token must be validated  
Prevents CSRF or malicious redirects.

---

# 9. Implementation Order

1. Build DB tables  
2. Build Models + Relations  
3. Build Integrations Grid UI  
4. Google OAuth backend  
5. Meta OAuth backend  
6. WordPress manual credentials  
7. Sync endpoints  
8. n8n workflows  
9. Integrate into dashboards  
10. Add debugging console for sync logs  

---
# 10. Example Integration Card Config (Frontend)
{
  "google_ads": {
    "title": "Google Ads",
    "icon": "/img/integrations/google-ads.svg",
    "auth_type": "oauth2",
    "description": "Sync campaigns, spend, keywords, conversions."
  },
  "google_gbp": {
    "title": "Google Business Profile",
    "icon": "/img/integrations/gbp.svg",
    "auth_type": "oauth2"
  },
  "wordpress": {
    "title": "WordPress",
    "icon": "/img/integrations/wp.svg",
    "auth_type": "api_key"
  }
}

# 11. Future-Proofing

Make integration system plug-and-play:

Adding a new integration should require
just a new record + sync job.

UI auto-recognizes new integration types.

n8n auto-discovers integrations requiring sync.

# 12. Future Additions

System is designed to add new integrations with:

- 1 new integration card config  
- 1 new sync service class  
- 1 new scheduler rule in n8n  

---

# END OF FILE
