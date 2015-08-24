# This data is part of the default $steps array to help you
# illustrate how this script works. Feel free to delete it.
# Note that each SQL statement ends if a semi-column!

CREATE TABLE IF NOT EXISTS `{:db_prefix}applications` (
  `ApplicationKey` int(11) NOT NULL AUTO_INCREMENT,
  `ApplicationId` varchar(36) NOT NULL,
  `ApplicationName` varchar(255) NOT NULL,
  `ApplicationRecieving` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ApplicationKey`),
  UNIQUE KEY `ApplicationId` (`ApplicationId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_event` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCategory` varchar(50) DEFAULT NULL,
  `EventName` varchar(50) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_eventvalue` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCategory` varchar(50) DEFAULT NULL,
  `EventName` varchar(50) DEFAULT NULL,
  `EventValue` varchar(50) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_eventperiod` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCategory` varchar(50) DEFAULT NULL,
  `EventName` varchar(50) DEFAULT NULL,
  `EventDuration` int(11) DEFAULT NULL,
  `EventCompleted` tinyint(1) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_log` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `LogMessage` varchar(255) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_customdata` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `EventCustomName` varchar(50) DEFAULT NULL,
  `EventCustomValue` varchar(50) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_exception` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `ExceptionMsg` varchar(255) DEFAULT NULL,
  `ExceptionStackTrace` text,
  `ExceptionSource` varchar(255) DEFAULT NULL,
  `ExceptionTargetSite` varchar(255) DEFAULT NULL,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_install` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}events_uninstall` (
  `EventId` int(11) NOT NULL AUTO_INCREMENT,
  `SessionId` varchar(36) NOT NULL,
  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`EventId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}locales` (
  `LCID` int(11) NOT NULL,
  `DisplayName` varchar(100) NOT NULL,
  `ShortCode` varchar(50) NOT NULL,
  PRIMARY KEY (`LCID`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}sessions` (
  `SessionKey` int(11) NOT NULL AUTO_INCREMENT,
  `SessionId` varchar(36) NOT NULL,
  `UniqueUserId` varchar(36) NOT NULL,
  `StartApp` int(11) NOT NULL,
  `StopApp` int(11) DEFAULT 0,
  `ApplicationId` varchar(36) NOT NULL,
  `ApplicationVersion` varchar(50) NOT NULL,
  PRIMARY KEY (`SessionKey`),
  UNIQUE KEY `SessionId` (`SessionId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}uniqueusers` (
  `UniqueUserKey` int(11) NOT NULL AUTO_INCREMENT,
  `UniqueUserId` varchar(36) NOT NULL,
  `Created` int(11) NOT NULL,
  `LastRecieved` int(11) NOT NULL,
  `IPAddress` varchar(46) NOT NULL,
  `Country` varchar(50) NOT NULL,
  `CountryCode` varchar(3) NOT NULL,
  `OSVersion` varchar(100) DEFAULT NULL,
  `OSServicePack` int(11) DEFAULT NULL,
  `OSArchitecture` int(11) DEFAULT NULL,
  `JavaVer` varchar(50) DEFAULT NULL,
  `NetVer` varchar(50) DEFAULT NULL,
  `NetSP` int(11) DEFAULT NULL,
  `LangID` int(11) DEFAULT NULL,
  `ScreenRes` varchar(20) DEFAULT NULL,
  `CPUName` varchar(40) DEFAULT NULL,
  `CPUBrand` varchar(20) DEFAULT NULL,
  `CPUFreq` int(11) DEFAULT NULL,
  `CPUCores` int(11) DEFAULT NULL,
  `CPUArch` int(11) DEFAULT NULL,
  `MemTotal` int(11) DEFAULT NULL,
  `MemFree` int(11) DEFAULT NULL,
  `DiskTotal` int(11) DEFAULT NULL,
  `DiskFree` int(11) DEFAULT NULL,
  PRIMARY KEY (`UniqueUserKey`),
  UNIQUE KEY `UniqueUserId` (`UniqueUserId`)
) ENGINE={:db_engine} {:db_charset};

CREATE TABLE IF NOT EXISTS `{:db_prefix}users` (
  `UserId` int(11) NOT NULL AUTO_INCREMENT,
  `UserName` varchar(8) NOT NULL,
  `UserEmail` varchar(30) NOT NULL,
  `UserPass` varchar(83) NOT NULL,
  `ActivateKey` varchar(21) DEFAULT NULL,
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `UserName` (`UserName`,`UserEmail`)
) ENGINE={:db_engine} {:db_charset};

INSERT IGNORE INTO `{:db_prefix}locales` (`LCID`, `DisplayName`, `ShortCode`) VALUES
(1078, 'Afrikaans ', 'af '),
(1052, 'Albanian ', 'sq '),
(14337, 'Arabic - U.A.E. ', 'ar-ae '),
(15361, 'Arabic - Bahrain ', 'ar-bh '),
(5121, 'Arabic - Algeria ', 'ar-dz '),
(3073, 'Arabic - Egypt ', 'ar-eg '),
(2049, 'Arabic - Iraq ', 'ar-iq '),
(11265, 'Arabic - Jordan ', 'ar-jo '),
(13313, 'Arabic - Kuwait ', 'ar-kw '),
(12289, 'Arabic - Lebanon ', 'ar-lb '),
(4097, 'Arabic - Libya ', 'ar-ly '),
(6145, 'Arabic - Morocco ', 'ar-ma '),
(8193, 'Arabic - Oman ', 'ar-om '),
(16385, 'Arabic - Qatar ', 'ar-qa '),
(1025, 'Arabic - Saudia Arabia ', 'ar-sa '),
(10241, 'Arabic - Syria ', 'ar-sy '),
(7169, 'Arabic - Tunisia ', 'ar-tn '),
(9217, 'Arabic - Yemen ', 'ar-ye '),
(1069, 'Basque ', 'eu '),
(1059, 'Belarusian ', 'be '),
(1026, 'Bulgarian ', 'bg '),
(1027, 'Catalan ', 'ca '),
(4, 'Chinese ', 'zh '),
(2052, 'Chinese - PRC ', 'zh-cn '),
(3076, 'Chinese - Hong Kong ', 'zh-hk '),
(4100, 'Chinese - Singapore ', 'zh-sg '),
(1028, 'Chinese - Taiwan ', 'zh-tw '),
(1050, 'Croatian ', 'hr '),
(1029, 'Czech ', 'cs '),
(1030, 'Danish ', 'da '),
(1043, 'Dutch ', 'nl '),
(2067, 'Dutch - Belgium ', 'nl-be '),
(9, 'English ', 'en '),
(3081, 'English - Australia ', 'en-au '),
(10249, 'English - Belize ', 'en-bz '),
(4105, 'English - Canada ', 'en-ca '),
(6153, 'English - Ireland ', 'en-ie '),
(8201, 'English - Jamaica ', 'en-jm '),
(5129, 'English - New Zealand ', 'en-nz '),
(7177, 'English - South Africa ', 'en-za '),
(11273, 'English - Trinidad ', 'en-tt '),
(2057, 'English - United Kingdom ', 'en-gb '),
(1033, 'English - United States ', 'en-us '),
(1061, 'Estonian ', 'et '),
(1065, 'Farsi ', 'fa '),
(1035, 'Finnish ', 'fi '),
(1080, 'Faeroese ', 'fo '),
(1036, 'French - Standard ', 'fr'),
(2060, 'French - Belgium ', 'fr-be '),
(3084, 'French - Canada ', 'fr-ca '),
(5132, 'French - Luxembourg ', 'fr-lu '),
(4108, 'French - Switzerland ', 'fr-ch '),
(1084, 'Gaelic - Scotland ', 'gd '),
(1031, 'German - Standard ', 'de '),
(3079, 'German - Austrian ', 'de-at '),
(5127, 'German - Lichtenstein ', 'de-li '),
(4103, 'German - Luxembourg ', 'de-lu '),
(2055, 'German - Switzerland ', 'de-ch '),
(1032, 'Greek ', 'el '),
(1037, 'Hebrew ', 'he '),
(1081, 'Hindi ', 'hi '),
(1038, 'Hungarian ', 'hu '),
(1039, 'Icelandic ', 'is '),
(1057, 'Indonesian ', 'in '),
(1040, 'Italian - Standard ', 'it '),
(2064, 'Italian - Switzerland ', 'it-ch '),
(1041, 'Japanese ', 'ja '),
(1042, 'Korean ', 'ko '),
(1062, 'Latvian ', 'lv '),
(1063, 'Lithuanian ', 'lt '),
(1071, 'Macedonian ', 'mk '),
(1086, 'Malay - Malaysia ', 'ms '),
(1082, 'Maltese ', 'mt '),
(1044, 'Norwegian - Bokm?l ', 'no '),
(1045, 'Polish ', 'pl '),
(2070, 'Portuguese - Standard ', 'pt '),
(1046, 'Portuguese - Brazil ', 'pt-br '),
(1047, 'Raeto-Romance ', 'rm '),
(1048, 'Romanian ', 'ro '),
(2072, 'Romanian - Moldova ', 'ro-mo '),
(1049, 'Russian ', 'ru '),
(2073, 'Russian - Moldova ', 'ru-mo '),
(3098, 'Serbian - Cyrillic ', 'sr '),
(1074, 'Setsuana ', 'tn '),
(1060, 'Slovenian ', 'sl '),
(1051, 'Slovak ', 'sk '),
(1070, 'Sorbian ', 'sb '),
(1034, 'Spanish - Standard ', 'es '),
(11274, 'Spanish - Argentina ', 'es-ar '),
(16394, 'Spanish - Bolivia ', 'es-bo '),
(13322, 'Spanish - Chile ', 'es-cl '),
(9226, 'Spanish - Columbia ', 'es-co '),
(5130, 'Spanish - Costa Rica ', 'es-cr '),
(7178, 'Spanish - Dominican Republic ', 'es-do '),
(12298, 'Spanish - Ecuador ', 'es-ec '),
(4106, 'Spanish - Guatemala ', 'es-gt '),
(18442, 'Spanish - Honduras ', 'es-hn '),
(2058, 'Spanish - Mexico ', 'es-mx '),
(19466, 'Spanish - Nicaragua ', 'es-ni '),
(6154, 'Spanish - Panama ', 'es-pa '),
(10250, 'Spanish - Peru ', 'es-pe '),
(20490, 'Spanish - Puerto Rico ', 'es-pr '),
(15370, 'Spanish - Paraguay ', 'es-py '),
(17418, 'Spanish - El Salvador ', 'es-sv '),
(14346, 'Spanish - Uruguay ', 'es-uy '),
(8202, 'Spanish - Venezuela ', 'es-ve '),
(1072, 'Sutu ', 'sx '),
(1053, 'Swedish ', 'sv '),
(2077, 'Swedish - Finland ', 'sv-fi '),
(1054, 'Thai ', 'th '),
(1055, 'Turkish ', 'tr '),
(1073, 'Tsonga ', 'ts '),
(1058, 'Ukranian ', 'uk '),
(1056, 'Urdu - Pakistan ', 'ur '),
(1066, 'Vietnamese ', 'vi '),
(1076, 'Xhosa ', 'xh '),
(1085, 'Yiddish ', 'ji '),
(1077, 'Zulu ', 'zu ');
