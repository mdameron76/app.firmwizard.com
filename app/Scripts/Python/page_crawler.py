"""
Page Crawler Worker
Crawls all pages from competitor websites and pushes to LawSoftware app
"""
import os
import sys
import logging
import requests
from requests.adapters import HTTPAdapter
from requests.packages.urllib3.util.retry import Retry
import urllib3
import hashlib
from datetime import datetime
from urllib.parse import urljoin, urlparse
from bs4 import BeautifulSoup
from collections import Counter
import re
import json
import time
from dotenv import load_dotenv

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from database import execute_query, get_setting
from api_tracker import log_api_usage
from seotools.analysis import parse_dom, count_keywords, count_ngrams

load_dotenv()

# Configuration - read from database settings with fallback to .env
KEYWORD_MIN_FREQUENCY = int(get_setting('KEYWORD_MIN_FREQUENCY') or os.getenv('KEYWORD_MIN_FREQUENCY', '2'))  # Only store keywords appearing 2+ times
KEYWORD_MIN_DENSITY = float(get_setting('KEYWORD_MIN_DENSITY') or os.getenv('KEYWORD_MIN_DENSITY', '0.001'))  # Minimum 0.1% density
KEYWORD_MAX_PER_PAGE = int(get_setting('KEYWORD_MAX_PER_PAGE') or os.getenv('KEYWORD_MAX_PER_PAGE', '200'))  # Max keywords per page

# Setup logging
log_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'logs')
os.makedirs(log_dir, exist_ok=True)
log_file = os.path.join(log_dir, 'page_crawler.log')

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

