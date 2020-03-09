DROP TABLE IF EXISTS `dictionaries`;
CREATE TABLE `dictionaries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `publisher` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `directions`;
CREATE TABLE `directions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dictionary_id` int(11) NOT NULL,
  `name` varchar(11) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `entries`;
CREATE TABLE `entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `direction_id` int(11) NOT NULL,
  `raw_entry_id` int(11) NOT NULL,
  `lemma_id` int(11) DEFAULT NULL,
  `lemma_ref` varchar(32) DEFAULT NULL,
  `headword` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `unique_entry` (`direction_id`,`lemma_id`,`headword`),
  KEY `idx_entry` (`direction_id`,`headword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `lemma_inflections`;
CREATE TABLE `lemma_inflections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `raw_lemma_id` int(11) NOT NULL,
  `lemma_id` int(11) DEFAULT NULL,
  `word` varchar(255) NOT NULL COMMENT 'The inflected word',
  `form` varchar(255) NOT NULL DEFAULT '' COMMENT 'Which inflected form is it',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inflection` (`raw_lemma_id`,`form`),
  KEY `idx_lemma_id` (`lemma_id`),
  KEY `idx_raw_lemma_id` (`raw_lemma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `raw_entries`;
CREATE TABLE `raw_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` varchar(32) NOT NULL,
  `book` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `lemma_references` varchar(1024) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `status` enum('new','updated','rendered','pending_delete','deleted') NOT NULL DEFAULT 'new',
  `solr_status` enum('new','updated','rendered','pending_delete','deleted') NOT NULL DEFAULT 'new',
  `reimporting` tinyint(1) NOT NULL DEFAULT '0',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `entry_id_book` (`entry_id`,`book`) USING BTREE,
  KEY `status` (`status`),
  KEY `book` (`book`),
  KEY `solr_status` (`solr_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `raw_lemmas`;
CREATE TABLE `raw_lemmas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lemma_id` varchar(32) NOT NULL,
  `lang` varchar(3) NOT NULL,
  `data` text NOT NULL,
  `status` enum('new','updated','rendered','pending_delete','deleted') NOT NULL DEFAULT 'new',
  `solr_status` enum('new','updated','rendered','pending_delete','deleted') NOT NULL DEFAULT 'new',
  `reimporting` tinyint(1) NOT NULL DEFAULT '0',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `lemma_id_lang` (`lemma_id`,`lang`),
  KEY `status` (`status`),
  KEY `solr_status` (`solr_status`),
  KEY `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



LOCK TABLES `dictionaries` WRITE;
INSERT INTO `dictionaries` (`id`, `name`, `publisher`)
VALUES
	(1,'Dictcc DA ↔ EN','Dictcc'),
	(2,'Dictcc DA ↔ DE','Dictcc'),
	(3,'Dictcc EN ↔ DE','Dictcc'),
	(4,'Apollo DA ↔ EN','Apollo');
UNLOCK TABLES;



LOCK TABLES `directions` WRITE;
INSERT INTO `directions` (`id`, `dictionary_id`, `name`)
VALUES
	(1,1,'DA → EN'),
	(2,1,'EN → DA'),
	(3,2,'DA → DE'),
	(4,2,'DE → DA'),
	(5,3,'EN → DE'),
	(6,3,'DE → EN'),
	(7,4,'DA → EN'),
	(8,4,'EN → DA');
UNLOCK TABLES;
