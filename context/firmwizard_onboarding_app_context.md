# FirmWizard Standalone Onboarding App — Build Context & AI Prompts

This file is meant to be **copied into an AI code assistant** (ChatGPT / Claude / Copilot, etc.) as context while you build `app.firmwizard.com` — the standalone onboarding + insights app for firms that are **not** using the full CRM.

It’s organized in progressive stages:

1. **Vision & Architecture**
2. **Data Model & APIs**
3. **5‑Step Smart Onboarding Wizard**
4. **AI Suggestion Prompts (OpenAI)**
5. **AI Agents (Blog, Competitors, GBP, Ads, etc.)**
6. **UI/UX & Visual Direction**
7. **Implementation Roadmap (Laravel)**

Where you see **PROMPT TEMPLATES**, you can literally paste those into your AI coding buddy with your current codebase for help.

---

## 1. Vision & High‑Level Architecture

### 1.1 Goal

Build a **standalone helper app** for FirmWizard (`app.firmwizard.com`) for firms that **don’t want to use the full CRM**, but:

- Will let us **integrate** with whatever they’re using now, **or**
- Will **manually upload** their client/case data.

The app will:

- Walk firms through a **5‑step smart onboarding wizard** (7–10 minutes).
- Generate a **normalized AI-ready firm profile JSON**.
- POST that profile to **n8n / oz** to kick off data collection & analysis.
- Provide a **dashboard full of stats, graphs, charts, animated visuals** around their marketing footprint.
- Expose a hub of **AI Agents** (blog, competitors, website, GBP, Google Ads, LSA, Meta Ads, Backlinks, GA, Firm Profile, etc.).

### 1.2 Components

- **app.firmwizard.com**
  - Laravel + MySQL app.
  - Manages users, firms, onboarding wizard, firm profiles, dashboards, and AI agent UI.
  - Talks to `collector.firmwizard.com` and `oz.firmwizard.com` via HTTP APIs and via n8n webhooks.
- **collector.firmwizard.com**
  - Data collector service (already defined in `collector.sql`).
  - Crawls/ingests external data (competitors, SERPs, GBP, Ads, backlinks, analytics, etc.).
  - Feeds data into internal DB + oz.
- **oz.firmwizard.com**
  - AI Engine (see `112520250417.sql`).
  - Stores AI-generated insights, recommendations, scores, etc.
  - Exposes endpoints / queues consumed by app.

### 1.3 Tech Stack

- **Backend:** Laravel (PHP), MySQL (MariaDB OK)
- **Frontend:** Blade + Alpine / Vue (you can decide later; prompts will stay framework-agnostic)
- **Queue:** Laravel Queues (Redis / DB)
- **Source Control:** GitHub: `https://github.com/mdameron76/app.firmwizard.com.git`
- **DB Connection (local dev):**

```env
DB_HOST=localhost
DB_DATABASE=app_firmwizard
DB_USER=root
DB_PASSWORD=LLK708vz12@
DB_PORT=3306
```

Use this .md as the “north star” so all helper prompts stay aligned.

---

## 2. Data Model & API Surface (App-Centric)

> Note: We **can’t see** the actual `collector.sql` and `oz.sql` here, so we design **app_firmwizard** in a way that’s flexible and maps cleanly to those existing DBs later.

### 2.1 Core Tables in `app_firmwizard`

**High-level entities:**

- `users`
- `firms`
- `firm_user` (pivot for multi-user / multi-firm)
- `firm_profiles` (high-level info: name, website, main office)
- `firm_onboarding_sessions` (wizard tracking)
- `ai_firm_profiles` (JSON profile built from onboarding + collector + oz)
- `ai_firm_profile_versions` (history / snapshots)
- `firm_metrics_daily` / `firm_metrics_monthly` (aggregated metrics for charts)
- `ai_jobs` (per-agent jobs: blog, competitors, etc.)

You can ask your AI coder to generate migrations.

**PROMPT TEMPLATE — Generate app_firmwizard Schema**

> _Use this when you’re ready to build migrations._

