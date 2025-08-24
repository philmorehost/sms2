-- Migration to add a status column to the users table for account suspension

ALTER TABLE `users`
ADD COLUMN `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active' AFTER `is_email_verified`;
