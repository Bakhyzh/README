import bcrypt
import logging
from sqlalchemy.orm import Session
import models
import schemas

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler("app.log"),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# User database operations
def get_user(db: Session, user_id: int):
    return db.query(models.User).filter(models.User.id == user_id).first()

def get_user_by_email(db: Session, email: str):
    return db.query(models.User).filter(models.User.email == email).first()

def get_user_by_username(db: Session, username: str):
    return db.query(models.User).filter(
        (models.User.username == username) | (models.User.email == username)
    ).first()

def get_users(db: Session, skip: int = 0, limit: int = 100):
    return db.query(models.User).offset(skip).limit(limit).all()

def create_user(db: Session, user: schemas.UserCreate):
    # Hash the password
    hashed_password = bcrypt.hashpw(user.password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    
    # Create new user
    db_user = models.User(
        fullname=user.fullname,
        email=user.email,
        username=user.username,
        password=hashed_password,
        is_admin=False
    )
    
    # Add and commit to database
    db.add(db_user)
    db.commit()
    db.refresh(db_user)
    
    return db_user

def update_user(db: Session, user_id: int, user_update: schemas.UserUpdate):
    db_user = get_user(db, user_id)
    if not db_user:
        return None
    
    # Update fields if they exist in the update
    update_data = user_update.dict(exclude_unset=True)
    
    # Hash password if it's being updated
    if 'password' in update_data and update_data['password']:
        update_data['password'] = bcrypt.hashpw(
            update_data['password'].encode('utf-8'), 
            bcrypt.gensalt()
        ).decode('utf-8')
    
    # Apply the updates
    for key, value in update_data.items():
        setattr(db_user, key, value)
    
    db.commit()
    db.refresh(db_user)
    
    return db_user

def delete_user(db: Session, user_id: int):
    db_user = get_user(db, user_id)
    if not db_user:
        return False
    
    db.delete(db_user)
    db.commit()
    
    return True

# Authentication functions
def verify_password(plain_password, hashed_password):
    return bcrypt.checkpw(plain_password.encode('utf-8'), hashed_password.encode('utf-8'))

def authenticate_user(db: Session, username: str, password: str):
    user = get_user_by_username(db, username)
    if not user:
        logger.info(f"User {username} not found")
        return False
    
    if not verify_password(password, user.password):
        logger.info(f"Invalid password for user {username}")
        return False
    
    logger.info(f"User {username} authenticated successfully")
    return user 