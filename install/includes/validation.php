<?php

/**
 * Validation class
 */
class Validation extends Validation_Core
{
    // These function are part of the default $steps array to help you
    // illustrate how this script works. Feel free to delete them.

    function validate_system_path($value, $params = array()) {
        if ( !is_file(rtrim($value, '/').'/inc/config.php') || !is_writable(rtrim($value, '/').'/inc/config.php') ) {
            $this->error = rtrim($value, '/').'/inc/config.php file does not exist or is not writeable.';
            return false;
        }

        return true;
    }
	
	function validate_geoip_path($value, $params = array()) {
		$this->validate_file(rtrim($value, '/').'/GeoIP.dat');
		$this->validate_file(rtrim($value, '/').'/GeoIPv6.dat');
	}
    
    function no_spaces($value, $params = array()) {
        return ( strstr($value, ' ' ) ? false : true );
    }
    
    function validate_file($value, $params = array()) {
        if ( !is_file($value) ) {
            $this->error = $value . ' file does not exist.';
            return false;
        }

        return true;
    }
}
