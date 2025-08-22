ALTER TABLE `users`
ADD COLUMN `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_admin`,
ADD COLUMN `email_otp` VARCHAR(10) NULL DEFAULT NULL AFTER `is_email_verified`,
ADD COLUMN `otp_expires_at` DATETIME NULL DEFAULT NULL AFTER `email_otp`;
