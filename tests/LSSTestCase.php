<?php
define( 'LSS_LOADED', true );

define( 'ROOTDIR', realpath( dirname(__FILE__ ) . '/../' ) );

@set_time_limit(300);

require_once( ROOTDIR . '/inc/class.config.php' );
require_once( ROOTDIR . '/inc/class.mysql.php' );
require_once( ROOTDIR . '/inc/version.php' );
require_once( ROOTDIR . '/inc/functions.php' );
require_once( ROOTDIR . '/inc/geoip.php' );

class LSSTestCase extends PHPUnit_Framework_TestCase {
	public $app_id;
	public $app_name;
	
	private $required_exts = 
		array(
			'mysqli',
			'gd',
			'hash',
			'json',
			'session',
			'SimpleXML',
			'zlib',
			'mbstring'
		);
		
	private $required_callables = 
		array(
			'curl_init'
		);
	
	public function __construct() {
	}
	
    public function setUp() {
    	
    	if ( defined( 'TRAVISCI' ) && (bool)TRAVISCI ) {
			foreach ( $this->create_tables_sql() as $sql ) {
	    		$sql = str_replace( '{:db_prefix}', Config::getInstance()->mysql->prefix, $sql );
	    		
				MySQL::getInstance()->execute_sql( $sql );
			}
	    	
	    	$this->add_application();
	    	$this->insert_options();
		} else {
			$this->get_application();
		}
    }
    
    private function add_application() {
		$this->app_id = generate_app_id();
		$this->app_name = 'Sample Application';
		
		MySQL::getInstance()->insert( array( "ApplicationName" => $this->app_name, "ApplicationId" => $this->app_id ), "applications" );
	}
	
	private function get_application() {
		MySQL::getInstance()->select( 'applications', '', '', '1' );
		
		$app_info = MySQL::getInstance()->arrayed_result;
		
		if ( empty( $app_info ) )
			throw new Exception( 'No application could be found' );
			
		$this->app_id = $app_info['ApplicationId'];
		$this->app_name = $app_info['ApplicationName'];
	}
    