class PageCrawler:
    def __init__(self, max_pages=50, max_depth=3):
        self.max_pages = max_pages
        self.max_depth = max_depth
        self.visited_urls = set()
        self.session = requests.Session()
        
        # Configure retry strategy for slow/unreliable sites
        retry_strategy = Retry(
            total=3,  # 3 retries
            backoff_factor=1,  # Wait 1, 2, 4 seconds between retries
            status_forcelist=[429, 500, 502, 503, 504],  # Retry on these status codes
            allowed_methods=["HEAD", "GET", "OPTIONS"]
        )
        adapter = HTTPAdapter(max_retries=retry_strategy)
        self.session.mount("http://", adapter)
        self.session.mount("https://", adapter)
        
        # Use more realistic browser headers to avoid bot detection
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.9',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1'
        })
    
    def crawl_website(self, competitor_id, website):
        """Crawl all pages from a website"""
        # Validate website
        if not website or website.strip() == '':
            logger.warning(f"[SKIP] Competitor {competitor_id} has no website")
            return 0
        
        logger.info(f"Starting crawl for competitor {competitor_id}: {website}")
        
        try:
            base_url = website if website.startswith('http') else f'http://{website}'
            parsed_base = urlparse(base_url)
            base_domain = parsed_base.netloc
            
            if not base_domain:
                logger.error(f"[SKIP] Invalid website URL for competitor {competitor_id}: {website}")
                return 0
        except Exception as e:
            logger.error(f"[SKIP] Error parsing website URL for competitor {competitor_id}: {website} - {e}")
            return 0
        
        pages_crawled = 0
        to_crawl = [(base_url, 0)]  # (url, depth)
        
        while to_crawl and pages_crawled < self.max_pages:
            url, depth = to_crawl.pop(0)
            
            if url in self.visited_urls or depth > self.max_depth:
                continue
            
            self.visited_urls.add(url)
            
            try:
                page_data = self.crawl_page(url, competitor_id)
                if page_data:
                    # Check if we got a 403 error
                    if page_data.get('status_code') == 403:
                        logger.warning(f"403 Forbidden received - marking competitor {competitor_id} as blocked")
                        execute_query("""
                            UPDATE competitors 
                            SET blocked_at = NOW(),
                                block_reason = '403 Forbidden - Site blocking crawlers'
                            WHERE id = %s
                        """, (competitor_id,))
                        break  # Stop crawling this competitor
                    
                    pages_crawled += 1
                    logger.info(f"Crawled {pages_crawled}/{self.max_pages}: {url}")
                    
                    # Add small delay between requests to appear more human-like
                    time.sleep(0.5)
                    
                    # Find internal links
                    if depth < self.max_depth:
                        for link in page_data.get('internal_links', []):
                            if link not in self.visited_urls:
                                to_crawl.append((link, depth + 1))
            
            except Exception as e:
                logger.error(f"Error crawling {url}: {e}")
                continue
        
        logger.info(f"Completed crawl for {website}: {pages_crawled} pages")
        return pages_crawled
    
    def crawl_page(self, url, competitor_id):
        """Crawl a single page and extract data"""
        try:
            # Try with SSL verification first
            try:
                response = self.session.get(url, timeout=30, allow_redirects=True, verify=True)
            except requests.exceptions.SSLError as ssl_error:
                # If SSL fails, try without verification (some sites have misconfigured certs)
                logger.warning(f"SSL verification failed for {url}, retrying without verification")
                urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
                response = self.session.get(url, timeout=30, allow_redirects=True, verify=False)
            
            status_code = response.status_code
            
            if status_code != 200:
                if status_code == 403:
                    logger.warning(f"403 Forbidden for {url} - Site may be blocking crawlers. Consider manual review.")
                    return {'status_code': 403, 'error': '403 Forbidden'}
                else:
                    logger.warning(f"Non-200 status for {url}: {status_code}")
                return None
            
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Extract page data
            page_data = self.extract_page_data(soup, url, status_code)
            page_data['competitor_id'] = competitor_id
            
            # Store in local database
            self.store_page_locally(page_data)
            
            return page_data
            
        except Exception as e:
            logger.error(f"Error processing {url}: {e}")
            return None
    
    def store_page_locally(self, page_data):
        """Store page data in local database"""
        try:
            url_hash = hashlib.sha256(page_data['url'].encode()).hexdigest()
            
            # Check if page already exists
            existing = execute_query("""
                SELECT id FROM crawler_pages 
                WHERE competitor_id = %s AND url_hash = %s
            """, (page_data['competitor_id'], url_hash), fetch=True)
            
            if existing:
                page_id = existing[0]['id']
                # Update existing page
                execute_query("""
                    UPDATE crawler_pages 
                    SET title = %s, content = %s, content_hash = %s,
                        meta_description = %s, meta_keywords = %s, word_count = %s,
                        page_type = %s, status_code = %s, crawled_at = NOW(),
                        sync_status = 'pending'
                    WHERE id = %s
                """, (
                    page_data['title'], page_data['content'], page_data['content_hash'],
                    page_data['meta_description'], page_data['meta_keywords'], page_data['word_count'],
                    page_data['page_type'], page_data['status_code'], page_id
                ))
                
                # Clear old related data
                execute_query("DELETE FROM crawler_page_headings WHERE page_id = %s", (page_id,))
                execute_query("DELETE FROM crawler_page_keywords WHERE page_id = %s", (page_id,))
                execute_query("DELETE FROM crawler_page_links WHERE page_id = %s", (page_id,))
                execute_query("DELETE FROM crawler_page_schemas WHERE page_id = %s", (page_id,))
            else:
                # Insert new page
                page_id = execute_query("""
                    INSERT INTO crawler_pages 
                    (competitor_id, url, url_hash, title, content, content_hash,
                     meta_description, meta_keywords, word_count, page_type, status_code,
                     crawled_at, sync_status)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), 'pending')
                """, (
                    page_data['competitor_id'], page_data['url'], url_hash,
                    page_data['title'], page_data['content'], page_data['content_hash'],
                    page_data['meta_description'], page_data['meta_keywords'], page_data['word_count'],
                    page_data['page_type'], page_data['status_code']
                ))
            
            # Store headings (IGNORE duplicates due to unique constraint)
            for heading in page_data.get('headings', []):
                execute_query("""
                    INSERT IGNORE INTO crawler_page_headings (page_id, level, position, anchor_id, text)
                    VALUES (%s, %s, %s, %s, %s)
                """, (page_id, heading['level'], heading['position'], heading.get('anchor_id'), heading['text']))
            
            # Store keywords (IGNORE duplicates due to unique constraint)
            # Filter to reduce database bloat: only store significant keywords
            keywords = page_data.get('keywords', [])
            filtered_keywords = [
                kw for kw in keywords
                if kw['frequency'] >= KEYWORD_MIN_FREQUENCY or 
                   kw['density'] >= KEYWORD_MIN_DENSITY or
                   kw['in_title'] or kw['in_h1'] or kw['in_h2'] or kw['in_url_slug']
            ]
            
            # Sort by importance and limit
            filtered_keywords.sort(key=lambda x: (
                x['in_title'] * 100 + 
                x['in_h1'] * 50 + 
                x['in_h2'] * 25 + 
                x['frequency'] * x['density']
            ), reverse=True)
            filtered_keywords = filtered_keywords[:KEYWORD_MAX_PER_PAGE]
            
            for keyword in filtered_keywords:
                execute_query("""
                    INSERT IGNORE INTO crawler_page_keywords 
                    (page_id, ngram_type, keyword, frequency, density, in_title, in_meta_description, in_h1, in_h2, in_url_slug)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """, (
                    page_id, keyword['ngram_type'], keyword['keyword'], keyword['frequency'],
                    keyword['density'], keyword['in_title'], keyword['in_meta_description'],
                    keyword['in_h1'], keyword['in_h2'], keyword['in_url_slug']
                ))
            
            # Store links (IGNORE duplicates due to unique constraint)
            for link in page_data.get('links', []):
                # Convert rel attribute to string (can be a list)
                rel_value = link.get('rel')
                if isinstance(rel_value, list):
                    rel_value = ' '.join(rel_value) if rel_value else None
                elif rel_value:
                    rel_value = str(rel_value)
                
                execute_query("""
                    INSERT IGNORE INTO crawler_page_links 
                    (page_id, href, anchor_text, link_type, rel, target, position)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (
                    page_id, link['href'], link.get('anchor_text'), link['link_type'],
                    rel_value, link.get('target'), link.get('position')
                ))
            
            # Store schemas (IGNORE duplicates due to unique constraint)
            schemas_count = len(page_data.get('schemas', []))
            if schemas_count > 0:
                logger.info(f"Storing {schemas_count} schemas for {page_data['url']}")
            
            for schema in page_data.get('schemas', []):
                try:
                    raw_json = schema.get('raw_json')
                    # Ensure raw_json is serializable
                    if raw_json is not None:
                        raw_json_str = json.dumps(raw_json, default=str)
                    else:
                        raw_json_str = None
                    
                    logger.info(f"Storing schema type: {schema['schema_type']}")
                    
                    execute_query("""
                        INSERT IGNORE INTO crawler_page_schemas 
                        (page_id, schema_type, source, schema_id, name, raw_json)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, (
                        page_id, schema['schema_type'], schema['source'],
                        schema.get('schema_id'), schema.get('name'),
                        raw_json_str
                    ))
                except Exception as e:
                    logger.warning(f"Error storing schema: {e}")
            
            # Store tracking pixels (IGNORE duplicates due to unique constraint)
            pixels_count = len(page_data.get('tracking_pixels', []))
            if pixels_count > 0:
                logger.info(f"Storing {pixels_count} tracking pixels for {page_data['url']}")
            
            for pixel in page_data.get('tracking_pixels', []):
                try:
                    execute_query("""
                        INSERT IGNORE INTO crawler_page_tracking_pixels 
                        (page_id, pixel_type, pixel_id, found_in)
                        VALUES (%s, %s, %s, %s)
                    """, (
                        page_id, pixel['pixel_type'], pixel['pixel_id'], 
                        pixel['found_in']
                    ))
                except Exception as e:
                    logger.warning(f"Error storing tracking pixel: {e}")
            
            # Update competitor's social media links if found
            social_media = page_data.get('social_media', {})
            if social_media:
                logger.info(f"Found social media links on {page_data['url']}: {list(social_media.keys())}")
                
                # Update Facebook URL if found and not already set
                if 'facebook' in social_media:
                    execute_query("""
                        UPDATE competitors 
                        SET facebook_page_url = %s
                        WHERE id = %s 
                        AND (facebook_page_url IS NULL OR facebook_page_url = '')
                    """, (social_media['facebook'], page_data['competitor_id']))
                    logger.info(f"Updated competitor {page_data['competitor_id']} Facebook URL: {social_media['facebook']}")
            
            logger.info(f"Stored page locally: {page_data['url']}")
            return page_id
            
        except Exception as e:
            logger.error(f"Error storing page locally: {e}", exc_info=True)
            return None
    
    def extract_page_data(self, soup, url, status_code):
        """Extract all data from a page"""
        # IMPORTANT: Extract schemas BEFORE removing script tags
        schemas = self.extract_schema(soup)
        
        # Extract tracking pixels BEFORE removing scripts
        tracking_pixels = self.extract_tracking_pixels(soup)
        
        # Extract social media links
        social_media = self.extract_social_media_links(soup, url)
        
        # Basic page info
        title = soup.find('title')
        title_text = title.get_text().strip() if title else ''
        
        # Meta tags
        meta_desc = soup.find('meta', attrs={'name': 'description'})
        meta_description = meta_desc['content'] if meta_desc and meta_desc.get('content') else ''
        
        meta_keys = soup.find('meta', attrs={'name': 'keywords'})
        meta_keywords = meta_keys['content'] if meta_keys and meta_keys.get('content') else ''
        
        # Content
        # Remove script and style elements (AFTER extracting schemas)
        for script in soup(['script', 'style', 'nav', 'footer', 'header']):
            script.decompose()
        
        text = soup.get_text()
        lines = (line.strip() for line in text.splitlines())
        chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
        content = '\n'.join(chunk for chunk in chunks if chunk)
        
        word_count = len(content.split())
        content_hash = hashlib.sha256(content.encode()).hexdigest()
        
        # Headings
        headings = []
        for i in range(1, 7):
            for pos, h in enumerate(soup.find_all(f'h{i}')):
                headings.append({
                    'level': i,
                    'position': pos,
                    'text': h.get_text().strip(),
                    'anchor_id': h.get('id')
                })
        
        # Links
        links = []
        parsed_url = urlparse(url)
        base_domain = parsed_url.netloc
        
        internal_links = []
        
        for pos, a in enumerate(soup.find_all('a', href=True)):
            href = a['href']
            absolute_url = urljoin(url, href)
            parsed_link = urlparse(absolute_url)
            
            # Determine if internal or external
            is_internal = parsed_link.netloc == base_domain or parsed_link.netloc == ''
            
            if is_internal and absolute_url not in internal_links:
                internal_links.append(absolute_url)
            
            links.append({
                'href': absolute_url,
                'anchor_text': a.get_text().strip()[:500],
                'link_type': 'internal' if is_internal else 'external',
                'rel': a.get('rel'),
                'target': a.get('target'),
                'position': pos
            })
        
        # Extract keywords (simple frequency analysis)
        keywords = self.extract_keywords(content, title_text, meta_description, soup)
        
        # Determine page type
        page_type = self.determine_page_type(url, title_text, content)
        
        return {
            'url': url,
            'title': title_text[:500],
            'content': content,
            'content_hash': content_hash,
            'meta_description': meta_description,
            'meta_keywords': meta_keywords,
            'word_count': word_count,
            'page_type': page_type,
            'status_code': status_code,
            'headings': headings,
            'links': links,
            'internal_links': internal_links,
            'keywords': keywords,
            'schemas': schemas,
            'tracking_pixels': tracking_pixels,
            'social_media': social_media
        }
    
    def extract_keywords(self, content, title, meta_desc, soup):
        """Extract keywords with frequency, positions, and n-grams using SEOTools"""
        keywords = []
        
        # Prepare regions for analysis (matching SEOTools format)
        regions = {
            'title': title,
            'meta_description': meta_desc,
            'body_text': content,
            'headings': {
                'h1': [h.get_text().strip() for h in soup.find_all('h1')],
                'h2': [h.get_text().strip() for h in soup.find_all('h2')],
                'h3': [h.get_text().strip() for h in soup.find_all('h3')],
                'h4': [h.get_text().strip() for h in soup.find_all('h4')],
                'h5': [h.get_text().strip() for h in soup.find_all('h5')],
                'h6': [h.get_text().strip() for h in soup.find_all('h6')],
            }
        }
        
        # Get URL slug for checking
        parsed_url = urlparse(soup.find('link', rel='canonical')['href'] if soup.find('link', rel='canonical') else '')
        url_slug = parsed_url.path.lower()
        
        # 1. Single-word keywords
        keyword_counts = count_keywords(content)
        title_lower = title.lower()
        meta_lower = meta_desc.lower()
        h1_text = ' '.join(regions['headings']['h1']).lower()
        h2_text = ' '.join(regions['headings']['h2']).lower()
        
        total_words = len(content.split())
        
        for word, freq in keyword_counts.most_common(50):
            keywords.append({
                'keyword': word,
                'ngram_type': 1,
                'frequency': freq,
                'density': round(freq / max(total_words, 1) * 100, 4),
                'in_title': word in title_lower,
                'in_meta_description': word in meta_lower,
                'in_h1': word in h1_text,
                'in_h2': word in h2_text,
                'in_url_slug': word in url_slug
            })
        
        # 2. Bigrams (2-word phrases)
        bigram_counts = count_ngrams(content, n=2)
        for bigram, freq in bigram_counts.most_common(30):
            keywords.append({
                'keyword': bigram,
                'ngram_type': 2,
                'frequency': freq,
                'density': round(freq / max(total_words, 1) * 100, 4),
                'in_title': bigram in title_lower,
                'in_meta_description': bigram in meta_lower,
                'in_h1': bigram in h1_text,
                'in_h2': bigram in h2_text,
                'in_url_slug': bigram in url_slug
            })
        
        # 3. Trigrams (3-word phrases)
        trigram_counts = count_ngrams(content, n=3)
        for trigram, freq in trigram_counts.most_common(20):
            keywords.append({
                'keyword': trigram,
                'ngram_type': 3,
                'frequency': freq,
                'density': round(freq / max(total_words, 1) * 100, 4),
                'in_title': trigram in title_lower,
                'in_meta_description': trigram in meta_lower,
                'in_h1': trigram in h1_text,
                'in_h2': trigram in h2_text,
                'in_url_slug': trigram in url_slug
            })
        
        return keywords
    
    def extract_schema(self, soup):
        """Extract schema.org JSON-LD markup"""
        schemas = []
        script_tags = soup.find_all('script', attrs={'type': 'application/ld+json'})
        logger.info(f"Found {len(script_tags)} script[type='application/ld+json'] tags")
        
        for idx, script in enumerate(script_tags):
            try:
                if not script.string:
                    logger.warning(f"Script tag {idx+1} has no string content")
                    continue
                    
                schema_data = json.loads(script.string)
                
                # Handle @graph structure (common in SEO plugins)
                if isinstance(schema_data, dict) and '@graph' in schema_data:
                    logger.info(f"Found @graph with {len(schema_data['@graph'])} schemas")
                    for item in schema_data['@graph']:
                        if isinstance(item, dict):
                            schemas.append({
                                'schema_type': item.get('@type', 'Unknown'),
                                'source': 'json_ld',
                                'schema_id': item.get('@id'),
                                'name': item.get('name'),
                                'raw_json': item
                            })
                # Handle single schema object
                elif isinstance(schema_data, dict):
                    logger.info(f"Found single schema: {schema_data.get('@type', 'Unknown')}")
                    schemas.append({
                        'schema_type': schema_data.get('@type', 'Unknown'),
                        'source': 'json_ld',
                        'schema_id': schema_data.get('@id'),
                        'name': schema_data.get('name'),
                        'raw_json': schema_data
                    })
                # Handle array of schemas
                elif isinstance(schema_data, list):
                    logger.info(f"Found schema array with {len(schema_data)} items")
                    for item in schema_data:
                        if isinstance(item, dict):
                            schemas.append({
                                'schema_type': item.get('@type', 'Unknown'),
                                'source': 'json_ld',
                                'schema_id': item.get('@id'),
                                'name': item.get('name'),
                                'raw_json': item
                            })
            except Exception as e:
                logger.warning(f"Error parsing schema tag {idx+1}: {e}")
                continue
        
        logger.info(f"Extracted {len(schemas)} total schemas")
        return schemas
    
    def extract_tracking_pixels(self, soup):
        """Extract marketing tracking pixels and tags"""
        pixels = []
        html_str = str(soup)
        
        # Facebook Pixel - check for fbq('init', 'PIXEL_ID')
        import re
        fb_patterns = [
            r"fbq\(['\"]init['\"],\s*['\"](\d+)['\"]",  # fbq('init', '123456')
            r"facebook\.com/tr\?id=(\d+)",  # facebook.com/tr?id=123456
        ]
        for pattern in fb_patterns:
            matches = re.findall(pattern, html_str, re.IGNORECASE)
            for match in matches:
                pixels.append({
                    'pixel_type': 'facebook_pixel',
                    'pixel_id': match,
                    'found_in': 'script'
                })
        
        # Google Analytics - GA4 (G-XXXXXXXXXX) and Universal (UA-XXXXXXXXX)
        ga_patterns = [
            r"['\"]G-([A-Z0-9]+)['\"]",  # GA4: G-XXXXXXXXXX
            r"['\"]UA-(\d+-\d+)['\"]",  # Universal: UA-XXXXXXX-X
            r"gtag\(['\"]config['\"],\s*['\"]([^'\"]+)['\"]",  # gtag('config', 'G-XXX')
        ]
        for pattern in ga_patterns:
            matches = re.findall(pattern, html_str)
            for match in matches:
                if match.startswith('G-') or match.startswith('UA-'):
                    pixels.append({
                        'pixel_type': 'google_analytics',
                        'pixel_id': match,
                        'found_in': 'script'
                    })
        
        # Google Tag Manager - GTM-XXXXXXX
        gtm_pattern = r"GTM-([A-Z0-9]+)"
        gtm_matches = re.findall(gtm_pattern, html_str)
        for match in gtm_matches:
            pixels.append({
                'pixel_type': 'google_tag_manager',
                'pixel_id': f'GTM-{match}',
                'found_in': 'script'
            })
        
        # Google Ads Conversion - AW-XXXXXXXXXX
        gads_pattern = r"['\"]AW-(\d+)['\"]"
        gads_matches = re.findall(gads_pattern, html_str)
        for match in gads_matches:
            pixels.append({
                'pixel_type': 'google_ads',
                'pixel_id': f'AW-{match}',
                'found_in': 'script'
            })
        
        # LinkedIn Insight Tag
        li_pattern = r"_linkedin_partner_id\s*=\s*['\"](\d+)['\"]"
        li_matches = re.findall(li_pattern, html_str)
        for match in li_matches:
            pixels.append({
                'pixel_type': 'linkedin_insight',
                'pixel_id': match,
                'found_in': 'script'
            })
        
        # Twitter/X Pixel
        twitter_pattern = r"twitter\.com/i/adsct\?txn_id=([^&'\"]+)"
        twitter_matches = re.findall(twitter_pattern, html_str, re.IGNORECASE)
        for match in twitter_matches:
            pixels.append({
                'pixel_type': 'twitter_pixel',
                'pixel_id': match,
                'found_in': 'script'
            })
        
        # TikTok Pixel
        tiktok_pattern = r"ttq\.load\(['\"]([^'\"]+)['\"]"
        tiktok_matches = re.findall(tiktok_pattern, html_str)
        for match in tiktok_matches:
            pixels.append({
                'pixel_type': 'tiktok_pixel',
                'pixel_id': match,
                'found_in': 'script'
            })
        
        # Remove duplicates
        unique_pixels = []
        seen = set()
        for pixel in pixels:
            key = (pixel['pixel_type'], pixel['pixel_id'])
            if key not in seen:
                seen.add(key)
                unique_pixels.append(pixel)
        
        if unique_pixels:
            pixel_summary = [f"{p['pixel_type']}:{p['pixel_id']}" for p in unique_pixels]
            logger.info(f"Found {len(unique_pixels)} tracking pixels: {pixel_summary}")
        
        return unique_pixels
    
    def extract_social_media_links(self, soup, url):
        """Extract social media profile links from page"""
        social_links = {}
        
        # Common social media patterns
        social_patterns = {
            'facebook': [
                r'(?:https?://)?(?:www\.)?facebook\.com/(?!sharer)([A-Za-z0-9\.\-_]+)',
                r'(?:https?://)?(?:www\.)?fb\.com/([A-Za-z0-9\.\-_]+)'
            ],
            'twitter': [
                r'(?:https?://)?(?:www\.)?twitter\.com/([A-Za-z0-9_]+)',
                r'(?:https?://)?(?:www\.)?x\.com/([A-Za-z0-9_]+)'
            ],
            'linkedin': [
                r'(?:https?://)?(?:www\.)?linkedin\.com/(?:company|in)/([A-Za-z0-9\-]+)'
            ],
            'instagram': [
                r'(?:https?://)?(?:www\.)?instagram\.com/([A-Za-z0-9\._]+)'
            ],
            'youtube': [
                r'(?:https?://)?(?:www\.)?youtube\.com/(?:c|channel|user)/([A-Za-z0-9\-_]+)',
                r'(?:https?://)?(?:www\.)?youtube\.com/@([A-Za-z0-9\-_]+)'
            ],
            'tiktok': [
                r'(?:https?://)?(?:www\.)?tiktok\.com/@([A-Za-z0-9\._]+)'
            ]
        }
        
        # Find all links
        for a in soup.find_all('a', href=True):
            href = a['href'].strip()
            
            # Check each social media platform
            for platform, patterns in social_patterns.items():
                if platform in social_links:
                    continue  # Already found this platform
                
                for pattern in patterns:
                    match = re.search(pattern, href, re.IGNORECASE)
                    if match:
                        # Construct full URL
                        if not href.startswith('http'):
                            href = urljoin(url, href)
                        
                        # Clean up URL (remove query params and fragments for cleaner storage)
                        parsed = urlparse(href)
                        clean_url = f"{parsed.scheme}://{parsed.netloc}{parsed.path}".rstrip('/')
                        
                        social_links[platform] = clean_url
                        logger.info(f"Found {platform} link: {clean_url}")
                        break
        
        return social_links
    
    def determine_page_type(self, url, title, content):
        """Determine the type of page"""
        url_lower = url.lower()
        title_lower = title.lower()
        content_lower = content.lower()
        
        if 'blog' in url_lower or 'news' in url_lower or 'article' in url_lower:
            return 'blog'
        elif 'practice-area' in url_lower or 'services' in url_lower or 'practice' in url_lower:
            return 'practice_area'
        elif 'about' in url_lower:
            return 'about'
        elif 'contact' in url_lower:
            return 'contact'
        elif 'attorney' in url_lower or 'lawyer' in url_lower or 'team' in url_lower:
            return 'attorney'
        elif url_lower.endswith('/') or url_lower.count('/') <= 3:
            return 'homepage'
        else:
            return 'unknown'

