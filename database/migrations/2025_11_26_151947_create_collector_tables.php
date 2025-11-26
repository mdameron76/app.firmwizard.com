<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Read and execute the SQL file
        $sqlPath = database_path('sql/collector_tables.sql');
        
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            DB::unprepared($sql);
        } else {
            // Fallback: Create tables inline (for when SQL file doesn't exist)
            $this->createTablesInline();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order due to foreign keys
        Schema::dropIfExists('crawler_page_tracking_pixels');
        Schema::dropIfExists('crawler_page_schemas');
        Schema::dropIfExists('crawler_page_links');
        Schema::dropIfExists('crawler_page_keywords');
        Schema::dropIfExists('crawler_page_headings');
        Schema::dropIfExists('crawler_pages');
        Schema::dropIfExists('competitor_practice_areas');
        Schema::dropIfExists('competitor_meta_ads');
        Schema::dropIfExists('competitor_gbp_posts');
        Schema::dropIfExists('competitor_gbp_reviews');
        Schema::dropIfExists('competitor_gbp_locations');
        Schema::dropIfExists('competitor_collections');
        Schema::dropIfExists('competitors');
        Schema::dropIfExists('api_usage');
        Schema::dropIfExists('collection_runs');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('firm_locations');
        Schema::dropIfExists('worker_activity');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('lsa_account_settings');
    }

    /**
     * Create tables inline as fallback
     */
    private function createTablesInline(): void
    {
        // Workers table (no dependencies)
        if (!Schema::hasTable('workers')) {
            DB::statement("
                CREATE TABLE workers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    type ENUM('competitor_finder','page_crawler','other') NOT NULL,
                    max_concurrent_collections INT DEFAULT 1,
                    enabled TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_enabled (enabled)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Firm locations (depends on firms table which already exists)
        if (!Schema::hasTable('firm_locations')) {
            DB::statement("
                CREATE TABLE firm_locations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    firm_id INT DEFAULT NULL,
                    name VARCHAR(255) NOT NULL,
                    address VARCHAR(500) DEFAULT NULL,
                    city VARCHAR(100) DEFAULT NULL,
                    state VARCHAR(50) DEFAULT NULL,
                    zip_code VARCHAR(20) DEFAULT NULL,
                    country VARCHAR(100) DEFAULT 'USA',
                    latitude DECIMAL(10,7) DEFAULT NULL,
                    longitude DECIMAL(10,7) DEFAULT NULL,
                    search_radius_km INT DEFAULT 25,
                    phone VARCHAR(50) DEFAULT NULL,
                    website VARCHAR(500) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_firm_id (firm_id),
                    INDEX idx_city_state (city, state),
                    INDEX idx_lat_lng (latitude, longitude)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Collections
        if (!Schema::hasTable('collections')) {
            DB::statement("
                CREATE TABLE collections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    firm_id INT DEFAULT NULL,
                    firm_location_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    query VARCHAR(255) NOT NULL,
                    worker_id INT DEFAULT NULL,
                    enabled TINYINT(1) DEFAULT 1,
                    schedule_cron VARCHAR(64) DEFAULT NULL,
                    last_run_at DATETIME DEFAULT NULL,
                    next_run_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_firm_location_id (firm_location_id),
                    INDEX idx_enabled (enabled),
                    INDEX idx_next_run_at (next_run_at),
                    INDEX idx_worker_id (worker_id),
                    INDEX idx_firm_id (firm_id),
                    FOREIGN KEY (firm_location_id) REFERENCES firm_locations(id) ON DELETE CASCADE,
                    FOREIGN KEY (worker_id) REFERENCES workers(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Collection runs
        if (!Schema::hasTable('collection_runs')) {
            DB::statement("
                CREATE TABLE collection_runs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    collection_id INT NOT NULL,
                    started_at DATETIME NOT NULL,
                    finished_at DATETIME DEFAULT NULL,
                    status ENUM('pending','running','success','warning','error') DEFAULT 'pending',
                    error_message TEXT DEFAULT NULL,
                    items_processed INT DEFAULT 0,
                    api_calls INT DEFAULT 0,
                    api_cost_estimate_cents INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_collection_id (collection_id),
                    INDEX idx_status (status),
                    INDEX idx_started_at (started_at),
                    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // API usage
        if (!Schema::hasTable('api_usage')) {
            DB::statement("
                CREATE TABLE api_usage (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    collection_run_id INT DEFAULT NULL,
                    api_name VARCHAR(100) NOT NULL,
                    endpoint VARCHAR(255) DEFAULT NULL,
                    calls INT DEFAULT 1,
                    cost_estimate_cents INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_collection_run_id (collection_run_id),
                    INDEX idx_api_name (api_name),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Competitors
        if (!Schema::hasTable('competitors')) {
            DB::statement("
                CREATE TABLE competitors (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    firm_id INT NOT NULL,
                    firm_location_id INT NOT NULL,
                    collection_id INT DEFAULT NULL,
                    name VARCHAR(255) NOT NULL,
                    website VARCHAR(255) DEFAULT NULL,
                    facebook_page_url VARCHAR(255) DEFAULT NULL,
                    address VARCHAR(255) DEFAULT NULL,
                    phone VARCHAR(50) DEFAULT NULL,
                    place_id VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    blocked_at DATETIME DEFAULT NULL,
                    block_reason VARCHAR(255) DEFAULT NULL,
                    active TINYINT(1) DEFAULT 1,
                    gbp_location_count INT DEFAULT 0,
                    gbp_last_scraped DATETIME DEFAULT NULL,
                    gbp_scrape_status VARCHAR(50) DEFAULT NULL,
                    UNIQUE KEY unique_firm_location_website (firm_location_id, website),
                    INDEX idx_place_id (place_id),
                    INDEX idx_firm_id (firm_id),
                    INDEX idx_firm_location_id (firm_location_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Competitor collections
        if (!Schema::hasTable('competitor_collections')) {
            DB::statement("
                CREATE TABLE competitor_collections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    competitor_id INT NOT NULL,
                    collection_id INT NOT NULL,
                    found_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_competitor_collection (competitor_id, collection_id),
                    INDEX idx_competitor_id (competitor_id),
                    INDEX idx_collection_id (collection_id),
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE,
                    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Continue with other tables...
        $this->createCompetitorTables();
        $this->createCrawlerTables();
        $this->createSupportTables();
    }

    private function createCompetitorTables(): void
    {
        if (!Schema::hasTable('competitor_practice_areas')) {
            DB::statement("
                CREATE TABLE competitor_practice_areas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    competitor_id INT NOT NULL,
                    practice_area VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_competitor_practice (competitor_id, practice_area),
                    INDEX idx_competitor_id (competitor_id),
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('competitor_gbp_locations')) {
            DB::statement("
                CREATE TABLE competitor_gbp_locations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    competitor_id INT NOT NULL,
                    location_name VARCHAR(500) NOT NULL,
                    place_id VARCHAR(255) DEFAULT NULL,
                    business_name VARCHAR(255) NOT NULL,
                    address_line1 VARCHAR(255) DEFAULT NULL,
                    locality VARCHAR(100) DEFAULT NULL,
                    administrative_area VARCHAR(100) DEFAULT NULL,
                    postal_code VARCHAR(20) DEFAULT NULL,
                    latitude DECIMAL(10,8) DEFAULT NULL,
                    longitude DECIMAL(11,8) DEFAULT NULL,
                    phone_number VARCHAR(50) DEFAULT NULL,
                    website_url VARCHAR(500) DEFAULT NULL,
                    rating DECIMAL(2,1) DEFAULT NULL,
                    review_count INT DEFAULT 0,
                    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_location_name (location_name),
                    INDEX idx_competitor_id (competitor_id),
                    INDEX idx_place_id (place_id),
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('competitor_gbp_reviews')) {
            DB::statement("
                CREATE TABLE competitor_gbp_reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    location_id INT NOT NULL,
                    competitor_id INT NOT NULL,
                    review_name VARCHAR(500) NOT NULL,
                    reviewer_name VARCHAR(255) DEFAULT NULL,
                    reviewer_profile_photo_url VARCHAR(500) DEFAULT NULL,
                    star_rating ENUM('ONE','TWO','THREE','FOUR','FIVE') NOT NULL,
                    comment TEXT DEFAULT NULL,
                    review_reply TEXT DEFAULT NULL,
                    review_reply_time TIMESTAMP NULL DEFAULT NULL,
                    create_time TIMESTAMP NULL DEFAULT NULL,
                    update_time TIMESTAMP NULL DEFAULT NULL,
                    raw_response JSON DEFAULT NULL,
                    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_review_name (review_name),
                    INDEX idx_location_id (location_id),
                    INDEX idx_competitor_id (competitor_id),
                    INDEX idx_star_rating (star_rating),
                    INDEX idx_create_time (create_time),
                    FOREIGN KEY (location_id) REFERENCES competitor_gbp_locations(id) ON DELETE CASCADE,
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('competitor_gbp_posts')) {
            DB::statement("
                CREATE TABLE competitor_gbp_posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    location_id INT NOT NULL,
                    competitor_id INT NOT NULL,
                    post_name VARCHAR(500) NOT NULL,
                    language_code VARCHAR(10) DEFAULT NULL,
                    summary TEXT DEFAULT NULL,
                    call_to_action_type VARCHAR(50) DEFAULT NULL,
                    call_to_action_url VARCHAR(500) DEFAULT NULL,
                    media_url VARCHAR(500) DEFAULT NULL,
                    media_format VARCHAR(50) DEFAULT NULL,
                    topic_type VARCHAR(100) DEFAULT NULL,
                    alert_type VARCHAR(100) DEFAULT NULL,
                    state ENUM('LIVE','NOT_LIVE') DEFAULT 'NOT_LIVE',
                    create_time TIMESTAMP NULL DEFAULT NULL,
                    update_time TIMESTAMP NULL DEFAULT NULL,
                    raw_response JSON DEFAULT NULL,
                    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_post_name (post_name),
                    INDEX idx_location_id (location_id),
                    INDEX idx_competitor_id (competitor_id),
                    INDEX idx_state (state),
                    INDEX idx_create_time (create_time),
                    FOREIGN KEY (location_id) REFERENCES competitor_gbp_locations(id) ON DELETE CASCADE,
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('competitor_meta_ads')) {
            DB::statement("
                CREATE TABLE competitor_meta_ads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    competitor_id INT NOT NULL,
                    ad_id VARCHAR(255) NOT NULL,
                    page_name VARCHAR(255) DEFAULT NULL,
                    ad_creative_body TEXT DEFAULT NULL,
                    ad_delivery_start_time TIMESTAMP NULL DEFAULT NULL,
                    ad_snapshot_url VARCHAR(500) DEFAULT NULL,
                    raw_response JSON DEFAULT NULL,
                    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_ad_id (ad_id),
                    INDEX idx_competitor_id (competitor_id),
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    private function createCrawlerTables(): void
    {
        if (!Schema::hasTable('crawler_pages')) {
            DB::statement("
                CREATE TABLE crawler_pages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    competitor_id INT NOT NULL,
                    url TEXT NOT NULL,
                    url_hash VARCHAR(64) NOT NULL,
                    title VARCHAR(500) DEFAULT NULL,
                    content LONGTEXT DEFAULT NULL,
                    content_hash VARCHAR(64) NOT NULL,
                    meta_description TEXT DEFAULT NULL,
                    word_count INT DEFAULT 0,
                    page_type VARCHAR(50) DEFAULT 'unknown',
                    status_code INT DEFAULT 200,
                    crawled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_competitor_url (competitor_id, url_hash),
                    INDEX idx_competitor_id (competitor_id),
                    INDEX idx_url_hash (url_hash),
                    FOREIGN KEY (competitor_id) REFERENCES competitors(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('crawler_page_headings')) {
            DB::statement("
                CREATE TABLE crawler_page_headings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    level TINYINT UNSIGNED NOT NULL,
                    position INT UNSIGNED NOT NULL,
                    anchor_id VARCHAR(255) DEFAULT NULL,
                    text TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_page_heading (page_id, level, position),
                    INDEX idx_page_level (page_id, level, position),
                    FOREIGN KEY (page_id) REFERENCES crawler_pages(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('crawler_page_keywords')) {
            DB::statement("
                CREATE TABLE crawler_page_keywords (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    ngram_type TINYINT UNSIGNED DEFAULT 1,
                    keyword VARCHAR(255) NOT NULL,
                    frequency INT DEFAULT 0,
                    density DECIMAL(6,4) DEFAULT 0.0000,
                    in_title TINYINT(1) DEFAULT 0,
                    in_meta_description TINYINT(1) DEFAULT 0,
                    in_h1 TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_page_keyword (page_id, keyword, ngram_type),
                    INDEX idx_page_keyword (page_id, keyword, ngram_type),
                    INDEX idx_keyword (keyword, ngram_type),
                    FOREIGN KEY (page_id) REFERENCES crawler_pages(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('crawler_page_links')) {
            DB::statement("
                CREATE TABLE crawler_page_links (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    href TEXT NOT NULL,
                    anchor_text TEXT DEFAULT NULL,
                    link_type ENUM('internal','external') DEFAULT 'internal',
                    rel VARCHAR(255) DEFAULT NULL,
                    target VARCHAR(32) DEFAULT NULL,
                    position INT UNSIGNED DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_page (page_id, link_type),
                    FOREIGN KEY (page_id) REFERENCES crawler_pages(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('crawler_page_schemas')) {
            DB::statement("
                CREATE TABLE crawler_page_schemas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    schema_type VARCHAR(255) NOT NULL,
                    source ENUM('json_ld','microdata','rdfa') DEFAULT 'json_ld',
                    schema_id VARCHAR(500) DEFAULT NULL,
                    name VARCHAR(500) DEFAULT NULL,
                    raw_json JSON DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_page (page_id),
                    INDEX idx_type (schema_type),
                    FOREIGN KEY (page_id) REFERENCES crawler_pages(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('crawler_page_tracking_pixels')) {
            DB::statement("
                CREATE TABLE crawler_page_tracking_pixels (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    pixel_type ENUM('facebook_pixel','google_analytics','google_tag_manager','google_ads','linkedin_insight','twitter_pixel','tiktok_pixel','other') NOT NULL,
                    pixel_id VARCHAR(100) DEFAULT NULL,
                    found_in ENUM('head','body','script') NOT NULL,
                    raw_snippet TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_page_pixel (page_id, pixel_type, pixel_id),
                    FOREIGN KEY (page_id) REFERENCES crawler_pages(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    private function createSupportTables(): void
    {
        if (!Schema::hasTable('worker_activity')) {
            DB::statement("
                CREATE TABLE worker_activity (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    worker_id INT NOT NULL,
                    collection_id INT DEFAULT NULL,
                    collection_run_id INT DEFAULT NULL,
                    activity_type VARCHAR(100) NOT NULL,
                    message TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_worker_id (worker_id),
                    INDEX idx_collection_run_id (collection_run_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!Schema::hasTable('lsa_account_settings')) {
            DB::statement("
                CREATE TABLE lsa_account_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    firm_id INT DEFAULT NULL,
                    enabled TINYINT(1) DEFAULT 1,
                    service_account_email VARCHAR(255) DEFAULT NULL,
                    service_account_key_json TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_firm_id (firm_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
};
