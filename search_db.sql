# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.24)
# Database: search
# Generation Time: 2020-03-02 20:06:18 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table dictionaries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `dictionaries`;

CREATE TABLE `dictionaries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `publisher` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `dictionaries` WRITE;
/*!40000 ALTER TABLE `dictionaries` DISABLE KEYS */;

INSERT INTO `dictionaries` (`id`, `name`, `publisher`)
VALUES
	(1,'Dictcc DA ↔ EN','Dictcc'),
	(2,'Dictcc DA ↔ DE','Dictcc'),
	(3,'Dictcc EN ↔ DE','Dictcc'),
	(4,'Apollo DA ↔ EN','Apollo');

/*!40000 ALTER TABLE `dictionaries` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table directions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `directions`;

CREATE TABLE `directions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dictionary_id` int(11) NOT NULL,
  `name` varchar(11) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `directions` WRITE;
/*!40000 ALTER TABLE `directions` DISABLE KEYS */;

INSERT INTO `directions` (`id`, `dictionary_id`, `name`)
VALUES
	(1,1,'DA → EN'),
	(2,1,'EN → DA'),
	(3,2,'DA → DE'),
	(4,2,'DE → DA'),
	(5,3,'EN → DE'),
	(6,3,'DE → EN'),
	(7,4,'DA → EN'),
	(8,5,'EN → DA');

/*!40000 ALTER TABLE `directions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table document_index
# ------------------------------------------------------------

DROP TABLE IF EXISTS `document_index`;

CREATE TABLE `document_index` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` int(11) unsigned NOT NULL,
  `term_id` int(11) unsigned NOT NULL,
  `position` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_document_term_position` (`document_id`,`term_id`,`position`),
  KEY `idx_term_position` (`term_id`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table documents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `documents`;

CREATE TABLE `documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `direction_id` int(11) NOT NULL,
  `headword` varchar(255) NOT NULL DEFAULT '',
  `translation` varchar(255) NOT NULL DEFAULT '',
  `wordclass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry` (`direction_id`,`headword`,`translation`,`wordclass`),
  KEY `idx_direction_headword` (`direction_id`,`headword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table entries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `entries`;

CREATE TABLE `entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `direction_id` int(11) NOT NULL,
  `raw_entry_id` int(11) NOT NULL,
  `lemma_id` int(11) DEFAULT NULL,
  `lemma_ref` varchar(32) DEFAULT NULL,
  `headword` varchar(255) NOT NULL DEFAULT '',
  `wordclass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry` (`direction_id`,`headword`,`wordclass`),
  KEY `idx_direction_headword` (`direction_id`,`headword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table inflections
# ------------------------------------------------------------

DROP TABLE IF EXISTS `inflections`;

CREATE TABLE `inflections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lemma_id` int(11) DEFAULT NULL,
  `form` varchar(255) NOT NULL DEFAULT '' COMMENT 'Which inflected form is it',
  `word` varchar(255) NOT NULL DEFAULT '' COMMENT 'The inflected word',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table info
# ------------------------------------------------------------

DROP TABLE IF EXISTS `info`;

CREATE TABLE `info` (
  `key` varchar(255) NOT NULL,
  `value` int(11) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table raw_entries
# ------------------------------------------------------------

DROP TABLE IF EXISTS `raw_entries`;

CREATE TABLE `raw_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` varchar(32) NOT NULL,
  `book` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `lemma_references` varchar(1024) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `entry_id_book` (`entry_id`,`book`) USING BTREE,
  KEY `book` (`book`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table raw_lemmas
# ------------------------------------------------------------

DROP TABLE IF EXISTS `raw_lemmas`;

CREATE TABLE `raw_lemmas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lemma_id` varchar(32) NOT NULL,
  `lang` varchar(3) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `lemma_id_lang` (`lemma_id`,`lang`),
  KEY `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table term_index
# ------------------------------------------------------------

DROP TABLE IF EXISTS `term_index`;

CREATE TABLE `term_index` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(255) NOT NULL,
  `num_hits` int(11) NOT NULL,
  `num_docs` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_term` (`term`),
  KEY `idx_term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table test_documents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `test_documents`;

CREATE TABLE `test_documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `direction_id` int(11) NOT NULL,
  `headword` varchar(255) NOT NULL DEFAULT '',
  `translation` varchar(255) NOT NULL DEFAULT '',
  `wordclass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
