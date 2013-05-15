<?php

$FpsInstallQueries = array();
$FpsInstallQueries[] = "CREATE TABLE `" . $Register['DB']->getFullTableName('blog') . "` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `main` longtext NOT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `views` int(11) default '0',
  `rate` int(11) default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments` int(11) NOT NULL default '0',
  `tags` VARCHAR( 255 ) NOT NULL,
  `description` TEXT NOT NULL,
  `commented` ENUM( '0', '1' ) DEFAULT '1' NOT NULL,
  `available` ENUM( '0', '1' ) DEFAULT '1' NOT NULL,
  `view_on_home` ENUM( '0', '1' ) DEFAULT '1' NOT NULL,
  `on_home_top` ENUM( '0', '1' ) DEFAULT '0' NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

$FpsInstallQueries[] = "CREATE TABLE `" . $Register['DB']->getFullTableName('blog') . "_sections` (
  `id` int(11) NOT NULL auto_increment,
  `parent_id` int(11) default '0',
  `announce` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL,
  `view_on_home` ENUM( '0', '1' ) DEFAULT '1' NOT NULL,
  `no_access` VARCHAR( 255 ) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";

$FpsInstallQueries[] = "CREATE TABLE `" . $Register['DB']->getFullTableName('blog') . "_add_content` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `field_id` INT(11) NOT NULL,
  `entity_id` INT(11) NOT NULL,
  `content` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci";

$FpsInstallQueries[] = "CREATE TABLE `" . $Register['DB']->getFullTableName('blog') . "_add_fields` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(10) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `size` INT(11) NOT NULL,
  `params` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci";

$FpsInstallQueries[] = "CREATE TABLE `" . $Register['DB']->getFullTableName('blog') . "_attaches` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `entity_id` INT NOT NULL,
  `user_id` INT NOT NULL ,
  `attach_number` INT NOT NULL ,
  `filename` VARCHAR( 100 ) NOT NULL ,
  `size` BIGINT NOT NULL ,
  `date` DATETIME NOT NULL ,
  `is_image` ENUM( '0', '1' ) DEFAULT '0' NOT NULL ,
  PRIMARY KEY ( `id` )
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";