def run_collection(collection_id, freshness_days=7, missed_only=False):
    """
    Run page crawler for all competitors in a collection
    
    Args:
        collection_id: The collection to crawl (can be page_crawler or competitor_finder)
        freshness_days: Number of days before re-crawling (default: 7 days)
        missed_only: Only crawl competitors with 0 pages (default: False)
    """
    logger.info(f"Starting page crawler for collection_id={collection_id} (freshness: {freshness_days} days)")
    
    # Create collection run record
    run_id = execute_query("""
        INSERT INTO collection_runs 
        (collection_id, started_at, status)
        VALUES (%s, NOW(), 'running')
    """, (collection_id,))
    
    logger.info(f"Created collection_run id={run_id}")
    
    try:
        # Get collection details and find the source collection for competitors
        collection = execute_query("""
            SELECT 
                c.*,
                w.name as worker_name,
                (SELECT c2.id FROM collections c2 
                 JOIN workers w2 ON c2.worker_id = w2.id
                 WHERE c2.firm_location_id = c.firm_location_id 
                 AND w2.name = 'competitor_finder'
                 LIMIT 1) as source_collection_id
            FROM collections c
            JOIN workers w ON c.worker_id = w.id
            WHERE c.id = %s
        """, (collection_id,), fetch=True)
        
        if not collection:
            logger.warning(f"Collection {collection_id} not found")
            return
        
        collection = collection[0]
        
        # If this is a page_crawler collection, use the paired competitor_finder collection
        target_collection_id = collection_id
        if collection['worker_name'] == 'page_crawler' and collection['source_collection_id']:
            target_collection_id = collection['source_collection_id']
            logger.info(f"Page crawler collection - using competitors from collection #{target_collection_id}")
        
        # Get competitor count from the target collection using the new schema
        competitor_count = execute_query("""
            SELECT COUNT(DISTINCT c.id) as count 
            FROM competitors c
            JOIN competitor_collections cc ON c.id = cc.competitor_id
            WHERE cc.collection_id = %s
        """, (target_collection_id,), fetch=True)[0]['count']
        
        logger.info(f"Running page crawler for: {collection['name']} ({competitor_count} competitors)")
        
        # Count competitors without websites
        no_website_count = execute_query("""
            SELECT COUNT(DISTINCT c.id) as count
            FROM competitors c
            JOIN competitor_collections cc ON c.id = cc.competitor_id
            WHERE cc.collection_id = %s 
            AND (c.website IS NULL OR c.website = '')
        """, (target_collection_id,), fetch=True)[0]['count']
        
        if no_website_count > 0:
            logger.info(f"Skipping {no_website_count} competitors with no website")
            # Get their names for logging
            no_website_comps = execute_query("""
                SELECT c.id, c.name
                FROM competitors c
                JOIN competitor_collections cc ON c.id = cc.competitor_id
                WHERE cc.collection_id = %s 
                AND (c.website IS NULL OR c.website = '')
                LIMIT 10
            """, (target_collection_id,), fetch=True)
            
            for comp in no_website_comps:
                logger.info(f"  [SKIP] {comp['name']} (ID: {comp['id']}) - No website")
            
            if no_website_count > 10:
                logger.info(f"  ... and {no_website_count - 10} more")
        
        # Get competitors that need crawling (never crawled OR stale)
        # Using the new schema with competitor_collections junction table
        where_clause = """
            WHERE cc.collection_id = %s 
            AND c.website IS NOT NULL 
            AND c.website != ''
            AND c.blocked_at IS NULL
        """
        
        if missed_only:
            # Only get competitors that have never been crawled
            where_clause += " AND (SELECT COUNT(*) FROM crawler_pages WHERE competitor_id = c.id) = 0"
            logger.info("Retry missed mode: Only crawling competitors with 0 pages")
        
        competitors = execute_query(f"""
            SELECT 
                c.id, 
                c.name, 
                c.website,
                MAX(cp.crawled_at) as last_crawled,
                COUNT(cp.id) as pages_count
            FROM competitors c
            JOIN competitor_collections cc ON c.id = cc.competitor_id
            LEFT JOIN crawler_pages cp ON c.id = cp.competitor_id
            {where_clause}
            GROUP BY c.id
            HAVING 
                last_crawled IS NULL  -- Never crawled
                OR last_crawled < DATE_SUB(NOW(), INTERVAL %s DAY)  -- Stale
            ORDER BY last_crawled IS NULL DESC, last_crawled ASC
        """, (target_collection_id, freshness_days), fetch=True)
        
        # Check for blocked competitors
        blocked_comps = execute_query("""
            SELECT c.id, c.name, c.website, c.blocked_at, c.block_reason
            FROM competitors c
            JOIN competitor_collections cc ON c.id = cc.competitor_id
            WHERE cc.collection_id = %s 
            AND c.website IS NOT NULL 
            AND c.website != ''
            AND c.blocked_at IS NOT NULL
        """, (target_collection_id,), fetch=True)
        
        if blocked_comps:
            logger.info(f"\n[BLOCKED] {len(blocked_comps)} competitors are blocked from crawling:")
            for comp in blocked_comps:
                logger.info(f"  [BLOCKED] {comp['name']} (ID: {comp['id']}) - {comp['block_reason']}")
                logger.info(f"           Blocked since: {comp['blocked_at']}")
            logger.info(f"  Use --unblock-competitor <id> to retry a blocked competitor\n")
        
        if not competitors:
            logger.info(f"All competitors are up-to-date (within {freshness_days} days)")
            return
        
        logger.info(f"Found {len(competitors)} competitors that need crawling:")
        for comp in competitors:
            if comp['last_crawled']:
                logger.info(f"  - {comp['name']}: Last crawled {comp['last_crawled']}, {comp['pages_count']} pages")
            else:
                logger.info(f"  - {comp['name']}: Never crawled")
        
        # Initialize crawler
        crawler = PageCrawler(max_pages=50, max_depth=3)
        
        total_pages = 0
        for competitor in competitors:
            try:
                pages_crawled = crawler.crawl_website(
                    competitor_id=competitor['id'],
                    website=competitor['website']
                )
                total_pages += pages_crawled
                logger.info(f"Crawled {pages_crawled} pages from {competitor['name']}")
            except Exception as e:
                logger.error(f"Error crawling competitor {competitor['name']}: {e}")
                continue
        
        logger.info(f"[SUCCESS] Page crawler completed: {total_pages} total pages crawled")
        
        # Update collection run as success
        execute_query("""
            UPDATE collection_runs 
            SET status = 'success',
                finished_at = NOW(),
                items_processed = %s
            WHERE id = %s
        """, (total_pages, run_id))
        
        return total_pages
        
    except Exception as e:
        logger.error(f"Error running page crawler for collection {collection_id}: {e}", exc_info=True)
        
        # Update collection run as error
        execute_query("""
            UPDATE collection_runs 
            SET status = 'error',
                finished_at = NOW(),
                error_message = %s
            WHERE id = %s
        """, (str(e), run_id))
        
        raise

