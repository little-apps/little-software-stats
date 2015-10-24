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
 * @since		Version 0.2
 */

// Set to true to display warnings
define( 'DISPLAY_WARNINGS', false );

function error_handler($errno, $errstr, $errfile, $errline) {
    global $errors;

    $errors[] = 'The following warning was given: ' . $errstr;
}

if ( DISPLAY_WARNINGS )
    set_error_handler( 'error_handler', E_WARNING | E_USER_WARNING );

session_start();

$warnings = array();
$errors = array();

// Import batch sql data
function db_import_sql( $sql ) {
    global $errors, $engines;

    $queries = array();
    $query = '';
    $comment = false;
    $replace = array( '{:db_prefix}' => Config::getInstance()->mysql->prefix, '{:db_charset}' => 'utf8', '{:db_engine}' => ( in_array('innodb', $engines) ? 'InnoDB' : 'MyISAM' ) );

    // read file into array
    $lines = explode("\n", $sql);

    // does array have anything in it?
    if ( is_array($lines) ) {
        // loop through sql array
        foreach ( $lines as $line ) {
            $line = trim($line);
            if ( $line ) {

                // is this a one line comment?
                if ( strpos($line, '--') !== 0 && strpos($line, '#') !== 0 ) {

                    // is this a multi line comment?
                    if ( strpos($line, '/*') === 0 ) {
                        $comment = true;
                    }

                    // are we inside a multi line comment?
                    if ( !$comment ) {

                        // append query
                        $query .= $line;

                        // is this the end of the query?
                        if ( substr(rtrim($query), -1) == ';' ) {

                            // do we need to replace anything?
                            if ( $replace ) {
                                // loop through the replacement array
                                foreach ( $replace as $replace_from => $replace_to ) {
                                    // replace values
                                    $query = preg_replace('#'.preg_quote($replace_from, '#').'#i', $replace_to, $query);
                                }
                            }

                            // truncate semi column
                            $query = rtrim($query, ';');

                            // run query
                            if ( !MySQL::getInstance()->execute_sql( $query ) ) {
                                return "Execute failed: " . MySQL::getInstance()->last_error;
                            }

                            // reset query
                            $query = '';
                        }
                    }

                    // is this the end of a multi line comment?
                    if ( substr(rtrim($line), -2) == '*/' ) {
                        $comment = false;
                    }
                }
            }
        }

        return true;
    } else {
        return "SQL is empty";
    }

    return false;
}

function remove_files($files) {
    if (empty($files))
        return false;

    if (!is_array($files))
        return false;

    foreach ($files as $file) {
        $file = realpath( $file );

        if ( !file_exists( $file ) ) {
            trigger_error( 'Path "' . $file . '" could not be found', E_USER_WARNING );
            continue;
        }

        if ( !is_file( $file ) ) {
            trigger_error( 'Path "' . $file . '" is not a file and cannot be deleted', E_USER_WARNING );
            continue;
        }

        unlink( $file );
    }

    return true;
}

// Get supported engines
function get_engines() {
	global $errors, $engines;

	if ( MySQL::getInstance()->execute_sql("SHOW ENGINES") ) {
		$rows = array();

		if( MySQL::getInstance()->records == 1 )
			$rows[] = $ret = MySQL::getInstance()->array_result();
		else if ( MySQL::getInstance()->records > 1 )
			$rows = $ret = MySQL::getInstance()->array_results();

		if ($ret === false) {
			return false;
		}

		if (count($rows) > 0) {
			foreach ($rows as $row) {
				if ( isset($row['Engine']) && $row['Engine'] && isset($row['Support']) && (strcasecmp($row['Support'], 'yes') == 0 || strcasecmp($row['Support'], 'default') == 0) ) {
					$engines[] = strtolower($row['Engine']);
				}
			}

			return true;
		}
	}

	return false;
}

function pre_upgrade_output() {
	global $preupgrade_outputs;
	
	foreach ($preupgrade_outputs as $func) {
		if (is_callable($func))
			call_user_func( $func );
	}
}

function check_pre_upgrade_needed() {
	global $preupgrade_funcs, $preupgrade_outputs;

	$preupgrade_funcs = array();
	$preupgrade_outputs = array();
	
	// Check if v0.1 config
	$config = include( ROOTDIR . '/inc/config.php' );

	if ($config === 1) {
		$preupgrade_funcs[] = 'v02_pre_upgrade';
		$preupgrade_outputs[] = 'v02_pre_upgrade_output';
	}

}

