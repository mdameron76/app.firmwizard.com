# AI Agent System Rebuild - Implementation Plan

## Vision
Transform FirmWizard into a fully autonomous AI marketing system that:
- Reads AI-generated marketing recommendations
- Generates actual content (blog posts, ads, profiles, etc.)
- Integrates with third-party platforms (WordPress, Google, Meta, etc.)
- Shows content for approval before publishing
- Automatically publishes approved content via platform APIs

## Data Source
- **Table**: `ai_marketing_recommendations`
- **Key Field**: `payload` (JSON) - contains strategies like `blog_content_plan`, `google_ads_strategy`, etc.
- **Scope**: Per firm (`firm_id`)

---

## Phase 0: Integration System (Foundation)
**Goal**: Build a flexible integration system that supports multiple authentication methods and platforms.

### Tasks:
- [ ] **0.1**: Create `integrations` table
  - Columns: `id`, `firm_id`, `platform` (wordpress, google_ads, meta_ads, gbp, etc.), `auth_type` (bearer_token, oauth2, jwt, openid), `credentials` (encrypted JSON), `status` (active/inactive), `last_synced_at`, `created_at`, `updated_at`
  
- [ ] **0.2**: Create Integration Model & Migrations
  - `Integration` model with encrypted credentials
  - Relationships: `belongsTo(Firm)`
  
- [ ] **0.3**: Create Integration Service Classes
  - `IntegrationManager` - handles CRUD for integrations
  - `AuthHandlers/BearerTokenAuth.php`
  - `AuthHandlers/OAuth2Auth.php`
  - `AuthHandlers/JwtAuth.php`
  - `AuthHandlers/OpenIdAuth.php`
  
- [ ] **0.4**: Create Integration API Endpoints
  - `POST /api/firms/{firm}/integrations` - Add new integration
  - `GET /api/firms/{firm}/integrations` - List all integrations
  - `GET /api/firms/{firm}/integrations/{integration}` - Get single integration
  - `PUT /api/firms/{firm}/integrations/{integration}` - Update integration
  - `DELETE /api/firms/{firm}/integrations/{integration}` - Remove integration
  - `POST /api/firms/{firm}/integrations/{integration}/test` - Test connection
  
- [ ] **0.5**: Create Integration UI Components
  - Integrations settings page
  - Add/Edit integration modal with dynamic auth fields based on `auth_type`
  - Connection status indicators
  - Test connection button

**Deliverable**: Fully functional integration system that can store and manage API credentials for any platform.

---

## Phase 1: UI/UX Restructure
**Goal**: Create proper navigation and layout structure for the AI agent system.

### Tasks:
- [ ] **1.1**: Add Left Sidebar Navigation
  - Dashboard (home icon)
  - AI Agents (dropdown with sub-items):
    - Blog Posts
    - Competitor Analysis
    - Website Analysis
    - Google Business Profile
    - Google Ads
    - Local Services Ads
    - Meta Ads
    - Backlinks
    - Analytics
    - Firm Profile
  - Integrations (settings icon)
  - Settings
  
- [ ] **1.2**: Create Dashboard Page
  - Widget cards for each AI agent showing:
    - Agent name & icon
    - Last run timestamp
    - Status (pending/completed/failed)
    - Quick stats (e.g., "3 blog posts pending approval")
    - Animated status indicators
  
- [ ] **1.3**: Update Routing
  - `/dashboard` - Main dashboard
  - `/ai-agents/blog-posts` - Blog post management
  - `/ai-agents/competitor-analysis` - Competitor insights
  - `/ai-agents/[agent-name]` - Pattern for each agent
  - `/integrations` - Integration management

**Deliverable**: Professional navigation structure with dashboard and individual agent pages.

---

## Phase 2: Blog Post Agent (Complete Feature)
**Goal**: Fully functional blog post generation, approval, and WordPress publishing system.

### Step 2.1: Database & Models
- [ ] **2.1.1**: Create `ai_generated_content` table
  - Columns: `id`, `firm_id`, `agent_type` (blog_post, ad_copy, etc.), `recommendation_id` (FK to ai_marketing_recommendations), `title`, `content` (text/HTML), `meta_description`, `keywords` (JSON), `status` (pending/approved/rejected/published), `preview_url`, `published_url`, `published_at`, `approved_by`, `approved_at`, `created_at`, `updated_at`
  
- [ ] **2.1.2**: Create `AiGeneratedContent` Model
  - Relationships: `belongsTo(Firm)`, `belongsTo(AiMarketingRecommendation, 'recommendation_id')`
  - Scopes: `pending()`, `approved()`, `published()`

### Step 2.2: WordPress Integration Service
- [ ] **2.2.1**: Create `WordPressIntegration` service class
  - Methods:
    - `testConnection()` - Verify API access
    - `createDraftPost($title, $content, $metadata)` - Create draft post
    - `publishPost($postId)` - Publish existing draft
    - `getPost($postId)` - Get post details
    - `listPosts($params)` - List existing posts
    - `updatePost($postId, $data)` - Update post
  
