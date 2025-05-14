from fastapi import FastAPI, Request, Depends, HTTPException, status
from fastapi.responses import JSONResponse, HTMLResponse, RedirectResponse, FileResponse, Response
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import OAuth2PasswordBearer, OAuth2PasswordRequestForm
from datetime import datetime, timedelta
from typing import Optional, List
from jose import JWTError, jwt
from pydantic import BaseModel
import os
import logging
import bcrypt
import uvicorn
from database import engine, SessionLocal, Base
from models import User
import models
import schemas
from sqlalchemy.orm import Session
import utils
import config

# Initialize FastAPI app
app = FastAPI(title="Portfolio API")

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allow all origins
    allow_credentials=True,
    allow_methods=["*"],  # Allow all methods
    allow_headers=["*"],  # Allow all headers
)

# Set up static files (for HTML, CSS, images)
app.mount("/static", StaticFiles(directory="static"), name="static")

# Set up templates
templates = Jinja2Templates(directory="templates")

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

# Create database tables if they don't exist
Base.metadata.create_all(bind=engine)

# Check if we're running on Render
is_render = os.environ.get("RENDER", "").lower() == "true"

# Create initial admin user
def create_admin_user(db):
    try:
        admin = db.query(models.User).filter(models.User.username == "admin").first()
        if not admin:
            hashed_password = bcrypt.hashpw("admin123".encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
            admin_user = models.User(
                fullname="Admin User",
                email="admin@example.com",
                username="admin",
                password=hashed_password,
                is_admin=True
            )
            db.add(admin_user)
            db.commit()
            logger.info("Admin user created successfully")
    except Exception as e:
        logger.error(f"Error creating admin user: {str(e)}")
        db.rollback()

# Initialize admin user
with SessionLocal() as db:
    create_admin_user(db)

# Dependency to get the database session
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# JWT settings from config
SECRET_KEY = config.SECRET_KEY
ALGORITHM = config.ALGORITHM
ACCESS_TOKEN_EXPIRE_MINUTES = config.ACCESS_TOKEN_EXPIRE_MINUTES

# OAuth2 password bearer for token authentication
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="token")

# Create a token
def create_access_token(data: dict, expires_delta: Optional[timedelta] = None):
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=15)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)
    return encoded_jwt

# Get current user from token
async def get_current_user(token: str = Depends(oauth2_scheme), db: Session = Depends(get_db)):
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        username: str = payload.get("sub")
        if username is None:
            raise credentials_exception
        token_data = schemas.TokenData(username=username)
    except JWTError:
        raise credentials_exception
    user = utils.get_user_by_username(db, username=token_data.username)
    if user is None:
        raise credentials_exception
    return user

# Get current active user
async def get_current_active_user(current_user: models.User = Depends(get_current_user)):
    return current_user

# Route to check if the database is configured correctly
@app.get("/check_db")
async def check_db(db: Session = Depends(get_db)):
    try:
        # Create admin user if it doesn't exist
        create_admin_user(db)
        
        # Get all users
        users = db.query(models.User).all()
        user_list = []
        for user in users:
            user_list.append({
                "id": user.id,
                "fullname": user.fullname,
                "email": user.email,
                "username": user.username,
                "created_at": str(user.created_at),
                "is_admin": user.is_admin
            })
        
        # HTML response
        html_content = f"""
        <h1>Database Status Check</h1>
        <p style='color:green'>Successfully connected to database!</p>
        <p style='color:green'>Users table exists.</p>
        
        <h2>Users in the database:</h2>
        <table border='1'>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Created At</th><th>Admin</th></tr>
        """
        
        for user in user_list:
            html_content += f"""
            <tr>
            <td>{user['id']}</td>
            <td>{user['fullname']}</td>
            <td>{user['email']}</td>
            <td>{user['username']}</td>
            <td>{user['created_at']}</td>
            <td>{user['is_admin']}</td>
            </tr>
            """
        
        html_content += """
        </table>
        <p style='color:green'>Database check completed successfully!</p>
        <p><a href='/register.html'>Go to registration page</a> | <a href='/login.html'>Go to login page</a></p>
        """
        
        return HTMLResponse(content=html_content)
    except Exception as e:
        logger.error(f"Database check error: {str(e)}")
        return HTMLResponse(
            content=f"""
            <h1>Database Status Check</h1>
            <p style='color:red'>Error connecting to database: {str(e)}</p>
            """,
            status_code=500
        )

