import os
import mysql.connector
from mysql.connector import pooling
from dotenv import load_dotenv

# Load .env from Laravel root directory (2 levels up from this script)
env_path = os.path.join(os.path.dirname(__file__), '..', '..', '.env')
load_dotenv(dotenv_path=env_path)

# Database connection pool - uses standard DB_* env vars
db_config = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_DATABASE', 'app_firmwizard'),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'port': int(os.getenv('DB_PORT', 3306)),
    'pool_name': 'collector_pool',
    'pool_size': 5
}

connection_pool = None

def init_db_pool():
    """Initialize database connection pool"""
    global connection_pool
    try:
        connection_pool = mysql.connector.pooling.MySQLConnectionPool(**db_config)
        print("Database connection pool initialized successfully")
        return True
    except Exception as e:
        print(f"Error initializing database pool: {e}")
        return False

def get_db_connection():
    """Get a connection from the pool"""
    global connection_pool
    if connection_pool is None:
        init_db_pool()
    return connection_pool.get_connection()

def execute_query(query, params=None, fetch=False):
    """Execute a query and return results if needed"""
    conn = None
    cursor = None
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(query, params or ())
        
        if fetch:
            result = cursor.fetchall()
            return result
        else:
            conn.commit()
            return cursor.lastrowid
    except Exception as e:
        if conn:
            conn.rollback()
        print(f"Database error: {e}")
        raise
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

def execute_many(query, params_list):
    """Execute multiple queries with different parameters"""
    conn = None
    cursor = None
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.executemany(query, params_list)
        conn.commit()
        return cursor.rowcount
    except Exception as e:
        if conn:
            conn.rollback()
        print(f"Database error: {e}")
        raise
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

# Settings cache
_settings_cache = {}

def get_setting(key, default=None):
    """Get a setting value from database with caching"""
    global _settings_cache
    
    # Check cache first
    if key in _settings_cache:
        return _settings_cache[key]
    
    try:
        result = execute_query(
            "SELECT setting_value FROM settings WHERE setting_key = %s",
            (key,),
            fetch=True
        )
        
        if result and len(result) > 0:
            value = result[0]['setting_value']
            _settings_cache[key] = value
            return value
    except Exception as e:
        print(f"Warning: Could not fetch setting '{key}' from database: {e}")
        # Fall back to environment variable
        return os.getenv(key, default)
    
    return default

def clear_settings_cache():
    """Clear the settings cache - useful after updates"""
    global _settings_cache
    _settings_cache = {}
