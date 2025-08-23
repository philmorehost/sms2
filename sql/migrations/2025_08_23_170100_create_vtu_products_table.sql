-- Migration to create the vtu_products table

CREATE TABLE `vtu_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_type` varchar(50) NOT NULL,
  `api_provider` varchar(50) NOT NULL,
  `network` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `api_product_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `user_discount_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `service_type` (`service_type`),
  KEY `network` (`network`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