# Login route
@app.post("/token")
async def login_for_access_token(form_data: OAuth2PasswordRequestForm = Depends(), db: Session = Depends(get_db)):
    user = utils.authenticate_user(db, form_data.username, form_data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect username or password",
            headers={"WWW-Authenticate": "Bearer"},
        )
    access_token_expires = timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    access_token = create_access_token(
        data={"sub": user.username}, expires_delta=access_token_expires
    )
    return {"access_token": access_token, "token_type": "bearer", "user_id": user.id, "is_admin": user.is_admin}

# Login route (compatible with the old PHP API)
@app.post("/debug_login.php")
async def debug_login(request: Request, db: Session = Depends(get_db)):
    form_data = await request.form()
    username = form_data.get("username")
    password = form_data.get("password")
    
    logger.info(f"Login attempt: username={username}")
    
    user = utils.authenticate_user(db, username, password)
    if not user:
        return JSONResponse(content={
            "status": "error",
            "message": "Invalid username or password",
            "debug": True
        })
    
    # Create session token
    access_token_expires = timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    access_token = create_access_token(
        data={"sub": user.username}, expires_delta=access_token_expires
    )
    
    # Check if user is admin
    if user.is_admin:
        logger.info(f"User {username} is an admin")
        return JSONResponse(content={
            "status": "success",
            "message": "Login successful! Redirecting to admin panel...",
            "redirect": "admin_panel.html",
            "token": access_token,
            "debug": True
        })
    else:
        logger.info(f"User {username} is not an admin")
        return JSONResponse(content={
            "status": "success",
            "message": "Login successful! Redirecting to profile...",
            "redirect": "profile.html", 
            "token": access_token,
            "debug": True
        })

# Register route
@app.post("/debug_register.php")
async def debug_register(request: Request, db: Session = Depends(get_db)):
    form_data = await request.form()
    fullname = form_data.get("fullname")
    email = form_data.get("email")
    username = form_data.get("username")
    password = form_data.get("password")
    confirm_password = form_data.get("confirmPassword")
    
    logger.info(f"Registration attempt: fullname={fullname}, email={email}, username={username}")
    
    # Validate data
    errors = []
    
    # Check passwords match
    if password != confirm_password:
        errors.append("Passwords do not match")
        logger.info("Error: passwords do not match")
    
    # Check if email exists
    if utils.get_user_by_email(db, email):
        errors.append("Email already registered. Please login.")
        logger.info(f"Error: email {email} already exists")
    
    # Check if username exists
    if utils.get_user_by_username(db, username):
        errors.append("Username already taken. Please choose another.")
        logger.info(f"Error: username {username} already exists")
    
    if errors:
        return JSONResponse(content={
            "status": "error",
            "message": ", ".join(errors),
            "login_instead": "login" in ", ".join(errors).lower(),
            "debug": True
        })
    
    # Create new user
    try:
        user = utils.create_user(db, schemas.UserCreate(
            fullname=fullname,
            email=email,
            username=username,
            password=password
        ))
        
        logger.info(f"Registration successful for {username}")
        
        # Create session token
        access_token_expires = timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
        access_token = create_access_token(
            data={"sub": user.username}, expires_delta=access_token_expires
        )
        
        return JSONResponse(content={
            "status": "success",
            "message": "Registration successful! Redirecting to login page...",
            "redirect": "login.html",
            "token": access_token,
            "debug": True
        })
    except Exception as e:
        logger.error(f"Critical error: {str(e)}")
        return JSONResponse(content={
            "status": "error",
            "message": "An error occurred during registration. Please try again later.",
            "debug_message": str(e),
            "debug": True
        })

