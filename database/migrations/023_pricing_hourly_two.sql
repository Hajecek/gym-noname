INSERT INTO app_settings (setting_key, setting_value, is_secret)
VALUES ('pricing.hourly_two', '200', 0)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
