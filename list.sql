-- Create the database
CREATE DATABASE IF NOT EXISTS portfolio_db;
USE portfolio_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin TINYINT(1) DEFAULT 0
);

-- Create an admin user (password: admin123)
INSERT INTO users (fullname, email, username, password, is_admin) 
VALUES ('Admin User', 'admin@example.com', 'admin', '$2y$10$8MmKvlRXhFYPzMjkgO7HwOcZZn4hGlxpXM7qdZR9xLbAZGpILkNAi', 1);