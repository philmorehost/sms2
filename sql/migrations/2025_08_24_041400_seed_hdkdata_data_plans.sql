-- Seeding HDKDATA Data Plans into the vtu_products table
-- This script is idempotent. It will insert new plans or update existing ones based on the api_product_id.
-- NOTE: The 'api_product_id' and 'amount' values are placeholders and must be updated
-- once the official HDKDATA API documentation is available.

INSERT INTO `vtu_products` (`service_type`, `provider`, `network`, `plan_type`, `name`, `api_product_id`, `amount`, `is_active`) VALUES
('data', 'HDKDATA', 'MTN', 'SME', 'MTN 500MB (SME)', 'hdk_mtn_sme_500mb', 150.00, 1),
('data', 'HDKDATA', 'MTN', 'SME', 'MTN 1GB (SME)', 'hdk_mtn_sme_1gb', 250.00, 1),
('data', 'HDKDATA', 'MTN', 'SME', 'MTN 2GB (SME)', 'hdk_mtn_sme_2gb', 500.00, 1),
('data', 'HDKDATA', 'MTN', 'SME', 'MTN 5GB (SME)', 'hdk_mtn_sme_5gb', 1250.00, 1),
('data', 'HDKDATA', 'MTN', 'Gifting', 'MTN 1GB (Gifting)', 'hdk_mtn_gifting_1gb', 300.00, 1),
('data', 'HDKDATA', 'MTN', 'Gifting', 'MTN 2GB (Gifting)', 'hdk_mtn_gifting_2gb', 600.00, 1),
('data', 'HDKDATA', 'GLO', 'SME', 'GLO 1GB (SME)', 'hdk_glo_sme_1gb', 280.00, 1),
('data', 'HDKDATA', 'GLO', 'SME', 'GLO 2GB (SME)', 'hdk_glo_sme_2gb', 560.00, 1),
('data', 'HDKDATA', 'Airtel', 'Corporate Gifting', 'Airtel 1GB (Corporate Gifting)', 'hdk_airtel_cg_1gb', 290.00, 1),
('data', 'HDKDATA', 'Airtel', 'Corporate Gifting', 'Airtel 2GB (Corporate Gifting)', 'hdk_airtel_cg_2gb', 580.00, 1),
('data', 'HDKDATA', '9mobile', 'Gifting', '9mobile 1GB (Gifting)', 'hdk_9mobile_gifting_1gb', 450.00, 1),
('data', 'HDKDATA', '9mobile', 'Gifting', '9mobile 2GB (Gifting)', 'hdk_9mobile_gifting_2gb', 900.00, 1)
ON DUPLICATE KEY UPDATE
`amount` = VALUES(`amount`),
`name` = VALUES(`name`),
`network` = VALUES(`network`),
`plan_type` = VALUES(`plan_type`),
`is_active` = 1;
