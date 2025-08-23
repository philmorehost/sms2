-- Migration to create the base tables for the VTU service feature

-- Table to store API provider credentials
CREATE TABLE `vtu_apis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(50) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_name` (`provider_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to define the available VTU services and their settings
CREATE TABLE `vtu_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `service_slug` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `default_api_provider_id` int(11) DEFAULT NULL,
  `transaction_limit_count` int(11) DEFAULT 10,
  `transaction_limit_period_hours` int(11) DEFAULT 24,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_slug` (`service_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert the default services
INSERT INTO `vtu_services` (`service_name`, `service_slug`) VALUES
('Airtime', 'airtime'),
('Data', 'data'),
('Electricity', 'electricity'),
('Cable TV', 'cable_tv'),
('Betting', 'betting'),
('Exam PIN', 'exam_pin'),
('Recharge Card Printing', 'recharge_card'),
('Data Card Printing', 'data_card');
