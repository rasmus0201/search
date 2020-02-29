# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.28)
# Database: search
# Generation Time: 2020-02-28 11:39:01 +0000
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
	(1,'Dictcc DA <-> EN','Dictcc'),
	(2,'Dictcc DA <-> DE','Dictcc'),
	(3,'Dictcc EN <-> DE','Dictcc');

/*!40000 ALTER TABLE `dictionaries` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table directions
# ------------------------------------------------------------

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
	(1,1,'DA -> EN'),
	(2,1,'EN -> DA'),
	(3,2,'DA -> DE'),
	(4,2,'DE -> DA'),
	(5,3,'EN -> DE'),
	(6,3,'DE -> EN');

/*!40000 ALTER TABLE `directions` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table entries
# ------------------------------------------------------------

CREATE TABLE `entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `direction_id` int(11) NOT NULL,
  `headword` varchar(255) NOT NULL DEFAULT '',
  `translation` varchar(255) NOT NULL DEFAULT '',
  `wordclass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry` (`direction_id`,`headword`,`translation`,`wordclass`),
  KEY `idx_direction_headword` (`direction_id`,`headword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table inflections
# ------------------------------------------------------------

CREATE TABLE `inflections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lemma_id` int(11) DEFAULT NULL,
  `form` varchar(255) NOT NULL DEFAULT '' COMMENT 'Which inflected form is it',
  `word` varchar(255) NOT NULL DEFAULT '' COMMENT 'The inflected word',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table lemmas
# ------------------------------------------------------------

CREATE TABLE `lemmas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ortography` varchar(255) NOT NULL DEFAULT '',
  `word_class` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table search_index
# ------------------------------------------------------------

-- CREATE TABLE `search_index` (
--   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
--   `entry_id` int(11) NOT NULL,
--   `term` varchar(255) NOT NULL DEFAULT '',
--   `position` mediumint(9) NOT NULL,
--   PRIMARY KEY (`id`),
--   KEY `idx_term` (`term`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS term_index (
    `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
    `term` VARCHAR(255),
    `num_hits` INT(11),
    `num_docs` INT(11),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_term` (`term`),
    KEY `idx_term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS document_index (
    `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
    `document_id` INT(11) unsigned,
    `term_id` INT(11) unsigned,
    `position` INT(11) unsigned,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_document_term_position` (`document_id`, `term_id`, `position`),
    KEY `idx_term_position` (`term_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS info (
    `key` VARCHAR(255),
    `value` INT(11),
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO info (`key`, `value`) values ('total_documents', 0);




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
