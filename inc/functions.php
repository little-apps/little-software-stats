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

if ( !function_exists( 'tr' ) ) {
    /**
     * Gets the translation for the specified text
     * @param string $text Text to get translation for
     * @return string Translation
     */
    function tr( $text ) {
        if ( empty( $text ) )
            return "";

        return gettext( $text );
    }
}

if ( !function_exists( '__' ) ) {
    /**
     * Returns translations for text
     * @param string $text Translation to lookup
     * @return string Translated text
     */
    function __( $text ) {
        return tr( $text );
    }
}

if ( !function_exists( '_e' ) ) {
    /**
     * Echoes translations for text
     * @param string $text Translation to lookup
     */
    function _e( $text ) {
        echo tr( $text );
    }
}

if ( !function_exists( 'redirect' ) ) {
    /**
     * Redirect to new URL
     * @param string $url URL to redirect to
     * @param int $status_code Status code to send (default: 301)
     * @return boolean Return false if parameter(s) is invalid, otherwise, the script is killed to allow redirection
     */
    function redirect( $url, $status_code = 301 ) {
        if ( !filter_var( $url, FILTER_VALIDATE_URL ) || ( $status_code < 300 || $status_code > 308 ) )
            return false;

        if (is_string($status_code))
		    $status_code = intval($status_code);

        if ( !headers_sent( ) ) {
            if (!function_exists('http_response_code')) {
                // If PHP version is less than 5.4
                header('Location: '.$url, true, $status_code);
            } else {
                http_response_code($status_code);
                header('Location: '.$url);
            }
        } else {
            // Prevent HTML from breaking
		    $url = htmlspecialchars( $url );

            echo '<script type="text/javascript">';
            echo 'window.location.href="'.$url.'";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
        }

        die();
    }
}

if ( !function_exists( 'verify_user' ) ) {
    /**
     * Make sure users logged in, otherwise, redirect them to login page
     */
    function verify_user( ) {
        global $site_url;

        if ( !SecureLogin::getInstance()->check_user( ) ) {
            redirect( $site_url . "/login.php" );
        }
    }
}

if ( !function_exists( 'get_option' ) ) {
    /**
     * Gets value for option
     * @param string $name Name to lookup
     * @return string|null Returns value as a string, otherwise null if name cannot be found
     */
    function get_option( $name ) {
        if ( !MySQL::getInstance()->select( 'options', array( 'name' => $name ), '', '0,1' ) )
            return null;
        
        return MySQL::getInstance()->arrayed_result['value'];
    }
}

if ( !function_exists( 'set_option' ) ) {
    /**
     * Sets option value
     * @param string $name Name for option
     * @param string $value Value for option
     * @return bool True if value was set, otherwise false if there was an error
     */
    function set_option( $name, $value ) {
        if ( !is_string( $value) )
            $value = strval( $value );
        
        $vars = array( 'name' => $name, 'value' => $value );
        
        if ( !MySQL::getInstance()->insert_or_update($vars, $vars, 'options') )
            return false;
        
        return true;
    }
}

if ( !function_exists( 'generate_csrf_token' ) ) {
    /**
     * Generates CSRF so it can be added to a form
     * @param bool $echo If true, echoes input field, otherwise, returns it (default: true)
     * @return string If $echo is false, returns input field
     */
    function generate_csrf_token( $echo = true ) {
        if ( Config::getInstance()->site->csrf == false )
            return;
        
        if ( isset( Session::getInstance()->token ) )
            $token = Session::getInstance()->token;
        else {
            $token = md5( uniqid( rand(), true ) );
            Session::getInstance()->token = $token;
        }

        $ret = '<input name="token" type="hidden" value="'.$token.'" />';
        
        if ( $echo )
            echo $ret;
        else
            return $ret;
    }
}

if ( !function_exists( 'verify_csrf_token' ) ) {
    /**
     * Verifies that CSRF token is valid 
     * @param bool $die_if_invalid If set to true, PHP will die if the token is invalid. Otherwise, this function will return true/false. The default is true.
     * @return bool Returns true if the CSRF token is valid or if CSRF validation is disabled, otherwise, false.
     */
    function verify_csrf_token( $die_if_invalid = true ) {
        if ( Config::getInstance()->site->csrf == false )
            return true;
        
        $is_valid = true;
        
        if ( empty( Session::getInstance()->token ) || empty( $_POST['token'] ) )
            $is_valid = false;
        else if ( $_POST['token'] != Session::getInstance()->token )
            $is_valid = false;

        if ( !$is_valid && $die_if_invalid )
			die( __( 'Cross-site request forgery token is invalid') );
		
		return $is_valid;
    }
}

