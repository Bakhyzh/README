# Portfolio Website with FastAPI Backend

This project is a personal portfolio website with a FastAPI backend that handles user authentication and data management.

## Features

- User registration and login
- Admin panel for user management
- JWT-based authentication
- MySQL database support with SQLite fallback
- Static file serving for HTML, CSS, and images

## Prerequisites

- Python 3.7+
- MySQL (optional, will fall back to SQLite if not available)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd <project-directory>
```

2. Create a virtual environment and activate it:
```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

3. Install the required packages:
```bash
pip install -r requirements.txt
```

4. Configure the database:
- The application will use MySQL by default with the following credentials:
  - Host: localhost
  - User: root
  - Password: (empty)
  - Database: portfolio_db
- If MySQL is not available, it will fall back to SQLite automatically

5. Create environment variables (optional):
Create a `.env` file in the project root with the following variables:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=portfolio_db
SECRET_KEY=your-secret-key-change-this-in-production
DEBUG=true
PORT=8000
```

## Running the Application

1. Start the server:
```bash
uvicorn main:app --reload
```

2. Access the application:
- Web interface: http://localhost:8000
- API documentation: http://localhost:8000/docs

## Deployment on Render.com

1. Create a new Web Service on Render.
2. Link your repository.
3. Configure the service:
   - Build Command: `pip install -r requirements.txt`
   - Start Command: `uvicorn main:app --host 0.0.0.0 --port $PORT`
4. Add environment variables:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` for the database
   - `SECRET_KEY` for JWT token generation
   - `DEBUG=false` for production

## File Structure

- `main.py` - The main FastAPI application
- `database.py` - Database connection setup
- `models.py` - SQLAlchemy models
- `schemas.py` - Pydantic schemas for validation
- `utils.py` - Utility functions
- `config.py` - Configuration settings
- `requirements.txt` - Required Python packages
- `static/` - Static files (HTML, CSS, images)

## API Endpoints

- `/check_db` - Check database status
- `/debug_login.php` - Login endpoint (compatible with old PHP API)
- `/debug_register.php` - Registration endpoint (compatible with old PHP API)
- `/admin_panel.php` - Admin panel
- `/delete_user.php` - Delete user endpoint
- `/logout.php` - Logout endpoint
- `/{file_path}` - Serve static files

## Authentication

The application uses JWT tokens for authentication. After login, the token should be included in the Authorization header for protected endpoints:

```
Authorization: Bearer <token>
```

## License

[Your License] 