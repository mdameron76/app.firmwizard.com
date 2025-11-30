-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.43 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for firmwizard_ai_engine
CREATE DATABASE IF NOT EXISTS `firmwizard_ai_engine` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `firmwizard_ai_engine`;

-- Dumping structure for table firmwizard_ai_engine.ai_attribution_events
CREATE TABLE IF NOT EXISTS `ai_attribution_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned DEFAULT NULL,
  `gclid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fbclid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crm_lead_id` bigint unsigned DEFAULT NULL,
  `crm_case_id` bigint unsigned DEFAULT NULL,
  `matched_with` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_attribution_events_firm_id_created_at_index` (`firm_id`,`created_at`),
  KEY `ai_attribution_events_firm_id_index` (`firm_id`),
  KEY `ai_attribution_events_gclid_index` (`gclid`),
  KEY `ai_attribution_events_fbclid_index` (`fbclid`),
  KEY `ai_attribution_events_utm_source_index` (`utm_source`),
  KEY `ai_attribution_events_crm_lead_id_index` (`crm_lead_id`),
  KEY `ai_attribution_events_crm_case_id_index` (`crm_case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.ai_attribution_events: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.ai_firm_profiles
CREATE TABLE IF NOT EXISTS `ai_firm_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `brand_voice` json DEFAULT NULL,
  `personalities` json DEFAULT NULL,
  `target_audience` json DEFAULT NULL,
  `service_areas` json DEFAULT NULL,
  `competitor_strategy` json DEFAULT NULL,
  `seo_topics` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_firm_profiles_firm_id_unique` (`firm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.ai_firm_profiles: ~1 rows (approximately)
INSERT INTO `ai_firm_profiles` (`id`, `firm_id`, `brand_voice`, `personalities`, `target_audience`, `service_areas`, `competitor_strategy`, `seo_topics`, `created_at`, `updated_at`) VALUES
	(1, 1, '{"tone": "Empathetic"}', NULL, NULL, '{"primary_location": {"zip": "63028", "city": "Festus", "state": "MO"}}', NULL, NULL, '2025-11-19 19:50:28', '2025-11-19 19:50:28');

-- Dumping structure for table firmwizard_ai_engine.ai_marketing_recommendations
CREATE TABLE IF NOT EXISTS `ai_marketing_recommendations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `type` enum('fb_ads','google_ads','gbp','seo','content','full_report') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `effectiveness_score` decimal(5,2) DEFAULT NULL,
  `status` enum('draft','pending_review','applied','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `applied_at` timestamp NULL DEFAULT NULL,
  `applied_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_marketing_recommendations_firm_id_status_created_at_index` (`firm_id`,`status`,`created_at`),
  KEY `ai_marketing_recommendations_firm_id_index` (`firm_id`),
  KEY `ai_marketing_recommendations_type_index` (`type`),
  KEY `ai_marketing_recommendations_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.ai_marketing_recommendations: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.ai_training_events
CREATE TABLE IF NOT EXISTS `ai_training_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` json DEFAULT NULL,
  `output` json DEFAULT NULL,
  `feedback` json DEFAULT NULL,
  `improvement_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_training_events_firm_id_event_type_created_at_index` (`firm_id`,`event_type`,`created_at`),
  KEY `ai_training_events_firm_id_index` (`firm_id`),
  KEY `ai_training_events_event_type_index` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.ai_training_events: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.content_wordpress_posts
CREATE TABLE IF NOT EXISTS `content_wordpress_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `post_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `publish_date` datetime DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `url` text COLLATE utf8mb4_unicode_ci,
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `content_wordpress_posts_firm_id_status_publish_date_index` (`firm_id`,`status`,`publish_date`),
  KEY `content_wordpress_posts_firm_id_index` (`firm_id`),
  KEY `content_wordpress_posts_post_id_index` (`post_id`),
  KEY `content_wordpress_posts_publish_date_index` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.content_wordpress_posts: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.crm_daily_snapshot
CREATE TABLE IF NOT EXISTS `crm_daily_snapshot` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `firm_location_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `total_cases` int NOT NULL DEFAULT '0',
  `active_cases` int NOT NULL DEFAULT '0',
  `closed_cases` int NOT NULL DEFAULT '0',
  `case_types` json DEFAULT NULL,
  `avg_case_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gender_mix` json DEFAULT NULL,
  `lead_sources` json DEFAULT NULL,
  `avg_response_time` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_daily_snapshot_firm_id_date_unique` (`firm_id`,`date`),
  KEY `crm_daily_snapshot_firm_id_index` (`firm_id`),
  KEY `crm_daily_snapshot_firm_location_id_index` (`firm_location_id`),
  KEY `crm_daily_snapshot_date_index` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.crm_daily_snapshot: ~1 rows (approximately)
INSERT INTO `crm_daily_snapshot` (`id`, `firm_id`, `firm_location_id`, `date`, `total_cases`, `active_cases`, `closed_cases`, `case_types`, `avg_case_value`, `gender_mix`, `lead_sources`, `avg_response_time`, `created_at`, `updated_at`) VALUES
	(1, 1, NULL, '2025-11-19', 42, 18, 0, NULL, 4000.00, NULL, NULL, 0, '2025-11-19 19:50:46', '2025-11-19 19:50:46');

-- Dumping structure for table firmwizard_ai_engine.firm_meta_ads
CREATE TABLE IF NOT EXISTS `firm_meta_ads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `page_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ad_archive_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ad_title` text COLLATE utf8mb4_unicode_ci,
  `ad_body` text COLLATE utf8mb4_unicode_ci,
  `cta` text COLLATE utf8mb4_unicode_ci,
  `destination_url` text COLLATE utf8mb4_unicode_ci,
  `display_format` enum('IMAGE','VIDEO','CAROUSEL') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spend` decimal(10,2) NOT NULL DEFAULT '0.00',
  `impressions` int NOT NULL DEFAULT '0',
  `clicks` int NOT NULL DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `firm_meta_ads_firm_id_start_date_index` (`firm_id`,`start_date`),
  KEY `firm_meta_ads_firm_id_index` (`firm_id`),
  KEY `firm_meta_ads_ad_archive_id_index` (`ad_archive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.firm_meta_ads: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.gbp_insights
CREATE TABLE IF NOT EXISTS `gbp_insights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_location_id` bigint unsigned NOT NULL,
  `metric_date` date NOT NULL,
  `views_search` int NOT NULL DEFAULT '0',
  `views_maps` int NOT NULL DEFAULT '0',
  `actions_website` int NOT NULL DEFAULT '0',
  `actions_phone` int NOT NULL DEFAULT '0',
  `actions_direction` int NOT NULL DEFAULT '0',
  `photo_views_owner` int NOT NULL DEFAULT '0',
  `photo_views_customers` int NOT NULL DEFAULT '0',
  `local_post_views` int NOT NULL DEFAULT '0',
  `raw_response` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gbp_insights_firm_location_id_metric_date_unique` (`firm_location_id`,`metric_date`),
  KEY `gbp_insights_firm_location_id_index` (`firm_location_id`),
  KEY `gbp_insights_metric_date_index` (`metric_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.gbp_insights: ~1 rows (approximately)
INSERT INTO `gbp_insights` (`id`, `firm_location_id`, `metric_date`, `views_search`, `views_maps`, `actions_website`, `actions_phone`, `actions_direction`, `photo_views_owner`, `photo_views_customers`, `local_post_views`, `raw_response`, `created_at`, `updated_at`) VALUES
	(1, 1, '2025-11-19', 250, 180, 45, 12, 0, 0, 0, 0, NULL, '2025-11-19 19:50:46', '2025-11-19 19:50:46');

-- Dumping structure for table firmwizard_ai_engine.google_ads_campaigns
CREATE TABLE IF NOT EXISTS `google_ads_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `campaign_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(10,2) NOT NULL DEFAULT '0.00',
  `clicks` int NOT NULL DEFAULT '0',
  `impressions` int NOT NULL DEFAULT '0',
  `conversions` int NOT NULL DEFAULT '0',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `period_start` date NOT NULL,
  `period_end` date DEFAULT NULL,
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `google_ads_campaigns_firm_id_period_start_index` (`firm_id`,`period_start`),
  KEY `google_ads_campaigns_firm_id_index` (`firm_id`),
  KEY `google_ads_campaigns_campaign_id_index` (`campaign_id`),
  KEY `google_ads_campaigns_period_start_index` (`period_start`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.google_ads_campaigns: ~1 rows (approximately)
INSERT INTO `google_ads_campaigns` (`id`, `firm_id`, `campaign_id`, `name`, `status`, `budget`, `clicks`, `impressions`, `conversions`, `cost`, `period_start`, `period_end`, `raw`, `created_at`, `updated_at`) VALUES
	(1, 1, 'test-001', 'Divorce - Festus', NULL, 0.00, 45, 1200, 3, 287.50, '2025-11-19', NULL, NULL, '2025-11-19 19:50:46', '2025-11-19 19:50:46');

-- Dumping structure for table firmwizard_ai_engine.google_lsa_leads
CREATE TABLE IF NOT EXISTS `google_lsa_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `lead_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_datetime` datetime DEFAULT NULL,
  `caller_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caller_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_requested` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_status` enum('new','contacted','booked','spam','client','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `billed` tinyint(1) NOT NULL DEFAULT '1',
  `charge_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `dispute_status` enum('none','submitted','won','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `converted_to_client` tinyint(1) NOT NULL DEFAULT '0',
  `case_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `google_lsa_leads_firm_id_index` (`firm_id`),
  KEY `google_lsa_leads_call_datetime_index` (`call_datetime`),
  KEY `google_lsa_leads_firm_id_call_datetime_index` (`firm_id`,`call_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.google_lsa_leads: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.google_lsa_weekly_metrics
CREATE TABLE IF NOT EXISTS `google_lsa_weekly_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `total_spend` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_leads` int NOT NULL DEFAULT '0',
  `qualified_leads` int NOT NULL DEFAULT '0',
  `converted_clients` int NOT NULL DEFAULT '0',
  `avg_cost_per_lead` decimal(10,2) NOT NULL DEFAULT '0.00',
  `avg_cost_per_client` decimal(10,2) NOT NULL DEFAULT '0.00',
  `top_service_requested` json DEFAULT NULL,
  `top_zip_codes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `google_lsa_weekly_metrics_firm_id_index` (`firm_id`),
  KEY `google_lsa_weekly_metrics_week_start_index` (`week_start`),
  KEY `google_lsa_weekly_metrics_firm_id_week_start_index` (`firm_id`,`week_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.google_lsa_weekly_metrics: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.migrations: ~16 rows (approximately)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '2025_11_18_000001_create_gbp_insights_table', 1),
	(2, '2025_11_18_000002_create_firm_meta_ads_table', 1),
	(3, '2025_11_18_000003_create_google_ads_campaigns_table', 1),
	(4, '2025_11_18_000004_create_seo_backlinks_table', 1),
	(5, '2025_11_18_000005_create_seo_serp_competitors_table', 1),
	(6, '2025_11_18_000006_create_web_ga4_traffic_table', 1),
	(7, '2025_11_18_000007_create_web_search_console_queries_table', 1),
	(8, '2025_11_18_000008_create_crm_daily_snapshot_table', 1),
	(9, '2025_11_18_000009_create_content_wordpress_posts_table', 1),
	(10, '2025_11_18_000010_create_ai_firm_profiles_table', 1),
	(11, '2025_11_18_000011_create_ai_marketing_recommendations_table', 1),
	(12, '2025_11_18_000012_create_ai_training_events_table', 1),
	(13, '2025_11_18_000013_create_ai_attribution_events_table', 1),
	(14, '2025_11_18_000014_create_system_alerts_table', 1),
	(15, '2025_11_19_000015_create_google_lsa_leads_table', 2),
	(16, '2025_11_19_000016_create_google_lsa_weekly_metrics_table', 2);

-- Dumping structure for table firmwizard_ai_engine.seo_backlinks
CREATE TABLE IF NOT EXISTS `seo_backlinks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `competitor_id` bigint unsigned DEFAULT NULL,
  `firm_id` bigint unsigned DEFAULT NULL,
  `source_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `anchor` text COLLATE utf8mb4_unicode_ci,
  `domain_rating` int DEFAULT NULL,
  `is_nofollow` tinyint(1) NOT NULL DEFAULT '0',
  `first_seen` date DEFAULT NULL,
  `last_seen` date DEFAULT NULL,
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_backlinks_firm_id_last_seen_index` (`firm_id`,`last_seen`),
  KEY `seo_backlinks_competitor_id_index` (`competitor_id`),
  KEY `seo_backlinks_firm_id_index` (`firm_id`),
  KEY `seo_backlinks_last_seen_index` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.seo_backlinks: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.seo_serp_competitors
CREATE TABLE IF NOT EXISTS `seo_serp_competitors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_location_id` bigint unsigned NOT NULL,
  `keyword` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competitor_domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rank` int DEFAULT NULL,
  `page_url` text COLLATE utf8mb4_unicode_ci,
  `title` text COLLATE utf8mb4_unicode_ci,
  `snippet` text COLLATE utf8mb4_unicode_ci,
  `raw` json DEFAULT NULL,
  `crawled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_serp_competitors_firm_location_id_keyword_crawled_at_index` (`firm_location_id`,`keyword`,`crawled_at`),
  KEY `seo_serp_competitors_firm_location_id_index` (`firm_location_id`),
  KEY `seo_serp_competitors_keyword_index` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.seo_serp_competitors: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.system_alerts
CREATE TABLE IF NOT EXISTS `system_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alert_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `recommended_fix` text COLLATE utf8mb4_unicode_ci,
  `status` enum('open','acknowledged','resolved','ignored') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_alerts_firm_id_status_severity_created_at_index` (`firm_id`,`status`,`severity`,`created_at`),
  KEY `system_alerts_firm_id_index` (`firm_id`),
  KEY `system_alerts_severity_index` (`severity`),
  KEY `system_alerts_source_index` (`source`),
  KEY `system_alerts_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.system_alerts: ~1 rows (approximately)
INSERT INTO `system_alerts` (`id`, `firm_id`, `severity`, `source`, `alert_type`, `title`, `message`, `data`, `recommended_fix`, `status`, `acknowledged_at`, `resolved_at`, `resolved_by`, `created_at`, `updated_at`) VALUES
	(1, 1, 'medium', 'gbp', 'photos_outdated', 'GBP Photos Need Update', 'No new photos uploaded in over 90 days', '{"days_since_last_upload": 95}', 'Upload 10-15 new geotagged photos of office, team, and local landmarks', 'open', NULL, NULL, NULL, '2025-11-19 19:50:58', '2025-11-19 19:50:58');

-- Dumping structure for table firmwizard_ai_engine.web_ga4_traffic
CREATE TABLE IF NOT EXISTS `web_ga4_traffic` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_location_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `sessions` int NOT NULL DEFAULT '0',
  `engaged_sessions` int NOT NULL DEFAULT '0',
  `bounce_rate` decimal(6,2) NOT NULL DEFAULT '0.00',
  `avg_engagement_time` int NOT NULL DEFAULT '0',
  `new_users` int NOT NULL DEFAULT '0',
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `web_ga4_traffic_firm_location_id_date_unique` (`firm_location_id`,`date`),
  KEY `web_ga4_traffic_firm_location_id_index` (`firm_location_id`),
  KEY `web_ga4_traffic_date_index` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.web_ga4_traffic: ~0 rows (approximately)

-- Dumping structure for table firmwizard_ai_engine.web_search_console_queries
CREATE TABLE IF NOT EXISTS `web_search_console_queries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_location_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `query` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clicks` int NOT NULL DEFAULT '0',
  `impressions` int NOT NULL DEFAULT '0',
  `ctr` decimal(6,4) NOT NULL DEFAULT '0.0000',
  `position` decimal(6,2) NOT NULL DEFAULT '0.00',
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `web_search_console_queries_firm_location_id_query_date_index` (`firm_location_id`,`query`,`date`),
  KEY `web_search_console_queries_firm_location_id_index` (`firm_location_id`),
  KEY `web_search_console_queries_date_index` (`date`),
  KEY `web_search_console_queries_query_index` (`query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table firmwizard_ai_engine.web_search_console_queries: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