- [ ] **2.2.2**: Support WordPress REST API authentication
  - Application Passwords (WordPress 5.6+)
  - JWT plugin support (optional)

### Step 2.3: Content Generation Service
- [ ] **2.3.1**: Create `BlogPostGeneratorService`
  - Read `blog_content_plan` from latest `ai_marketing_recommendations` for firm
  - For each article in the plan:
    - Generate full blog post content using OpenAI (title, intro, body, conclusion)
    - Generate meta description
    - Extract/format keywords
    - Save to `ai_generated_content` with status `pending`
  
- [ ] **2.3.2**: Implement OpenAI structured output for blog posts
  - JSON schema for consistent blog post format
  - Include H2/H3 headings, paragraphs, lists
  - FAQ schema generation
  - Internal linking suggestions

### Step 2.4: Blog Post Management Page
- [ ] **2.4.1**: Create `/ai-agents/blog-posts` page
  - Top section: Pending posts (highlighted)
  - List of all generated posts with filters (pending/approved/published)
  - Each post shows: title, excerpt, status badge, keywords, created date
  - Action buttons: Preview, Approve, Reject, Delete
  
- [ ] **2.4.2**: Create Blog Post Preview Modal
  - Full post preview with formatting
  - Meta information (keywords, description)
  - Buttons: Approve & Publish, Save as Draft, Reject
  
- [ ] **2.4.3**: Create Blog Post API Endpoints
  - `GET /api/firms/{firm}/blog-posts` - List all generated posts
  - `POST /api/firms/{firm}/blog-posts/generate` - Trigger blog post generation from recommendations
  - `GET /api/firms/{firm}/blog-posts/{post}` - Get single post
  - `POST /api/firms/{firm}/blog-posts/{post}/approve` - Approve post
  - `POST /api/firms/{firm}/blog-posts/{post}/publish` - Publish to WordPress
  - `POST /api/firms/{firm}/blog-posts/{post}/reject` - Reject post
  - `DELETE /api/firms/{firm}/blog-posts/{post}` - Delete post

### Step 2.5: Publishing Workflow
- [ ] **2.5.1**: Implement approval workflow
  - User clicks "Approve & Publish" → creates draft in WordPress → updates status to `approved`
  - Store WordPress post ID in `ai_generated_content`
  
- [ ] **2.5.2**: Implement publish workflow
  - Check WordPress integration exists and is active
  - Create draft post in WordPress
  - Update status to `published`
  - Store `published_url` and `published_at`
  - Send notification/email to user
  
- [ ] **2.5.3**: Handle errors gracefully
  - WordPress API failures
  - Authentication errors
  - Missing integration errors

### Step 2.6: Dashboard Widget
- [ ] **2.6.1**: Create "Blog Posts" dashboard widget
  - Show count of pending posts
  - Show count of published posts this month
  - Last generated date
  - Animated pulse indicator for pending posts
  - Quick link to blog post management page

**Deliverable**: Complete blog post generation, approval, and WordPress publishing system.

---

## Phase 3: Competitor Analysis Agent
**Goal**: Pull competitor data from crawler, analyze with AI, display insights.

### Tasks:
- [ ] **3.1**: Create Competitor Analysis page
- [ ] **3.2**: Read competitor data from `competitors` and related tables
- [ ] **3.3**: Enhance `CompetitorAnalysisAgent` to use real data
- [ ] **3.4**: Display insights: competitor strengths/weaknesses, opportunities, recommendations
- [ ] **3.5**: Add dashboard widget

**Deliverable**: Functional competitor analysis with real crawler data.

---

## Phase 4: Google Business Profile Agent
**Goal**: Generate GBP optimization recommendations and auto-apply changes via Google My Business API.

### Integration Required:
- **Google My Business API** (OAuth2)

### Tasks:
- [ ] **4.1**: Create Google My Business integration (OAuth2 flow)
- [ ] **4.2**: Create GBP optimization page
- [ ] **4.3**: Implement `GbpAnalysisAgent` with real API data
- [ ] **4.4**: Generate optimization suggestions (posts, photos, hours, attributes)
- [ ] **4.5**: Auto-publish approved GBP posts
- [ ] **4.6**: Add dashboard widget

**Deliverable**: GBP optimization with auto-publishing capability.

---

## Phase 5: Google Ads Agent
**Goal**: Analyze Google Ads performance and generate campaign recommendations.

### Integration Required:
- **Google Ads API** (OAuth2)

### Tasks:
- [ ] **5.1**: Create Google Ads integration
- [ ] **5.2**: Create Google Ads analysis page
- [ ] **5.3**: Implement `GoogleAdsAgent` with real API data
- [ ] **5.4**: Display campaign performance metrics
- [ ] **5.5**: Generate keyword opportunities and bid recommendations
- [ ] **5.6**: Add dashboard widget

