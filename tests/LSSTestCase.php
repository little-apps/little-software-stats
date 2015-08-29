<?php
define( 'LSS_LOADED', true );

define( 'ROOTDIR', realpath( dirname(__FILE__ ) . '/../' ) );

require_once( ROOTDIR . '/inc/class.config.php' );
require_once( ROOTDIR . '/inc/class.mysql.php' );
require_once( ROOTDIR . '/inc/functions.php' );

class LSSTestCase extends PHPUnit_Framework_TestCase {
	public $app_id;
	public $app_name;
	
	public function __construct() {
		global $config;
		
		$config = Config::getInstance(  );
	}
	

    public function setUp() {
    	global $db;
    	
    	$db = MySQL::getInstance();
    	
    	if ( defined( 'TRAVISCI' ) && (bool)TRAVISCI ) {
			foreach ( $this->create_tables_sql() as $sql ) {
	    		$sql = str_replace( '{:db_prefix}', Config::getInstance()->mysql->prefix, $sql );
	    		
				$db->execute_sql( $sql );
			}
	    	
	    	$this->add_application();
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
		$app_info = MySQL::getInstance()->select( 'applications', '', '', '1' );
		
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
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}s` (`Key` int(11) NOT NULL AUTO_INCREMENT, `Id` varchar(36) NOT NULL, `UniqueUserId` varchar(36) NOT NULL, `StartApp` int(11) NOT NULL, `StopApp` int(11) DEFAULT 0, `ApplicationId` varchar(36) NOT NULL, `ApplicationVersion` varchar(50) NOT NULL, PRIMARY KEY (`Key`), UNIQUE KEY `Id` (`Id`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}uniqueusers` (`UniqueUserKey` int(11) NOT NULL AUTO_INCREMENT, `UniqueUserId` varchar(36) NOT NULL, `Created` int(11) NOT NULL, `LastRecieved` int(11) NOT NULL, `IPAddress` varchar(46) NOT NULL, `Country` varchar(50) NOT NULL, `CountryCode` varchar(3) NOT NULL, `OSVersion` varchar(100) DEFAULT NULL, `OSServicePack` int(11) DEFAULT NULL, `OSArchitecture` int(11) DEFAULT NULL, `JavaVer` varchar(50) DEFAULT NULL, `NetVer` varchar(50) DEFAULT NULL, `NetSP` int(11) DEFAULT NULL, `LangID` int(11) DEFAULT NULL, `ScreenRes` varchar(20) DEFAULT NULL, `CPUName` varchar(40) DEFAULT NULL, `CPUBrand` varchar(20) DEFAULT NULL, `CPUFreq` int(11) DEFAULT NULL, `CPUCores` int(11) DEFAULT NULL, `CPUArch` int(11) DEFAULT NULL, `MemTotal` int(11) DEFAULT NULL, `MemFree` int(11) DEFAULT NULL, `DiskTotal` int(11) DEFAULT NULL, `DiskFree` int(11) DEFAULT NULL, PRIMARY KEY (`UniqueUserKey`), UNIQUE KEY `UniqueUserId` (`UniqueUserId`))';
		$sql[] = 'CREATE TABLE IF NOT EXISTS `{:db_prefix}users` (`UserId` int(11) NOT NULL AUTO_INCREMENT, `UserName` varchar(8) NOT NULL, `UserEmail` varchar(30) NOT NULL, `UserPass` varchar(83) NOT NULL, `ActivateKey` varchar(21) DEFAULT NULL, PRIMARY KEY (`UserId`), UNIQUE KEY `UserName` (`UserName`,`UserEmail`))';
		
		return $sql;
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
}