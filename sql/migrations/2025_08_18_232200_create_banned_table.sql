CREATE TABLE `banned` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL COMMENT 'e.g., ip, email, word, phone_number, country',
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_value` (`type`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
