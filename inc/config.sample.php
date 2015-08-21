<?php
if ( basename( $_SERVER['PHP_SELF'] ) == 'config.php' )
    die( 'This page cannot be loaded directly' );

return array(
	'site' => array(
		'url' => 'http://stats.yourwebsite.com/',
		'path' => '/home/username/public_html/',
		'geoip_path' => '/home/username/public_html/geoipdb/GeoIP.dat',
		'geoipv6_path' => '/home/username/public_html/geoipdb/GeoIPV6.dat',
		// Only enable for developing!
		'debug' => false,
		// Set to false to disable cross site request forgery protection (not recommended)
		'csrf' => true,
		// If true, IP addresses specified in headers will be used for tracking purposes. This is useful for users behind proxies but can allow for fake IP addresses.
		'header_ip_address' => true
	),
	'mysql' => array(
		'host' => 'localhost',
		'user' => 'usernamehere',
		'pass' => 'passwordhere',
		'db' => 'database',
		'prefix' => 'lss_',
		// Usually needed when having heavy loads
		'persistent' => false
	)
	
);