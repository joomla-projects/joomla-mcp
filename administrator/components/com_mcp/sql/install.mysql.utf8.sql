--
-- Table structure for table `#__mcp`
--

CREATE TABLE IF NOT EXISTS `#__mcp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `user_id` int NOT NULL DEFAULT 0,
  `user_name` varchar(255) NOT NULL DEFAULT '',
  `user_token` varchar(255) NOT NULL DEFAULT '',
  `capabilities` varchar(5120) NOT NULL DEFAULT '',
  `additional_json` varchar(5120) NOT NULL DEFAULT '',
  `state` tinyint NOT NULL DEFAULT 0,
  `catid` int unsigned NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `ordering` int NOT NULL DEFAULT 0,
  `metakey` text NOT NULL,
  `params` text NOT NULL,
  `checked_out` int unsigned NOT NULL DEFAULT 0,
  `checked_out_time` datetime,
  `publish_up` datetime,
  `publish_down` datetime,
  `reset` datetime,
  `created` datetime NOT NULL,
  `created_by` int unsigned NOT NULL DEFAULT 0,
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `modified` datetime NOT NULL,
  `modified_by` int unsigned NOT NULL DEFAULT 0,
  `version` int unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_state` (`state`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

