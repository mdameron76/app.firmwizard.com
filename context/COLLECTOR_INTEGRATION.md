# Collector Database Integration

## Overview
This integration connects your FirmWizard app to the `lawsoftware_collector` database for competitor analysis, web crawling, and GBP data collection.

## Setup

### 1. Configure Collector Database Connection

Add to your `.env` file:

```env
# Collector Database (for web crawling & competitor data)
COLLECTOR_DB_HOST=127.0.0.1
COLLECTOR_DB_PORT=3306
COLLECTOR_DB_DATABASE=lawsoftware_collector
COLLECTOR_DB_USERNAME=root
COLLECTOR_DB_PASSWORD=your_password
```

### 2. Install Python Dependencies

```bash
cd app/Scripts/Python
pip install -r requirements.txt
```

### 3. Test Database Connection

```bash
php artisan tinker
>>> DB::connection('collector')->select('SELECT COUNT(*) as count FROM competitors');
```

## Models Available

All models use the `collector` database connection:

- `App\Models\Collector\Competitor` - Competitor firms
- `App\Models\Collector\CrawlerPage` - Crawled web pages
- `App\Models\Collector\CrawlerPageHeading` - H1-H6 headings
- `App\Models\Collector\CrawlerPageKeyword` - Keywords with density/frequency
- `App\Models\Collector\CrawlerPageLink` - Internal/external links
- `App\Models\Collector\CrawlerPageSchema` - Schema.org markup
- `App\Models\Collector\CrawlerPageTrackingPixel` - Tracking pixels found
- `App\Models\Collector\Collection` - Crawl collections/campaigns
- `App\Models\Collector\CollectionRun` - Collection execution history
- `App\Models\Collector\CompetitorPracticeArea` - Practice areas
- `App\Models\Collector\CompetitorGbpLocation` - Google Business Profile locations
- `App\Models\Collector\CompetitorGbpReview` - GBP reviews
- `App\Models\Collector\CompetitorGbpPost` - GBP posts
- `App\Models\Collector\CompetitorMetaAd` - Facebook/Instagram ads

## API Endpoints

### 1. Crawl a Competitor Page

```http
POST /api/crawler/page
Authorization: Bearer {token}
Content-Type: application/json

{
  "competitor_id": 1,
  "url": "https://competitor-website.com/practice-areas",
  "save_to_db": true
}
```

**Response:**
```json
{
  "success": true,
  "page_id": 123,
  "data": {
    "title": "Practice Areas - Competitor Law Firm",
    "word_count": 1245,
    "headings_count": 12,
    "keywords_count": 89,
    "links_count": 45
  }
}
```

### 2. Get Crawled Pages for Competitor

```http
GET /api/crawler/pages/{competitorId}
Authorization: Bearer {token}
```

### 3. Generic Crawler (Simple)

```http
POST /api/crawler/crawl
Authorization: Bearer {token}

{
  "url": "https://example.com",
  "script": "example_crawler.py"
}
```

## Using with n8n

### Example Workflow: Competitor Analysis

1. **HTTP Request** - Get competitors list
   ```
   GET /api/firms/{firmId}/competitors
   ```

2. **Loop** - For each competitor

3. **HTTP Request** - Crawl competitor pages
   ```
   POST /api/crawler/page
   {
     "competitor_id": "{{ $json.id }}",
     "url": "{{ $json.website }}"
   }
   ```

4. **HTTP Request** - Get crawled data
   ```
   GET /api/crawler/pages/{{ $json.competitor_id }}
   ```

5. **Code** - Analyze keywords, headings, etc.

6. **HTTP Request** - Send to AI for analysis
   ```
   POST /api/firms/{firmId}/ai-jobs
   {
     "job_type": "competitor_analysis",
     "input_json": {
       "competitor_data": "{{ $json }}"
     }
   }
   ```

## Python Scripts

### page_crawler.py
Crawls a single page and stores:
- Page metadata (title, description, word count)
- All headings (H1-H6) with positions
- Keywords with density and frequency
- Internal/external links
- Schema.org markup
- Tracking pixels (GA, Facebook, etc.)

**Usage:**
```bash
python page_crawler.py \
  "https://example.com" \
  1 \                      # competitor_id
  "127.0.0.1" \           # db_host
  "3306" \                # db_port
  "lawsoftware_collector" \ # db_name
  "root" \                # db_user
  "password" \            # db_pass
  "1"                     # save_to_db (1=yes, 0=no)
```

## Example: Query Competitor Data

```php
use App\Models\Collector\Competitor;

// Get competitor with all crawled pages
$competitor = Competitor::with([
    'pages.headings',
    'pages.keywords',
    'pages.links',
    'pages.schemas',
    'practiceAreas',
    'gbpLocations.reviews'
])->find(1);

// Get top keywords across all pages
$topKeywords = $competitor->pages()
    ->join('crawler_page_keywords', 'crawler_pages.id', '=', 'crawler_page_keywords.page_id')
    ->where('ngram_type', 1)
    ->groupBy('keyword')
    ->selectRaw('keyword, SUM(frequency) as total_frequency')
    ->orderByDesc('total_frequency')
    ->limit(50)
    ->get();

// Get practice areas mentioned
$practiceAreas = $competitor->practiceAreas()->pluck('practice_area');
```

## Database Schema

See the SQL files in `collector.firmwizard.com/` for full schema:
- `crawler_pages` - Main page data
- `crawler_page_headings` - H1-H6 structure
- `crawler_page_keywords` - Keyword analysis
- `crawler_page_links` - Link graph
- `crawler_page_schemas` - Structured data
- `crawler_page_tracking_pixels` - Marketing pixels
- `competitors` - Competitor firms
- `collections` - Crawl campaigns
- `competitor_gbp_locations` - GBP data
- `competitor_meta_ads` - Facebook/Instagram ads

## Next Steps

1. Create additional Python scrapers for:
   - GBP data collection
   - Meta Ad Library scraping
   - SERP position tracking
   
2. Build Laravel controllers/API endpoints for:
   - Collections management
   - Competitor discovery
   - Automated crawl scheduling

3. Integrate with AI agents to:
   - Analyze competitor content strategies
   - Identify keyword opportunities
   - Generate competitive insights

## Full API Documentation

**http://127.0.0.1:8000/swagger**
