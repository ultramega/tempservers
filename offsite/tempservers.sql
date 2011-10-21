-- phpMyAdmin SQL Dump
-- version 2.11.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 15, 2010 at 01:15 PM
-- Server version: 5.1.41
-- PHP Version: 5.2.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `tempservers`
--

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `sid` mediumint(8) NOT NULL,
  `cfg` text NOT NULL,
  `raw` tinyint(1) NOT NULL,
  `mapcycle` text NOT NULL,
  UNIQUE KEY `sid` (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `city` varchar(64) NOT NULL,
  `state` varchar(64) NOT NULL,
  `country` varchar(64) NOT NULL,
  `referrer` varchar(64) NOT NULL,
  `account_overall` tinyint(1) NOT NULL,
  `account_tz` tinyint(1) NOT NULL,
  `account_captcha` tinyint(1) NOT NULL,
  `account_deftz` tinyint(1) NOT NULL,
  `account_comments` text NOT NULL,
  `booking_overall` tinyint(1) NOT NULL,
  `booking_date` tinyint(1) NOT NULL,
  `booking_time` tinyint(1) NOT NULL,
  `booking_game` tinyint(1) NOT NULL,
  `booking_comments` text NOT NULL,
  `cp_overall` tinyint(1) NOT NULL,
  `cp_use` tinyint(1) NOT NULL,
  `cp_speed` tinyint(1) NOT NULL,
  `cp_config` tinyint(1) NOT NULL,
  `cp_addition` text NOT NULL,
  `cp_comments` text NOT NULL,
  `server_performance` tinyint(1) NOT NULL,
  `server_ping` tinyint(1) NOT NULL,
  `server_uptime` tinyint(1) NOT NULL,
  `server_features` text NOT NULL,
  `server_comments` text NOT NULL,
  `misc_useagain` tinyint(1) NOT NULL,
  `misc_cost` varchar(12) NOT NULL,
  `misc_comments` text NOT NULL,
  `timestamp` bigint(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `twid` bigint(32) NOT NULL,
  `text` varchar(256) NOT NULL,
  `time` bigint(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=37 ;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `sid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `start` bigint(11) NOT NULL,
  `end` bigint(11) NOT NULL,
  `rcon` varchar(64) NOT NULL,
  `game` varchar(12) NOT NULL,
  `serverID` mediumint(8) NOT NULL,
  UNIQUE KEY `sid` (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=60 ;

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE `servers` (
  `serverID` mediumint(8) NOT NULL AUTO_INCREMENT,
  `host` mediumint(8) NOT NULL DEFAULT '0',
  `ip` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`serverID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `tid` mediumint(8) NOT NULL,
  `content` text NOT NULL,
  `poster` mediumint(8) NOT NULL,
  `time` bigint(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `support_cat`
--

CREATE TABLE `support_cat` (
  `catID` mediumint(8) NOT NULL AUTO_INCREMENT,
  `category` varchar(128) NOT NULL,
  PRIMARY KEY (`catID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `support_thread`
--

CREATE TABLE `support_thread` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) NOT NULL,
  `catID` mediumint(8) NOT NULL,
  `title` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `replies` smallint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `trans`
--

CREATE TABLE `trans` (
  `transID` mediumint(8) NOT NULL AUTO_INCREMENT,
  `txn_id` varchar(32) NOT NULL,
  `sid` mediumint(8) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `credits_used` smallint(4) NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL,
  `timestamp` bigint(11) NOT NULL,
  PRIMARY KEY (`transID`),
  UNIQUE KEY `sid` (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=60 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `user` varchar(64) NOT NULL,
  `pass` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `timezone` varchar(32) NOT NULL,
  `admin` tinyint(1) NOT NULL,
  `credits` smallint(6) NOT NULL,
  `join_date` bigint(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `key` varchar(8) NOT NULL,
  `fb_promo` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=36 ;