function v02_pre_upgrade_get_users() {
	$select_name = 'user[login_id]';
	$input_name_login = 'user[login_input]';
	$input_name_email = 'user[email]';
	
	$input_id = ( !empty( $_POST['user']['login_id'] ) ? $_POST['user']['login_id'] : '' );
	$input_login = ( !empty( $_POST['user']['login_input'] ) ? htmlspecialchars( trim ( $_POST['user']['login_input'] ) ) : '' );
	$input_email = ( !empty( $_POST['user']['email'] ) ? htmlspecialchars( trim ( $_POST['user']['email'] ) ) : '' );
	
	if ( function_exists( 'mysqli_connect' ) ) {
		$db = mysqli_connect( MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB );
		
		if ( mysqli_connect_error() ) {
			echo '<li><label for="'.$input_name_login.'">Username: </label><input name="'.$input_name_login.'" type="text" value="'.$input_login.'" /></li>';
			echo '<li><label for="'.$input_name_email.'">User email: </label><input name="'.$input_name_email.'" type="text" value="'.$input_email.'" /></li>';
			return;
		}
		
		$result = $db->query( 'SELECT UserId, UserName FROM ' . MYSQL_PREFIX . 'users' );
		
		if ( !is_a( $result, 'mysqli_result' ) ) {
			echo '<li><label for="'.$input_name_login.'">Username: </label><input name="'.$input_name_login.'" type="text" value="'.$input_login.'" /></li>';
			echo '<li><label for="'.$input_name_email.'">User email: </label><input name="'.$input_name_email.'" type="text" value="'.$input_email.'" /></li>';
			return;
		}
		
		if ( $result->num_rows == 0 ) {
			echo '<li><label for="'.$input_name_login.'">Username: </label><input name="'.$input_name_login.'" type="text" value="'.$input_login.'" /></li>';
			echo '<li><label for="'.$input_name_email.'">User email: </label><input name="'.$input_name_email.'" type="text" value="'.$input_email.'" /></li>';
			return;
		}
		
		echo '<li>';
		echo '<label for="'.$select_name.'">User: </label>';
		echo '<select name="'.$select_name.'">';
		
		while ( $row = $result->fetch_assoc() ) {
			echo '<option value="' . htmlspecialchars( $row['UserId'] ) . '"'. ( $input_id == $row['UserId'] ? ' selected' : '' ) .'>' . htmlspecialchars( $row['UserName'] ) . '</option>';
		}
		
		echo '</select>';
		echo '</select>';
		echo '</li>';
	} else {
		if ( !mysql_connect( MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB ) ) {
			echo '<li><label for="'.$input_name_login.'">Username: </label><input name="'.$input_name_login.'" type="text" value="'.$input_login.'" /></li>';
			echo '<li><label for="'.$input_name_email.'">User email: </label><input name="'.$input_name_email.'" type="text" value="'.$input_email.'" /></li>';
			return;
		}
		
		$result = mysql_query(  'SELECT UserId, UserName FROM ' . MYSQL_PREFIX . 'users' );
		
		if ( !$result ) {
			echo '<li><label for="'.$input_name_login.'">Username: </label><input name="'.$input_name_login.'" type="text" value="'.$input_login.'" /></li>';
			echo '<li><label for="'.$input_name_email.'">User email: </label><input name="'.$input_name_email.'" type="text" value="'.$input_email.'" /></li>';
			return;
		}
		
		echo '<li>';
		echo '<label for="'.$select_name.'">User: </label>';
		echo '<select name="'.$select_name.'">';
		
		while ( $row = mysql_fetch_assoc($result) ) {
			echo '<option value="' . htmlspecialchars( $row['UserId'] ) . '"'. ( $input_id == $row['UserId'] ? ' selected' : '' ) .'>' . htmlspecialchars( $row['UserName'] ) . '</option>';
		}
		
		echo '</select>';
		echo '</li>';
	}
}

function v02_pre_upgrade_output() {
	$config = v02_pre_upgrade_config( ( empty( $_POST ) ? true : false ) ); 
?>
	<h2>Please ensure the configuration below is correct</h2>
	        	
	<h3>Folder Settings</h3>
	<ul>
		<li><label for="site[url]">URL: </label><input name="site[url]" type="text" value="<?php echo htmlspecialchars($config['site']['url']) ?>" /></li>
		<li><label for="site[path]">Folder: </label><input name="site[path]" type="text" value="<?php echo htmlspecialchars($config['site']['path']) ?>" /></li>
	</ul>
		
	<h3>Site Settings</h3>
	<ul>
		<li><label for="site[name]">Name (optional): </label><input name="site[name]" type="text" value="<?php echo htmlspecialchars($config['site']['name']) ?>" /></li>
		<li><label for="site[noreplyemail]">No-reply Email (optional): </label><input name="site[noreplyemail]" type="text" value="<?php echo htmlspecialchars($config['site']['noreplyemail']) ?>" /></li>
		<li><label for="site[geoip_path]">GeoIP Path: </label><input name="site[geoip_path]" type="text" value="<?php echo htmlspecialchars($config['site']['geoip_path']) ?>" /></li>
		<li><label for="site[geoipv6_path]">GeoIPv6 Path: </label><input name="site[geoipv6_path]" type="text" value="<?php echo htmlspecialchars($config['site']['geoipv6_path']) ?>" /></li>
		<li><label for="site[debug]">Enable Debugging: </label><input name="site[debug]" type="checkbox" <?php echo ( !empty( $config['site']['debug'] ) ? 'checked' : '' ); ?> /></li>
		<li><label for="site[csrf]">CSRF Protection: </label><input name="site[csrf]" type="checkbox" <?php echo ( !empty( $config['site']['csrf'] ) ? 'checked' : '' ); ?> /></li>
		<li><label for="site[header_ip_address]">Allow Header IP Address: </label><input name="site[header_ip_address]" type="checkbox" <?php echo ( !empty( $config['site']['header_ip_address'] ) ? 'checked' : '' ); ?> /></li>
	</ul>
		
	<h3>MySQL Settings</h3>
	<ul>
		<li><label for="mysql[host]">Host: </label><input name="mysql[host]" type="text" value="<?php echo htmlspecialchars($config['mysql']['host']) ?>" /></li>
		<li><label for="mysql[user]">Username: </label><input name="mysql[user]" type="text" value="<?php echo htmlspecialchars($config['mysql']['user']) ?>" /></li>
		<li><label for="mysql[pass]">Password: </label><input name="mysql[pass]" type="text" value="<?php echo htmlspecialchars($config['mysql']['pass']) ?>" /></li>
		<li><label for="mysql[db]">Database: </label><input name="mysql[db]" type="text" value="<?php echo htmlspecialchars($config['mysql']['db']) ?>" /></li>
		<li><label for="mysql[prefix]">Prefix: </label><input name="mysql[prefix]" type="text" value="<?php echo htmlspecialchars($config['mysql']['prefix']) ?>" /></li>
		<li><label for="mysql[persistent]">Persistent Connection: </label><input name="mysql[persistent]" type="checkbox" <?php echo ( !empty( $config['mysql']['persistent'] ) ? 'checked' : '' ); ?> /></li>
	</ul>
	
	<h3>User Settings</h3>
	<p>This version uses PHP's built-in password hashing functions. It is HIGHLY recommended that you enter a new password below in order to ensure compatiability and security.</p>
	<ul>
		<?php v02_pre_upgrade_get_users(); ?>
		<li><label for="user[pass]">New Password: </label><input name="user[pass]" type="password" value="" /></li>
		<li><label for="user[pass_verify]">Confirm Password: </label><input name="user[pass_verify]" type="password" value="" /></li>
	</ul>
<?php
}

