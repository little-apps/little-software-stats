<?php
/**
 * Little Software Stats
 *
 * An open source program that allows developers to keep track of how their software is being used
 *
 * @package		Little Software Stats
 * @author		Little Apps
 * @copyright   Copyright (c) 2011, Little Apps
 * @license		http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 * @link		http://little-software-stats.com
 * @since		Version 0.1
 * @filesource
 */

if ( !defined( 'LSS_LOADED' ) ) die( 'This page cannot be loaded directly' );

if ( !defined( 'LSS_API' ) ) { // Sessions aren't used with API
	session_start();

	if ( !session_id() ) {
	    die( 'PHP Session could not be started.' );
	}
}


if ( !defined( 'ROOTDIR' ) )
    define( 'ROOTDIR', realpath( dirname( __FILE__ ) . '/../' ) );

if ( @file_exists( ROOTDIR . '/inc/config.php' ) && @filesize( ROOTDIR . '/inc/config.php' ) > 0 )
    require_once( ROOTDIR . '/inc/config.php' );
else {
    die( 'You must <a href="install/">install and configure</a> Little Software Stats first' );
}

// Increase time limit
@set_time_limit( 300 );

require_once( ROOTDIR . '/inc/class.mysql.php' );
require_once( ROOTDIR . '/inc/class.securelogin.php' );
require_once( ROOTDIR . '/inc/version.php' );
require_once( ROOTDIR . '/inc/functions.php' );
require_once( ROOTDIR . '/min/utils.php' );

if ( SITE_DEBUG ) {
    ini_set( 'display_errors', 1 );
    error_reporting( E_ALL );
}

$db = MySQL::getInstance();
$login = SecureLogin::getInstance();

if ( version_compare( PHP_VERSION, MIN_PHP_VERSION, "<" ) )
    die( __( "It appears that the web server is not running PHP 5. Please contact your administrator to have it upgraded." ) );

if ( version_compare( $db->get_db_version(), MIN_MYSQL_VERSION, "<" ) )
    die( __( "It appears that the web server is not running PHP 5. Please contact your administrator to have it upgraded." ) );
    
// Set timezone to UTC
date_default_timezone_set('UTC');
$db->execute_sql('SET time_zone = "+00:00"');

if ( !defined( 'SITE_NAME' ) ) {
    $site_name = strtolower( $_SERVER['SERVER_NAME'] );
    if ( substr( $site_name, 0, 4 ) == 'www.' )
        define( 'SITE_NAME', substr( $site_name, 4 ) );
    else
        define( 'SITE_NAME', $site_name );
    unset( $site_name );
}

if ( !defined( 'SITE_NOREPLYEMAIL' ) ) {
    define( 'SITE_NOREPLYEMAIL', 'noreply@'. SITE_NAME );
}

// Remove leading slash from URL
$GLOBALS['site_url'] = SITE_URL;
if ( substr( $GLOBALS['site_url'], -1 ) == '/' ) {
    $GLOBALS['site_url'] = rtrim( $GLOBALS['site_url'], '/' );
}

// Make sure user is already logged in
if ( $login->check_user() ) {
    $needs_refresh = false;

    // Set request variable to default if not set already
    $apps = get_applications();

    $sanitized_input = array();

    // Requires MySQL connection to call mysql_real_escape_string()
    foreach ( $_GET as $k => $v ) {
        $sanitized_input[$k] = $db->secure_data( $v );
    }

    if ( !isset( $sanitized_input['id'] ) ) {
        if ( count( $apps ) > 0 )
            $sanitized_input['id'] = $apps[0]['AppId'];
        else
            $sanitized_input['id'] = 'add';

        $needs_refresh = true;
    }
    
    // Prevents LFI (Local File Inclusion)
    if ( !isset( $sanitized_input['page'] ) ) {
        $sanitized_input['page'] = 'dashboard';
        $needs_refresh = true;
    } else {
        $validpages = array(
            'appsettings', 'averagetime', 'bouncerate', 'cpus', 'customdata', 'dashboard',
            'events', 'eventstiming', 'eventsvalue', 'exceptions', 'executions', 'installations',
            'languages', 'licenses', 'logs', 'loyaltytime', 'mapoverlay', 'memory', 'myaccount',
            'newvsreturning', 'operatingsystems', 'pluginsandvms', 'screenresolutions', 'settings',
            'uninstallations', 'versions'
        );

        if ( !ctype_alpha( $sanitized_input['page'] ) || !in_array( $sanitized_input['page'], $validpages ) || !file_exists( ROOTDIR . '/pages/' . $sanitized_input['page'] . '.php' ) ) {
            $sanitized_input['page'] = 'dashboard';
            $needs_refresh = true;
        }
    }

    if ( !isset( $sanitized_input['ver'] ) ) {
        $sanitized_input['ver'] = 'all';
        $needs_refresh = true;
    }

    if ( !isset( $sanitized_input['graphBy'] ) ) {
        $sanitized_input['graphBy'] = 'day';
        $needs_refresh = true;
    }

    // Check if start and end are valid and then store as Unix time

    if ( !isset( $sanitized_input['start'] ) ) {
        $sanitized_input['start'] = time() - ( 30 * 24 * 3600 );
        $needs_refresh = true;
    } elseif ( ( $sanitized_input['start'] = strtotime( $sanitized_input['start'] ) ) === false ) {
        $sanitized_input['start'] = time() - ( 30 * 24 * 3600 );
        $needs_refresh = true;
    }

    if ( !isset( $sanitized_input['end'] ) || !strtotime( $sanitized_input['end'] ) ) {
        $sanitized_input['end'] = time();
        $needs_refresh = true;
    } elseif ( ( $sanitized_input['end'] = strtotime( $sanitized_input['end'] ) ) === false ) {
        $sanitized_input['start'] = time();
        $needs_refresh = true;
    }

    // Make sure time range is valid for graphs
    if ( $sanitized_input['graphBy'] == 'day' )
        $tick_interval = strtotime( '+1 day', 0 );
    elseif ( $sanitized_input['graphBy'] == 'week' )
        $tick_interval = strtotime( '+1 week', 0 );
    elseif ( $sanitized_input['graphBy'] == 'month' )
        $tick_interval = strtotime( '+1 month', 0 );

    $time_range = $sanitized_input['end'] - $sanitized_input['start'];

    if ( $time_range < $tick_interval ) {
        $sanitized_input['end'] = $sanitized_input['end'] + ( $tick_interval - $time_range );

        $needs_refresh = true;

        // Enable notification of time change
        $_SESSION['time_changed'] = true;
    }

    unset( $time_range, $end );
}