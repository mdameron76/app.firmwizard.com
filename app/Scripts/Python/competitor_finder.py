"""
Competitor Finder Worker
Runs collections to find competitors using Google Places API
"""
import os
import sys
import logging
from datetime import datetime
from dotenv import load_dotenv

# Add current directory to path for imports
current_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, current_dir)

from database import execute_query, get_db_connection, get_setting
from api_tracker import log_api_usage
from law_competitor_finder import LawFirmCompetitorFinder

load_dotenv()

# Setup logging
log_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'logs')
os.makedirs(log_dir, exist_ok=True)
log_file = os.path.join(log_dir, 'competitor_finder.log')

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

def run_collection(collection_id):
    """
    Run a single collection to find competitors
    """
    conn = None
    try:
        logger.info(f"Starting collection run for collection_id={collection_id}")
        
        # Get collection details
        collection = execute_query("""
            SELECT 
                c.*,
                fl.city, fl.state, fl.zipcode, fl.radius_meters
            FROM collections c
            JOIN firm_locations fl ON c.firm_location_id = fl.id
            WHERE c.id = %s AND c.enabled = 1
        """, (collection_id,), fetch=True)
        
        if not collection:
            logger.warning(f"Collection {collection_id} not found or disabled")
            print(f"Collection {collection_id} not found or disabled")
            return
        
        collection = collection[0]
        logger.info(f"Running collection: {collection['name']}")
        print(f"Running collection: {collection['name']}")
        
        # Create collection run
        run_id = execute_query("""
            INSERT INTO collection_runs 
            (collection_id, started_at, status)
            VALUES (%s, NOW(), 'running')
        """, (collection_id,))
        
        logger.info(f"Created collection_run id={run_id}")
        
        try:
            # Initialize competitor finder
            api_key = get_setting('GOOGLE_MAPS_API_KEY') or os.getenv('GOOGLE_MAPS_API_KEY')
            if not api_key:
                error_msg = "GOOGLE_MAPS_API_KEY not set in settings or environment"
                logger.error(error_msg)
                raise Exception(error_msg)
            
            finder = LawFirmCompetitorFinder(google_api_key=api_key)
            
            # Find competitors
            search_info = f"{collection['query']} in {collection['city']}, {collection['state']}"
            logger.info(f"Searching for: {search_info}")
            print(f"Searching for: {search_info}")
            
            competitors = finder.find_competitors(
                city=collection['city'],
                state=collection['state'],
                zipcode=collection['zipcode'],
                radius_meters=collection['radius_meters'],
                query=collection['query']
            )
            
            logger.info(f"Found {len(competitors)} competitors")
            print(f"Found {len(competitors)} competitors")
            
            # Log API usage (estimate based on competitors found)
            # Geocode: 1 call
            log_api_usage(run_id, 'geocode', units=1)
            
            # Places text search: typically 1-3 calls depending on results
            places_calls = max(1, len(competitors) // 20)  # 20 results per page
            log_api_usage(run_id, 'places_text', units=places_calls)
            
            # Places details: 1 call per competitor
            log_api_usage(run_id, 'places_details', units=len(competitors))
            
            # Get firm_id and firm_location_id from collection
            firm_id = collection['firm_id']
            firm_location_id = collection['firm_location_id']
            
            # Store competitors
            for comp in competitors:
                # Check if competitor already exists for this firm_location (prevents duplicates across collections)
                existing = execute_query("""
                    SELECT id FROM competitors 
                    WHERE firm_location_id = %s AND (website = %s OR place_id = %s)
                """, (firm_location_id, comp.website, comp.place_id), fetch=True)
                
                if existing:
                    competitor_id = existing[0]['id']
                    # Update existing competitor
                    execute_query("""
                        UPDATE competitors 
                        SET name = %s, address = %s, phone = %s, place_id = %s, website = %s, updated_at = NOW()
                        WHERE id = %s
                    """, (comp.name, comp.address, comp.phone, comp.place_id, comp.website, competitor_id))
                else:
                    # Insert new competitor
                    try:
                        competitor_id = execute_query("""
                            INSERT INTO competitors 
                            (firm_id, firm_location_id, name, website, address, phone, place_id)
                            VALUES (%s, %s, %s, %s, %s, %s, %s)
                        """, (firm_id, firm_location_id, comp.name, comp.website, comp.address, comp.phone, comp.place_id))
                    except Exception as e:
                        # If duplicate due to unique constraint, fetch the existing one
                        if 'Duplicate entry' in str(e):
                            existing = execute_query("""
                                SELECT id FROM competitors 
                                WHERE firm_location_id = %s AND (website = %s OR place_id = %s)
                            """, (firm_location_id, comp.website, comp.place_id), fetch=True)
                            if existing:
                                competitor_id = existing[0]['id']
                            else:
                                raise e
                        else:
                            raise e
                
                # Link competitor to this collection (if not already linked)
                execute_query("""
                    INSERT IGNORE INTO competitor_collections (competitor_id, collection_id)
                    VALUES (%s, %s)
                """, (competitor_id, collection_id))
                
                # Clear existing practice areas
                execute_query("DELETE FROM competitor_practice_areas WHERE competitor_id = %s", 
                            (competitor_id,))
                
                # Insert practice areas (IGNORE duplicates)
                for area in comp.practice_areas:
                    execute_query("""
                        INSERT IGNORE INTO competitor_practice_areas (competitor_id, practice_area)
                        VALUES (%s, %s)
                    """, (competitor_id, area))
            
            # Update collection run as success
            execute_query("""
                UPDATE collection_runs 
                SET finished_at = NOW(), status = 'success', items_processed = %s
                WHERE id = %s
            """, (len(competitors), run_id))
            
            # Update collection last_run_at
            execute_query("""
                UPDATE collections 
                SET last_run_at = NOW()
                WHERE id = %s
            """, (collection_id,))
            
            # Auto-create page_crawler collection if it doesn't exist and we have competitors with websites
            # Count competitors linked to this collection that have websites
            competitors_with_websites = execute_query("""
                SELECT COUNT(DISTINCT c.id) as count
                FROM competitors c
                JOIN competitor_collections cc ON c.id = cc.competitor_id
                WHERE cc.collection_id = %s AND c.website IS NOT NULL AND c.website != ''
            """, (collection_id,), fetch=True)[0]['count']
            
            if competitors_with_websites > 0:
                # Check if page_crawler collection already exists for this firm_location
                collection_info = execute_query("""
                    SELECT firm_location_id, name FROM collections WHERE id = %s
                """, (collection_id,), fetch=True)[0]
                
                existing_crawler = execute_query("""
                    SELECT id FROM collections
                    WHERE firm_location_id = %s 
                    AND worker_id = 3  -- page_crawler worker
                    AND name LIKE CONCAT(%s, '%')
                """, (collection_info['firm_location_id'], collection_info['name'].split(' - ')[0] + '%'), fetch=True)
                
                if not existing_crawler:
                    # Create page_crawler collection
                    crawler_name = collection_info['name'] + ' - Page Crawler'
                    new_crawler_id = execute_query("""
                        INSERT INTO collections 
                        (firm_location_id, name, query, worker_id, enabled, schedule_cron)
                        VALUES (%s, %s, %s, 3, 1, '0 2 * * 0')
                    """, (collection_info['firm_location_id'], crawler_name, 'Crawl competitor websites'))
                    
                    logger.info(f"Auto-created page_crawler collection #{new_crawler_id}: {crawler_name}")
                    print(f"[SUCCESS] Auto-created page_crawler collection #{new_crawler_id} with {competitors_with_websites} competitors to crawl")
                else:
                    logger.info(f"Page_crawler collection already exists (ID: {existing_crawler[0]['id']})")
            
            logger.info(f"Collection run {run_id} completed successfully with {len(competitors)} competitors")
            print(f"[SUCCESS] Collection run {run_id} completed successfully")
            return run_id
            
        except Exception as e:
            # Update collection run as error
            error_msg = str(e)
            logger.error(f"Collection run {run_id} failed: {error_msg}", exc_info=True)
            execute_query("""
                UPDATE collection_runs 
                SET finished_at = NOW(), status = 'error', error_message = %s
                WHERE id = %s
            """, (error_msg, run_id))
            print(f"[ERROR] Collection run {run_id} failed: {error_msg}")
            raise
            
    except Exception as e:
        logger.error(f"Error running collection {collection_id}: {e}", exc_info=True)
        print(f"Error running collection {collection_id}: {e}")
        raise

def run_all_enabled_collections():
    """
    Run all enabled collections
    """
    logger.info("Starting to run all enabled collections")
    collections = execute_query("""
        SELECT id, name FROM collections WHERE enabled = 1
    """, fetch=True)
    
    logger.info(f"Found {len(collections)} enabled collections")
    print(f"Found {len(collections)} enabled collections")
    
    for collection in collections:
        try:
            run_collection(collection['id'])
        except Exception as e:
            logger.error(f"Failed to run collection {collection['name']}: {e}")
            print(f"Failed to run collection {collection['name']}: {e}")
            continue

if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description='Run competitor finder for collections')
    parser.add_argument('--collection-id', type=int, help='Run specific collection by ID')
    parser.add_argument('--all', action='store_true', help='Run all enabled collections')
    
    args = parser.parse_args()
    
    try:
        if args.collection_id:
            logger.info(f"Starting worker with collection_id={args.collection_id}")
            run_collection(args.collection_id)
        elif args.all:
            logger.info("Starting worker for all enabled collections")
            run_all_enabled_collections()
        else:
            print("Usage:")
            print("  python workers/competitor_finder.py --collection-id 1")
            print("  python workers/competitor_finder.py --all")
    except Exception as e:
        logger.error(f"Worker failed: {e}", exc_info=True)
        sys.exit(1)