function v02_pre_upgrade_config($use_defines) {
	// Upgrade config
	$config_default =
	        array(
				'site' => array(
					'url' => '',
					'path' => '',
					'geoip_path' => '',
					'geoipv6_path' => '',
					'debug' => false,
					'csrf' => true,
					'header_ip_address' => true
				),
				'mysql' => array(
					'host' => '',
					'user' => '',
					'pass' => '',
					'db' => '',
					'prefix' => '',
					'persistent' => false
				)
			);


	if ( $use_defines ) {
 	    if ( !defined( 'SITE_GEOIP_PATH' ) )
		    define( 'SITE_GEOIP_PATH', realpath( dirname( __FILE__ ) . '/../geoipdb/GeoIP.dat' ) );

    	if ( !defined( 'SITE_GEOIPV6_PATH' ) ) {
    		$geoips_dir = dirname( SITE_GEOIP_PATH );

    		define( 'SITE_GEOIPV6_PATH', rtrim( $geoips_dir, '/' ) . '/GeoIPv6.dat' );
    	}

		// Convert config file from defines to array
	    $config_new =
	        array(
				'site' => array(
					'url' => strval( SITE_URL ),
					'path' => strval( SITE_PATH ),
					'geoip_path' => strval( SITE_GEOIP_PATH ),
					'geoipv6_path' => strval( SITE_GEOIPV6_PATH ),
					'debug' => ( defined( 'SITE_DEBUG' ) ? (bool)SITE_DEBUG : false ),
					'csrf' => ( defined( 'SITE_CSRF' ) ? (bool)SITE_CSRF : true ),
					'header_ip_address' => true
				),
				'mysql' => array(
					'host' => strval( MYSQL_HOST ),
					'user' => strval( MYSQL_USER ),
					'pass' => strval( MYSQL_PASS ),
					'db' => strval( MYSQL_DB ),
					'prefix' => strval( MYSQL_PREFIX ),
					'persistent' => ( defined( 'MYSQL_PERSISTENT' ) ? (bool)MYSQL_PERSISTENT : false )
				)
			);

		if ( defined( 'SITE_NAME' ) )
			$config_new['site']['name'] = strval( SITE_NAME );

		if ( defined( 'SITE_NOREPLYEMAIL' ) )
			$config_new['site']['noreplyemail'] = strval( SITE_NOREPLYEMAIL );
	} else {
		// Use POST data
		$config_new = array_merge( $config_default, $_POST );
	}

	// Change relative path to absolute
	$config_new['site']['path'] = realpath( $config_new['site']['path'] );

	return $config_new;
}

function v02_pre_upgrade_check_config( $config ) {
	global $errors;

	if ( empty( $config['site']['url'] ) )
		$errors[] = 'Site URL cannot be empty';
		
	if ( !filter_var( $config['site']['url'], FILTER_VALIDATE_URL ) )
		$errors[] = 'Site URL is not a valid URL';
		
	if ( empty( $config['site']['path'] ) )
		$errors[] = 'Site path cannot be empty';
		
	if ( !is_dir( $config['site']['path'] ) )
		$errors[] = 'Site path is not a directory or does not exist';

    if ( !empty( $config_new['site']['noreplyemail'] ) && !filter_var($config_new['site']['noreplyemail'], FILTER_VALIDATE_EMAIL ) )
        $errors[] = 'The specified no-reply email is not a valid email address';
	
	if ( empty( $config['site']['geoip_path'] ) )
		$errors[] = 'GeoIP path cannot be empty';
		
	if ( !file_exists( $config['site']['geoip_path'] ) )
		$errors[] = 'GeoIP path does not exist';

	if ( empty( $config['site']['geoipv6_path'] ) )
		$errors[] = 'GeoIPv6 path cannot be empty';
		
	if ( !file_exists( $config['site']['geoipv6_path'] ) )
		$errors[] = 'GeoIPv6 path does not exist';

	if ( empty( $config['mysql']['host'] ) )
		$errors[] = 'MySQL host cannot be empty';
	
	if ( empty( $config['mysql']['user'] ) )
		$errors[] = 'MySQL username cannot be empty';
		
	if ( empty( $config['mysql']['pass'] ) )
		$errors[] = 'MySQL password cannot be empty';

	if ( empty( $config['mysql']['db'] ) )
		$errors[] = 'MySQL database cannot be empty';
		
	if ( function_exists( 'mysqli_connect' ) ) {
		mysqli_connect($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['pass'], $config['mysql']['db'] );
		
		if ( mysqli_connect_error() )
			$errors[] = 'Unable to connect MySQL server: ' . mysqli_connect_error();
	} else {
		if ( !mysql_connect($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['pass'], $config['mysql']['db'] ) )
			$errors[] = 'Unable to connect MySQL server: ' . mysql_error();
	}
		
	return ( empty( $errors ) ? true : false );
}

