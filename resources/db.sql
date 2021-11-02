--
-- Database: `moniteur`
--

-- --------------------------------------------------------

--
-- Table structure for table `docs`
--

DROP TABLE IF EXISTS `docs`;
CREATE TABLE IF NOT EXISTS `docs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numac` int(11) NOT NULL,
  `pub_date` date NOT NULL,
  `prom_date` date NOT NULL,
  `type` mediumint(8) unsigned NOT NULL,
  `eli_type_fr` tinytext DEFAULT NULL,
  `eli_type_nl` tinytext DEFAULT NULL,
  `chrono_id` int unsigned DEFAULT NULL,
  `chamber_id` int unsigned DEFAULT NULL,
  `senate_id` int unsigned DEFAULT NULL,
  `chamber_leg` tinyint(3) unsigned DEFAULT NULL,
  `senate_leg` tinyint(3) unsigned DEFAULT NULL,
  `source` mediumint(8) unsigned DEFAULT NULL,
  `anonymise` tinyint(1) NOT NULL,
  `version` tinyint(3) unsigned NOT NULL,
  `languages` set('fr','nl') DEFAULT NULL,
  `createdTS` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numac` (`numac`),
  KEY `pub_date` (`pub_date`),
  KEY `prom_date` (`prom_date`),
  KEY `type` (`type`),
  KEY `version` (`version`),
  KEY `anonymise` (`anonymise`),
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `done_dates`
--

DROP TABLE IF EXISTS `done_dates`;
CREATE TABLE IF NOT EXISTS `done_dates` (
  `date` date NOT NULL,
  PRIMARY KEY (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `doc_links`
--

DROP TABLE IF EXISTS `doc_links`;
CREATE TABLE IF NOT EXISTS `doc_links` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `numac` int(10) NOT NULL,
  `chrono` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `eli` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `ecli` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `pdf` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numac` (`numac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



-- --------------------------------------------------------

--
-- Table structure for table `links_cache`
--

DROP TABLE IF EXISTS `links_cache`;
CREATE TABLE IF NOT EXISTS `links_cache` (
  `numac` int(10) NOT NULL,
  `linkto` int(10) NOT NULL,
  `ln` varchar(2) NOT NULL,
  `position` smallint(6) NOT NULL,
  `version` tinyint(4) NOT NULL,
  UNIQUE KEY `numac_linkto_ln` (`numac`,`linkto`,`ln`),
  KEY `numac_ln_version` (`numac`,`ln`,`version`),
  KEY `numac_ln` (`numac`,`ln`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE INDEX version ON links_cache(version);
CREATE INDEX numac ON links_cache(numac);
CREATE INDEX ln ON links_cache(ln);
CREATE INDEX linkto_numac ON links_cache(linkto, numac);

-- --------------------------------------------------------

--
-- Table structure for table `raw_ids`
--

DROP TABLE IF EXISTS `raw_ids`;
CREATE TABLE IF NOT EXISTS `raw_ids` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doc_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `version` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_id` (`doc_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `raw_pages`
--

DROP TABLE IF EXISTS `raw_pages`;
CREATE TABLE IF NOT EXISTS `raw_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numac` int(11) NOT NULL,
  `pub_date` date NOT NULL,
  `raw_fr` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `raw_nl` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `version` tinyint(4) NOT NULL,
  `createdTS` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numac` (`numac`),
  KEY `pub_date` (`pub_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=0 ;
CREATE INDEX version ON raw_pages(version);                                                                             
ALTER TABLE raw_pages ADD FULLTEXT(raw_fr);                                                                           
~                                                   

-- --------------------------------------------------------

--
-- Table structure for table `render_cache`
--

DROP TABLE IF EXISTS `render_cache`;
CREATE TABLE IF NOT EXISTS `render_cache` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `numac` int(10) NOT NULL,
  `ln` varchar(2) NOT NULL,
  `text` mediumblob NOT NULL,
  `version` tinyint(4) NOT NULL,
  `createdTS` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numac_ln` (`numac`,`ln`),
  UNIQUE KEY `numac_ln_version` (`numac`, `ln`, `version`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
CREATE INDEX version ON render_cache(version);                                                                           
CREATE INDEX numac ON render_cache(numac);                                                                               
CREATE INDEX ln ON render_cache(ln);                                                                                     
                            

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

DROP TABLE IF EXISTS `sources`;
CREATE TABLE IF NOT EXISTS `sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_nl` varchar(8192) DEFAULT NULL,
  `source_fr` varchar(8192) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `source_fr` (`source_fr`),
  KEY `source_nl` (`source_nl`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `text`
--

DROP TABLE IF EXISTS `text`;
CREATE TABLE IF NOT EXISTS `text` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numac` int(10) unsigned NOT NULL,
  `length` int(10) unsigned NOT NULL,
  `ln` varchar(2) NOT NULL,
  `raw` mediumtext NOT NULL,
  `pure` mediumtext NOT NULL,
  `createdTS` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numac_ln` (`numac`,`ln`),
  KEY `ln` (`ln`),
  KEY `length` (`length`),
  KEY `numac` (`numac`),
  FULLTEXT KEY `FT_pure` (`pure`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `titles`
--

DROP TABLE IF EXISTS `titles`;
CREATE TABLE IF NOT EXISTS `titles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numac` int(10) unsigned NOT NULL,
  `ln` varchar(2) NOT NULL,
  `raw` text,
  `pure` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numac_ln` (`numac`,`ln`),
  KEY `ln` (`ln`),
  KEY `numac` (`numac`),
  FULLTEXT KEY `FT_pure` (`pure`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `types`
--

DROP TABLE IF EXISTS `types`;
CREATE TABLE IF NOT EXISTS `types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ord` tinyint(4) unsigned NULL,
  `type_nl` varchar(255) NOT NULL,
  `type_fr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  `createdTS` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `type_nl` (`type_nl`),
  KEY `type_fr` (`type_fr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------


--
-- Table structure for table `tag_links`
--

DROP TABLE IF EXISTS `tag_links`;
CREATE TABLE IF NOT EXISTS `tag_links` (
  `id` bigint(16) unsigned NOT NULL AUTO_INCREMENT,
  `text_id` int(6) unsigned NOT NULL,
  `word_id` int(6) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `text_id` (`text_id`,`word_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_relations`
--

DROP TABLE IF EXISTS `tag_relations`;
CREATE TABLE IF NOT EXISTS `tag_relations` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `word_a` int(6) NOT NULL,
  `word_b` int(6) NOT NULL,
  `strength` int(7) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relation` (`word_a`,`word_b`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_stopwords`
--

DROP TABLE IF EXISTS `tag_stopwords`;
CREATE TABLE IF NOT EXISTS `tag_stopwords` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ln` varchar(2) NOT NULL,
  `word` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ln` (`ln`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `tag_words`
--

DROP TABLE IF EXISTS `tag_words`;
CREATE TABLE IF NOT EXISTS `tag_words` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ln` varchar(2) NOT NULL,
  `total_count` int(10) unsigned NOT NULL DEFAULT '1',
  `doc_count` int(10) unsigned NOT NULL DEFAULT '1',
  `word` varchar(32) NOT NULL,
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`),
  KEY `ln` (`ln`,`total_count`,`doc_count`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