> You are a senior Laravel architect.
> I am building a new Laravel app called `app.firmwizard.com`. I need migrations for the following tables, optimized for MySQL:
>
> - `firms`: id, name, slug, website_url, primary_email, primary_phone, timezone, created_at, updated_at.
> - `users`: use Laravel’s default `users` table structure with name, email, password, email_verified_at, remember_token, etc.
> - `firm_user`: pivot table connecting users to firms with role (owner, admin, staff), created_at.
> - `firm_profiles`: firm_id (FK), main_office_city, main_office_state, main_office_zip, practice_areas_json, brand_voice_json, target_demographics_json, service_areas_json, marketing_goals_json, onboarding_completed_at, created_at, updated_at.
> - `firm_onboarding_sessions`: id, firm_id, user_id, current_step (1-5), completed_steps_json, wizard_data_json, is_completed (bool), completed_at, created_at, updated_at.
> - `ai_firm_profiles`: id, firm_id, source (onboarding|collector|oz), profile_json (LONGTEXT), version, is_active, created_at, updated_at.
> - `ai_firm_profile_versions`: id, firm_id, ai_firm_profile_id, profile_json, created_at.
> - `firm_metrics_daily`: id, firm_id, date, metric_type, metric_value (decimal), meta_json.
> - `firm_metrics_monthly`: id, firm_id, month (YYYY-MM), metric_type, metric_value, meta_json.
> - `ai_jobs`: id, firm_id, job_type (blog_post|competitor_analysis|website_analysis|gbp_analysis|google_ads|lsa_ads|meta_ads|backlinks|analytics|firm_profile), input_json, output_json, status (pending|running|completed|failed), started_at, finished_at, error_message.
>
> Please:
> - Write Laravel migrations with foreign key constraints.
> - Add appropriate indexes on `firm_id`, `metric_type`, `date`, `month`, and `job_type`.
> - Use snake_case for all columns.
> - Use `json` type where appropriate and `longText` for large JSON bodies.
> Return ONLY the migration classes, no explanation text.


### 2.2 Internal API Endpoints (App)

The app will expose internal APIs (for SPA front-end and for n8n / oz):

- `POST /api/firms` — create firm (internal use / admin)
- `GET /api/firms/{firm}` — show firm
- `GET /api/firms/{firm}/profile` — get merged firm profile (`ai_firm_profiles` active)
- `POST /api/firms/{firm}/onboarding/start`
- `GET /api/firms/{firm}/onboarding/current`
- `POST /api/firms/{firm}/onboarding/step/{step}` — save step data
- `POST /api/n8n/firms/{firm}/profile` — called from wizard completion; sends user-input-only profile
- `PATCH /api/n8n/firms/{firm}/profile` — called by n8n when competitor + keyword data is ready
- `GET /api/firms/{firm}/metrics/summary`
- `GET /api/firms/{firm}/metrics/timeseries`
- `POST /api/firms/{firm}/ai-jobs` — create job for an AI Agent
- `GET /api/firms/{firm}/ai-jobs/{job}` — poll job status/results

**PROMPT TEMPLATE — Generate Laravel API Controllers & Routes**

> You are a Laravel expert. I’m building a JSON API for `app.firmwizard.com`.
> Based on the following endpoints, generate:
> - API routes in `routes/api.php`
> - Skeleton controllers with method signatures and basic validation only
>
> Endpoints:
> - `POST /api/firms`
> - `GET /api/firms/{firm}`
> - `GET /api/firms/{firm}/profile`
> - `POST /api/firms/{firm}/onboarding/start`
> - `GET /api/firms/{firm}/onboarding/current`
> - `POST /api/firms/{firm}/onboarding/step/{step}`
> - `POST /api/n8n/firms/{firm}/profile`
> - `PATCH /api/n8n/firms/{firm}/profile`
> - `GET /api/firms/{firm}/metrics/summary`
> - `GET /api/firms/{firm}/metrics/timeseries`
> - `POST /api/firms/{firm}/ai-jobs`
> - `GET /api/firms/{firm}/ai-jobs/{job}`
>
> Assume standard Laravel 11, API auth via Sanctum, and Eloquent models for Firm, FirmProfile, FirmOnboardingSession, AiFirmProfile, FirmMetricDaily, AiJob. Return only the code.


---

## 3. 5‑Step Smart Onboarding Wizard

**Rule:** No free-text fields unless strictly necessary. The wizard uses **visual controls** (checkboxes, dropdowns, sliders, rank lists) and leverages OpenAI to **offer 3–5 curated options** whenever users need help.

