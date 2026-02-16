-- Add the PWA enabled setting, defaulting to 1 (true)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('pwa_enabled', '1')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`; -- Do nothing if it already exists