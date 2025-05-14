import os
import shutil
import sys
from database import Base, engine
import models
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)

def create_directory(path):
    """Create directory if it doesn't exist"""
    if not os.path.exists(path):
        os.makedirs(path)
        print(f"Created directory: {path}")
    else:
        print(f"Directory already exists: {path}")

def setup_project():
    """Set up the project structure"""
    print("Setting up the FastAPI project structure...")
    
    # Create required directories
    create_directory("static")
    create_directory("templates")
    
    # Move HTML, CSS, and image files to static directory
    extensions = ['.html', '.css', '.jpg', '.png', '.gif', '.svg', '.js']
    
    for filename in os.listdir("."):
        file_ext = os.path.splitext(filename)[1].lower()
        if file_ext in extensions and os.path.isfile(filename):
            try:
                shutil.move(filename, os.path.join("static", filename))
                print(f"Moved {filename} to static directory")
            except shutil.Error:
                print(f"File already exists in static directory: {filename}")
    
    print("\nSetup complete!")
    print("\nTo run the application, use:")
    print("uvicorn main:app --reload")

def setup_database():
    try:
        # Create all database tables
        logger.info("Creating database tables...")
        Base.metadata.create_all(bind=engine)
        logger.info("Database tables created successfully")
        
        # Additional setup can be added here
        
    except Exception as e:
        logger.error(f"Error during database setup: {str(e)}")
        raise

if __name__ == "__main__":
    # Check if running on Render.com
    if os.environ.get("RENDER"):
        logger.info("Running on Render.com, setting up database...")
    else:
        logger.info("Running locally, setting up database...")
    
    setup_database()

    # Check if running on Render.com
    if os.environ.get("RENDER"):
        logger.info("Running on Render.com, setting up project...")
    else:
        logger.info("Running locally, setting up project...")
    
    setup_project() 