### 3.1 Shared Wizard Behavior

- Each step saved into `firm_onboarding_sessions.wizard_data_json`.
- When all 5 steps done → construct **user-input-only profile** → POST to `/api/n8n/firms/{firmId}/profile`.
- n8n collects external data, then PATCHes to merge discovered data into `ai_firm_profiles`.

**Base JSON structure examples** below use your sample data.

---

### 3.2 Step 1 — Brand Voice

**Goal:** Capture tone/style/values + core messaging.

**Fields (selectable):**

- `tone` (single-select dropdown)
  - Examples: `Compassionate yet strong`, `Direct and fearless`, `Calm and reassuring`, `Strategic and precise`.
- `style` (single-select dropdown)
  - Examples: `Professional but approachable`, `Highly formal`, `Conversational and plain-language`.
- `values` (multi-select, max 3–5)
  - Examples: `Client-focused`, `Empathetic`, `Assertive advocacy`, `Family-first approach`, `Results-driven`.
- `messaging` (single select from AI-suggested taglines, plus “Custom” option).

**Target JSON:**

```json
"brand_voice": {
  "tone": "Compassionate yet strong",
  "style": "Professional but approachable",
  "values": [
    "Client-focused",
    "Empathetic",
    "Assertive advocacy",
    "Family-first approach"
  ],
  "messaging": "Guiding families through difficult transitions with strength and compassion"
}
```

---

### 3.3 Step 2 — Practice Areas

**Goal:** Map their actual work to standardized practice area taxonomy.

**Controls:**
- Primary practice type: Dropdown (Family Law, Criminal Defense, PI, etc.)
- Detailed areas: Checkbox list + “Add custom practice area” option with AI help.

**Example Practice Areas from sample:**

```json
"practice_areas": [
  "Family Law",
  "Divorce",
  "Child Custody",
  "Custody Modifications",
  "Paternity Actions",
  "Child Support",
  "Protection Orders",
  "Domestic Violence Cases",
  "Adoption",
  "Contempt & Enforcement Motions",
  "Grandparent Rights"
]
```

Store in `firm_profiles.practice_areas_json` and as part of `ai_firm_profiles.profile_json`.

---

### 3.4 Step 3 — Target Clients

**Goal:** Capture demographics + psychographics + pain points.

**Controls:**
- Age range: two sliders (min, max).
- Income: selectable bands.
- Pain points: checkbox list (with AI-recommended defaults based on practice areas).
- Geography: high-level area (hooked into Step 4).
- Situational triggers: multi-select from AI suggestions.

**JSON example:**

```json
"target_demographics": {
  "primary": "Women facing divorce, custody battles, or urgent family law crises",
  "age_range": "28-62",
  "income_level": "Working class to upper-middle class",
  "pain_points": [
    "High-conflict custody disputes",
    "Feeling overwhelmed by court deadlines and legal processes",
    "Fear of losing time or rights with their children",
    "Need for fast, decisive legal action during emergencies",
    "Financial stress during separation",
    "Protection from abusive or manipulative partners",
    "Confusion about parental rights and obligations"
  ],
  "demographics": "Primarily residents of Jefferson, Ste. Genevieve, St. Francois, and surrounding Missouri counties",
  "psychographics": [
    "Value direct communication and fast responses from their attorney",
    "Prefer an attorney who will fight aggressively when needed",
    "Often distrustful of the legal system due to past experiences",
    "Seeking stability, safety, and a clear plan forward during emotional turmoil"
  ],
  "situational_triggers": [
    "Spouse files for divorce unexpectedly",
    "Emergency custody issues arise",
    "Domestic violence requiring immediate protection",
    "Court notices or deadlines they don’t understand",
    "A prior attorney failed to take action or communicate",
    "Major life transitions such as separation, relocation, or financial hardship"
  ]
}
```

---

### 3.5 Step 4 — Service Area

**Goal:** Precisely define where they want to appear and advertise.

**Controls:**
- Primary office: city/state/zip (pre-filled if known).
- Radius slider (e.g., 10–75 miles).
- Auto-populated:
  - Counties
  - Nearby cities
  - Zip codes

Use **Maps/Geocoding API** (e.g., Google Maps Places + Distance Matrix) to fetch:

