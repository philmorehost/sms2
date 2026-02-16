CREATE TABLE IF NOT EXISTS `sms_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(100) NOT NULL,
  `country_code` varchar(10) NOT NULL,
  `network_name` varchar(100) DEFAULT NULL,
  `network_prefix` varchar(10) NOT NULL,
  `rate` decimal(10,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `network_prefix` (`network_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert a default rate
INSERT IGNORE INTO `sms_rates` (`country_name`, `country_code`, `network_name`, `network_prefix`, `rate`) VALUES
('Default', 'ALL', 'All Networks', 'default', 0.0200);