if ( !function_exists( 'get_page_contents' ) ) {
    /**
     * Gets webpage contents using either cURL or file_get_contents()
     * @param string $url URL to retrieve
     * @param string $file_path File path to download to (default: '')
     * @return string|bool Returns webpage contents or true if URL was downloaded to file, otherwise false if an error occurred
     */
    function get_page_contents( $url, $file_path = '' ) {
        $ret = false;
        
        $is_gz = ( strpos( $url, '.gz' ) !== false ? true : false );

        if ( function_exists( 'curl_init' ) ) {
            $ch = curl_init( $url );

            if ( $file_path != '' ) {
                if ( $is_gz )
                    $fp = fopen( $file_path . '.gz', 'w' );
                else
                    $fp = fopen( $file_path, 'w' );
                if ( !$fp )
                    return false;

                curl_setopt( $ch, CURLOPT_FILE, $fp );
            }

            curl_setopt( $ch, CURLOPT_FAILONERROR, 1 );
            curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 1000 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            if ( !isset( $fp ) )
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

            $ret = curl_exec( $ch );

            if ( curl_errno( $ch ) )
                return false;

            if ( isset( $fp ) )
                fclose( $fp );
            curl_close( $ch );

            if ( $is_gz ) {
                copy( 'compress.zlib://' . $file_path . '.gz', $file_path );

                if ( file_exists( $file_path ) )
                    unlink( $file_path . '.gz' );
            }
        } else if ( ini_get('allow_url_fopen') == true ) {
            // Takes 2x as long as cURL

            if ( $is_gz )
                $url = 'compress.zlib://' . $url;

            if ( $file_path == '' ) {
                $ret = @file_get_contents( $url );

                if ( !is_string( $ret ) )
                    return false;
            } else {
                $ret = copy( $url, $file_path );
            }

        }

        return $ret;
    }
}

if ( !function_exists( 'is_geoip_update_available' ) ) {
    /**
     * Checks if GeoIP database is up to date
     * @return boolean True if update available, otherwise false if current version or error occurred
     */
    function is_geoip_update_available() {
        $last_checked = get_option( 'geoips_database_checked' );
        
        if ( $last_checked != null && ( time() - strtotime( $last_checked ) ) < strtotime( '+2 weeks', 0 ) )
            return false;
        
        $update_url = get_option( 'geoips_database_update_url' );
        $current_version = get_option( 'geoips_database_version' );

        $xml_contents = get_page_contents( $update_url );

        if ( $xml_contents == false )
            return false;

        $xml = simplexml_load_string( $xml_contents );

        $last_version = (string)$xml->version;
        $download_url = (string)$xml->download;

        set_option( 'geoips_database_checked', date( "Y-m-d" ) );

        if ( strtotime( $last_version ) > strtotime( $current_version ) ) {
        	Session::getInstance()->geoip_update = true;
            Session::getInstance()->geoip_update_url = $download_url;
            Session::getInstance()->geoip_database_ver = $last_version;
            
            return true;
        } else {
            return false;
        }
    }
}

if ( !function_exists( 'is_geoipv6_update_available' ) ) {
    /**
     * Checks if GeoIP database is up to date
     * @return boolean True if update available, otherwise false if current version or error occurred
     */
    function is_geoipv6_update_available() {
        $last_checked = get_option( 'geoips_database_v6_checked' );
        
        if ( $last_checked != null && ( time() - strtotime( $last_checked ) ) < strtotime( '+2 weeks', 0 ) )
            return false;
        
        $update_url = get_option( 'geoips_database_v6_update_url' );
        $current_version = get_option( 'geoips_database_v6_version' );

        $xml_contents = get_page_contents( $update_url );

        if ( $xml_contents == false )
            return false;

        $xml = simplexml_load_string( $xml_contents );

        $last_version = (string)$xml->version;
        $download_url = (string)$xml->download;

        set_option( 'geoips_database_v6_checked', date( "Y-m-d" ) );

        if ( strtotime( $last_version ) > strtotime( $current_version ) ) {
        	Session::getInstance()->geoip_update_v6 = true;
            Session::getInstance()->geoip_update_v6_url = $download_url;
            Session::getInstance()->geoip_database_v6_ver = $last_version;
            
            return true;
        } else {
            return false;
        }
    }
}

if ( !function_exists( 'download_geoip_update' ) ) {
    /**
     * Downloads and updates to latest GeoIP database
     * @return boolean True if update was successful, otherwise false
     */
    function download_geoip_update() {
    	if ( ( isset( Session::getInstance()->geoip_update ) ) && Session::getInstance()->geoip_update ) {
			$ret = true;
			
			if ( !isset( Session::getInstance()->geoip_update_url ) ) 
	            $ret = false;
	        
	        if ( !is_string( Session::getInstance()->geoip_update_url ) )
	            $ret = false;
	        
	        if ( $ret ) {
				$url = Session::getInstance()->geoip_update_url;
		        $dst_file = Config::getInstance()->site->geoip_path;

		        if ( get_page_contents( $url, $dst_file ) ) {
		            set_option( 'geoips_database_version', Session::getInstance()->geoip_database_ver );
		            
		            unset( Session::getInstance()->geoip_update );
		            unset( Session::getInstance()->geoip_database_ver );
		            unset( Session::getInstance()->geoip_update_url );
		        }
			}
		}
		
		if ( ( isset( Session::getInstance()->geoip_update_v6 ) ) && Session::getInstance()->geoip_update_v6 ) {
			$ret = true;
			
			if ( !isset( Session::getInstance()->geoip_update_v6_url ) ) 
	            $ret = false;
	        
	        if ( !is_string( Session::getInstance()->geoip_update_v6_url ) )
	            $ret = false;
	        
	        if ( $ret ) {
				$url = Session::getInstance()->geoip_update_v6_url;
		        $dst_file = Config::getInstance()->site->geoipv6_path;

		        if ( get_page_contents( $url, $dst_file ) ) {
		            set_option( 'geoips_database_v6_version', Session::getInstance()->geoip_database_v6_ver );
		            
		            unset( Session::getInstance()->geoip_update_v6 );
		            unset( Session::getInstance()->geoip_database_v6_ver );
		            unset( Session::getInstance()->geoip_update_v6_url );
		        }
			}
		}
        

        return $ret;
    }
}

