# Python Crawler Integration

## Overview
This integration allows you to execute Python web crawling scripts via Laravel API endpoints, perfect for n8n workflows or other external automation tools.

## Setup

### 1. Install Python Dependencies
```bash
pip install requests beautifulsoup4
```

### 2. Place Your Crawler Scripts
Put your Python crawler scripts in:
```
app/Scripts/Python/
```

### 3. Script Requirements
Your Python scripts should:
- Accept URL as command-line argument: `sys.argv[1]`
- Output JSON to stdout
- Return structure: `{"success": true/false, "data": {...}, "error": "..."}`

Example:
```python
import sys
import json

url = sys.argv[1]
result = {"success": True, "data": {"title": "Example"}}
print(json.dumps(result))
```

## API Endpoints

### 1. Execute Crawler (Synchronous)
```http
POST /api/crawler/crawl
Content-Type: application/json
Authorization: Bearer {token}

{
  "url": "https://example.com",
  "script": "example_crawler.py",
  "params": {}
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "title": "Example Site",
    "meta_description": "...",
    "links_count": 45
  }
}
```

### 2. Queue Crawler (Asynchronous)
```http
POST /api/crawler/queue
Content-Type: application/json
Authorization: Bearer {token}

{
  "url": "https://example.com",
  "script": "my_crawler.py",
  "callback_url": "https://your-webhook.com/callback"
}
```

### 3. List Available Scripts
```http
GET /api/crawler/scripts
Authorization: Bearer {token}
```

**Response:**
```json
{
  "scripts": [
    "example_crawler.py",
    "your_custom_crawler.py"
  ]
}
```

## Using with n8n

### Method 1: HTTP Request Node
1. Add **HTTP Request** node
2. Set Method: `POST`
3. Set URL: `http://127.0.0.1:8000/api/crawler/crawl`
4. Authentication: Bearer Token (use Sanctum token)
5. Body:
```json
{
  "url": "{{ $json.websiteUrl }}",
  "script": "your_crawler.py"
}
```

### Method 2: Webhook Callback (Async)
1. Use `/api/crawler/queue` endpoint
2. Provide `callback_url` pointing to your n8n webhook
3. n8n receives results when crawling completes

## Example: Custom Crawler Script

Create `app/Scripts/Python/competitor_crawler.py`:

```python
#!/usr/bin/env python3
import sys
import json
import requests
from bs4 import BeautifulSoup

def crawl_competitor(url):
    response = requests.get(url, timeout=30)
    soup = BeautifulSoup(response.content, 'html.parser')
    
    return {
        'success': True,
        'url': url,
        'title': soup.find('title').text,
        'headings': [h2.text for h2 in soup.find_all('h2')],
        'services': extract_services(soup),
        'contact_info': extract_contact(soup),
    }

if __name__ == '__main__':
    url = sys.argv[1]
    result = crawl_competitor(url)
    print(json.dumps(result))
```

## Authentication

All crawler endpoints require **Sanctum authentication**:

1. Generate token:
```bash
php artisan tinker
>>> $user = User::find(1)
>>> $token = $user->createToken('n8n-crawler')->plainTextToken
```

2. Use token in n8n:
   - Authentication Type: `Header Auth`
   - Header: `Authorization`
   - Value: `Bearer {token}`

## Advanced: Queue Jobs

For long-running crawlers, create a Laravel job:

```php
php artisan make:job CrawlWebsiteJob
```

Then dispatch in the `queueCrawl()` method to prevent timeouts.

## Troubleshooting

### "Python not found"
- Ensure Python is in your PATH
- Or use full path: `C:\Python311\python.exe`

### Script timeout
- Increase `max_execution_time` in php.ini
- Or use async queue method

### Permission errors
- Ensure Python scripts are readable
- On Unix: `chmod +x app/Scripts/Python/*.py`

## API Documentation

View full API docs with examples:
**http://127.0.0.1:8000/swagger**