```json
"service_areas": {
  "primary_location": {
    "city": "Festus",
    "state": "MO",
    "zip": "63028"
  },
  "counties": [
    "Jefferson County",
    "St. Francois County"
  ],
  "radius_miles": 20,
  "zip_codes": [
    "63028",
    "63050",
    "63051",
    "63080",
    "63087"
  ],
  "nearby_cities": [
    "Crystal City",
    "Hillsboro",
    "De Soto",
    "Pevely",
    "Herculaneum",
    "Imperial",
    "Barnhart"
  ]
}
```

---

### 3.6 Step 5 — Marketing Goals

**Goal:** Budget expectations + desired outcomes.

**Controls:**
- Monthly marketing budget: slider or stepped select (e.g., `$1k–$2k`, `$2k–$5k`, etc.).
- Rank goals (drag-and-drop):
  - “Increase qualified leads”
  - “Grow Google Maps ranking”
  - “Improve website conversion rates”
  - “Dominate local search for key terms”
  - “Increase case quality (higher-value matters)”
- Auto-estimate: `"Based on your budget, expect 12–18 leads/month"` (rough AI-powered estimate).

**JSON skeleton:**

```json
"marketing_goals": {
  "monthly_budget_range": "2000-5000",
  "primary_goals_ranked": [
    "Increase qualified family law leads",
    "Improve Google Maps visibility in core cities",
    "Increase consultation bookings from website"
  ],
  "lead_volume_estimate": {
    "min_leads": 12,
    "max_leads": 18,
    "confidence": 0.65
  }
}
```

---

## 4. AI Suggestions (OpenAI) — Prompt Patterns

**Principle:** The wizard should feel **relaxing and guided**. Instead of asking, “What’s your tone?”, we ask OpenAI to generate 3–5 good options *based on what we already know*.

### 4.1 Shared System Prompt for Wizard Suggestions

Use a consistent system prompt for all wizard calls:

> You are an expert legal marketing strategist specializing in family law and small law firms. The user is an attorney or law firm staff member filling out a smart onboarding form. They do NOT want to type paragraphs. Your job is to turn their existing answers into a small set of 3–5 clear, pre-written options they can choose from.
>
> Rules:
> - Always return STRICT JSON.
> - Keep each option short, specific, and non-generic.
> - Tailor wording to the data provided (practice areas, service locations, target clients, brand voice).
> - Never ask questions, just provide options.
> - Avoid jargon and buzzwords; use plain language a stressed-out client would understand.
> - Do not include any explanations outside of JSON.


### 4.2 Example Prompt — Brand Voice Options (Step 1)

**Input fields available:** practice areas, location, target_demographics (if already partially filled).

**Prompt to send to OpenAI:**

```json
{
  "role": "user",
  "content": "Based on the following firm information, generate 3-5 brand voice options. Each option should specify: tone, style, core_values (3-5 bullet points), and a short core_message tagline.\n\nFirm data:\n{{firm_data_json}}\n\nReturn JSON with this shape:\n{\n  \"brand_voice_options\": [\n    {\n      \"id\": \"option_1\",\n      \"tone\": \"...\",\n      \"style\": \"...\",\n      \"core_values\": [\"...\"],\n      \"core_message\": \"...\"\n    }\n  ]\n}"
}
```

The UI then renders those as selectable cards.

### 4.3 Example Prompt — Target Pain Points (Step 3)

```json
{
  "role": "user",
  "content": "The firm primarily handles the following practice areas and works in these locations. Suggest 5-7 realistic client pain points (phrased from the client's perspective) for this firm.\n\nData:\n{{firm_data_json}}\n\nReturn strictly JSON:\n{\n  \"pain_point_options\": [\n    \"...\",\n    \"...\"\n  ]\n}"
}
```

### 4.4 Example Prompt — Marketing Goal Suggestions (Step 5)

```json
{
  "role": "user",
  "content": "Given this firm profile and their approximate budget range, suggest 4-6 prioritized marketing goals for family law marketing. Focus on measurable outcomes.\n\nFirm profile:\n{{firm_data_json}}\n\nReturn strictly JSON:\n{\n  \"goal_options\": [\n    \"Increase qualified family law leads from Google Search\",\n    \"Improve Google Maps rankings in top 5 target cities\",\n    \"Boost consultation bookings from website contact forms\",\n    \"Increase signed cases involving child custody and protection orders\"\n  ]\n}"
}
```

