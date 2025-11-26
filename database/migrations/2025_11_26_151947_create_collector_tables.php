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
        // Create tables using Laravel Schema Builder for database compatibility
        $this->createTablesInline();
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
            Schema::create('workers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('display_name', 255);
                $table->text('description')->nullable();
                $table->string('script_path', 255);
                $table->boolean('enabled')->default(true);
                $table->string('icon', 50)->default('fas fa-cog');
                $table->string('color', 20)->default('primary');
                $table->boolean('accepts_collection_id')->default(true);
                $table->boolean('accepts_competitor_id')->default(false);
                $table->timestamps();
                
                $table->index('enabled');
            });
        }

        // Firm locations (depends on firms table which already exists)
        if (!Schema::hasTable('firm_locations')) {
            Schema::create('firm_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('firm_id')->nullable()->constrained('firms')->onDelete('cascade');
                $table->string('external_location_id', 64)->nullable();
                $table->string('name', 255)->nullable();
                $table->string('address_line1', 255)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 50)->nullable();
                $table->string('zipcode', 20)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->integer('radius_meters')->default(25000);
                $table->timestamps();
                
                $table->unique(['firm_id', 'city', 'state', 'zipcode'], 'unique_firm_location');
                $table->index('external_location_id');
            });
        }

        // Collections
        if (!Schema::hasTable('collections')) {
            Schema::create('collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('firm_id')->nullable()->constrained('firms')->onDelete('cascade');
                $table->foreignId('firm_location_id')->constrained('firm_locations')->onDelete('cascade');
                $table->string('name', 255);
                $table->string('query', 255);
                $table->foreignId('worker_id')->nullable()->constrained('workers')->onDelete('set null');
                $table->boolean('enabled')->default(true);
                $table->string('schedule_cron', 64)->nullable();
                $table->dateTime('last_run_at')->nullable();
                $table->dateTime('next_run_at')->nullable();
                $table->timestamps();
                
                $table->index('firm_location_id');
                $table->index('enabled');
                $table->index('next_run_at');
                $table->index('firm_id');
            });
        }

        // Collection runs
        if (!Schema::hasTable('collection_runs')) {
            Schema::create('collection_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
                $table->dateTime('started_at');
                $table->dateTime('finished_at')->nullable();
                $table->enum('status', ['pending', 'running', 'success', 'warning', 'error'])->default('pending');
                $table->text('error_message')->nullable();
                $table->integer('items_processed')->default(0)->comment('Generic count: competitors, leads, pages, etc.');
                $table->integer('api_calls')->default(0);
                $table->integer('api_cost_estimate_cents')->default(0);
                $table->timestamps();
                
                $table->index('collection_id');
                $table->index('status');
                $table->index('started_at');
            });
        }

        // API usage
        if (!Schema::hasTable('api_usage')) {
            Schema::create('api_usage', function (Blueprint $table) {
                $table->id();
                $table->string('vendor', 100);
                $table->string('api_name', 100);
                $table->foreignId('collection_run_id')->nullable()->constrained('collection_runs')->onDelete('set null');
                $table->integer('units')->default(1);
                $table->integer('cost_cents')->default(0);
                $table->dateTime('called_at');
                $table->timestamps();
                
                $table->index('collection_run_id');
                $table->index('vendor');
                $table->index('called_at');
            });
        }

        // Competitors
        if (!Schema::hasTable('competitors')) {
            Schema::create('competitors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
                $table->foreignId('firm_location_id')->constrained('firm_locations')->onDelete('cascade');
                $table->foreignId('collection_id')->nullable()->constrained('collections')->onDelete('set null');
                $table->string('name', 255);
                $table->string('website', 255)->nullable();
                $table->string('facebook_page_url', 255)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('place_id', 255)->nullable();
                $table->dateTime('blocked_at')->nullable();
                $table->string('block_reason', 255)->nullable();
                $table->dateTime('facebook_last_scraped')->nullable();
                $table->integer('facebook_ads_count')->default(0);
                $table->string('facebook_scrape_status', 50)->nullable();
                $table->boolean('active')->default(true);
                $table->integer('gbp_location_count')->default(0)->comment('Number of GBP locations found');
                $table->dateTime('gbp_last_scraped')->nullable()->comment('Last time GBP data was collected');
                $table->string('gbp_scrape_status', 50)->nullable()->comment('Status of last GBP scrape');
                $table->string('gbp_account_name', 500)->nullable()->comment('GBP account resource name if found');
                $table->timestamps();
                
                $table->unique(['firm_location_id', 'website'], 'unique_firm_location_website');
                $table->unique(['firm_location_id', 'place_id'], 'unique_firm_location_place');
                $table->index('place_id');
                $table->index('firm_id');
            });
        }

        // Competitor collections (junction table)
        if (!Schema::hasTable('competitor_collections')) {
            Schema::create('competitor_collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
                $table->timestamp('found_at')->useCurrent();
                
                $table->unique(['competitor_id', 'collection_id'], 'unique_competitor_collection');
                $table->index('competitor_id');
                $table->index('collection_id');
            });
        }

        // Continue with other tables...
        $this->createCompetitorTables();
        $this->createCrawlerTables();
        $this->createSupportTables();
    }

    private function createCompetitorTables(): void
    {
        if (!Schema::hasTable('competitor_practice_areas')) {
            Schema::create('competitor_practice_areas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->string('practice_area', 255);
                $table->timestamps();
                
                $table->unique(['competitor_id', 'practice_area'], 'unique_competitor_practice');
                $table->index('competitor_id');
            });
        }

        if (!Schema::hasTable('competitor_gbp_locations')) {
            Schema::create('competitor_gbp_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->string('location_name', 500)->unique();
                $table->string('place_id', 255)->nullable();
                $table->string('store_code', 100)->nullable();
                $table->string('business_name', 255);
                $table->string('language_code', 10)->default('en');
                $table->string('title', 500)->nullable();
                $table->text('description')->nullable();
                $table->string('address_line1', 255)->nullable();
                $table->string('address_line2', 255)->nullable();
                $table->string('locality', 100)->nullable();
                $table->string('administrative_area', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 3)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('primary_category', 255)->nullable();
                $table->json('additional_categories')->nullable();
                $table->string('phone_number', 50)->nullable();
                $table->string('website_url', 500)->nullable();
                $table->json('service_areas')->nullable();
                $table->json('attributes')->nullable();
                $table->json('regular_hours')->nullable();
                $table->json('special_hours')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_published')->default(false);
                $table->boolean('is_suspended')->default(false);
                $table->boolean('is_duplicate')->default(false);
                $table->text('profile_photo_url')->nullable();
                $table->string('cover_photo_url', 500)->nullable();
                $table->string('logo_url', 500)->nullable();
                $table->integer('photos_count')->default(0);
                $table->decimal('rating', 2, 1)->nullable();
                $table->integer('review_count')->default(0);
                $table->integer('total_reviews')->default(0);
                $table->date('opening_date')->nullable();
                $table->string('price_level', 50)->nullable();
                $table->integer('completeness_score')->default(0);
                $table->json('completeness_details')->nullable();
                $table->json('labels')->nullable();
                $table->json('raw_response')->nullable();
                $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending');
                $table->timestamp('synced_at')->nullable();
                $table->text('sync_error')->nullable();
                $table->timestamp('first_seen_at')->useCurrent();
                $table->timestamp('last_scraped_at')->useCurrent();
                $table->timestamps();
                
                $table->index('competitor_id');
                $table->index('place_id');
                $table->index('business_name');
                $table->index(['latitude', 'longitude']);
                $table->index('sync_status');
            });
        }

        if (!Schema::hasTable('competitor_gbp_reviews')) {
            Schema::create('competitor_gbp_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('location_id')->constrained('competitor_gbp_locations')->onDelete('cascade');
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->string('review_name', 500)->unique();
                $table->string('reviewer_name', 255)->nullable();
                $table->string('reviewer_profile_photo_url', 500)->nullable();
                $table->enum('star_rating', ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE']);
                $table->text('comment')->nullable();
                $table->text('review_reply')->nullable();
                $table->timestamp('review_reply_time')->nullable();
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamp('first_seen_at')->useCurrent();
                $table->timestamp('last_scraped_at')->useCurrent();
                
                $table->index('location_id');
                $table->index('competitor_id');
                $table->index('star_rating');
                $table->index('create_time');
            });
        }

        if (!Schema::hasTable('competitor_gbp_posts')) {
            Schema::create('competitor_gbp_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('location_id')->constrained('competitor_gbp_locations')->onDelete('cascade');
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->string('post_name', 500)->unique();
                $table->enum('post_type', ['STANDARD', 'EVENT', 'OFFER', 'ALERT', 'COVID_19'])->default('STANDARD');
                $table->text('summary')->nullable();
                $table->string('call_to_action_type', 50)->nullable();
                $table->string('call_to_action_url', 500)->nullable();
                $table->enum('media_format', ['PHOTO', 'VIDEO'])->nullable();
                $table->string('media_url', 500)->nullable();
                $table->string('event_title', 255)->nullable();
                $table->dateTime('event_start_date')->nullable();
                $table->dateTime('event_end_date')->nullable();
                $table->string('offer_coupon_code', 100)->nullable();
                $table->string('offer_redeem_url', 500)->nullable();
                $table->text('offer_terms')->nullable();
                $table->string('language_code', 10)->default('en');
                $table->enum('state', ['LIVE', 'PROCESSING', 'REJECTED'])->default('LIVE');
                $table->timestamp('create_time')->nullable();
                $table->timestamp('update_time')->nullable();
                $table->string('search_url', 500)->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamp('first_seen_at')->useCurrent();
                $table->timestamp('last_scraped_at')->useCurrent();
                
                $table->index('location_id');
                $table->index('competitor_id');
                $table->index('post_type');
                $table->index('create_time');
            });
        }

        if (!Schema::hasTable('competitor_meta_ads')) {
            Schema::create('competitor_meta_ads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->string('ad_archive_id', 50)->unique();
                $table->string('page_id', 50);
                $table->string('page_name', 255);
                $table->string('ad_library_url', 500);
                $table->text('ad_body_text')->nullable();
                $table->string('ad_title', 500)->nullable();
                $table->string('cta_text', 100)->nullable();
                $table->string('cta_type', 50)->nullable();
                $table->string('link_url', 500)->nullable();
                $table->text('link_description')->nullable();
                $table->enum('display_format', ['SINGLE_IMAGE', 'VIDEO', 'CAROUSEL', 'DCO', 'UNKNOWN'])->default('UNKNOWN');
                $table->json('publisher_platforms');
                $table->json('page_categories')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('ad_start_date')->nullable();
                $table->dateTime('ad_end_date')->nullable();
                $table->string('currency', 10)->nullable();
                $table->decimal('spend_min', 10, 2)->nullable();
                $table->decimal('spend_max', 10, 2)->nullable();
                $table->string('impressions_range', 100)->nullable();
                $table->json('creative_cards')->nullable();
                $table->json('extra_images')->nullable();
                $table->json('extra_videos')->nullable();
                $table->json('extra_texts')->nullable();
                $table->json('extra_links')->nullable();
                $table->string('page_profile_picture_url', 500)->nullable();
                $table->string('page_profile_uri', 500)->nullable();
                $table->integer('page_like_count')->nullable();
                $table->boolean('page_is_deleted')->default(false);
                $table->json('raw_snapshot')->nullable();
                $table->enum('collection_status', ['active', 'stopped', 'error'])->default('active');
                $table->text('collection_notes')->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_active')->nullable();
                $table->timestamp('last_scraped_at')->useCurrent();
                $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending');
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
                
                $table->index(['competitor_id', 'is_active']);
                $table->index('page_id');
                $table->index('collection_status');
                $table->index('sync_status');
            });
        }
    }

    private function createCrawlerTables(): void
    {
        if (!Schema::hasTable('crawler_pages')) {
            Schema::create('crawler_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('competitor_id')->constrained('competitors')->onDelete('cascade');
                $table->text('url');
                $table->string('url_hash', 64);
                $table->string('title', 500)->nullable();
                $table->longText('content')->nullable();
                $table->string('content_hash', 64);
                $table->text('meta_description')->nullable();
                $table->text('meta_keywords')->nullable();
                $table->integer('word_count')->default(0);
                $table->string('page_type', 50)->default('unknown');
                $table->integer('status_code')->default(200);
                $table->timestamp('crawled_at')->useCurrent();
                $table->timestamp('synced_at')->nullable();
                $table->enum('sync_status', ['pending', 'synced', 'error'])->default('pending');
                $table->text('sync_error')->nullable();
                $table->timestamps();
                
                $table->unique(['competitor_id', 'url_hash'], 'unique_competitor_url');
                $table->index('competitor_id');
                $table->index('url_hash');
                $table->index('sync_status');
                $table->index('synced_at');
            });
        }

        if (!Schema::hasTable('crawler_page_headings')) {
            Schema::create('crawler_page_headings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('crawler_pages')->onDelete('cascade');
                $table->unsignedTinyInteger('level')->comment('1=H1, 2=H2, 3=H3, etc.');
                $table->unsignedInteger('position');
                $table->string('anchor_id', 255)->nullable();
                $table->text('text');
                $table->timestamps();
                
                $table->unique(['page_id', 'level', 'position'], 'unique_page_heading');
            });
        }

        if (!Schema::hasTable('crawler_page_keywords')) {
            Schema::create('crawler_page_keywords', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('crawler_pages')->onDelete('cascade');
                $table->unsignedTinyInteger('ngram_type')->default(1)->comment('1=word, 2=bigram, 3=trigram');
                $table->string('keyword', 255);
                $table->integer('frequency')->default(0);
                $table->decimal('density', 6, 4)->default(0.0000);
                $table->integer('first_position')->nullable();
                $table->decimal('first_position_percent', 6, 4)->nullable();
                $table->boolean('in_title')->default(false);
                $table->boolean('in_meta_description')->default(false);
                $table->boolean('in_h1')->default(false);
                $table->boolean('in_h2')->default(false);
                $table->boolean('in_url_slug')->default(false);
                $table->timestamps();
                
                $table->unique(['page_id', 'keyword', 'ngram_type'], 'unique_page_keyword');
                $table->index(['keyword', 'ngram_type']);
            });
        }

        if (!Schema::hasTable('crawler_page_links')) {
            Schema::create('crawler_page_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('crawler_pages')->onDelete('cascade');
                $table->text('href');
                $table->text('anchor_text')->nullable();
                $table->enum('link_type', ['internal', 'external'])->default('internal');
                $table->string('rel', 255)->nullable();
                $table->string('target', 32)->nullable();
                $table->unsignedInteger('position')->nullable();
                $table->timestamps();
                
                $table->index(['page_id', 'link_type']);
            });
        }

        if (!Schema::hasTable('crawler_page_schemas')) {
            Schema::create('crawler_page_schemas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('crawler_pages')->onDelete('cascade');
                $table->string('schema_type', 255);
                $table->enum('source', ['json_ld', 'microdata', 'rdfa'])->default('json_ld');
                $table->string('schema_id', 500)->nullable();
                $table->string('name', 500)->nullable();
                $table->json('raw_json')->nullable();
                $table->timestamps();
                
                $table->index('page_id');
                $table->index('schema_type');
            });
        }

        if (!Schema::hasTable('crawler_page_tracking_pixels')) {
            Schema::create('crawler_page_tracking_pixels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('crawler_pages')->onDelete('cascade');
                $table->enum('pixel_type', ['facebook_pixel', 'google_analytics', 'google_tag_manager', 'google_ads', 'linkedin_insight', 'twitter_pixel', 'tiktok_pixel', 'other']);
                $table->string('pixel_id', 100)->nullable();
                $table->enum('found_in', ['head', 'body', 'script']);
                $table->text('raw_snippet')->nullable();
                $table->timestamps();
                
                $table->unique(['page_id', 'pixel_type', 'pixel_id'], 'unique_page_pixel');
            });
        }
    }

    private function createSupportTables(): void
    {
        if (!Schema::hasTable('worker_activity')) {
            Schema::create('worker_activity', function (Blueprint $table) {
                $table->id();
                $table->string('worker_type', 100)->nullable();
                $table->string('worker_action', 255);
                $table->string('target_type', 50)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->enum('status', ['started', 'running', 'completed', 'failed'])->default('started');
                $table->dateTime('started_at');
                $table->dateTime('finished_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->integer('items_processed')->default(0);
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index('worker_type');
                $table->index('status');
                $table->index('started_at');
                $table->index(['target_type', 'target_id']);
            });
        }

        if (!Schema::hasTable('lsa_account_settings')) {
            Schema::create('lsa_account_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
                $table->foreignId('location_id')->nullable()->constrained('firm_locations')->onDelete('set null');
                $table->string('account_id', 50);
                $table->string('manager_customer_id', 50);
                $table->string('business_name', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->enum('collection_frequency', ['hourly', 'daily', 'weekly'])->default('daily');
                $table->dateTime('last_collected_at')->nullable();
                $table->timestamps();
                
                $table->unique(['firm_id', 'account_id'], 'unique_firm_account');
                $table->index('account_id');
                $table->index('is_active');
            });
        }
    }
};
