INSERT INTO settings (setting_key, setting_value) VALUES
('sms_chars_1unit', '160'),
('sms_chars_multunit', '153'),
('sms_max_units', '10')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