if ( !function_exists( 'get_min_uri' ) ) {
    /**
     * Gets minified URI for specified group
     * @param string $group Group
     * @param bool $echo If true echoes URI, otherwise returns it (default: true)
     * @return string Returns minified URI if $echo is false
     */
    function get_min_uri( $group, $echo = true ) {
        global $site_url;

        $uri = Minify_getUri( $group, array( 'minAppUri' => $site_url . '/min', 'rewriteWorks' => false ) );
        
        if ( $echo )
            echo $uri;
        else
            return $uri;
    }
}

if ( !function_exists( 'send_mail' ) ) {
    /**
     * Sends mail using geek mail
     * @global PHPMailer $php_mailer Class for PHPMailer
     * @param string $to Email address to send to
     * @param string $subject Subject
     * @param string $message Message to send
     * @param string $attach Path of file to attach
     * @return bool Returns true if message was sent
     */
    function send_mail( $to, $subject, $message, $attach = '' ) {
        global $php_mailer;
        
        if ( !isset( $php_mailer ) ) {
			require_once( dirname(__FILE__).'/phpmailer/PHPMailerAutoload.php' );

			$php_mailer = new PHPMailer();
		}
		
		$send_method = get_option( 'mail_protocol' );
        
		if ( strcasecmp( $send_method, 'smtp' ) == 0 ) {
			$php_mailer->isSMTP();
			$php_mailer->Host = get_option( 'mail_smtp_server' );
			$php_mailer->Port = intval( get_option( 'mail_smtp_port' ) );
			
			$smtp_user = get_option( 'mail_smtp_user' );
			$smtp_pass = get_option( 'mail_smtp_pass' );
			
			if ( !empty( $smtp_user ) || !empty( $smtp_pass ) ) {
				$php_mailer->SMTPAuth = true;
				$php_mailer->Username = $smtp_user;
				$php_mailer->Password = $smtp_pass;
			} else {
				$php_mailer->SMTPAuth = false;
			}
		} else if ( strcasecmp( $send_method, 'sendmail' ) == 0 ) {
			$php_mailer->isSendmail();
			$php_mailer->Sendmail = get_option( 'mail_sendmail_path' );
		} else {
			$php_mailer->isMail();
		}
		
		$php_mailer->SetFrom( SITE_NOREPLYEMAIL, __( 'Little Software Stats' ) );
		$php_mailer->addAddress( $to );
		$php_mailer->Subject = $subject;
		$php_mailer->isHTML( false );
		$php_mailer->Body = $message;
		
		if ( !empty( $attach ) )
			$mail->addAttachment( $attach ); 
		
		$php_mailer->send();
    }
}

if ( !function_exists( 'get_language_by_lcid' ) ) {
    /**
     * Looks up LCID and returns information
     * @param int $lcid LCID to lookup
     * @return string Returns language name, otherwise, 'Unknown' if it couldnt be found 
     */
    function get_language_by_lcid( $lcid ) {
        if ( !is_numeric( $lcid ) )
            return __( 'Unknown' );
        
        $lcid = intval( $lcid );

        if ( MySQL::getInstance()->select( 'locales', array( 'LCID' => $lcid ), '', '0,1' ) )
            return MySQL::getInstance()->arrayed_result['DisplayName'];

        return __( 'Unknown' );
    }
}

if ( !function_exists( 'create_date_range_array' ) ) {
    /**
     * Takes two dates formatted as epoch time and creates an inclusive array of the dates between the from and to dates.
     * @param int $date_from Start date
     * @param int $date_to End date
     * @param string $by Time range (day, week, or month)
     * @return array|bool Array of date range, otherwise, false if arguments are invalid
     */
    function create_date_range_array( $date_from, $date_to, $by = '' ) {
        global $sanitized_input;

        $range = array();
        
        
        if (is_string( $date_from ) && !is_numeric( $date_from ) )
        	// Convert to unix time
        	$date_from = strtotime( $date_from );
        	
        if (is_string( $date_to ) && !is_numeric( $date_to ) )
        	// Convert to unix time
        	$date_to = strtotime( $date_to );
       
        if ( $by == '' )
            $by = $sanitized_input['graphBy'];

        // Make sure by argument is valid
        if ( $by != 'day' && $by != 'week' && $by != 'month' )
            return false;
        
        if ( $by == 'day' )
            $time_interval = 24 * 3600;
        elseif ( $by == 'week' )
            $time_interval = 7 * 24 * 3600;
        elseif ( $by == 'month' )
            $time_interval = 30 * 24 * 3600;  

        $diff = '+1 ' . $by;

        if ( $date_to >= $date_from ) {
            $range[] = $date_from; // first entry

            // Make sure we dont add any unneeded intervals
            if ( ( $date_to - $date_from ) > $time_interval ) {
                while ( $date_from < $date_to ) {
                    $date_from = strtotime( $diff, $date_from );
                    
                    if ( $date_from > $date_to ) {
                        $range[] = $date_to;
                        break;
                    }
                    
                    $range[] = $date_from;
                }
            }

            return $range;
        }

        return false;
    }
}