def run_single_competitor(collection_id, competitor_id):
    """
    Run page crawler for a single competitor
    
    Args:
        collection_id: Collection ID (for tracking purposes)
        competitor_id: Specific competitor to crawl
    """
    logger.info(f"Starting single competitor crawl: competitor_id={competitor_id}")
    
    # Get competitor details
    competitor = execute_query("""
        SELECT id, name, website
        FROM competitors
        WHERE id = %s
    """, (competitor_id,), fetch=True)
    
    if not competitor:
        logger.error(f"Competitor {competitor_id} not found")
        return
    
    competitor = competitor[0]
    
    if not competitor['website']:
        logger.error(f"Competitor {competitor['name']} has no website")
        return
    
    logger.info(f"Crawling competitor: {competitor['name']} ({competitor['website']})")
    
    # Initialize crawler
    crawler = PageCrawler(max_pages=50, max_depth=3)
    
    # Crawl the website
    try:
        pages_crawled = crawler.crawl_website(
            competitor_id=competitor['id'],
            website=competitor['website']
        )
        logger.info(f"Crawled {pages_crawled} pages from {competitor['name']}")
    except Exception as e:
        logger.error(f"Error crawling {competitor['name']}: {str(e)}")
        raise

if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description='Run page crawler for competitor websites')
    parser.add_argument('--collection-id', type=int, help='Run for specific collection by ID')
    parser.add_argument('--competitor-id', type=int, help='Run for specific competitor by ID')
    parser.add_argument('--retry-missed', action='store_true', help='Only crawl competitors with 0 pages')
    parser.add_argument('--freshness-days', type=int, default=7, help='Re-crawl if older than N days (default: 7)')
    parser.add_argument('--force', action='store_true', help='Force crawl all competitors, ignore freshness')
    parser.add_argument('--unblock-competitor', type=int, help='Unblock a competitor by ID to allow crawling again')
    
    args = parser.parse_args()
    
    try:
        if args.unblock_competitor:
            # Unblock a specific competitor
            execute_query("""
                UPDATE competitors 
                SET blocked_at = NULL,
                    block_reason = NULL
                WHERE id = %s
            """, (args.unblock_competitor,))
            logger.info(f"Unblocked competitor {args.unblock_competitor}")
            sys.exit(0)
        
        if args.collection_id:
            logger.info(f"Starting page crawler with collection_id={args.collection_id}")
            freshness = 0 if args.force else args.freshness_days
            
            if args.competitor_id:
                # Crawl single competitor
                run_single_competitor(args.collection_id, args.competitor_id)
            elif args.retry_missed:
                # Only crawl competitors with 0 pages
                run_collection(args.collection_id, freshness_days=freshness, missed_only=True)
            else:
                # Crawl all competitors
                run_collection(args.collection_id, freshness_days=freshness)
        elif args.competitor_id:
            logger.error("--competitor-id requires --collection-id")
            print("Use --collection-id along with --competitor-id")
        else:
            print("Usage:")
            print("  python workers/page_crawler.py --collection-id 1")
            print("  python workers/page_crawler.py --collection-id 1 --competitor-id 5")
            print("  python workers/page_crawler.py --collection-id 1 --retry-missed")
            print("  python workers/page_crawler.py --collection-id 1 --freshness-days 30")
            print("  python workers/page_crawler.py --collection-id 1 --force  # Crawl all regardless of age")
    except Exception as e:
        logger.error(f"Page crawler failed: {e}", exc_info=True)
        sys.exit(1)