# API endpoint to get all users (for admin panel)
@app.get("/api/users")
async def get_users(current_user: models.User = Depends(get_current_active_user), db: Session = Depends(get_db)):
    if not current_user.is_admin:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    users = utils.get_users(db)
    user_list = []
    for user in users:
        user_list.append({
            "id": user.id,
            "fullname": user.fullname,
            "email": user.email,
            "username": user.username,
            "created_at": str(user.created_at),
            "is_admin": user.is_admin
        })
    
    return user_list

# Admin panel route
@app.get("/admin_panel.php", response_class=HTMLResponse)
async def admin_panel(current_user: models.User = Depends(get_current_active_user), db: Session = Depends(get_db)):
    if not current_user.is_admin:
        return RedirectResponse(url="/login.html")
    
    # Get all users
    users = db.query(models.User).all()
    
    # Return HTML content (to be replaced with template rendering)
    return RedirectResponse(url="/admin_panel.html")

# Delete user route
@app.post("/delete_user.php")
async def delete_user(
    request: Request,
    current_user: models.User = Depends(get_current_active_user),
    db: Session = Depends(get_db)
):
    if not current_user.is_admin:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    # Get user ID from query parameters
    user_id = request.query_params.get("id")
    if not user_id:
        return JSONResponse(content={"status": "error", "message": "User ID is required"})
    
    try:
        user_id = int(user_id)
    except ValueError:
        return JSONResponse(content={"status": "error", "message": "Invalid user ID"})
    
    # Don't allow admin to delete themselves
    if user_id == current_user.id:
        return JSONResponse(content={"status": "error", "message": "Cannot delete your own admin account"})
    
    # Get user
    user = db.query(models.User).filter(models.User.id == user_id).first()
    if not user:
        return JSONResponse(content={"status": "error", "message": "User not found"})
    
    # Don't allow deletion of admin users
    if user.is_admin:
        return JSONResponse(content={"status": "error", "message": "Cannot delete admin users"})
    
    # Delete user
    db.delete(user)
    db.commit()
    
    return JSONResponse(content={"status": "success", "message": "User deleted successfully"})

# Logout route
@app.get("/logout.php")
async def logout():
    # In JWT-based auth, the client just needs to discard the token
    # No server-side session to invalidate
    return JSONResponse(content={"status": "success", "message": "Logged out successfully"})

# Serve HTML files
@app.get("/{file_path:path}")
async def serve_file(file_path: str):
    # If no file_path is provided, serve index.html
    if file_path == "" or file_path == "/":
        file_path = "index.html"
    
    # Check if the file exists in the static directory
    file_path = os.path.join("static", file_path)
    if os.path.exists(file_path) and os.path.isfile(file_path):
        # Determine content type based on file extension
        content_type = "text/html"
        if file_path.endswith(".css"):
            content_type = "text/css"
        elif file_path.endswith(".js"):
            content_type = "application/javascript"
        elif file_path.endswith((".jpg", ".jpeg")):
            content_type = "image/jpeg"
            # For images, return binary content
            return FileResponse(file_path, media_type=content_type)
        elif file_path.endswith(".png"):
            content_type = "image/png"
            # For images, return binary content
            return FileResponse(file_path, media_type=content_type)
        elif file_path.endswith(".gif"):
            content_type = "image/gif"
            # For images, return binary content
            return FileResponse(file_path, media_type=content_type)
        elif file_path.endswith(".svg"):
            content_type = "image/svg+xml"
            # For images, return binary content
            return FileResponse(file_path, media_type=content_type)
        
        # For text files, read the content
        if content_type.startswith("text/") or content_type == "application/javascript":
            with open(file_path, "r", encoding="utf-8") as f:
                content = f.read()
            return Response(content=content, media_type=content_type)
        else:
            # For other files, serve as binary
            return FileResponse(file_path, media_type=content_type)
    
    # If file not found, return 404
    raise HTTPException(status_code=404, detail="File not found")

# Health check endpoint for Render.com
@app.get("/healthz")
async def health_check():
    try:
        # Check database connection
        with SessionLocal() as db:
            db.execute("SELECT 1")
        return {"status": "healthy", "message": "API is running"}
    except Exception as e:
        logger.error(f"Health check failed: {str(e)}")
        return JSONResponse(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            content={"status": "unhealthy", "message": str(e)}
        )

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True) 