if ( !function_exists( 'page_title' ) ) {
    /**
     * Echoes page title
     */
    function page_title() {
        global $sanitized_input;

        $pages = array(
            'dashboard' => __( 'Dashboard' ),
            'appsettings' => __( 'Application Settings' ),
            'executions' => __( 'Executions' ),
            'installations' => __( 'Installations' ),
            'uninstallations' => __( 'Uninstallations' ),
            'versions' => __( 'Versions' ),
            'licenses' => __( 'Licenses' ),
            'averagetime' => __( 'Average Time' ),
            'loyaltytime' => __( 'Loyalty Time' ),
            'newvsreturning' => __( 'New vs. Returning' ),
            'bouncerate' => __( 'Bounce Rate' ),
            'events' => __( 'Events' ),
            'eventstiming' => __( 'Events Timing' ),
            'eventsvalue' => __( 'Events Value' ),
            'customdata' => __( 'Custom Data' ),
            'logs' => __( 'Logs' ),
            'exceptions' => __( 'Exceptions' ),
            'operatingsystems' => __( 'Operating Systems' ),
            'languages' => __( 'Languages' ),
            'cpus' => __( 'CPUs' ),
            'memory' => __( 'Memory' ),
            'screenresolutions' => __( 'Screen Resolutions' ),
            'pluginsandvms' => __( 'Plugins and VMs' ),
            'mapoverlay' => __( 'Map Overlay' ),
            'myaccount' => __( 'My Account' ),
            'add' => __( 'Add Application' ),
            'settings' => __( 'Settings' )
        );

        $page = $sanitized_input['page'];

        $page_title = 'Little Software Stats | ';

        if ( $page != 'settings' ) {
            $app_name = get_current_app_name();
            if ( $app_name != '' )
                $page_title .= $app_name . ' | ';
        }

        $page_title .= $pages[$page];

        echo $page_title;
    }
}

if ( !function_exists( 'get_page_url' ) ) {
    /**
     * Outputs page URL
     * @param string $page Page
     * @param bool $echo If true it echoes URL, otherwise, it returns the URL
     * @param boolean $encode_html If true, encodes HTML characters (default is true)
     * @return string If $echo is false, returns URL. Otherwise, it is echoed and not returned.
     */
    function get_page_url( $page, $echo = true, $encode_html = true ) {
        global $sanitized_input, $site_url;

        $id = urlencode( $sanitized_input['id'] );
        $ver = urlencode( $sanitized_input['ver'] );
        $graph_by = urlencode( $sanitized_input['graphBy'] );
        $start = urlencode( date( 'Y-m-d', $sanitized_input['start'] ) );
        $end = urlencode( date( 'Y-m-d', $sanitized_input['end'] ) );

        $url = $site_url;

        if ( get_option( 'site_rewrite' ) == 'true' )
            $url .= '/'.$id.'/'.$ver.'/'.$graph_by.'/'.$page.'/'.$start.'/'.$end;
        else    
            $url .= '/index.php?id='.$id.'&ver='.$ver.'&graphBy='.$graph_by.'&page='.$page.'&start='.$start.'&end='.$end;
            
        if ( $encode_html )
        	$url = htmlspecialchars( $url );

        if ( $echo )
            echo $url;
        else
            return $url;
    }
}

if ( !function_exists( 'get_file_url' ) ) {
    /**
     * Returns file URL
     * @param string $file Relative path of file
     * @param array|string $query If not empty, the query (including the ?) is appended to the file URL. If it is an array and not empty, it is converted to an array with http_build_query(). (default is a empty string)
     * @param boolean $encode_html If true, encodes HTML characters (default is true)
     * @return string File URL
     */
    function get_file_url( $file, $query = '', $encode_html = true ) {
        global $site_url;

        if ( !file_exists( Config::getInstance()->site->path . '/' . ltrim( $file, '/' ) ) )
            return '';
            
        $query_str = '';
            
        if ( !empty( $query ) ) {
			if ( is_array( $query ) )
				$query_str = http_build_query( $query );
			else if ( is_string( $query ) )
				$query_str = $query;
		}
            
        if ( $encode_html )
        	$ret = htmlspecialchars( $site_url . '/' . ltrim( $file, '/' ) . $query_str );
        else
        	$ret = $site_url . '/' . ltrim( $file, '/' ) . $query_str;

        return $ret;
    }
}

if ( !function_exists( 'file_url' ) ) {
	/**
     * Echoes file URL
     * @param string $file Relative path of file
     * @param array|string $query If not empty, the query (including the ?) is appended to the file URL. If it is an array and not empty, it is converted to an array with http_build_query(). (default is a empty string)
     * @param boolean $encode_html If true, encodes HTML characters (default is true)
     */
    function file_url( $file, $query = '', $encode_html = true ) {
        echo get_file_url( $file, $query, $encode_html );
    }
}

