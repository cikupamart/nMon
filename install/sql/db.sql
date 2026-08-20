-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Sep 06, 2017 at 07:40 PM
-- Server version: 5.7.9
-- PHP Version: 5.6.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `nmon`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_alertlog`
--

DROP TABLE IF EXISTS `app_alertlog`;
CREATE TABLE IF NOT EXISTS `app_alertlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contactid` int(11) NOT NULL,
  `contactname` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobilenumber` varchar(255) NOT NULL,
  `pushbullet` varchar(255) NOT NULL,
  `twitter` varchar(255) NOT NULL,
  `pushover` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_checks`
--

DROP TABLE IF EXISTS `app_checks`;
CREATE TABLE IF NOT EXISTS `app_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `common` varchar(12) NOT NULL,
  `type` varchar(12) NOT NULL,
  `port` varchar(12) NOT NULL,
  `timeout` int(11) NOT NULL,
  `host` varchar(512) NOT NULL,
  `send` text NOT NULL,
  `expect` text NOT NULL,
  `status` int(1) NOT NULL,
  `geodata` TEXT NOT NULL,
  `on_map` INT(1) NOT NULL,
  `lat` VARCHAR(32) NOT NULL,
  `lng` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_checks`
--

INSERT INTO `app_checks` (`id`, `groupid`, `name`, `common`, `type`, `port`, `timeout`, `host`, `send`, `expect`, `status`) VALUES
(1, 1, 'Google Public DNS Server', 'tcp', 'tcp', '53', 5, '8.8.8.8', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `app_checks_alerts`
--

DROP TABLE IF EXISTS `app_checks_alerts`;
CREATE TABLE IF NOT EXISTS `app_checks_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `checkid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `occurrences` int(10) NOT NULL DEFAULT '1',
  `contacts` text NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `checkid` (`checkid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_checks_alerts`
--

INSERT INTO `app_checks_alerts` (`id`, `checkid`, `type`, `comparison`, `comparison_limit`, `occurrences`, `contacts`, `status`) VALUES
(1, 1, 'offline', '==', '', 2, 'a:1:{i:0;s:1:\"1\";}', 1),
(2, 1, 'responsetime', '>=', '700', 2, 'a:1:{i:0;s:1:\"1\";}', 1);

-- --------------------------------------------------------

--
-- Table structure for table `app_checks_history`
--

DROP TABLE IF EXISTS `app_checks_history`;
CREATE TABLE IF NOT EXISTS `app_checks_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `checkid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `latency` varchar(10) NOT NULL,
  `statuscode` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `checkid` (`checkid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_checks_incidents`
--

DROP TABLE IF EXISTS `app_checks_incidents`;
CREATE TABLE IF NOT EXISTS `app_checks_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `checkid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`checkid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_contacts`
--

DROP TABLE IF EXISTS `app_contacts`;
CREATE TABLE IF NOT EXISTS `app_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` INT(11) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobilenumber` varchar(255) NOT NULL,
  `pushbullet` varchar(255) NOT NULL,
  `twitter` varchar(255) NOT NULL,
  `pushover` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_dnsbls`
--

DROP TABLE IF EXISTS `app_dnsbls`;
CREATE TABLE IF NOT EXISTS `app_dnsbls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `host` (`host`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_dnsbls`
--

INSERT INTO `app_dnsbls` (`id`, `host`) VALUES
(11, 'b.barracudacentral.org'),
(7, 'bl.spamcop.net'),
(12, 'cbl.abuseat.org'),
(1, 'dnsbl-1.uceprotect.net'),
(2, 'dnsbl-2.uceprotect.net'),
(3, 'dnsbl-3.uceprotect.net'),
(4, 'dnsbl.dronebl.org'),
(5, 'dnsbl.sorbs.net'),
(10, 'pbl.spamhaus.org'),
(8, 'sbl.spamhaus.org'),
(13, 'spam.abuse.ch'),
(14, 'spam.spamrats.com'),
(9, 'xbl.spamhaus.org'),
(6, 'zen.spamhaus.org');

-- --------------------------------------------------------

--
-- Table structure for table `app_groups`
--

DROP TABLE IF EXISTS `app_groups`;
CREATE TABLE IF NOT EXISTS `app_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_groups`
--

INSERT INTO `app_groups` (`id`, `name`) VALUES
(1, 'Main Group');

-- --------------------------------------------------------

--
-- Table structure for table `app_pages`
--

DROP TABLE IF EXISTS `app_pages`;
CREATE TABLE IF NOT EXISTS `app_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` INT(11) NOT NULL DEFAULT '0',
  `pagekey` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `info` text NOT NULL,
  `servers` text NOT NULL,
  `websites` text NOT NULL,
  `checks` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_servers`
--

DROP TABLE IF EXISTS `app_servers`;
CREATE TABLE IF NOT EXISTS `app_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `type` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `serverkey` varchar(64) NOT NULL,
  `status` int(1) NOT NULL,
  `geodata` text NOT NULL,
  `on_map` INT(1) NOT NULL,
  `lat` VARCHAR(32) NOT NULL,
  `lng` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serverkey` (`serverkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_servers_alerts`
--

DROP TABLE IF EXISTS `app_servers_alerts`;
CREATE TABLE IF NOT EXISTS `app_servers_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `occurrences` int(10) NOT NULL,
  `contacts` text NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_servers_history`
--

DROP TABLE IF EXISTS `app_servers_history`;
CREATE TABLE IF NOT EXISTS `app_servers_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `app_servers_incidents`
--

DROP TABLE IF EXISTS `app_servers_incidents`;
CREATE TABLE IF NOT EXISTS `app_servers_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_websites`
--

DROP TABLE IF EXISTS `app_websites`;
CREATE TABLE IF NOT EXISTS `app_websites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `expect` text NOT NULL,
  `timeout` int(11) NOT NULL,
  `status` int(1) NOT NULL,
  `geodata` TEXT NOT NULL,
  `on_map` INT(1) NOT NULL,
  `lat` VARCHAR(32) NOT NULL,
  `lng` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_websites`
--

INSERT INTO `app_websites` (`id`, `groupid`, `name`, `url`, `expect`, `timeout`, `status`) VALUES
(1, 1, 'Google', 'https://www.google.com', '', 0, 0),
(2, 1, 'CodeCanyon', 'https://codecanyon.net/', '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `app_websites_alerts`
--

DROP TABLE IF EXISTS `app_websites_alerts`;
CREATE TABLE IF NOT EXISTS `app_websites_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `websiteid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `occurrences` int(10) NOT NULL,
  `contacts` text NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `websiteid` (`websiteid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_websites_alerts`
--

INSERT INTO `app_websites_alerts` (`id`, `websiteid`, `type`, `comparison`, `comparison_limit`, `occurrences`, `contacts`, `status`) VALUES
(1, 0, 'responsecode', '!=', '200', 2, 'a:1:{i:0;s:1:\"1\";}', 1),
(2, 0, 'loadtime', '>=', '5', 2, 'a:1:{i:0;s:1:\"1\";}', 1),
(3, 1, 'responsecode', '!=', '200', 2, 'a:1:{i:0;s:1:\"1\";}', 1),
(4, 1, 'loadtime', '>=', '5', 2, 'a:1:{i:0;s:1:\"1\";}', 1),
(5, 2, 'responsecode', '!=', '200', 2, 'a:1:{i:0;s:1:\"1\";}', 1),
(6, 2, 'loadtime', '>=', '5', 2, 'a:1:{i:0;s:1:\"1\";}', 1);

-- --------------------------------------------------------

--
-- Table structure for table `app_websites_history`
--

DROP TABLE IF EXISTS `app_websites_history`;
CREATE TABLE IF NOT EXISTS `app_websites_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `websiteid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `latency` varchar(10) NOT NULL,
  `statuscode` varchar(10) NOT NULL,
  `has_expected` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `websiteid` (`websiteid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_websites_incidents`
--

DROP TABLE IF EXISTS `app_websites_incidents`;
CREATE TABLE IF NOT EXISTS `app_websites_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `websiteid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`websiteid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_activitylog`
--

DROP TABLE IF EXISTS `core_activitylog`;
CREATE TABLE IF NOT EXISTS `core_activitylog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `ipaddress` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_config`
--

DROP TABLE IF EXISTS `core_config`;
CREATE TABLE IF NOT EXISTS `core_config` (
  `name` varchar(128) NOT NULL,
  `value` varchar(512) NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `core_config`
--

INSERT INTO `core_config` (`name`, `value`) VALUES
('app_name', '<b>n</b>Mon'),
('app_url', ''),
('check_timeout', '5'),
('company_details', ''),
('company_name', 'nMon Company'),
('date_format', 'Y-m-d;yyyy-mm-dd'),
('db_version', '2.0'),
('default_contacts', 'a:1:{i:0;s:1:\"1\";}'),
('default_lang', 'en'),
('email_from_address', 'nmon@example.com'),
('email_from_name', 'nMon'),
('email_smtp_auth', 'false'),
('email_smtp_domain', ''),
('email_smtp_enable', 'false'),
('email_smtp_host', ''),
('email_smtp_password', ''),
('email_smtp_port', ''),
('email_smtp_security', ''),
('email_smtp_username', ''),
('history_retention', '90'),
('log_retention', '90'),
('sms_api_id', ''),
('sms_from', ''),
('sms_password', ''),
('sms_provider', 'clickatell'),
('sms_user', ''),
('table_records', '50'),
('timezone', 'UTC'),
('twitter_apikey', ''),
('twitter_apisecret', ''),
('twitter_token', ''),
('twitter_tokensecret', ''),
('website_timeout', '100'),
('week_start', '1'),
('pushover_apitoken', ''),
('xss_filtering', 'true'),
('google_maps_api_key', ''),
('telegram_bot_token', ''),
('telegram_chat_id', '');

-- --------------------------------------------------------

--
-- Table structure for table `core_cronlog`
--

DROP TABLE IF EXISTS `core_cronlog`;
CREATE TABLE IF NOT EXISTS `core_cronlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `data` text NOT NULL,
  `execution_time` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_emaillog`
--

DROP TABLE IF EXISTS `core_emaillog`;
CREATE TABLE IF NOT EXISTS `core_emaillog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `to` varchar(128) NOT NULL,
  `subject` varchar(256) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_files`
--

DROP TABLE IF EXISTS `core_files`;
CREATE TABLE IF NOT EXISTS `core_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipmentid` int(11) NOT NULL,
  `vehicleid` int(11) NOT NULL,
  `staffid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_languages`
--

DROP TABLE IF EXISTS `core_languages`;
CREATE TABLE IF NOT EXISTS `core_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `core_languages`
--

INSERT INTO `core_languages` (`id`, `code`, `name`) VALUES
(1, 'en', 'English (System)');

-- --------------------------------------------------------

--
-- Table structure for table `core_notifications`
--

DROP TABLE IF EXISTS `core_notifications`;
CREATE TABLE IF NOT EXISTS `core_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `info` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `core_notifications`
--

INSERT INTO `core_notifications` (`id`, `name`, `subject`, `message`, `info`) VALUES
(1, 'New User', 'New User', '<p>Hello {contact},<br><br>Your account has been successfully created.</p><p><br>Email Address: {email}<br>Password: {password}<br><br><br>Best regards,<br>{company}<br></p>', ''),
(2, 'Password Reset', 'Password Reset', '<p>Hello {contact},<br><br>Please follow the link below to reset your password.<br>{resetlink}<br><br>Best regards,<br>{company}<br></p>', ''),
(3, 'nMon Incident Alert', '{subject}', '<p>Hello {contact},</p><p><b>{message}</b></p><p><br>Best regards,<br>{company}<br></p>', '');

-- --------------------------------------------------------

--
-- Table structure for table `core_roles`
--

DROP TABLE IF EXISTS `core_roles`;
CREATE TABLE IF NOT EXISTS `core_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `perms` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `core_roles`
--

INSERT INTO `core_roles` (`id`, `name`, `perms`) VALUES
(1, 'Super Administrator', 'a:40:{i:0;s:9:\"addServer\";i:1;s:10:\"editServer\";i:2;s:12:\"deleteServer\";i:3;s:11:\"viewServers\";i:4;s:10:\"addWebsite\";i:5;s:11:\"editWebsite\";i:6;s:13:\"deleteWebsite\";i:7;s:12:\"viewWebsites\";i:8;s:8:\"addCheck\";i:9;s:9:\"editCheck\";i:10;s:11:\"deleteCheck\";i:11;s:10:\"viewChecks\";i:12;s:10:\"addContact\";i:13;s:11:\"editContact\";i:14;s:13:\"deleteContact\";i:15;s:12:\"viewContacts\";i:16;s:8:\"addGroup\";i:17;s:9:\"editGroup\";i:18;s:11:\"deleteGroup\";i:19;s:10:\"viewGroups\";i:20;s:7:\"addPage\";i:21;s:8:\"editPage\";i:22;s:10:\"deletePage\";i:23;s:9:\"viewPages\";i:24;s:7:\"addUser\";i:25;s:8:\"editUser\";i:26;s:10:\"deleteUser\";i:27;s:9:\"viewUsers\";i:28;s:7:\"addRole\";i:29;s:8:\"editRole\";i:30;s:10:\"deleteRole\";i:31;s:9:\"viewRoles\";i:32;s:14:\"manageSettings\";i:33;s:8:\"viewLogs\";i:34;s:13:\"viewAlertLogs\";i:35;s:10:\"viewSystem\";i:36;s:6:\"search\";i:37;s:4:\"Null\";i:38;s:13:\"remoteControl\";i:39;s:9:\"viewMaps\";}'),
(2, 'Operator', 'a:20:{i:0;s:9:\"addServer\";i:1;s:11:\"viewServers\";i:2;s:10:\"addWebsite\";i:3;s:12:\"viewWebsites\";i:4;s:8:\"addCheck\";i:5;s:10:\"viewChecks\";i:6;s:10:\"addContact\";i:7;s:12:\"viewContacts\";i:8;s:8:\"addGroup\";i:9;s:10:\"viewGroups\";i:10;s:7:\"addPage\";i:11;s:9:\"viewPages\";i:12;s:7:\"addUser\";i:13;s:9:\"viewUsers\";i:14;s:9:\"viewRoles\";i:15;s:8:\"viewLogs\";i:16;s:13:\"viewAlertLogs\";i:17;s:10:\"viewSystem\";i:18;s:6:\"search\";i:19;s:4:\"Null\";}');

-- --------------------------------------------------------

--
-- Table structure for table `core_ratelimit`
--

DROP TABLE IF EXISTS `core_ratelimit`;
CREATE TABLE IF NOT EXISTS `core_ratelimit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key_timestamp` (`key`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_smslog`
--

DROP TABLE IF EXISTS `core_smslog`;
CREATE TABLE IF NOT EXISTS `core_smslog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to` varchar(128) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `core_statuses`
--

DROP TABLE IF EXISTS `core_statuses`;
CREATE TABLE IF NOT EXISTS `core_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `message` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `core_statuses`
--

INSERT INTO `core_statuses` (`id`, `code`, `type`, `message`) VALUES
(45, 10, 'success', 'Item has been added successfully!'),
(46, 20, 'success', 'Item has been saved successfully!'),
(47, 30, 'success', 'Item has been deleted successfully!'),
(48, 11, 'danger', 'Error! Cannot add item.'),
(49, 21, 'danger', 'Error! Cannot save item.'),
(50, 31, 'danger', 'Error! Cannot delete item.'),
(51, 40, 'success', 'Settings updated successfully!'),
(52, 1200, 'danger', 'Authentication Failed!'),
(53, 1300, 'success', 'Please check your email for a password reset link.'),
(54, 1400, 'danger', 'Email address was not found.'),
(55, 1500, 'danger', 'Invalid reset key!'),
(56, 1600, 'success', 'Success. Please log in with your new password! '),
(57, 1, 'danger', 'Unauthorized Access'),
(58, 50, 'warning', 'Disabled in demo mode!');

-- --------------------------------------------------------

--
-- Table structure for table `core_users`
--

DROP TABLE IF EXISTS `core_users`;
CREATE TABLE IF NOT EXISTS `core_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleid` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `groups` text NOT NULL,
  `theme` varchar(64) NOT NULL,
  `sidebar` varchar(64) NOT NULL,
  `layout` varchar(64) NOT NULL,
  `notes` text NOT NULL,
  `sessionid` varchar(255) NOT NULL,
  `resetkey` varchar(255) NOT NULL,
  `lang` varchar(2) NOT NULL,
  `avatar` mediumblob NOT NULL,
  `autorefresh` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `app_remote_commands`
--

DROP TABLE IF EXISTS `app_remote_commands`;
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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
