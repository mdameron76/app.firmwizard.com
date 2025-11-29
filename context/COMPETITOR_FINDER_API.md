# Competitor Finder API Integration

## Overview
Find law firm competitors using Google Places API and automatically create collections for crawling their websites.

## Setup

### 1. Install Additional Python Dependencies
```bash
cd app/Scripts/Python
pip install -r requirements.txt
```

New packages added:
- `mysql-connector-python` - MySQL database connector
- `googlemaps` - Google Maps/Places API client

### 2. Add Environment Variables

Add to `.env`:
```env
# Google Maps API Key (for competitor finding)
GOOGLE_MAPS_API_KEY=your_google_api_key_here

# Collector Database (if not already added)
COLLECTOR_DB_HOST=127.0.0.1
COLLECTOR_DB_PORT=3306
COLLECTOR_DB_DATABASE=lawsoftware_collector
COLLECTOR_DB_USERNAME=root
COLLECTOR_DB_PASSWORD=your_password
```

### 3. Laravel Models Created
- `App\Models\Collector\FirmLocation` - Firm location data
- `App\Models\Collector\ApiUsage` - API call tracking
- `App\Models\Collector\Worker` - Worker definitions
- `App\Models\Collector\WorkerActivity` - Worker execution logs

## API Endpoints

### 1. Create a Collection
```http
POST /api/crawler/collections
Authorization: Bearer {token}
Content-Type: application/json

{
  "firm_id": 1,
  "firm_location_id": 1,
  "name": "Family Law Competitors - Atlanta",
  "query": "family law attorney",
  "enabled": true
}
```

**Response:**
```json
{
  "success": true,
  "collection": {
    "id": 1,
    "firm_id": 1,
    "firm_location_id": 1,
    "name": "Family Law Competitors - Atlanta",
    "query": "family law attorney",
    "enabled": true
  }
}
```

### 2. Find Competitors
```http
POST /api/crawler/find-competitors
Authorization: Bearer {token}
Content-Type: application/json

{
  "collection_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "run_id": 5,
  "competitors_found": 12,
  "output": "Collection run 5 completed successfully..."
}
```

**What it does:**
1. Gets collection details (location, query, radius)
2. Uses Google Geocoding API to get coordinates
3. Searches Google Places for law firms
4. Scrapes websites to identify practice areas
5. Stores competitors in database
6. Links competitors to collection
7. Tracks API usage and costs

### 3. Get All Collections
```http
GET /api/crawler/collections
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "collections": [
    {
      "id": 1,
      "name": "Family Law Competitors - Atlanta",
      "query": "family law attorney",
      "enabled": true,
      "last_run_at": "2025-11-26T08:00:00.000000Z",
      "runs": [
        {
          "id": 5,
          "status": "success",
          "items_processed": 12,
          "started_at": "2025-11-26T08:00:00.000000Z"
        }
      ]
    }
  ]
}
```

## Using with n8n

### Complete Workflow Example

**Workflow: "Find & Analyze Competitors"**

#### Step 1: Create Collection
```
HTTP Request Node
- Method: POST
- URL: http://127.0.0.1:8000/api/crawler/collections
- Auth: Bearer Token
- Body:
{
  "firm_id": {{ $json.firm_id }},
  "firm_location_id": {{ $json.location_id }},
  "name": "{{ $json.practice_area }} Competitors - {{ $json.city }}",
  "query": "{{ $json.practice_area }} attorney",
  "enabled": true
}
```

#### Step 2: Find Competitors
```
HTTP Request Node
- Method: POST
- URL: http://127.0.0.1:8000/api/crawler/find-competitors
- Body:
{
  "collection_id": {{ $json.collection.id }}
}
```

#### Step 3: Get Competitors List
```
HTTP Request Node
- Method: GET
- URL: http://127.0.0.1:8000/api/crawler/pages/{{ $json.competitor_id }}
```

#### Step 4: Loop Through Competitors
```
Split In Batches Node
- Batch Size: 5
```

#### Step 5: Crawl Each Competitor Website
```
HTTP Request Node
- Method: POST
- URL: http://127.0.0.1:8000/api/crawler/page
- Body:
{
  "competitor_id": {{ $json.id }},
  "url": "{{ $json.website }}",
  "save_to_db": true
}
```

#### Step 6: Send to AI for Analysis
```
HTTP Request Node
- Method: POST
- URL: http://127.0.0.1:8000/api/firms/{firmId}/ai-jobs
- Body:
{
  "job_type": "competitor_analysis",
  "input_json": {
    "competitor_data": {{ $json }}
  }
}
```

## Python Scripts

### competitor_finder.py
Executes Google Places API search and stores results.

**Command Line Usage:**
```bash
python competitor_finder.py --collection-id 1
python competitor_finder.py --all  # Run all enabled collections
```

**What it does:**
1. Queries Google Places Text Search API
2. Gets place details for each result
3. Scrapes competitor websites
4. Identifies practice areas from content
5. Stores in database
6. Tracks API usage/costs

### law_competitor_finder.py
Core logic for finding and analyzing law firm competitors.

**Features:**
- Google Geocoding API integration
- Google Places API search
- Website scraping with BeautifulSoup
- Practice area detection from keywords
- Configurable search radius

## Database Tables

### Collections
Stores competitor search campaigns:
- `firm_id` - Associated firm
- `firm_location_id` - Location to search around
- `query` - Search query (e.g., "family law attorney")
- `enabled` - Active/inactive
- `schedule_cron` - Optional scheduling

### Competitors
Found competitors:
- `name`, `website`, `address`, `phone`
- `place_id` - Google Places ID
- `firm_location_id` - Associated search location
- Relationships: pages, practice areas, GBP data

### API Usage
Tracks Google API calls:
- `vendor` - "Google"
- `api_name` - "geocode", "places_text", "places_details"
- `units` - Number of calls
- `cost_cents` - Estimated cost
- `collection_run_id` - Associated run

## Cost Tracking

Google Maps API Pricing (as of 2024):
- Geocoding: $5 per 1,000 requests
- Places Text Search: $32 per 1,000 requests
- Places Details: $17 per 1,000 requests

Example search for 20 competitors:
- 1 geocode call = $0.005
- 1 text search call = $0.032
- 20 details calls = $0.340
- **Total: ~$0.38 per search**

## Example: Full Integration

```php
// Laravel controller
use App\Models\Collector\Collection;
use App\Models\Collector\Competitor;

// Create collection
$collection = Collection::create([
    'firm_id' => 1,
    'firm_location_id' => 1,
    'name' => 'Family Law - Atlanta',
    'query' => 'family law attorney',
    'enabled' => true,
]);

// Trigger competitor finder via API
$response = Http::post('http://127.0.0.1:8000/api/crawler/find-competitors', [
    'collection_id' => $collection->id,
]);

// Get found competitors
$competitors = Competitor::where('collection_id', $collection->id)
    ->with('practiceAreas')
    ->get();

// Crawl each website
foreach ($competitors as $competitor) {
    if ($competitor->website) {
        Http::post('http://127.0.0.1:8000/api/crawler/page', [
            'competitor_id' => $competitor->id,
            'url' => $competitor->website,
            'save_to_db' => true,
        ]);
    }
}
```

## API Documentation

Full interactive documentation:
**http://127.0.0.1:8000/swagger**

All endpoints under "Crawler" tag.
