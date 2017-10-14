CREATE TABLE `session` (
  `token` char(64) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;