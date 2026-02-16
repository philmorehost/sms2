-- First, add the new column with a default value
ALTER TABLE `sms_rates`
ADD COLUMN `service_type` ENUM('sms', 'voice', 'whatsapp', 'otp') NOT NULL DEFAULT 'sms' AFTER `rate`;

-- Now, update the existing 'default' rate to be explicitly for SMS
UPDATE `sms_rates` SET `service_type` = 'sms' WHERE `network_prefix` = 'default';

-- Drop the old unique key
ALTER TABLE `sms_rates` DROP INDEX `network_prefix`;

-- Add the new composite unique key
ALTER TABLE `sms_rates` ADD UNIQUE KEY `prefix_service_type` (`network_prefix`, `service_type`);