if ( !function_exists( 'get_applications' ) ) {
    /**
     * Gets applications info and sets id if its not already
     * @global bool $needs_refresh Variable for refresh page
     * @return array Applications
     */
    function get_applications() {
        global $needs_refresh, $sanitized_input;

        MySQL::getInstance()->select( "applications" );

        $apps = array();

        if ( MySQL::getInstance()->records == 1 ) {            
            $apps[] = array( 'AppName' => MySQL::getInstance()->arrayed_result['ApplicationName'], 'AppId' => MySQL::getInstance()->arrayed_result['ApplicationId'] );
        } else if ( MySQL::getInstance()->records > 1 ) {
            foreach ( MySQL::getInstance()->arrayed_results as $row ) {
                $apps[] = array( 'AppName' => $row['ApplicationName'], 'AppId' => $row['ApplicationId'] );
            }
        } else {
            // No records found
        }

        return $apps;
    }
}

if ( !function_exists( 'app_url' ) ) {
    /**
     * Echoes URL to application ID
     * @param string $id Application ID
     */
    function app_url( $id ) {
        global $sanitized_input, $site_url;

        $rewrite_enabled = get_option( 'site_rewrite' );
        $page = $sanitized_input['page'];
        $ver = $sanitized_input['ver'];
        $graph_by = $sanitized_input['graphBy'];
        $start = date( 'Y-m-d', $sanitized_input['start'] );
        $end = date( 'Y-m-d', $sanitized_input['end'] );
        
        if ( $rewrite_enabled == 'true' )
            echo $site_url . '/' . $id . '/' . $ver . '/' . $sanitized_input['graphBy'] . '/' . $page . '/' . $start . '/' . $end;
        else 
            echo $site_url . '/?id='.$id.'&ver='.$ver.'&graphBy='.$sanitized_input['graphBy'].'&page='.$page.'&start='.$start.'&end='.$end;
    }
}

if ( !function_exists( 'get_current_app_name' ) ) {
    /**
     * Gets current application name
     * @global array $apps Applications
     * @return string Application Name, otherwise, returns nothing if not found
     */
    function get_current_app_name() {
        global $apps, $sanitized_input;
        
        if ( !isset( $apps ) )
            $apps = get_applications();
        
        foreach ( $apps as $app ) {
            if ( $app['AppId'] == $sanitized_input['id'] )
                return $app['AppName'];
        }
        
        return '';
    }
}

if ( !function_exists( 'app_versions' ) ) {
    /**
     * Gets application versions
     * @return string HTML Code
     */
    function app_versions() {
        global $sanitized_input;

        $html = '<select id="versions" class="styledselect">';
        $html .= '<option value="all" ' . ( ( $sanitized_input['ver'] == 'all' ) ? ( 'selected' ) : ( '' ) ) . '>'. __('All Versions') . '</option>';

        MySQL::getInstance()->select_distinct( 'ApplicationVersion', 'sessions', array( 'ApplicationId' => $sanitized_input['id'] ), 'ApplicationVersion' );

        if ( MySQL::getInstance()->records == 1 ) {
            $ver = MySQL::getInstance()->arrayed_result['ApplicationVersion'];
            $html .= '<option value="'.$ver.'" '.( ( $sanitized_input['ver'] == $ver ) ? ( 'selected' ) : ( '' ) ).'>'.$ver.'</option>';
        } else if ( MySQL::getInstance()->records > 1 ) {
            foreach ( MySQL::getInstance()->arrayed_results as $row ) {
                $ver = $row['ApplicationVersion'];
                $html .= '<option value="'.$ver.'" '.( ( $sanitized_input['ver'] == $ver ) ? ( 'selected' ) : ( '' ) ).'>'.$ver.'</option>';
            } 
        }

        $html .= '</select>';

        echo $html;
    }
}

if ( !function_exists( 'get_unique_user_info' ) ) {
    /**
     * Get user data for unique ID
     * @param string $unique_id Unique ID
     * @return array|bool Returns user data or false if nothing found
     */
    function get_unique_user_info( $unique_id ) {
        MySQL::getInstance()->select( 'uniqueusers', array( 'UniqueUserId' => $unique_id ), '', '0,1' );

        if ( MySQL::getInstance()->records == 0 )
            return false;

        return MySQL::getInstance()->arrayed_result;
    }
}

if ( !function_exists( 'get_unique_user_from_session_id' ) ) {
    /**
     * Get unique ID from session ID
     * @param string $session_id Session ID
     * @return array|bool Returns unique ID or false if nothing found
     */
    function get_unique_user_from_session_id( $session_id ) {
    	MySQL::getInstance()->select( 'sessions', array( 'SessionId' => $session_id ), '', '0,1' );

        if ( MySQL::getInstance()->records == 0 )
            return false;

        return MySQL::getInstance()->arrayed_result['UniqueUserId'];
    }
}