---

## 5. AI Agents — Definitions & Prompts

Each Agent pulls from:

- `ai_firm_profiles.profile_json` (merged view)
- Collector data (competitors, SERPs, ads)
- Oz/AI historical recommendations

### 5.1 Common Agent Requirements

For every agent job in `ai_jobs`:

- `job_type` identifies agent.
- `input_json` includes firm_id, current profile, and any extra parameters (date ranges, focus areas).
- `output_json` will hold structured results (headlines, recommendations, action items).
- Agents should be **idempotent** (can re-run later).

**Generic Agent System Prompt:**

> You are OZ, an AI marketing strategist for law firms. You receive a firm’s structured profile plus relevant marketing data. Your job is to return **actionable, structured recommendations**, not filler text. Always respond in JSON that can be directly rendered by a dashboard.
>
> Every list of recommendations must:
> - Label items by priority (high, medium, low).
> - Include a clear “why this matters” explanation.
> - Include a short “first step” suggestion for implementation.


### 5.2 Blog Post Agent

- **job_type:** `blog_post`
- **Goal:** Generate SEO blog topics + outlines tailored to firm.

**Desired Output JSON (example):**

```json
{
  "topics": [
    {
      "slug": "emergency-child-custody-in-jefferson-county",
      "title": "Emergency Child Custody in Jefferson County: What You Need to Know Today",
      "target_keyword": "emergency child custody jefferson county mo",
      "search_intent": "Informational",
      "priority": "high",
      "why_it_matters": "Addresses urgent, high-value cases that align with the firm’s strengths in high-conflict custody.",
      "outline": [
        "Intro: When every day counts",
        "What qualifies as an emergency custody situation in Missouri",
        "How judges in Jefferson County typically view emergency filings",
        "Step-by-step: What to do in the first 24–48 hours",
        "Common mistakes parents make (and how to avoid them)",
        "How our firm can help you today"
      ]
    }
  ]
}
```

**PROMPT TEMPLATE — Blog Post Agent**

> Use this when implementing blog agent logic or just testing it.

