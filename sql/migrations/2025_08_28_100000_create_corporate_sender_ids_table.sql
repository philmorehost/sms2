CREATE TABLE `corporate_sender_ids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_id` varchar(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `cac_certificate_path` varchar(255) NOT NULL,
  `mtn_letter_path` varchar(255) NOT NULL,
  `glo_letter_path` varchar(255) NOT NULL,
  `airtel_letter_path` varchar(255) NOT NULL,
  `nine_mobile_letter_path` varchar(255) NOT NULL,
  `cbn_license_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `corporate_sender_ids_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