**Deliverable**: Google Ads analysis and recommendations.

---

## Phase 6: Meta Ads Agent
**Goal**: Analyze Facebook/Instagram ads and generate recommendations.

### Integration Required:
- **Meta Graph API** (OAuth2)

### Tasks:
- [ ] **6.1**: Create Meta integration
- [ ] **6.2**: Create Meta Ads page
- [ ] **6.3**: Implement `MetaAdsAgent` with real API data
- [ ] **6.4**: Display ad performance metrics
- [ ] **6.5**: Generate audience targeting recommendations
- [ ] **6.6**: Add dashboard widget

**Deliverable**: Meta ads analysis and recommendations.

---

## Phase 7: LSA (Local Services Ads) Agent
**Goal**: Track LSA performance and generate optimization recommendations.

### Integration Required:
- **Google Ads API** (LSA reporting endpoint)

### Tasks:
- [ ] **7.1**: Extend Google Ads integration for LSA
- [ ] **7.2**: Create LSA analysis page
- [ ] **7.3**: Implement `LsaAgent` with real API data
- [ ] **7.4**: Display leads, cost per lead, conversion metrics
- [ ] **7.5**: Generate profile optimization recommendations
- [ ] **7.6**: Add dashboard widget

**Deliverable**: LSA performance tracking and optimization.

---

## Phase 8: Website Analysis Agent
**Goal**: Analyze website performance using real crawler data and analytics.

### Integration Required:
- **Google Analytics 4 API** (OAuth2) - optional
- Uses existing crawler data

### Tasks:
- [ ] **8.1**: Create website analysis page
- [ ] **8.2**: Enhance `WebsiteAnalysisAgent` to use crawler data
- [ ] **8.3**: Optional: Integrate GA4 for traffic data
- [ ] **8.4**: Display SEO issues, performance metrics, content gaps
- [ ] **8.5**: Add dashboard widget

**Deliverable**: Comprehensive website analysis with actionable insights.

---

## Phase 9: Backlinks Agent
**Goal**: Monitor backlink profile and generate link-building opportunities.

### Integration Required:
- **Ahrefs API** or **SEMrush API** (Bearer Token) - optional
- Uses crawler data for internal analysis

### Tasks:
- [ ] **9.1**: Create backlinks analysis page
- [ ] **9.2**: Implement `BacklinksAgent`
- [ ] **9.3**: Optional: Integrate Ahrefs/SEMrush for backlink data
- [ ] **9.4**: Generate link-building opportunities
- [ ] **9.5**: Add dashboard widget

**Deliverable**: Backlink monitoring and opportunities.

---

## Phase 10: Analytics Agent
**Goal**: Aggregate all metrics across platforms into unified dashboard.

### Tasks:
- [ ] **10.1**: Create analytics overview page
- [ ] **10.2**: Pull data from all integrations
- [ ] **10.3**: Display KPIs: traffic, leads, conversions, rankings
- [ ] **10.4**: Generate insights and trends
- [ ] **10.5**: Add dashboard widget

**Deliverable**: Unified analytics dashboard.

---

## Phase 11: Firm Profile Agent
**Goal**: Review and optimize firm profile data for consistency across platforms.

### Tasks:
- [ ] **11.1**: Create firm profile review page
- [ ] **11.2**: Implement `FirmProfileAgent`
- [ ] **11.3**: Check NAP consistency across integrations
- [ ] **11.4**: Generate profile optimization recommendations
- [ ] **11.5**: Add dashboard widget

**Deliverable**: Firm profile consistency checker.

---

## Technical Considerations

### Security
- Encrypt all integration credentials using Laravel's `Crypt` facade
- Store sensitive data in `credentials` JSON column (encrypted)
- Use environment variables for OAuth client IDs/secrets
- Implement rate limiting on API endpoints
- Validate all user inputs

### Queue System
- Use Laravel queues for long-running tasks:
  - Blog post generation (OpenAI calls)
  - WordPress publishing
  - API data fetching from integrations
  - Bulk operations

### Error Handling
- Graceful failures with user-friendly messages
- Log all API errors for debugging
- Retry logic for transient failures (rate limits, timeouts)
- Notification system for critical failures

### Testing Strategy
- Unit tests for each integration service
- Feature tests for API endpoints
- Mock external API calls in tests
- Test OAuth flows in staging environment

---

## Success Metrics
- [ ] User can connect WordPress and publish blog posts with 2 clicks
- [ ] AI generates blog posts based on recommendations without manual input
- [ ] All integrations support multiple auth methods
- [ ] Dashboard provides at-a-glance status of all AI agents
- [ ] System handles 100+ firms with multiple integrations each

---

## Notes
- Start with **Phase 0 (Integrations)** as it's the foundation for all other phases
- Each phase should be fully functional before moving to the next
- Blog post agent (Phase 2) is the most critical - perfect this first
- Other agents can follow the same pattern established in Phase 2