> Using the firm profile and competitor data below, generate 8–12 blog post topics and outlines optimized for SEO and for attracting our ideal clients.\n\nFirm Profile JSON:\n{{ai_firm_profile_json}}\n\nCompetitor/Keyword JSON:\n{{competitor_data_json}}\n\nReturn strictly JSON using this shape:\n{ \"topics\": [ { ... } ] } (like the example provided.


### 5.3 Competitor Analysis Agent

- **job_type:** `competitor_analysis`
- Input: firm profile + competitor list + SERP snapshots.
- Output: summary of competitive landscape + gaps.

Structure suggestion:

```json
{
  "competitor_overview": [
    {
      "name": "Competitor Firm A",
      "city": "Festus",
      "primary_practice": "Family Law",
      "strengths": ["Strong GBP presence", "High number of Google reviews"],
      "weaknesses": ["Weak content on custody modifications"],
      "visibility_score": 82
    }
  ],
  "opportunities": [
    {
      "priority": "high",
      "description": "Own emergency custody in Jefferson County search results.",
      "why_it_matters": "High-intent, urgent cases with strong alignment to firm strengths.",
      "first_step": "Create a dedicated landing page for emergency custody in Jefferson County and run LSAs targeting related queries."
    }
  ]
}
```

Create similar structured output/ prompts for Website Analysis, GBP, Google Ads, LSA Ads, Meta Ads, Backlinks, Analytics, Firm Profile Deep-Dive.

---

## 6. UI/UX & Visual Direction

### 6.1 Color Palette

Use this palette consistently:

- **#2a1437** — Deep background / sidebar
- **#220f2d** — Headers, panels, accents
- **#8fd4d9** — Primary accent (buttons, highlights, progress)
- **#c99c58** — Secondary accent (wizard step icons, KPI badges)

Style direction:

- Clean, modern SaaS look.
- Minimal text per screen, plenty of whitespace.
- Stepper at top for 5-step wizard with clear labeling.
- Animated but subtle transitions between steps.
- Simple iconography for each step (voice, gavel, user, map pin, target).

### 6.2 Screen Inventory

- **Auth:**
  - Register
  - Login
  - Forgot Password
- **Firm Selection:**
  - Choose Firm (for users with multiple)
- **Dashboard:**
  - “Finish your onboarding” hero if incomplete.
  - Key stats once data is live: leads estimate, visibility score, GBP health, Ads coverage, etc.
- **Onboarding Wizard:**
  - 5 full-width steps, each with:
    - Left: short description + reassurance copy.
    - Right: cards, checkboxes, sliders, AI suggestion options.
- **AI Agents Hub:**
  - Tiles/cards for each agent (Blog, Competitors, Website, GBP, Ads, etc.)
  - Status badges: “Ready”, “Running”, “New results”, “Needs onboarding.”
- **Profile View:**
  - Read-only view of AI Firm Profile with tabs (Brand Voice, Practice Areas, Clients, Service Area, Goals).

**PROMPT TEMPLATE — Tailwind/Blade Page Mockups**

> You are a senior front-end engineer using Laravel Blade + TailwindCSS.
> Using the following color palette and requirements, create Blade templates (no layout, just `<div>` contents) for:
> - A 5-step onboarding wizard layout.
> - A dashboard page showing KPIs and links to AI agents.
>
> Colors:
> - #2a1437 (main background)
> - #220f2d (cards/headers)
> - #8fd4d9 (primary buttons/accents)
> - #c99c58 (secondary accents)
>
> Design requirements:
> - Centered max-width container.
> - Stepper at the top for the wizard.
> - Clean spacing, large readable typography.
> - Use Tailwind utility classes only.
> Return only the Blade-compatible HTML snippets.


---

## 7. Implementation Roadmap (Laravel)

Use this as your “do this next” checklist.

### Phase 0 — Bootstrap Project

1. Create Laravel project (if not already) and push to `app.firmwizard.com` repo.
2. Configure `.env` with DB creds.
3. Install Laravel Breeze / Jetstream for auth (your choice).
4. Create `Firm`, `FirmProfile`, `FirmOnboardingSession`, `AiFirmProfile`, `FirmMetricDaily`, `AiJob` models.

### Phase 1 — Auth & Firm Setup

1. Migrations + seeders for `firms`, `users`, `firm_user`.
2. Simple UI for internal admin to create a firm + assign initial user.
3. On login, redirect to **Firm Selector** if user has multiple firms.
4. Once firm selected, store `current_firm_id` in session.

### Phase 2 — Onboarding Wizard

1. Create `FirmOnboardingSession` service class to read/write wizard state.
2. Build the 5-step wizard UI + API endpoints.
3. Integrate OpenAI suggestions for each step (server-side endpoints that return option lists).
4. On completion:
   - Build JSON profile from steps (brand_voice, practice_areas, target_demographics, service_areas, marketing_goals).
   - Save snapshot to `ai_firm_profiles` with `source = "onboarding"`.
   - Trigger `POST /api/n8n/firms/{firmId}/profile` (or call n8n webhook) with this JSON.

### Phase 3 — Collector & Oz Integration

1. When n8n finishes competitor/keyword discovery, it will call `PATCH /api/n8n/firms/{firmId}/profile` using your existing pattern:
   - Merge discovered data into `ai_firm_profiles` (new version).
   - Mark as active profile.
2. Implement endpoints to read metrics and AI outputs from `collector` and `oz` DBs or APIs and aggregate into `firm_metrics_daily` / `monthly`.

### Phase 4 — Dashboard & AI Agents

1. Dashboard page:
   - Show high-level KPIs from `firm_metrics_*`.
   - Show “Onboarding complete / in progress” status.
   - Show “Next Best Action” from oz (if available).
2. AI Agents hub:
   - UI to start new `ai_jobs` per agent type.
   - Background queue workers that:
     - Pull firm profile + collector data.
     - Call oz/OpenAI with appropriate prompts.
     - Store structured output in `ai_jobs.output_json`.
3. Detail pages per agent type rendering results nicely.

### Phase 5 — Polish & Production

1. Add RBAC (roles on `firm_user`).
2. Add audit logging on major changes (profile updates, major recommendations).
3. Harden API validation and authentication (Sanctum, rate limiting).
4. Prepare deployment pipeline (GitHub → your hosting).

---

You can now feed pieces of this .md into your AI coding assistant as you go, using the **PROMPT TEMPLATES** to generate migrations, routes, controllers, frontend views, and integration code step-by-step.
