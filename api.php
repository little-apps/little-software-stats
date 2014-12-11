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
 */

// Prevents other pages from being loaded directly
define( 'LSS_LOADED', true );

require_once dirname( __FILE__ ) .'/inc/main.php';
require_once ROOTDIR . '/inc/class.api.php';

$api = API::getInstance();

$type = ( ( isset( $_GET['type'] ) ) ? ( strtolower( $_GET['type'] ) ) : ( 'json' ) );
if ( $type != 'json' && $type != 'xml' )
    lss_exit( 'Invalid data format specified' );

/**
 * Parses data
 * @param array $data Array containing parsed data
 * @return int Returns status code
 */
function parse_data( $data ) { 
    global $api;
    
    $ret = '';
    
    if ( isset( $data['ID'] ) )
        $data['ID'] = strtoupper( $data['ID'] );
    
    if ( isset( $data['ss'] ) )
        $data['ss'] = strtoupper( $data['ss'] );
    
    switch ( $data['tp'] ) {
        // Start App
        case "strApp": 
            $ret = $api->start_app( $data['aid'], $data['aver'], $data['ID'], $data['ss'], $data['ts'],
                    $data['osv'], $data['ossp'], $data['osar'], $data['osjv'],
                    $data['osnet'], $data['osnsp'], $data['oslng'], $data['osscn'],
                    $data['cnm'], $data['cbr'], $data['cfr'], $data['ccr'],
                    $data['car'], $data['mtt'], $data['mfr'], $data['dtt'], $data['dfr'] );
            break;
        // Stop App
        case "stApp":
            $ret = $api->stop_app( $data['ts'], $data['ss'] );
            break;
        // Event
        case "ev":
            $ret = $api->event( $data['ts'], $data['ss'], $data['ca'], $data['nm'] );
            break;
        // Event Value
        case "evV":
            $ret = $api->event_value( $data['ts'], $data['ss'], $data['ca'], $data['nm'], $data['vl'] );
            break;
        // Event Period
        case "evP":
            $ret = $api->event_period( $data['ts'], $data['ss'], $data['ca'], $data['nm'], $data['tm'], $data['ec'] );
            break;
        // Log
        case "lg":
            $ret = $api->log( $data['ts'], $data['ss'], $data['ms'] );
            break;
        // Custom Data
        case "ctD":
            $ret = $api->custom_data( $data['ts'], $data['ss'], $data['nm'], $data['vl'] );
            break;
        // Exception
        case "exC":
            $ret = $api->exception( $data['ts'], $data['ss'], $data['msg'], $data['stk'], $data['src'], $data['tgs'] );
            break;
        // Install
        case "ist":
            $ret = $api->install( $data['ts'], $data['ss'], $data['aid'], $data['aver'] );
            break;
        // Uninstall
        case "ust":
            $ret = $api->uninstall( $data['ts'], $data['ss'], $data['aid'], $data['aver'] );
            break;
        // No event found
        default:
            break;
    }
    
    return $ret;
}

/**
 * Gets error response for specified error code
 * @global string $type Type of format being used (xml or json)
 * @param int $error_code Error code
 * @return boolean|string Returns error code and message as XML or JSON or false if the error code wasn't found
 */
function get_error( $error_code ) {
    global $type;
    
    if ( !is_numeric( $error_code ) )
        return false;
    
    $error_code = intval( $error_code );
    
    $errors = array(
        1 => 'Success',
        -8 => 'Empty POST data',
        -9 => 'Invalid JSON/XML string',
        -10 => 'Missing required data',
        -11 => 'Application ID not found',
        -12 => 'User ID not found',
        -13 => 'Use POST request',
        -14 => 'Application version not found',
        -15 => 'Invalid event data'
    );
    
    if ( !array_key_exists( $error_code, $errors ) )
        return false;

    if ( $type == 'json' ) {
        header( "Content-Type: text/json" );
        
        return json_encode( array(
            'status_code' => $error_code,
            'status_message' => $errors[$error_code]
        ) );
    } else {
        header( "Content-Type: text/xml" );
        
        $status = new SimpleXMLElement( '<Status/>' );
        $status->addChild( 'Code', $error_code );
        $status->addChild( 'Message', $errors[$error_code] );
        
        return $status->asXML();
    }
}

$error_code = 1;

if ( $_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_METHOD'] != 'POST' ) {
    if ( !function_exists( 'http_response_code' ) ) {
        // If PHP version is less than 5.4
        header( 'Allow: POST', true, 405 );
    } else {
        http_response_code( 405 );
        header( 'Allow: POST' );
    }

    lss_exit( get_error( -13 ) );
}