function v02_pre_upgrade_validate_pass() {
	global $errors;
	
	if ( empty( $_POST['user']['pass'] ) )
		return true;
		
	if ( isset( $_POST['user']['login_id'] ) ) {
		if ( !is_numeric( $_POST['user']['login_id'] ) ) {
			$errors[] = 'User ID must be a number';
			return false;
		} 
		
		$_POST['user']['login_id'] = intval( $_POST['user']['login_id'] );
	} else if ( isset( $_POST['user']['login_input'] ) ) {
		$_POST['user']['login_input'] = trim( $_POST['user']['login_input'] );
		
		if ( empty( $_POST['user']['login_input'] ) ) {
			$errors[] = 'Username cannot be empty';
			return false;
		}
		
		if ( !preg_match('#^([-a-z0-9_-])+$#i', $_POST['user']['login_input'] ) ) {
			$errors[] = 'Username can only contain letters, numbers, underscores, and dashes';
			return false;
		}
		
		if ( strlen( $_POST['user']['login_input'] ) < 5 ) {
			$errors[] = 'Username must be at least 5 characters long';
			return false;
		}
		
		if ( strlen( $_POST['user']['login_input'] ) > 20 ) {
			$errors[] = 'Username cannot be longer than 20 characters';
			return false;
		}
		
		if ( empty( $_POST['user']['email'] ) ) {
			$errors[] = 'User email must be specified';
			return false;
		}
		
		$_POST['user']['email'] = trim( $_POST['user']['email'] );
		
		if ( !filter_var( $_POST['user']['email'], FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = 'User email is not in correct format';
			return false;
		}
	} else {
		$errors[] = 'Password specified, but no user was specified';
		return false;
	}
	
		
	if ( empty( $_POST['user']['pass_verify'] ) ) {
		$errors[] = 'Please enter the password a second time';
		return false;
	}
	
	$pass1 = $_POST['user']['pass'];
	$pass2 = $_POST['user']['pass_verify'];
	
	if ( preg_match( '/\s/', $pass1 ) ) {
		$errors[] = 'The password can only contain letters, digits and punctuation';
		return false;
	}
	
	if ( strlen( $pass1 ) <= 5 ) {
		$errors[] = 'The password must be at least 5 characters long';
		return false;
	}
	
	if ( strlen( $pass1 ) >= 20 ) {
		$errors[] = 'The password cannot be longer than 5 characters long';
		return false;
	}
	
	if ( $pass1 != $pass2 ) {
		$errors[] = 'The passwords entered do not match';
		return false;
	}
	
	return true;
}

function v02_pre_upgrade() {
	global $errors;
	
	$config_new = v02_pre_upgrade_config(false);
	
	if ( !v02_pre_upgrade_check_config( $config_new ) )
		return false;
		
	if ( !v02_pre_upgrade_validate_pass() )
		return false;
		
	$config_file = '<?php'."\n";;
    $config_file .= '// See inc/config.sample.php for documentation and example'."\n";
	$config_file .= 'if ( basename( $_SERVER[\'PHP_SELF\'] ) == \'config.php\' )'."\n";
	$config_file .= '    die( \'This page cannot be loaded directly\' );'."\n\n";
	$config_file .= 'return ' . var_export( $config_new, true ) . ';'."\n";
	
	if (file_put_contents( ROOTDIR . '/inc/config.php', $config_file ) === false) {
		$errors[] = 'Unable to write to file "' . ROOTDIR . '/inc/config.php"';
		return false;
	}
	
	if ( ( isset( $_POST['user']['login_id'] ) || !empty( $_POST['user']['login_input'] ) ) && !empty( $_POST['user']['pass'] ) ) {
		$_SESSION['password_update'] = true;
		
		// Update password during Upgrade
		require( ROOTDIR . '/inc/password_compat/lib/password.php' );
		
		if ( isset( $_POST['user']['login_id'] ) )
			$_SESSION['password_login_id'] = $_POST['user']['login_id'];
		else {
			$_SESSION['password_login_input'] = $_POST['user']['login_input'];
			$_SESSION['password_login_email'] = $_POST['user']['email'];
		}

		$_SESSION['password_hash'] = password_hash( $_POST['user']['pass'], PASSWORD_DEFAULT );;
	}
	

	return true;
}

function v02upgrade() {
    global $errors;

    // Update SQL

    $sql = <<<SQL
    ALTER TABLE `{:db_prefix}uniqueusers` 
	CHANGE `UniqueUserId` `UniqueUserId` VARCHAR( 36 ),
    CHANGE `IPAddress` `IPAddress` VARCHAR( 20 ),
    CHANGE `Country` `Country` VARCHAR( 50 ),
    CHANGE `OSVersion` `OSVersion` VARCHAR( 100 ),
    CHANGE `JavaVer` `JavaVer` VARCHAR( 50 ),
    CHANGE `NetVer` `NetVer` VARCHAR( 50 ),
    CHANGE `ScreenRes` `ScreenRes` VARCHAR( 20 ),
    CHANGE `CPUName` `CPUName` VARCHAR( 40 ),
    CHANGE `CPUBrand` `CPUBrand` VARCHAR( 20 );

    ALTER TABLE `{:db_prefix}sessions` 
	CHANGE `SessionId` `SessionId` VARCHAR( 36 ),
    CHANGE `UniqueUserId` `UniqueUserId` VARCHAR( 36 ),
    CHANGE `ApplicationId` `ApplicationId` VARCHAR( 36 ),
    CHANGE `ApplicationVersion` `ApplicationVersion` VARCHAR( 50 ),
	ALTER `StopApp` SET DEFAULT 0;

    CREATE TABLE IF NOT EXISTS `{:db_prefix}events_event` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `EventCategory` varchar(50) DEFAULT NULL,
	  `EventName` varchar(50) DEFAULT NULL,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_eventvalue` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `EventCategory` varchar(50) DEFAULT NULL,
	  `EventName` varchar(50) DEFAULT NULL,
	  `EventValue` varchar(50) DEFAULT NULL,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_eventperiod` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `EventCategory` varchar(50) DEFAULT NULL,
	  `EventName` varchar(50) DEFAULT NULL,
	  `EventDuration` int(11) DEFAULT NULL,
	  `EventCompleted` tinyint(1) DEFAULT NULL,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_log` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `LogMessage` varchar(255) DEFAULT NULL,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_customdata` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `EventCustomName` varchar(50) DEFAULT NULL,
	  `EventCustomValue` varchar(50) DEFAULT NULL,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_exception` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `ExceptionMsg` varchar(255) DEFAULT NULL,
	  `ExceptionStackTrace` text,
	  `ExceptionSource` varchar(255) DEFAULT NULL,
	  `ExceptionTargetSite` varchar(255) DEFAULT NULL,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_install` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};

	CREATE TABLE IF NOT EXISTS `{:db_prefix}events_uninstall` (
	  `EventId` int(11) NOT NULL AUTO_INCREMENT,
	  `SessionId` varchar(36) NOT NULL,
	  `UtcTimestamp` TIMESTAMP NULL DEFAULT NULL,
	  PRIMARY KEY (`EventId`)
	) ENGINE={:db_engine} DEFAULT CHARSET={:db_charset};
SQL;

    $ret = db_import_sql( $sql );

    if ( $ret !== true )
        $errors[] = "Error upgrading tables to v0.2. (" . $ret . ")";
	else {
		// Convert events table into separate tables
		
		$sql = <<<SQL
	SET time_zone = "+00:00";

	INSERT INTO {:db_prefix}events_event (`EventCategory`,`EventName`,`SessionId`,`UtcTimestamp`) SELECT `EventCategory`,`EventName`,`SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'ev';
	INSERT INTO {:db_prefix}events_eventvalue (`EventCategory`,`EventName`,`EventValue`,`SessionId`,`UtcTimestamp`) SELECT `EventCategory`,`EventName`,`EventValue`,`SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'evV';
	INSERT INTO {:db_prefix}events_eventperiod (`EventCategory`,`EventName`,`EventDuration`,`EventCompleted`,`SessionId`,`UtcTimestamp`) SELECT `EventCategory`,`EventName`,`EventDuration`,`EventCompleted`,`SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'evP';
	INSERT INTO {:db_prefix}events_log (`LogMessage`,`SessionId`,`UtcTimestamp`) SELECT `LogMessage`,`SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'lg';
	INSERT INTO {:db_prefix}events_customdata (`EventCustomName`,`EventCustomValue`,`SessionId`,`UtcTimestamp`) SELECT `EventCustomName`,`EventCustomValue`,`SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'ctD';
	INSERT INTO {:db_prefix}events_exception (`ExceptionMsg`,`ExceptionStackTrace`,`ExceptionSource`,`ExceptionTargetSite`,`SessionId`,`UtcTimestamp`) SELECT `ExceptionMsg`,`ExceptionStackTrace`,`ExceptionSource`,`ExceptionTargetSite`,`SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'exC';
	INSERT INTO {:db_prefix}events_install (`SessionId`,`UtcTimestamp`) SELECT `SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'ist';
	INSERT INTO {:db_prefix}events_uninstall (`SessionId`,`UtcTimestamp`) SELECT `SessionId`,FROM_UNIXTIME(`UtcTimestamp`) FROM {:db_prefix}events WHERE `EventCode` = 'ust';

	ALTER TABLE `{:db_prefix}sessions` ADD COLUMN `StartApp2` TIMESTAMP NULL DEFAULT NULL AFTER `StartApp`;
	UPDATE `{:db_prefix}sessions` SET `StartApp2`=FROM_UNIXTIME(`StartApp`);
	ALTER TABLE `{:db_prefix}sessions` DROP `StartApp`;
	ALTER TABLE `{:db_prefix}sessions` CHANGE `StartApp2` `StartApp` TIMESTAMP NULL DEFAULT NULL;

	ALTER TABLE `{:db_prefix}sessions` ADD COLUMN `StopApp2` TIMESTAMP NULL DEFAULT NULL AFTER `StopApp`;
	UPDATE `{:db_prefix}sessions` SET `StopApp2`=FROM_UNIXTIME(`StopApp`);
	ALTER TABLE `{:db_prefix}sessions` DROP `StopApp`;
	ALTER TABLE `{:db_prefix}sessions` CHANGE `StopApp2` `StopApp` TIMESTAMP NULL DEFAULT NULL;

    ALTER TABLE `{:db_prefix}uniqueusers` ADD COLUMN `Created2` TIMESTAMP NULL DEFAULT NULL AFTER `Created`;
	UPDATE `{:db_prefix}uniqueusers` SET `Created2`=FROM_UNIXTIME(`Created`);
	ALTER TABLE `{:db_prefix}uniqueusers` DROP `Created`;
	ALTER TABLE `{:db_prefix}uniqueusers` CHANGE `Created2` `Created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

    ALTER TABLE `{:db_prefix}uniqueusers` ADD COLUMN `LastRecieved2` TIMESTAMP NULL DEFAULT NULL AFTER `LastRecieved`;
	UPDATE `{:db_prefix}uniqueusers` SET `LastRecieved2`=FROM_UNIXTIME(`LastRecieved`);
	ALTER TABLE `{:db_prefix}uniqueusers` DROP `LastRecieved`;
	ALTER TABLE `{:db_prefix}uniqueusers` CHANGE `LastRecieved2` `LastRecieved` TIMESTAMP NOT NULL;
SQL;

		$ret = db_import_sql( $sql );
		
		if ( $ret !== true )
			$errors[] = "Error converting tables to v0.2. (" . $ret . ")";

        // Remove old files
        remove_files(
            array(
                ROOTDIR . '/inc/class.geekmail.php',
                ROOTDIR . '/inc/class.passwordhash.php',
                ROOTDIR . '/js/jquery/jquery.highcharts.js'
            )
        );
        
        // Update password
        if ( !empty( $_SESSION['password_update'] ) && ( isset( $_SESSION['password_login_id'] ) || !empty( $_SESSION['password_login_input'] ) ) && !empty( $_SESSION['password_hash'] ) ) {
        	if ( isset( $_SESSION['password_login_id'] ) ) {
				MySQL::getInstance()->update( "users", array( 'UserPass' => $_SESSION['password_hash'] ), array( 'UserId' => intval( $_SESSION['password_login_id'] ) ) );
			} else {
				if ( MySQL::getInstance()->select_count( "users", '*', array( 'UserName' => $_SESSION['password_login_input'] ) ) ) {
					MySQL::getInstance()->update( "users", array( 'UserPass' => $_SESSION['password_hash'] ), array( 'UserName' => $_SESSION['password_login_input'] ) );
				} else {
					MySQL::getInstance()->insert( 
						array(
							'UserName' => $_SESSION['password_login_input'],
							'UserEmail' => $_SESSION['password_login_email'],
							'UserPass' => $_SESSION['password_hash']
						),
						"users"
					);
				}
			}
        
			// Clear hash
			unset( $_SESSION['password_update'],  $_SESSION['password_login_id'],  $_SESSION['password_login_input'], $_SESSION['password_hash'] );
		}
	}

}