if ( !function_exists( 'calculate_percent' ) ) {
    /**
     * Calculates a percent
     * @param int $fraction Fraction
     * @param int $total Total
     * @param int $round Decimal to round to (default: 2)
     * @return int Percent on success, otherwise 0
     */
    function calculate_percent( $fraction, $total, $round = 2 ) {
        if ( !is_numeric( $fraction ) || !is_numeric( $total ) )
            return 0;
        
        if ( $total == 0 )
            return 0;
        
        $fraction = intval( $fraction );
        $total = intval( $total );

        $percent = ( ( $fraction / $total ) * 100 );

        return round( $percent, $round );
    }
}

if ( !function_exists( 'calculate_percentage_increase' ) ) {
    /**
     * Calculates percentage increase from last month period
     * @param int $last_month_period Total from last month period
     * @param int $current_period Total from current period
     * @return int Percentage increase, otherwise, 0 if not enough data available
     */
    function calculate_percentage_increase( $last_month_period, $current_period ) {
        if ( !is_numeric( $last_month_period ) || !is_numeric( $current_period ) )
            return 0;

        $last_month_period = intval( $last_month_period );
        $current_period = intval( $current_period );

        if ( $last_month_period == 0 ) {
            if ( $current_period > 0 )
                return 100;
            else
                return 0;
        }

        $difference = ( ( ( $current_period - $last_month_period ) / $last_month_period ) * 100 );

        return round( $difference );
    }
}

if ( !function_exists( 'get_time_duration' ) ) {
    /**
     * Converts time duration to a string
     * @param int $duration Time duration (in seconds)
     * @return string Time duration
     */
    function get_time_duration( $duration ) {
        if ( !is_numeric( $duration ) )
            return '0 s';
        
        $duration = intval( $duration );
        
        $hours = $minutes = $seconds = 0;

        while($duration >= 3600) {
            $hours++;
            $duration -= 3600;
        }

        while($duration >= 60) {
            $minutes++;
            $duration -= 60;
        }

        $seconds = round( $duration );
        
        return ( ( $hours > 0 ) ? ( $hours . 'h ' ) : ( '' ) ) . ( ( $minutes > 0 ) ? ( $minutes . 'm ' ) : ( '' ) ) . $seconds . 's';
    }
}

if ( !function_exists( 'generate_app_id' ) ) {
    /**
     * Generates application ID
     * @return string Application ID
     */
    function generate_app_id() {
        $salt = "abcdef0123456789";
        $salt_len = strlen( $salt );
        
        $app_id = '';
        
        mt_srand(); 

        for ( $i = 0; $i < 32; $i++ ) { 
            $chr = substr( $salt, mt_rand( 0, $salt_len - 1 ), 1 ); 
            $app_id .= $chr;
        } 
        
        return $app_id;
    }
}

if ( !function_exists( 'convert_size_to_string' ) ) {
    /**
     * Converts size to nearest decimal and returns formatted string
     * (Based on Chris Jester-Young's implementation)
     * @param int $size Size (in bytes)
     * @param int $precision Number of decimal digits to round to (default: 2)
     * @return string|bool Returns formatted string, otherwise false if size is not a number
     */
    function convert_size_to_string( $size, $precision = 2) {
        if ( !is_numeric( $size ) )
            return false;
        
        $base = log( $size ) / log( 1024 );
        $suffixes = array( ' B', ' KB', ' MB', ' GB', ' TB' );   

        return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[floor( $base )];
    }
}

if ( !function_exists( 'convert_area_chart_data_to_json' ) ) {
    /**
     * Converts area chart data to JSON so it can be parsed by HighCharts line chart
     * @param array $chart_data Array containing chart data
     * @return string|bool Returns JSON string or false if $chart_data isnt an array 
     */
    function convert_area_chart_data_to_json( $chart_data ) {
        if ( !is_array( $chart_data ) )
            return false;
        
        $json = array();
        
        foreach ( $chart_data as $name => $data ) {
            $json[] = array( 'name' => $name, 'data' => $data );
        }
        
        return json_encode( $json );
    }
}

if ( !function_exists( 'convert_line_chart_data_to_array' ) ) {
    /**
     * Converts line chart data to an array so it can be parsed by HighChartsPHP
     * @param array $chart_data Array containing chart data
     * @return array|bool Returns array or false if $chart_data isnt an array 
     */
    function convert_line_chart_data_to_array( $chart_data ) {
        if ( !is_array( $chart_data ) )
            return false;
        
        $arr = array();
        
        foreach ( $chart_data as $name => $data ) {
            $arr[] = array( 'name' => $name, 'data' => $data );
        }
        
        return $arr;
    }
}

if ( !function_exists( 'convert_pie_chart_data_to_json' ) ) {
    /**
     * Converts pie chart data to JSON so it can be parsed by HighCharts line chart
     * @param array $chart_data Array containing pie chart data
     * @return string|bool Returns JSON string or false if $chart_data isnt an array 
     */
    function convert_pie_chart_data_to_json( $chart_data ) {
        if ( !is_array( $chart_data ) )
            return false;
        
        $json = array();
        
        foreach ( $chart_data as $name => $total ) {
            if ( is_int( $name ) )
                $name = strval( $name );
            
            if ( !is_int( $total ) )
                $total = intval ( $total );
            
            $json[] = array( $name, $total );
        }
        
        return json_encode( $json );
    }
}

