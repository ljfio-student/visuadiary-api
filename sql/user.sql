CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL,
  `password` char(60) NOT NULL,
  `aws_face_id` varchar(128) DEFAULT '',
  `aws_s3_key` varchar(128) DEFAULT '',
  `aws_collection_id` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;