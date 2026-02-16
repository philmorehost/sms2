ALTER TABLE `phonebook_contacts`
ADD COLUMN `birthday` DATE DEFAULT NULL AFTER `last_name`;

CREATE TABLE IF NOT EXISTS `birthday_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_id` varchar(11) NOT NULL,
  `message_template` text NOT NULL,
  `send_time` time NOT NULL DEFAULT '09:00:00',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