    private function create_tables_sql() {
		$sql = array();
		
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}applications` (`ApplicationKey` int(11) NOT NULL AUTO_INCREMENT, `ApplicationId` varchar(36) NOT NULL, `ApplicationName` varchar(255) NOT NULL, `ApplicationRecieving` tinyint(1) DEFAULT \'1\', PRIMARY KEY (`ApplicationKey`), UNIQUE KEY `ApplicationId` (`ApplicationId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_event` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `EventCategory` varchar(50) DEFAULT NULL, `EventName` varchar(50) DEFAULT NULL, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_eventvalue` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `EventCategory` varchar(50) DEFAULT NULL, `EventName` varchar(50) DEFAULT NULL, `EventValue` varchar(50) DEFAULT NULL, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_eventperiod` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `EventCategory` varchar(50) DEFAULT NULL, `EventName` varchar(50) DEFAULT NULL, `EventDuration` int(11) DEFAULT NULL, `EventCompleted` tinyint(1) DEFAULT NULL, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_log` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `LogMessage` varchar(255) DEFAULT NULL, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_customdata` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `EventCustomName` varchar(50) DEFAULT NULL, `EventCustomValue` varchar(50) DEFAULT NULL, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_exception` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `ExceptionMsg` varchar(255) DEFAULT NULL, `ExceptionStackTrace` text, `ExceptionSource` varchar(255) DEFAULT NULL, `ExceptionTargetSite` varchar(255) DEFAULT NULL, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_install` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}events_uninstall` (`EventId` int(11) NOT NULL AUTO_INCREMENT, `Id` varchar(36) NOT NULL, `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL, PRIMARY KEY (`EventId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}locales` (`LCID` int(11) NOT NULL, `DisplayName` varchar(100) NOT NULL, `ShortCode` varchar(50) NOT NULL, PRIMARY KEY (`LCID`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}options` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(64) NOT NULL, `value` longtext NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}sessions` (`Key` int(11) NOT NULL AUTO_INCREMENT, `Id` varchar(36) NOT NULL, `UniqueUserId` varchar(36) NOT NULL, `StartApp` int(11) NOT NULL, `StopApp` int(11) DEFAULT 0, `ApplicationId` varchar(36) NOT NULL, `ApplicationVersion` varchar(50) NOT NULL, PRIMARY KEY (`Key`), UNIQUE KEY `Id` (`Id`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}uniqueusers` (`UniqueUserKey` int(11) NOT NULL AUTO_INCREMENT, `UniqueUserId` varchar(36) NOT NULL, `Created` int(11) NOT NULL, `LastRecieved` int(11) NOT NULL, `IPAddress` varchar(46) NOT NULL, `Country` varchar(50) NOT NULL, `CountryCode` varchar(3) NOT NULL, `OSVersion` varchar(100) DEFAULT NULL, `OSServicePack` int(11) DEFAULT NULL, `OSArchitecture` int(11) DEFAULT NULL, `JavaVer` varchar(50) DEFAULT NULL, `NetVer` varchar(50) DEFAULT NULL, `NetSP` int(11) DEFAULT NULL, `LangID` int(11) DEFAULT NULL, `ScreenRes` varchar(20) DEFAULT NULL, `CPUName` varchar(40) DEFAULT NULL, `CPUBrand` varchar(20) DEFAULT NULL, `CPUFreq` int(11) DEFAULT NULL, `CPUCores` int(11) DEFAULT NULL, `CPUArch` int(11) DEFAULT NULL, `MemTotal` int(11) DEFAULT NULL, `MemFree` int(11) DEFAULT NULL, `DiskTotal` int(11) DEFAULT NULL, `DiskFree` int(11) DEFAULT NULL, PRIMARY KEY (`UniqueUserKey`), UNIQUE KEY `UniqueUserId` (`UniqueUserId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}users` (`UserId` int(11) NOT NULL AUTO_INCREMENT, `UserName` varchar(8) NOT NULL, `UserEmail` varchar(30) NOT NULL, `UserPass` varchar(83) NOT NULL, `ActivateKey` varchar(21) DEFAULT NULL, PRIMARY KEY (`UserId`), UNIQUE KEY `UserName` (`UserName`,`UserEmail`))';
		
		
		$sql[] = "INSERT IGNORE INTO `{:db_prefix}locales` (`LCID`, `DisplayName`, `ShortCode`) VALUES
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
					(1077, 'Zulu ', 'zu ')";
		
		return $sql;
	}
	
	private function generate_options() {
		try {
			$geoips_database_version = $this->geoip_get_version(Config::getInstance()->site->geoip_path);
		} catch (Exception $e) {
			$this->fail("The following exception was thrown trying to get the GeoIP version: " . $e->getMessage());
		}
		
		try {
			$geoips_database_v6_version = $this->geoip_get_version(Config::getInstance()->site->geoipv6_path);
		} catch (Exception $e) {
			$this->fail("The following exception was thrown trying to get the GeoIPv6 version: " . $e->getMessage());
		}
		
		return array(
			'current_version' => VERSION,
			'site_adminemail' => $site_adminemail,
			'site_rewrite' => 'true', // true or false
			'recaptcha_enabled' => 'false', // true or false
			'recaptcha_public_key' => '',
			'recaptcha_private_key' => '',
			'mail_protocol' => 'mail', // mail, smtp, or sendmail
			'mail_smtp_server' => 'localhost',
			'mail_smtp_port' => '25',
			'mail_smtp_username' => 'username',
			'mail_smtp_password' => 'password',
			'mail_sendmail_path' => '/usr/sbin/sendmail',
			'geoips_service' => 'database', // database or api
			'geoips_api_key' => '',
			'geoips_database_version' => date('Y-m-d', $geoips_database_version),
			'geoips_database_update_url' => 'http://little-software-stats.com/geolite.xml',
			'geoips_database_v6_version' => date('Y-m-d', $geoips_database_v6_version),
			'geoips_database_v6_update_url' => 'http://little-software-stats.com/geolitev6.xml'
		);
	}
	
    private function insert_options() {
		foreach ($this->generate_options() as $name => $value) {
			if ( !is_string( $value) )
	            $value = strval( $value );
	            
	        echo 'Inserting option ' . $name . ' value: ' . $value . "\n";
	        
	        MySQL::getInstance()->insert( array( 'name' => $name, 'value' => $value ), 'options');
		}
	}
	
	private function geoip_get_version($file) {
		if (!is_readable($file))
			throw new Exception("File $file is not readable\n");

		if (!($geoip_fp = geoip_open($file, GEOIP_STANDARD)))
			throw new Exception("Unable to open GeoIP database file");
			
		echo 'Getting GeoIP database version from file ' . $file . "\n";
		
		$geoips_database_version_str = geoip_version($geoip_fp);
		
		echo 'GeoIP database version: '. $geoips_database_version_str . "\n";
		
		$geoips_database_version_time = time();
		
		foreach (explode(' ', $geoips_database_version_str) as $str) {
			if (strlen($str) == 8 && is_numeric($str)) {
				$geoips_database_version_time = strtotime($str);
				
				echo 'Unix time for GeoIP database is ' . $geoips_database_version_time . "\n";
                
                break;
			}
		}
		
		/*for ($i = 0; $i < strlen($geoips_database_version_str) - 9; $i++) {
            if (ctype_space(substr($geoips_database_version_str, $i, 1))) {
                $date_str = substr($geoips_database_version_str, $i+1, 8);
                $geoips_database_version_time = strtotime($date_str);
                
                break;
            }
        }*/
		
		geoip_close($geoip_fp);
		
        return $geoips_database_version_time;
	}
    
    public function tearDown() {
    	if ( defined( 'TRAVISCI' ) && (bool)TRAVISCI ) {
			foreach ( $this->drop_tables_sql() as $sql ) {
				MySQL::getInstance()->execute_sql( $sql );
			}
		}
    }
    
    private function drop_tables_sql() {
		$tables = 
			array(
				'applications', 
				'events_event', 
				'events_eventvalue', 
				'events_eventperiod',
				'events_log', 
				'events_customdata', 
				'events_exception', 
				'events_install', 
				'events_uninstall', 
				'locales', 
				'options', 
				'sessions', 
				'uniqueusers', 
				'users'
			);
		
		$sql = array();
		
		foreach ( $tables as $table ) {
			$sql[] = 'DROP TABLE IF EXISTS ' . Config::getInstance()->mysql->prefix . $table;
		}
		
		return $sql;
	}
	
	public function testExtensionsLoaded() {
		if ( empty( $this->required_exts ) )
			return;
			
		foreach ( $this->required_exts as $ext ) {
			$ext_loaded = extension_loaded( $ext );
			$this->assertTrue( $ext_loaded, 'Extension "' . $ext . '" is not loaded' );
		}
	}
	
	public function testCallablesExist() {
		if ( empty( $this->required_callables ) )
			return;
			
		foreach ( $this->required_callables as $callable ) {
			$callable_loaded = is_callable( $callable );
			
			$callable_str = $this->callable_to_string( $callable );
			
			$this->assertTrue( $callable_loaded, 'Callable "' . $callable_str . '" does not exist' );
		}
	}
	
	private function callable_to_string( $callable ) {
		$callable_str = '(unknown)';
		
		if ( is_string( $callable ) ) {
			$callable_str = $callable;
		} else if ( is_array( $callable ) ) {
			list( $class, $method ) = $callable;
			
			if ( is_object( $class ) )
				$class = get_class( $class );
				
			$callable_str = $class . '::' . $method;
		}
		
		return $callable_str;
	}
}