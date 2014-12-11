SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `lss_applications`
--

CREATE TABLE IF NOT EXISTS `lss_applications` (
  `ApplicationKey` int(11) NOT NULL AUTO_INCREMENT,
  `ApplicationId` char(36) NOT NULL,
  `ApplicationName` char(255) NOT NULL,
  `ApplicationRecieving` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ApplicationKey`),
  UNIQUE KEY `ApplicationId` (`ApplicationId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_event`
--

CREATE TABLE IF NOT EXISTS `lss_events_event` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCategory` varchar(50) DEFAULT NULL,
  `EventName` varchar(50) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_eventvalue`
--

CREATE TABLE IF NOT EXISTS `lss_events_eventvalue` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCategory` varchar(50) DEFAULT NULL,
  `EventName` varchar(50) DEFAULT NULL,
  `EventValue` varchar(50) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_eventperiod`
--

CREATE TABLE IF NOT EXISTS `lss_events_eventperiod` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCategory` varchar(50) DEFAULT NULL,
  `EventName` varchar(50) DEFAULT NULL,
  `EventDuration` int(11) DEFAULT NULL,
  `EventCompleted` tinyint(1) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_log`
--

CREATE TABLE IF NOT EXISTS `lss_events_log` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `LogMessage` varchar(255) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_customdata`
--

CREATE TABLE IF NOT EXISTS `lss_events_customdata` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCustomName` varchar(50) DEFAULT NULL,
  `EventCustomValue` varchar(50) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_exception`
--

CREATE TABLE IF NOT EXISTS `lss_events_exception` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `ExceptionMsg` varchar(255) DEFAULT NULL,
  `ExceptionStackTrace` text,
  `ExceptionSource` varchar(255) DEFAULT NULL,
  `ExceptionTargetSite` varchar(255) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_install`
--

CREATE TABLE IF NOT EXISTS `lss_events_install` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_events_uninstall`
--

CREATE TABLE IF NOT EXISTS `lss_events_uninstall` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

-- --------------------------------------------------------

--
-- Table structure for table `lss_locales`
--

CREATE TABLE IF NOT EXISTS `lss_locales` (
  `LCID` int(11) NOT NULL,
  `DisplayName` varchar(100) NOT NULL,
  `ShortCode` varchar(50) NOT NULL,
  PRIMARY KEY (`LCID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `lss_options`
--

CREATE TABLE IF NOT EXISTS `lss_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `lss_sessions`
--

CREATE TABLE IF NOT EXISTS `lss_sessions` (
  `SessionKey` int(11) NOT NULL AUTO_INCREMENT,
  `SessionId` char(36) NOT NULL,
  `UniqueUserId` char(36) NOT NULL,
  `StartApp` int(11) NOT NULL,
  `StopApp` int(11) NOT NULL,
  `ApplicationId` char(36) NOT NULL,
  `ApplicationVersion` char(50) NOT NULL,
  PRIMARY KEY (`SessionKey`),
  UNIQUE KEY `SessionId` (`SessionId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `lss_uniqueusers`
--

CREATE TABLE IF NOT EXISTS `lss_uniqueusers` (
  `UniqueUserKey` int(11) NOT NULL AUTO_INCREMENT,
  `UniqueUserId` char(36) NOT NULL,
  `Created` int(11) NOT NULL,
  `LastRecieved` int(11) NOT NULL,
  `IPAddress` char(20) NOT NULL,
  `Country` char(50) NOT NULL,
  `CountryCode` varchar(3) NOT NULL,
  `OSVersion` char(100) DEFAULT NULL,
  `OSServicePack` int(11) DEFAULT NULL,
  `OSArchitecture` int(11) DEFAULT NULL,
  `JavaVer` char(50) DEFAULT NULL,
  `NetVer` char(50) DEFAULT NULL,
  `NetSP` int(11) DEFAULT NULL,
  `LangID` int(11) DEFAULT NULL,
  `ScreenRes` char(20) DEFAULT NULL,
  `CPUName` char(40) DEFAULT NULL,
  `CPUBrand` char(20) DEFAULT NULL,
  `CPUFreq` int(11) DEFAULT NULL,
  `CPUCores` int(11) DEFAULT NULL,
  `CPUArch` int(11) DEFAULT NULL,
  `MemTotal` int(11) DEFAULT NULL,
  `MemFree` int(11) DEFAULT NULL,
  `DiskTotal` int(11) DEFAULT NULL,
  `DiskFree` int(11) DEFAULT NULL,
  PRIMARY KEY (`UniqueUserKey`),
  UNIQUE KEY `UniqueUserId` (`UniqueUserId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `lss_users`
--

CREATE TABLE IF NOT EXISTS `lss_users` (
  `UserId` int(11) NOT NULL AUTO_INCREMENT,
  `UserName` char(8) NOT NULL,
  `UserEmail` char(30) NOT NULL,
  `UserPass` varchar(83) NOT NULL,
  `ActivateKey` char(21) DEFAULT NULL,
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `UserName` (`UserName`,`UserEmail`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
