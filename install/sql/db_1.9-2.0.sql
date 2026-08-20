-- DATABASE UPGRADE FROM 1.9 to 2.0

-- Remote Commands table for remote control feature
CREATE TABLE IF NOT EXISTS `app_remote_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `command` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `output` longtext,
  `exit_code` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Add Telegram settings to config
INSERT INTO `core_config` (`name`, `value`) VALUES
('telegram_bot_token', ''),
('telegram_chat_id', '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
