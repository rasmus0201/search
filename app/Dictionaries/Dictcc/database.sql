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


DROP TABLE IF EXISTS `lemmas`;
CREATE TABLE `lemmas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `raw_lemma_id` int(11) NOT NULL,
  `lemma_ref` varchar(32) NOT NULL DEFAULT '',
  `word` varchar(255) NOT NULL DEFAULT '',
  `wordclass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_raw_lemma_id` (`raw_lemma_id`),
  KEY `unique_lemma_ref` (`lemma_ref`),
  KEY `idx_raw_lemma_id` (`raw_lemma_id`),
  KEY `idx_lemma_ref` (`lemma_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