$post_data = '';

if ( isset( $_POST['data'] ) )
    $post_data = trim( $_POST['data'] );
else if ( file_get_contents( "php://input" ) )
    $post_data = trim( file_get_contents( "php://input" ) );

if ( !$post_data )
    $error_code = -8;

if ( $error_code != 1 )
    lss_exit( get_error( $error_code ) );

if ( get_magic_quotes_gpc() )
    $post_data = stripslashes( $post_data );

if ( $type == 'json' ) {
    function br2nl(&$val, $key) {
        $val = str_replace('<br>', "\n", $val);
    }
    
    $post_data = str_replace(array("\r\n", "\n", "\r", "\\"), array("<br>", "<br>", "<br>", "\\\\"), $post_data );

    $json_array = json_decode( $post_data, true );

    if ( $json_array == NULL )
        $error_code = -9;

    if ( $error_code != 1 )
        lss_exit( get_error( $error_code ) );
    
    array_walk_recursive( $json_array, 'br2nl' );
    
    foreach ( $json_array as $data ) {
        if ( count( $data ) > 1 ) {
            $sorted_json = array();

            foreach ( $data as $child_array ) {
                if ( !isset( $child_array['tp'] ) )
                    continue;

                if ( $child_array['tp'] == 'strApp' ) {
                    $sorted_json[0] = $child_array;
                } elseif ( $child_array['tp'] == 'stApp' ) {
                    $child_last = $child_array;
                } elseif ( intval( $child_array['fl'] ) == 0 ) {
                    $sorted_json[] = $child_array;
                } else {
                    $flow_id = intval( $child_array['fl'] );
                    $sorted_json[$flow_id] = $child_array;

                    unset( $flow_id );
                }
            }

            unset( $data, $child_array );

            if ( isset( $child_last ) )
                $sorted_json[] = $child_last;

            if ( !isset( $sorted_json[0] ) || !isset( $child_last ) )
                lss_exit( get_error( -10 ) );

            foreach ( $sorted_json as $data ) {
                $error_code = parse_data( $data );

                if ( $error_code != 1 )
                    break;
            }

            unset( $sorted_json, $data );
        } else {
            // Not enough data
            lss_exit( get_error( -10 ) );
        }
    }

    unset( $json_array );
} else {
    $sorted_xml = array();

    if ( SITE_DEBUG ) {
        $xml = simplexml_load_string( $post_data, 'SimpleXMLElement', LIBXML_NOCDATA );
    } else {
        // Suppress XML parsing errors
        libxml_use_internal_errors();
        
        $xml = @simplexml_load_string( $post_data, 'SimpleXMLElement', LIBXML_NOCDATA );
    }
	
    if ( $xml === false )
        lss_exit( get_error ( -9 ) );
    
    $xml_data = $xml->children();
    
    foreach ( $xml_data as $children ) {
        if ( count( $children ) > 1 ) {
            foreach ( $children as $child_object ) {
                $child_array = @json_decode( @json_encode( $child_object ), true );

                if ( !isset( $child_array['tp'] ) ) {
                    unset( $child_array );
                    continue;
                }

                $flow_id = ( ( ( isset( $child_array['fl'] ) ) && is_numeric( $child_array['fl'] ) && $child_array['fl'] > 0 ) ? ( intval( $child_array['fl'] ) ) : ( 0 ) );

                if ( $child_array['tp'] == 'strApp' )
                    $sorted_xml[0] = $child_array;
                elseif ( $child_array['tp'] == 'stApp' )
                    $child_last = $child_array;
                elseif ( $flow_id == 0 )
                    $sorted_xml[] = $child_array;
                else
                    $sorted_xml[$flow_id] = $child_array;

                unset( $child_array, $flow_id );
            }

            unset( $children, $child_object );

            if ( isset( $child_last ) )
                $sorted_xml[] = $child_last;

            if ( !isset( $sorted_xml[0] ) || !isset( $child_last ) )
                lss_exit( get_error( -10 ) );

            foreach ( $sorted_xml as $data ) {
                $error_code = parse_data( $data );

                if ( $error_code != 1 )
                    break;
            }

            unset( $sorted_xml, $data );
        } else {
            // Not enough data
            lss_exit( get_error( -10 ) );
        } 
    }

    unset( $xml_data );
}

lss_exit( get_error( $error_code ) );