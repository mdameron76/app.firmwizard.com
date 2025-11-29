# FirmWizard Full Attribution System (With firm_id Tracking)

## 1. Overview
This document outlines the complete end-to-end attribution tracking architecture for FirmWizard — including pixel logic, GBP redirect tracking, multi-touch attribution, database structures, and event flow. This updated version includes the **firm_id** embedded in the tracking pixel to support multi-firm data isolation.

---

# 2. The FirmWizard Pixel (With firm_id)

Place this script on every client website:

```html
<script>
  (function() {
    let fwid = localStorage.getItem("fwid");
    if (!fwid) {
      fwid = "fw_" + crypto.randomUUID();
      localStorage.setItem("fwid", fwid);
    }

    let fw = {
      firm_id: "{{firm_id}}",  // Inject dynamically per firm
      fwid: fwid,
      url: window.location.href,
      referrer: document.referrer || null,
      user_agent: navigator.userAgent,
      utm: Object.fromEntries(new URLSearchParams(window.location.search).entries()),
      ts: Date.now()
    };

    fetch("https://oz.firmwizard.com/api/pixel/collect", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(fw)
    });
  })();
</script>
```

### Pixel captures:
- **firm_id** (identifies the law firm)
- **FWID** (FirmWizard visitor ID)
- UTMs
- Referrer
- Device info / user agent
- Landing pages
- Timestamp

---

# 3. GBP Redirect Tracking

Create a redirect endpoint on the client site:

Example:
```
https://clientdomain.com/gbp
```

When a user clicks from Google Business Profile → this endpoint:

### What happens:
1. Pixel fires and logs **GBP Click**
2. FWID + firm_id + metadata are stored
3. User is instantly redirected to homepage with appended UTMs:

```
https://clientdomain.com/?utm_source=GBP&utm_medium=organic&utm_campaign=google-my-business
```

---

# 4. Event Types Captured

### Website events:
- Pageviews
- Pixel loads
- UTM passthrough
- Click paths

### Conversion events:
- Web forms
- Phone calls (CallRail)
- Chat widgets
- LSA leads
- Google Ads gclid
- Facebook fbclid

### Manually defined events:
- GBP redirect clicks
- Calendly bookings (optional)
- Email reply tracking (optional)

---

# 5. Unified Event Intake → ai_attribution_events

Every event flows into:

```
POST /api/pixel/collect
```

Saved to:

```
ai_attribution_events
```

### Example record:

```json
{
  "firm_id": 12,
  "fwid": "fw_84ab129f",
  "gclid": null,
  "fbclid": null,
  "utm_source": "GBP",
  "utm_medium": "organic",
  "utm_campaign": "google-my-business",
  "crm_lead_id": null,
  "crm_case_id": null,
  "matched_with": "first_touch",
  "event_type": "gbp_click",
  "created_at": "2025-01-02 14:12:09"
}
```

---

# 6. Attribution Engine Logic

Supports multiple models:

### First-Touch
Origin source gets credit.

### Last-Touch
Final channel gets credit.

### Linear Multi-Touch
Each touchpoint gets weighted equally.

### Position-Based (40/20/40)
Best for law firms.

### Time-Decay
Recent touches weighted heavier.

---

# 7. Lead & Case Matching

When a CRM lead is created:
1. The FWID field is captured from hidden fields
2. All pixel events tied to that FWID become the lead timeline
3. If the lead becomes a case, that case inherits the FWID history

---

# 8. Per-Lead Timeline Viewer

Example:

```
FWID: fw_84ab129f
----------------------------------------
2025-01-02 09:12   GBP Click
2025-01-02 09:12   /divorce-attorney
2025-01-02 09:14   CallRail: inbound call (93 sec)
2025-01-02 09:15   CRM Lead Created
2025-01-06 10:22   Consultation Booked
2025-01-10 16:55   Case Opened
----------------------------------------
```

---

# 9. Marketing ROI Dashboard

For each **firm_id**, show per-channel:

- Spend
- Leads
- Consults
- Signed cases
- Revenue per channel
- ROI
- Cost per retained case
- Top performing channels
- Weak channels & recommendations

---

# 10. Recommended Next Steps
1. Deploy pixel with firm_id
2. Add GBP redirect endpoint
3. Capture FWID into all forms - <input type="hidden" name="fwid" id="fwid">
4. Build unified attribution ingestion service
5. Build AI scoring & recommendation engine
6. Build visuals in FirmWizard Analytics

---

# 11. Summary

This system gives FirmWizard:

- True multi-touch attribution  
- Full GBP tracking  
- LSA + Ads + SEO performance clarity  
- Unified cross-device identity (FWID)  
- Revenue-based ROI calculations  
- AI recommendations powered by event data  

FirmWizard becomes a **source-of-truth attribution platform** for law firms.
