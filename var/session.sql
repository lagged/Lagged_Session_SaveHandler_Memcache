CREATE TABLE IF NOT EXISTS `session2` (
`session_id` varchar(32) NOT NULL DEFAULT '',
`session_data` text NOT NULL,
`user_id` int(10) DEFAULT NULL,
`rec_dateadd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`rec_datemod` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
PRIMARY KEY (`session_id`),
KEY `gc` (`rec_datemod`),
KEY `user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
