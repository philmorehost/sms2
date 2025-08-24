-- Migration to add secret_key and sandbox mode to the vtu_apis table for VTPass compatibility

ALTER TABLE `vtu_apis`
ADD COLUMN `secret_key` VARCHAR(255) NULL AFTER `api_key`,
ADD COLUMN `is_sandbox` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`;
