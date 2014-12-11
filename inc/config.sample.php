<?php
if ( basename( $_SERVER['PHP_SELF'] ) == 'config.php' )
    die( 'This page cannot be loaded directly' );

define('SITE_URL', 'http://stats.yourwebsite.com/');
define('SITE_PATH', '/home/username/public_html/');
define('SITE_GEOIP_PATH', '/home/username/public_html/geoipdb/GeoIP.dat');
define('SITE_GEOIPV6_PATH', '/home/username/public_html/geoipdb/GeoIPV6.dat');
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'user');
define('MYSQL_PASS', 'pass');
define('MYSQL_DB', 'database');
define('MYSQL_PREFIX', 'lss_');
// Usually needed when having heavy loads
define('MYSQL_PERSISTENT', false);
// Only enable for developing!
define('SITE_DEBUG', false);
// Set to false to disable cross site request forgery protection (not recommended)
define('SITE_CSRF', true);
