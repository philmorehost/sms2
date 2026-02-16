ALTER TABLE `users`
ADD COLUMN `last_login` DATETIME DEFAULT NULL AFTER `referral_balance`;
