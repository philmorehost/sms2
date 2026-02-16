CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'info' COMMENT 'info, success, warning, danger',
  `placement` varchar(255) NOT NULL DEFAULT 'all' COMMENT 'Page to display on, e.g., all, dashboard.php, send-sms.php. Comma-separated for multiple pages.',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
