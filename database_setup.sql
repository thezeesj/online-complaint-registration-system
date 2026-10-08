-- Create Database
CREATE DATABASE IF NOT EXISTS complaint_system;
USE complaint_system;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS complaint_chat;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS users;

-- Create Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE DEFAULT NULL,
    admission_no VARCHAR(50) UNIQUE DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','counselor','faculty','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Complaints Table
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'Submitted',
    proof VARCHAR(255),
    counselor_viewed TINYINT(1) DEFAULT 0,
    user_unread TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (status)
);

-- Create Complaint Chat Table (with is_read field)
CREATE TABLE complaint_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_role ENUM('student','counselor','faculty','admin') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (complaint_id),
    INDEX (sender_id)
);

-- Create Feedback Table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    complaint_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (complaint_id)
);

-- Create test accounts (optional)
INSERT INTO users (name, email, password, role) VALUES
('Demo Student', 'student@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'student'),
('Demo Counselor', 'counselor@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'counselor'),
('Admin User', 'admin@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'admin');
-- Both test accounts have password: 'password'
