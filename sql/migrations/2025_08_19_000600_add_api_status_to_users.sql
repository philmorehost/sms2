ALTER TABLE `users`
ADD COLUMN `api_access_status` ENUM('none', 'requested', 'approved', 'denied') NOT NULL DEFAULT 'none' AFTER `api_key`;

-- Update existing users who have an API key to 'approved' status
UPDATE `users` SET `api_access_status` = 'approved' WHERE `api_key` IS NOT NULL AND `api_key` != '';
