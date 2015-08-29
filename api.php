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

// So we know the script was called with the API
define( 'LSS_API', true );

require_once dirname( __FILE__ ) .'/inc/main.php';
require_once ROOTDIR . '/inc/class.api.php';

$api = API::getInstance();

$type = ( ( isset( $_GET['type'] ) ) ? ( strtolower( $_GET['type'] ) ) : ( 'json' ) );
if ( $type != 'json' && $type != 'xml' )
    lss_exit( 'Invalid data format specified' );

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

            foreach ( $sorted_json as $sorted_data ) {
                $error_code = parse_data( $sorted_data );

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

    if ( $config->site->debug ) {
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