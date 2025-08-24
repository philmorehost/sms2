-- Seeding HDKDATA Data Plans into the vtu_products table
-- This script is idempotent. It will insert new plans or update existing ones based on the product_code.
-- NOTE: The 'product_code' and 'price' values are placeholders and must be updated
-- once the official HDKDATA API documentation is available.

-- Get the service_id for 'Mobile Data' and store it in a variable to avoid repeated lookups.
SET @data_service_id = (SELECT id FROM vtu_services WHERE service_name = 'Mobile Data' LIMIT 1);

-- Insert the data plans, using the variable for service_id.
INSERT INTO `vtu_products` (`service_id`, `product_name`, `product_code`, `price`, `network`, `provider`, `plan_type`, `status`) VALUES
(@data_service_id, 'MTN 500MB (SME)', 'hdk_mtn_sme_500mb', 150.00, 'MTN', 'HDKDATA', 'SME', 'active'),
(@data_service_id, 'MTN 1GB (SME)', 'hdk_mtn_sme_1gb', 250.00, 'MTN', 'HDKDATA', 'SME', 'active'),
(@data_service_id, 'MTN 2GB (SME)', 'hdk_mtn_sme_2gb', 500.00, 'MTN', 'HDKDATA', 'SME', 'active'),
(@data_service_id, 'MTN 5GB (SME)', 'hdk_mtn_sme_5gb', 1250.00, 'MTN', 'HDKDATA', 'SME', 'active'),
(@data_service_id, 'MTN 1GB (Gifting)', 'hdk_mtn_gifting_1gb', 300.00, 'MTN', 'HDKDATA', 'Gifting', 'active'),
(@data_service_id, 'MTN 2GB (Gifting)', 'hdk_mtn_gifting_2gb', 600.00, 'MTN', 'HDKDATA', 'Gifting', 'active'),
(@data_service_id, 'GLO 1GB (SME)', 'hdk_glo_sme_1gb', 280.00, 'GLO', 'HDKDATA', 'SME', 'active'),
(@data_service_id, 'GLO 2GB (SME)', 'hdk_glo_sme_2gb', 560.00, 'GLO', 'HDKDATA', 'SME', 'active'),
(@data_service_id, 'Airtel 1GB (Corporate Gifting)', 'hdk_airtel_cg_1gb', 290.00, 'Airtel', 'HDKDATA', 'Corporate Gifting', 'active'),
(@data_service_id, 'Airtel 2GB (Corporate Gifting)', 'hdk_airtel_cg_2gb', 580.00, 'Airtel', 'HDKDATA', 'Corporate Gifting', 'active'),
(@data_service_id, '9mobile 1GB (Gifting)', 'hdk_9mobile_gifting_1gb', 450.00, '9mobile', 'HDKDATA', 'Gifting', 'active'),
(@data_service_id, '9mobile 2GB (Gifting)', 'hdk_9mobile_gifting_2gb', 900.00, '9mobile', 'HDKDATA', 'Gifting', 'active')
ON DUPLICATE KEY UPDATE
`price` = VALUES(`price`),
`product_name` = VALUES(`product_name`),
`network` = VALUES(`network`),
`plan_type` = VALUES(`plan_type`),
`status` = 'active';
