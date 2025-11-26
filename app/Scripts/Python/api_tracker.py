import os
from dotenv import load_dotenv

load_dotenv()

# Google API cost tracking (in USD per 1000 calls)
GOOGLE_COST_PER_1000 = {
    'geocode': float(os.getenv('GOOGLE_GEOCODE_COST_PER_1000', 5.0)),
    'places_text': float(os.getenv('GOOGLE_PLACES_TEXT_COST_PER_1000', 17.0)),
    'places_details': float(os.getenv('GOOGLE_PLACES_DETAILS_COST_PER_1000', 17.0))
}

from database import execute_query

def log_api_usage(collection_run_id, api_name, vendor='google', units=1):
    """
    Log API usage and calculate cost
    
    Args:
        collection_run_id: ID of the collection run
        api_name: Name of the API endpoint (geocode, places_text, places_details)
        vendor: API vendor (default: google)
        units: Number of API calls (default: 1)
    """
    try:
        # Calculate cost
        cost_per_1000 = GOOGLE_COST_PER_1000.get(api_name, 0)
        cost_usd = (cost_per_1000 / 1000.0) * units
        cost_cents = int(round(cost_usd * 100))
        
        # Insert into api_usage table
        query = """
            INSERT INTO api_usage (vendor, api_name, collection_run_id, units, cost_cents, called_at)
            VALUES (%s, %s, %s, %s, %s, NOW())
        """
        execute_query(query, (vendor, api_name, collection_run_id, units, cost_cents))
        
        # Update collection_runs totals
        update_query = """
            UPDATE collection_runs
            SET api_calls = api_calls + %s,
                api_cost_estimate_cents = api_cost_estimate_cents + %s
            WHERE id = %s
        """
        execute_query(update_query, (units, cost_cents, collection_run_id))
        
        return cost_cents
    except Exception as e:
        print(f"Error logging API usage: {e}")
        raise

def get_total_api_cost_by_date(start_date=None, end_date=None):
    """Get total API cost grouped by date"""
    query = """
        SELECT
            DATE(called_at) as day,
            SUM(units) as total_calls,
            SUM(cost_cents)/100.0 as total_cost_usd
        FROM api_usage
    """
    
    params = []
    if start_date and end_date:
        query += " WHERE DATE(called_at) BETWEEN %s AND %s"
        params = [start_date, end_date]
    elif start_date:
        query += " WHERE DATE(called_at) >= %s"
        params = [start_date]
    
    query += " GROUP BY DATE(called_at) ORDER BY day DESC"
    
    return execute_query(query, params, fetch=True)

def get_collection_run_cost(collection_run_id):
    """Get total cost for a specific collection run"""
    query = """
        SELECT
            SUM(units) as total_calls,
            SUM(cost_cents)/100.0 as total_cost_usd
        FROM api_usage
        WHERE collection_run_id = %s
    """
    result = execute_query(query, (collection_run_id,), fetch=True)
    return result[0] if result else {'total_calls': 0, 'total_cost_usd': 0}
