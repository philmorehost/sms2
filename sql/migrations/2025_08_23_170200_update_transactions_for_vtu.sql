-- Migration to add VTU-related columns to the transactions table

ALTER TABLE `transactions`
ADD COLUMN `vtu_service_type` VARCHAR(50) NULL COMMENT 'e.g., airtime, data, cable_tv' AFTER `type`,
ADD COLUMN `vtu_recipient` VARCHAR(255) NULL COMMENT 'e.g., phone number, smartcard number' AFTER `description`,
ADD COLUMN `vtu_is_refunded` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;