if ( !file_exists( '../inc/config.php' ) )
    die( 'File config.php is not found.' );
    
if ( !is_writable( '../inc/config.php' ) )
	die( 'File inc/config.php must be writable.' );

// Check for installed extensions
if ( !extension_loaded( 'mysql' ) && !extension_loaded( 'mysqli' ) )
    die( 'MySQL/MySQLi extension(s) must be installed with PHP' );

if ( !extension_loaded( 'mbstring' ) ) die( 'Multibyte String extension is not installed.' );


define('LSS_LOADED', true);

// Remove time limit
set_time_limit(0); // This may have no affect if web server uses PHP-FPM

// Set root dir
if ( !defined( 'ROOTDIR' ) )
    define( 'ROOTDIR', realpath( dirname( __FILE__ ) . '/../' ) );

require_once( ROOTDIR . '/inc/version.php' );
    
check_pre_upgrade_needed();

if ( ( isset($_POST['pre_update'] ) ) && $_POST['pre_update'] == 'true' && !empty( $preupgrade_funcs ) ) {
	$ret = true;
	
	foreach ( $preupgrade_funcs as $func ) {
		if ( function_exists( $func ) ) {
			if ( !call_user_func( $func ) ) {
				$ret = false;
				break;
			}
		}
			
	}
	
	if ( $ret )
		$preupgrade_funcs = false;
} else if ( ( isset($_POST['update'] ) ) && $_POST['update'] == 'true' ) {
	require_once( '../inc/class.config.php' );
	
	require_once( '../inc/class.mysql.php' );

    // REMOVE THIS AFTER v0.2!
    if ( !MySQL::getInstance()->execute_sql( "INSERT IGNORE INTO ".Config::getInstance()->mysql->prefix."options (`Name`, `Value`) VALUES('current_version', '0.1')" ) )
        $errors[] = "Execute failed: " . MySQL::getInstance()->last_error;

    if ( !MySQL::getInstance()->select( 'options', array( 'name' => 'current_version' ) ) )
        $errors[] = "Execute failed: " . MySQL::getInstance()->last_error;

    $install_version = MySQL::getInstance()->arrayed_result['value'];

	if (empty($errors)) {
		if (!get_engines()) {
			$errors[] = 'Unable to get support MySQL engines';
		}

		if (empty($errors)) {
			if ( version_compare( $install_version, VERSION, '>=' ) ) {
				$errors[] = 'You already seem to be running an up to date version (v' . VERSION . ').';
			} else {
				// Block API calls
				MySQL::getInstance()->execute_sql( "UPDATE ".Config::getInstance()->mysql->prefix."applications SET `ApplicationRecieving` = 0" );
			
				if ( version_compare( $install_version, '0.2', '<' ) && empty( $errors ) )
					v02upgrade();

				if ( empty( $errors ) ) {
					// Update installed version
					if ( !MySQL::getInstance()->update( 'options', array( 'Value' => VERSION ), array( 'Name' => 'current_version' ) ) )
						$errors[] = "Execute failed: " . MySQL::getInstance()->last_error;
				}
				
				// Unblock API calls
				MySQL::getInstance()->execute_sql( "UPDATE ".Config::getInstance()->mysql->prefix."applications SET `ApplicationRecieving` = 1" );
			}
		}
	}
}