if ( !function_exists( 'convert_pie_chart_data_to_array' ) ) {
    /**
     * Converts pie chart data to array so it can be parsed by HighChartsPHP
     * @param array $chart_data Array containing pie chart data
     * @return array|bool Returns array or false if $chart_data isnt an array 
     */
    function convert_pie_chart_data_to_array( $chart_data ) {
        if ( !is_array( $chart_data ) )
            return false;
        
        $arr = array();

        foreach ( $chart_data as $name => $total ) {
            if ( is_int( $name ) )
                $name = strval( $name );
            
            if ( !is_int( $total ) )
                $total = intval ( $total );
            
            $arr[] = array( $name, $total );
        }
        
        return $arr;
    }
}

if ( !function_exists( 'show_msg_box' ) ) {
    /**
     * Generates a message box
     * @param string $text Caption of message box
     * @param string $type Type of message box (green, red, or yellow)
     * @param bool $echo If true, echoes HTML code, otherwise, returns it (default is true)
     * @param bool $encode_html If true, the $text variable is encoded with htmlspecialchars() (default is true)
     * @return string HTML code (or nothing if type was invalid)
     */
    function show_msg_box( $text, $type, $echo = true, $encode_html = true ) {
        $valid_types = array( 'green', 'red', 'yellow' );

        $ret = '';

        if ( in_array( $type, $valid_types ) && ( is_string( $type ) ) ) {
        	if ( $encode_html )
        		$text = htmlspecialchars( $text );
        	
            $ret = "<div id=\"message-".$type."\">
                    <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
                    <tbody>
                    <tr>
                            <td class=\"".$type."-left\">".$text."</td>
                            <td class=\"".$type."-right\"><a class=\"close-".$type."\"><img alt=\"Close\" src=\"".get_file_url( '/images/table/icon_close_'.$type.'.gif' )."\"></a></td>
                    </tr>
                    </tbody>
                    </table>
                    </div>";
        }

        if ( $echo )
            echo $ret;
        else
            return $ret;
    }
}

if ( !function_exists( 'is_page_current' ) ) {
    /**
     * If current page, echoes class so it is selected
     * @param string $page Page name
     */
    function is_page_current( $page ) {
        global $sanitized_input;

        if ( $page == $sanitized_input['page'] )
            echo "sub_show";
    }
}

if ( !function_exists( 'check_ip_address' ) ) {
	/**
	 * Checks if IP address is valid and not a private IP
	 * @param string $ip IP Address
	 * @return boolean Returns true if IP is valid, otherwise, false
	 */
	function check_ip_address( $ip ) {
		if ( !function_exists( 'filter_var' ) ) {
	        $ip_bin = inet_pton( $ip );
	        
	        if ( $ip_bin === false )
	        	// Can return false if $ip is IPv6 and its not supported by PHP
	        	return false;
	        	
	        if (strlen($ip_bin) == 4) {
				// IPv4
				$private_ips = array(
	                array( "0.0.0.0", "2.255.255.255" ),
	                array( "10.0.0.0", "10.255.255.255" ),
	                array( "127.0.0.0", "127.255.255.255" ),
	                array( "169.254.0.0", "169.254.255.255" ),
	                array( "172.16.0.0", "172.31.255.255" ),
	                array( "192.0.2.0", "192.0.2.255" ),
	                array( "192.168.0.0", "192.168.255.255" ),
	                array( "255.255.255.0", "255.255.255.255" )
	            );
			} elseif (strlen($ip_bin) == 16) {
				// IPv6
				$private_ips = array(
					array( "::", "::" ),
					array( "::1", "::1" ),
					array( "fc00::", "fdff:ffff:ffff:ffff:ffff:ffff:ffff:ffff" ),
					array( "fe80::", "febf:ffff:ffff:ffff:ffff:ffff:ffff:ffff"),
					array( "::ffff:0:0", "::ffff:ffff:ffff"),
					array( "::ffff:0:0:0", "::ffff:0:ffff:ffff"),
					array( "64:ff9b::", "64:ff9b::ffff:ffff"),
					array( "2002::", "2002:ffff:ffff:ffff:ffff:ffff:ffff:ffff"),
					array( "2001::", "2001:0:ffff:ffff:ffff:ffff:ffff:ffff"),
					array( "2001:2::", "2001:2:0:ffff:ffff:ffff:ffff:ffff" ),
					array( "2001:10::", "2001:1f:ffff:ffff:ffff:ffff:ffff:ffff" ),
					array( "2001:db8::", "2001:db8:ffff:ffff:ffff:ffff:ffff:ffff" ),
					array( "100::", "100::ffff:ffff:ffff:ffff" ),
	            );
			} else {
				return false;
			}
			
			foreach ( $private_ips as $range ) {
	            $min = inet_pton( $range[0] );
	            $max = inet_pton( $range[1] );
	            if ( $ip_bin >= $min && $ip_bin <= $max )
	                return false;
	        }
		
			return true;
		} else {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_IPV6|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE ) !== false ) 
				return true;
		}
	    
	    return false;
	}
}

