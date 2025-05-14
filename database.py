from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
import os
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler("db_log.txt"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Database connection configuration
DB_HOST = os.environ.get("DB_HOST", "localhost")
DB_USER = os.environ.get("DB_USER", "root")
DB_PASS = os.environ.get("DB_PASS", "")
DB_NAME = os.environ.get("DB_NAME", "portfolio_db")

# Check if we're running on Render
is_render = os.environ.get("RENDER", "").lower() == "true"

# Always use SQLite for Render deployment until a proper database is set up
if is_render:
    DATABASE_URL = "sqlite:///./portfolio.db"
    engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
    logger.info(f"Running on Render.com, using SQLite database")
else:
    # For local development
    try:
        DATABASE_URL = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}"
        engine = create_engine(DATABASE_URL)
        logger.info(f"MySQL database connection successful: host={DB_HOST}, user={DB_USER}, db={DB_NAME}")
    except Exception as e:
        logger.error(f"MySQL connection error: {str(e)}")
        # Fallback to SQLite if MySQL connection fails
        DATABASE_URL = "sqlite:///./portfolio.db"
        engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
        logger.info("Falling back to SQLite database")

# Create a SessionLocal class
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# Create a Base class
Base = declarative_base()