?>
<html>
    <head>
        <title>Update Little Software Stats</title>
		<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,700' rel='stylesheet' type='text/css' />
		<link href='http://fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css' />
		<style type="text/css">
			html, body, div, span, applet, object, iframe,
			h1, h2, h3, h4, h5, h6, p, blockquote, pre,
			a, abbr, acronym, address, big, cite, code,
			del, dfn, em, img, ins, kbd, q, s, samp,
			small, strike, strong, sub, sup, tt, var,
			b, u, i, center,
			dl, dt, dd, ol, ul, li,
			fieldset, form, label, legend,
			table, caption, tbody, tfoot, thead, tr, th, td,
			article, aside, canvas, details, embed, 
			figure, figcaption, footer, header, hgroup, 
			menu, nav, output, ruby, section, summary,
			time, mark, audio, video {
				margin: 0;
				padding: 0;
				border: 0;
				font-size: 100%;
				font: inherit;
				vertical-align: baseline;
			}
			/* HTML5 display-role reset for older browsers */
			article, aside, details, figcaption, figure, 
			footer, header, hgroup, menu, nav, section {
				display: block;
			}
			body {
				line-height: 1;
				background-color: #ECE9E2;
			}
			ol, ul {
				list-style: none;
			}
			blockquote, q {
				quotes: none;
			}
			blockquote:before, blockquote:after,
			q:before, q:after {
				content: '';
				content: none;
			}
			table {
				border-collapse: collapse;
				border-spacing: 0;
			}
			
			.logo { 
				text-align: center;
				margin: 20px 0;
			}
			
			form > div {
				width: 547px;
				background-color: #f3f8fc;
				border: 3px solid rgba(137, 199, 239, .40);
				border-radius: 7px;
				padding: 20px;
				margin: 0 auto;
			}
			
			form > div {
				font-weight: 600;
				font-family: 'Open Sans', sans-serif;
				text-align: center;
			}
			
			form > div > h1 {
				font-size: 18px;
			}
			
			form > div > h2 {
				font-size: 16px;
				margin-top: 13px;
				font-weight: 900;
			}
			
			form > div > h3 {
				font-size: 15px;
				margin-top: 10px;
				
			}
			
			form > div > p {
				font-size: 14px;
				margin: 15px 0;
			}
			
			form > div > ul {
				display: block;
			}
			
			form > div > ul > li {
				height: 36px;
				line-height: 36px;
				margin-top: 15px;
			}
			
			form > div > ul > li > label {
				text-align: right;
				clear: both;
				float:left;
				margin-right:15px;
				width: 197px;
			}
			
			form > div > ul > li > input, form > div > ul > li > select {
				float: left;
				height: 36px;
				border: 1px solid rgba(217,217,217,.7); /* stroke */
				-moz-border-radius: 5px / 5px 5px 5px 5px;
				-webkit-border-radius: 5px / 5px 5px 5px 5px;
				border-radius: 5px / 5px 5px 5px 5px; /* border radius */
				-moz-background-clip: padding;
				-webkit-background-clip: padding-box;
				background-clip: padding-box; /* prevents bg color from leaking outside the border */
				-moz-box-shadow: 0 2px 4px rgba(0,0,0,.22), inset 0 2px 0 #fff, inset 0 0 2px rgba(255,255,255,.7); /* drop shadow, inner shadow and inner glow */
				-webkit-box-shadow: 0 2px 4px rgba(0,0,0,.22), inset 0 2px 0 #fff, inset 0 0 2px rgba(255,255,255,.7); /* drop shadow, inner shadow and inner glow */
				box-shadow: 0 2px 4px rgba(0,0,0,.22), inset 0 2px 0 #fff, inset 0 0 2px rgba(255,255,255,.7); /* drop shadow, inner shadow and inner glow */
				background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEzOSAzNiIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PGxpbmVhckdyYWRpZW50IGlkPSJoYXQwIiBncmFkaWVudFVuaXRzPSJvYmplY3RCb3VuZGluZ0JveCIgeDE9IjUwJSIgeTE9IjEwMCUiIHgyPSI1MCUiIHkyPSItMS40MjEwODU0NzE1MjAyZS0xNCUiPgo8c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjZjRmNGY0IiBzdG9wLW9wYWNpdHk9IjEiLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjZmRmZGZkIiBzdG9wLW9wYWNpdHk9IjEiLz4KICAgPC9saW5lYXJHcmFkaWVudD4KCjxyZWN0IHg9IjAiIHk9IjAiIHdpZHRoPSIxMzkiIGhlaWdodD0iMzYiIGZpbGw9InVybCgjaGF0MCkiIC8+Cjwvc3ZnPg==); /* gradient overlay */
				background-image: -moz-linear-gradient(bottom, #f4f4f4 0%, #fdfdfd 100%); /* gradient overlay */
				background-image: -o-linear-gradient(bottom, #f4f4f4 0%, #fdfdfd 100%); /* gradient overlay */
				background-image: -webkit-linear-gradient(bottom, #f4f4f4 0%, #fdfdfd 100%); /* gradient overlay */
				background-image: linear-gradient(bottom, #f4f4f4 0%, #fdfdfd 100%); /* gradient overlay */
				
				color: #5f5f5f; /* text color + color overlay */
				font-family: 'Open Sans', sans-serif;
				font-size: 15px;
				font-weight: bold;
				text-shadow: 0 1px 0 rgba(255,255,255,.5); /* drop shadow */

			}
			
			form > div > ul > li > input[type="checkbox"] {
				margin: 0;
			}
			
			form > div > ul > li > input[type="text"], form > div > ul > li > input[type="password"] {
				width: 335px;
				height: 36px;
				border: 1px solid rgba(217,217,217,.5); /* stroke */
				-moz-border-radius: 5px;
				-webkit-border-radius: 5px;
				border-radius: 5px; /* border radius */
				-moz-background-clip: padding;
				-webkit-background-clip: padding-box;
				background-clip: padding-box; /* prevents bg color from leaking outside the border */
				background-color: #fff; /* color overlay */
				font-family: 'Open Sans', sans-serif;
				font-size: 15px;
				padding-left: 10px;
				padding-right: 10px;
			}
			
			form > div > input[type="submit"] {
				margin-top: 15px;
				background: #286398; /* Old browsers */
				background: -moz-linear-gradient(top,  #286398 0%, #3b85c8 100%); /* FF3.6+ */
				background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#286398), color-stop(100%,#3b85c8)); /* Chrome,Safari4+ */
				background: -webkit-linear-gradient(top,  #286398 0%,#3b85c8 100%); /* Chrome10+,Safari5.1+ */
				background: -o-linear-gradient(top,  #286398 0%,#3b85c8 100%); /* Opera 11.10+ */
				background: -ms-linear-gradient(top,  #286398 0%,#3b85c8 100%); /* IE10+ */
				background: linear-gradient(to bottom,  #286398 0%,#3b85c8 100%); /* W3C */
				filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#286398', endColorstr='#3b85c8',GradientType=0 ); /* IE6-9 */
				box-shadow: 0px 2px 0px 0px rgba(22, 81, 129, 1); 
				border: medium none;
				border-radius: 5px;
				height: 36px;
				width: 140px;
				font: 700 16px/36px 'Lato', sans-serif;
				color: #fff;
				cursor: pointer;
			}
			
			.info-box {
				width: 547px;
				border-radius: 7px;
				padding: 20px;
				margin: 20px auto;
				background-clip: padding-box; /* prevents bg color from leaking outside the border */
			}
			
			.errors {
				background-color: #FBE3E4;
				border: 3px solid rgba(131, 31, 17, .40);
			}
			
			.warnings {
				background-color: #fefdec;
				border: 3px solid #e6e4c4;
			}
			
			.info-box > ul > li {
				font: 700 16px 'Open Sans', sans-serif;
				text-align: center;
			}
		</style>
		<script type="text/javascript" src="../js/jquery/jquery.min.js"></script>
		<?php if ( ( isset( $_POST['update'] ) ) && $_POST['update'] == 'true' && empty( $errors ) ) : ?>
		<script type="text/javascript">
			$(document).ready(function() {
				alert('Little Software Stats has been successfully updated to v<?php echo VERSION ?>. Please delete or rename the install directory. You will now be redirected to the login.');
				window.location.href = "../login.php";
			});
		</script>
		<?php endif; ?>
    </head>
    <body>
        <div class="logo"><img src="../images/shared/logo.png" /></div>
        <?php if ( !empty( $warnings ) ) : ?>
		<div class="info-box warnings">
			<ul>
				<?php foreach ( $warnings as $warning ) : ?>
				<li><?php echo htmlspecialchars( $warning ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		<?php if ( !empty( $errors ) ) : ?>
		<div class="info-box errors">
			<ul>
				<?php foreach ( $errors as $error ) : ?>
				<li><?php echo htmlspecialchars( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
        <form action="update.php" method="post">
        	<?php if ( !empty( $preupgrade_funcs ) ) : ?>
        	<input type="hidden" name="pre_update" value="true" />
            <div>
	        	<h1>Some things need to be done before Little Software Stats can be upgraded</h1>
	        	
	        	<?php pre_upgrade_output(); ?>
	        	
	        	<p>Click the button below to prepare Little Software Stats to be updated</p>
	        	<input type="submit" name="submit" value="Pre-update" />
        	</div>
        	<?php else : ?>
        	<input type="hidden" name="update" value="true" />
        	<div>
            	<h1>Click the button below to update Little Software Stats to v<?php echo VERSION ?></h1>
				<h2>Please make sure you have made a backup of Little Software Stats before updating it</h2>
	            <input type="submit" name="submit" value="Update" />
            </div>
            <?php endif; ?>
        </form>
    </body>
</html>