-- TechSecure v2.0 Migration
-- Run as: mysql -u pengelola -p techsecure_db < migration_v2.sql

USE techsecure_db;

-- Add email + reset token columns to existing users table
ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN reset_token VARCHAR(32) DEFAULT NULL;
ALTER TABLE users ADD COLUMN reset_token_expiry INT DEFAULT NULL;

-- New table: reports (used by VULN-03 BOLA download endpoint)
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed data: at least 2 users so IDOR/BOLA has something to find
-- (adjust/insert your real admin user separately if needed)
INSERT INTO users (username, password, email) VALUES
    ('budi.santoso', 'Budi12345', 'budi.santoso@techsecure.local'),
    ('siti.rahma', 'SitiRahma99', 'siti.rahma@techsecure.local')
ON DUPLICATE KEY UPDATE username=username;

-- Seed reports, one confidential-looking report per user
INSERT INTO reports (user_id, title, content) VALUES
    (1, 'Q1 Financial Summary - CONFIDENTIAL', 'Admin internal report. Revenue: Rp 2.4B. Do not share externally.'),
    (2, 'Employee Salary Report - Budi Santoso', 'Employee ID: 1001. Base salary: Rp 8.500.000. Bank account: 1234567890.'),
    (3, 'Employee Salary Report - Siti Rahma', 'Employee ID: 1002. Base salary: Rp 9.200.000. Bank account: 9876543210.');