if ( !function_exists( 'get_ip_address' ) ) {
	/**
	 * Gets ip address of client
	 * @return string IP Address 
	 */
	function get_ip_address() {
		if ( !isset( $_SERVER['REMOTE_ADDR'] ) ) {
			// Most likely being run from command line, use fake IP address
			
			mt_srand( );
			
			return mt_rand(1, 255) . '.' . mt_rand(1, 255) . '.' . mt_rand(1, 255) . '.' . mt_rand(1, 255);
		}
		
	    // Get ip address
	    if ( Config::getInstance()->site->header_ip_address ) {
			if ( ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) && $this->check_ip_address( $_SERVER['HTTP_CLIENT_IP'] ) )
		        return $_SERVER['HTTP_CLIENT_IP'];
		    
		    if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		        $ip_array = explode( ",", $_SERVER['HTTP_X_FORWARDED_FOR'] );
		        if ( $this->check_ip_address( trim( $ip_array[count( $ip_array ) - 1] ) ) )
		            return trim( $ip_array[count( $ip_array ) - 1] );
		    }
		    
		    if ( ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) && $this->check_ip_address( $_SERVER['HTTP_X_FORWARDED'] ) )
		        return $_SERVER['HTTP_X_FORWARDED'];
		    
		    if ( ( isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) ) && $this->check_ip_address( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) )
		        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		    
		    if ( ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) && $this->check_ip_address( $_SERVER['HTTP_FORWARDED_FOR'] ) )
		        return $_SERVER['HTTP_FORWARDED_FOR'];
		    
		    if ( ( isset( $_SERVER['HTTP_FORWARDED'] ) ) && $this->check_ip_address( $_SERVER['HTTP_FORWARDED'] ) )
		        return $_SERVER['HTTP_FORWARDED'];
		}

	    // If all other IP addresses are invalid or header specified IP addresses aren't used -> Go with REMOTE_ADDR
	    return $_SERVER['REMOTE_ADDR'];
	}
}

if ( !function_exists( 'lss_exit' ) ) {
	/**
	* Exits Little Software Stats cleanly
	* @param string $output Text to output upon exitting (optional)
	* 
	* @return
	*/
	function lss_exit($output = null) {
		MySQL::getInstance()->close();
		
		if ($output != null)
			exit( $output );
		else
			exit();
	}	
}

if ( defined( 'LSS_API' ) ) {
	if ( !function_exists( 'parse_data' ) ) {
		/**
		 * Parses data
		 * @param array $data Array containing parsed data
		 * @return int Returns status code
		 */
		function parse_data( $data ) { 
		    $ret = '';
		    
		    if ( isset( $data['ID'] ) )
		        $data['ID'] = strtoupper( $data['ID'] );
		    
		    if ( isset( $data['ss'] ) )
		        $data['ss'] = strtoupper( $data['ss'] );
		    
		    switch ( $data['tp'] ) {
		        // Start App
		        case "strApp": 
		            $ret = API::getInstance()->start_app( $data['aid'], $data['aver'], $data['ID'], $data['ss'], $data['ts'],
		                    $data['osv'], $data['ossp'], $data['osar'], $data['osjv'],
		                    $data['osnet'], $data['osnsp'], $data['oslng'], $data['osscn'],
		                    $data['cnm'], $data['cbr'], $data['cfr'], $data['ccr'],
		                    $data['car'], $data['mtt'], $data['mfr'], $data['dtt'], $data['dfr'] );
		            break;
		        // Stop App
		        case "stApp":
		            $ret = API::getInstance()->stop_app( $data['ts'], $data['ss'] );
		            break;
		        // Event
		        case "ev":
		            $ret = API::getInstance()->event( $data['ts'], $data['ss'], $data['ca'], $data['nm'] );
		            break;
		        // Event Value
		        case "evV":
		            $ret = API::getInstance()->event_value( $data['ts'], $data['ss'], $data['ca'], $data['nm'], $data['vl'] );
		            break;
		        // Event Period
		        case "evP":
		            $ret = API::getInstance()->event_period( $data['ts'], $data['ss'], $data['ca'], $data['nm'], $data['tm'], $data['ec'] );
		            break;
		        // Log
		        case "lg":
		            $ret = API::getInstance()->log( $data['ts'], $data['ss'], $data['ms'] );
		            break;
		        // Custom Data
		        case "ctD":
		            $ret = API::getInstance()->custom_data( $data['ts'], $data['ss'], $data['nm'], $data['vl'] );
		            break;
		        // Exception
		        case "exC":
		            $ret = API::getInstance()->exception( $data['ts'], $data['ss'], $data['msg'], $data['stk'], $data['src'], $data['tgs'] );
		            break;
		        // Install
		        case "ist":
		            $ret = API::getInstance()->install( $data['ts'], $data['ss'], $data['aid'], $data['aver'] );
		            break;
		        // Uninstall
		        case "ust":
		            $ret = API::getInstance()->uninstall( $data['ts'], $data['ss'], $data['aid'], $data['aver'] );
		            break;
		        // No event found
		        default:
		            break;
		    }
		    
		    return $ret;
		}
	}

	if ( !function_exists('get_error' ) ) {
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
		    
		    if (!isset($type)) 
		    	$type = ( ( isset( $_GET['type'] ) ) ? ( strtolower( $_GET['type'] ) ) : ( 'json' ) );
		    
		    $errors = array(
		        1 => 'Success',
		        -7 => 'Unable to connect to database',
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
	}

}