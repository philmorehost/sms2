-- Migration to add plan_type to the vtu_products table

ALTER TABLE `vtu_products`
ADD COLUMN `plan_type` VARCHAR(50) NULL COMMENT 'e.g., SME, Gifting, Corporate' AFTER `network`;
