<?php

// This config is used with Travis CI

if ( !defined( 'ROOTDIR' ) )
    define( 'ROOTDIR', realpath( dirname( __FILE__ ) . '/../' ) );

return array(
    'site' => array(
    	'url' => 'http://localhost/',
    	'path' => ROOTDIR,
    	'geoip_path' => ROOTDIR . '/geoipdb/GeoIP.dat',
    	'geoipv6_path' => ROOTDIR . '/geoipdb/GeoIPV6.dat',
    	'debug' => false,
    	'csrf' => true,
    	'header_ip_address' => true
    ),
    'mysql' => array(
    	'host' => 'localhost',
    	'user' => 'root',
    	'pass' => '',
    	'db' => 'test',
    	'prefix' => 'lss_',
    	'persistent' => false
    )
);