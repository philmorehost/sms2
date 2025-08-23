ALTER TABLE `password_resets` ADD `otp_code` VARCHAR(255) NOT NULL AFTER `email`;
