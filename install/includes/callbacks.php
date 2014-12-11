<?php
/**
 * Callbacks class
 */
class Callbacks extends Callbacks_Core {
    function install($params = array()) {
        $dbconf = array(
            'db_host' => $_SESSION['params']['db_hostname'],
            'db_user' => $_SESSION['params']['db_username'],
            'db_pass' => $_SESSION['params']['db_password'],
            'db_name' => $_SESSION['params']['db_name'],
            'db_encoding' => 'utf8',
        );
        if ( !$this->db_init($dbconf) ) {
            return false;
        }

        $replace = array(
            '{:db_prefix}' => $_SESSION['params']['db_prefix'],
            '{:db_engine}' => in_array('innodb', $this->db_engines) ? 'InnoDB' : 'MyISAM',
            '{:db_charset}' => $this->db_version >= '4.1' ? 'DEFAULT CHARSET=utf8' : ''
        );

        if ( !$this->db_import_file(BASE_PATH.'sql/data.sql', $replace) ) {
            return false;
        }

        $this->db_close();

        $config_file = '<?php'."\n";;
        $config_file .= '// ------------------------------------------------------'."\n";
        $config_file .= '// DO NOT ALTER THIS FILE UNLESS YOU HAVE A REASON TO'."\n";
        $config_file .= '// ------------------------------------------------------'."\n";
        $config_file .= 'define(\'SITE_URL\', \'' . $_SESSION['params']['virtual_path'] . '\');'."\n";
        $config_file .= 'define(\'SITE_PATH\', \'' . $_SESSION['params']['system_path'] . '\');'."\n";

        $config_file .= 'define(\'MYSQL_HOST\', \'' . $_SESSION['params']['db_hostname'] . '\');'."\n";
        $config_file .= 'define(\'MYSQL_USER\', \'' . $_SESSION['params']['db_username'] . '\');'."\n";
        $config_file .= 'define(\'MYSQL_PASS\', \'' . $_SESSION['params']['db_password'] . '\');'."\n";
        $config_file .= 'define(\'MYSQL_DB\', \'' . $_SESSION['params']['db_name'] . '\');'."\n";
        $config_file .= 'define(\'MYSQL_PREFIX\', \'' . $_SESSION['params']['db_prefix'] . '\');'."\n";
        
        $config_file .= "// Usually needed when having heavy loads\n";
        $config_file .= "define('MYSQL_PERSISTENT', '" . ( in_array('db_persistent', $_SESSION['params']['db_persistent']) ? 'true' : 'false' ) . "');\n";
        
        $config_file .= "// Only enable for developing!\n";
        $config_file .= "define('SITE_DEBUG', false);\n";
        
        $config_file .= "// Set to false to disable cross site request forgery protection (not recommended)\n";
        $config_file .= "define('SITE_CSRF', true);\n";

        @file_put_contents(rtrim($_SESSION['params']['system_path'], '/').'/inc/config.php', $config_file);

        return true;
    }

    function setup($params = array()) {
        $dbconf = array(
            'db_host' => $_SESSION['params']['db_hostname'],
            'db_user' => $_SESSION['params']['db_username'],
            'db_pass' => $_SESSION['params']['db_password'],
            'db_name' => $_SESSION['params']['db_name'],
            'db_encoding' => 'utf8',
        );
        
        if ( !($db = $this->db_init($dbconf)) ) {
            return false;
        }
        
        // Escape each string in $_SESSION['params']
        $escaped_params = array();
        foreach ($_SESSION['params'] as $key => $value) {
            if (is_string($value)) {
                $escaped_params[$key] = $this->db_escape($value);
            }
        }

        $pass = $this->hash_password( $escaped_params['user_password'] );

        $sql = "INSERT INTO `".$escaped_params['db_prefix']."users` (`UserName`, `UserEmail`, `UserPass`) VALUES(";
        $sql .= "'".strtolower( $escaped_params['user_username'] )."', ";
        $sql .= "'".strtolower( $escaped_params['user_email'] )."', ";
        $sql .= "'".$pass."')";

        $this->db_query($sql);
        
        $sql = "INSERT INTO `".$escaped_params['db_prefix']."applications` (`ApplicationId`, `ApplicationName`) VALUES(";
        $sql .= "'".$escaped_params['app_id']."', ";
        $sql .= "'".$escaped_params['app_name']."')";

        $this->db_query($sql);

        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('current_version', '".$this->db_escape(VERSION)."')");

        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('site_adminemail', '".$escaped_params['user_email']."')");
        
        $rewrite = ( in_array('rewrite_enabled', $_SESSION['params']['rewrite_enabled']) ? 'true' : 'false' );
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('site_rewrite', '".$rewrite."')");
        
        $recaptcha = ( in_array('recaptcha_enabled', $_SESSION['params']['recaptcha_enabled']) ? 'true' : 'false' );
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('recaptcha_enabled', '".$recaptcha."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('recaptcha_public_key', '".$escaped_params['recaptcha_publickey']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('recaptcha_private_key', '".$escaped_params['recaptcha_privatekey']."')");
        
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('mail_protocol', '".$escaped_params['mail_protocol']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('mail_smtp_server', '".$escaped_params['smtp_server']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('mail_smtp_port', '".$escaped_params['smtp_port']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('mail_smtp_username', '".$escaped_params['smtp_username']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('mail_smtp_password', '".$escaped_params['smtp_password']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('mail_sendmail_path', '".$escaped_params['sendmail_path']."')");
        
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_service', '".$escaped_params['geoip_service']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_api_key', '".$escaped_params['geoip_apikey']."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_database', '".$escaped_params['geoip_path']."')");
        
        $geoip_version = $this->get_geoip_version($escaped_params['geoip_path']);
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_database_version', '".date('Y-m-d', $geoip_version)."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_database_update_url', 'http://little-software-stats.com/geolite.xml')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_database_v6_version', '".date('Y-m-d', $geoip_version)."')");
        $this->db_query("INSERT INTO `".$escaped_params['db_prefix']."options` (`Name`, `Value`) VALUES('geoips_database_v6_update_url', 'http://little-software-stats.com/geolitev6.xml')");
        
        $this->db_close();

        return true;
    }
    
    function get_geoip_version($file) {
        $fp = fopen($file,"rb") or die( "Can not open $file\n" );
        
        define("STRUCTURE_INFO_MAX_SIZE", 20);
        define("DATABASE_INFO_MAX_SIZE", 100);
        
        $hasStructureInfo = false;
        fseek($fp,-3,SEEK_END);  
        for ($i = 0;$i < STRUCTURE_INFO_MAX_SIZE;$i++) {
            $buf = fread($fp,3);
            if ($buf == (chr(255) . chr(255) . chr(255))) {
                $hasStructureInfo = true;
                break;
            }
            fseek($fp,-4,SEEK_CUR);
        }  
        if ($hasStructureInfo == true) {
            fseek($fp,-6,SEEK_CUR);
        } else {
            # no structure info, must be pre Sep 2002 database, go back to
            fseek($fp,-3,SEEK_END);
        }
        for ($i = 0;$i < DATABASE_INFO_MAX_SIZE;$i++){
            $buf = fread($fp,3);
            if ($buf == (chr(0). chr(0). chr(0))){
                $retval = fread($fp,$i);            
                fclose($fp);
                
                // Convert to unix timestamp
                for ($i = 0; $i < strlen($retval) - 9; $i++) {
                    if (ctype_space(substr($retval, $i, 1))) {
                        $date_str = substr($retval, $i+1, 8);

                        return strtotime($date_str);
                    }
                }
            }
            fseek($fp,-4,SEEK_CUR);
        } 

        fclose($fp);
        return time();
    }

    /******** Password Hashing Functions ********/
	
    private $itoa64;
    private $iteration_count_log2;
    private $portable_hashes;
    private $random_state;
	
    /**
     * Generates a random string
     * @access private
     * @param int $count Length of string
     * @return string Random string 
     */
    private function get_random_bytes($count) {
        $output = '';
        if (is_readable('/dev/urandom') &&
            ($fh = @fopen('/dev/urandom', 'rb'))) {
                $output = fread($fh, $count);
                fclose($fh);
        }

        if (strlen($output) < $count) {
            $output = '';
            for ($i = 0; $i < $count; $i += 16) {
                $this->random_state = md5(microtime() . $this->random_state);
                $output .= pack('H*', md5($this->random_state));
            }
            
            $output = substr($output, 0, $count);
        }

        return $output;
    }

    /**
     * Base64 encoder
     * @access private
     * @param string $input String to encode
     * @param int $count Length of string
     * @return string Base64 encoded string 
     */
    private function encode64($input, $count) {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $this->itoa64[$value & 0x3f];
            if ($i < $count)
                $value |= ord($input[$i]) << 8;
            $output .= $this->itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count)
                break;
            if ($i < $count)
                $value |= ord($input[$i]) << 16;
            $output .= $this->itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count)
                break;
            $output .= $this->itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }

    /**
     * Generates salt for portable hashes
     * @param string $input Random string
     * @return string Salt 
     */
    private function gensalt_private($input) {
        $output = '$P$';
        $output .= $this->itoa64[min($this->iteration_count_log2 +
                ((PHP_VERSION >= '5') ? 5 : 3), 30)];
        $output .= $this->encode64($input, 6);

        return $output;
    }

    /**
     * Generates portable password hash
     * (Should only be used when crypt libraries in PHP are very limited)
     * @access private
     * @param string $password Plain text password
     * @param string $setting Salt generated using gensalt_private()
     * @return string Portable password hash
     */
    private function crypt_private($password, $setting) {
        $output = '*0';
        if (substr($setting, 0, 2) == $output)
            $output = '*1';

        $sanitized_input['id'] = substr($setting, 0, 3);
        # We use "$P$", phpBB3 uses "$H$" for the same thing
        if ($sanitized_input['id'] != '$P$' && $sanitized_input['id'] != '$H$')
            return $output;

        $count_log2 = strpos($this->itoa64, $setting[3]);
        if ($count_log2 < 7 || $count_log2 > 30)
            return $output;

        $count = 1 << $count_log2;

        $salt = substr($setting, 4, 8);
        if (strlen($salt) != 8)
            return $output;

        /**
         * We're kind of forced to use MD5 here since it's the only
         * cryptographic primitive available in all versions of PHP
         * currently in use.  To implement our own low-level crypto
         * in PHP would result in much worse performance and
         * consequently in lower iteration counts and hashes that are
         * quicker to crack (by non-PHP code).
         */
        if (PHP_VERSION >= '5') {
            $hash = md5($salt . $password, TRUE);
            do {
                $hash = md5($hash . $password, TRUE);
            } while (--$count);
        } else {
            $hash = pack('H*', md5($salt . $password));
            do {
                $hash = pack('H*', md5($hash . $password));
            } while (--$count);
        }

        $output = substr($setting, 0, 12);
        $output .= $this->encode64($hash, 16);

        return $output;
    }

    /**
     * Generates a salt for a extended DES-based hash
     * @access private
     * @param string $input Random string
     * @return string Salt 
     */
    private function gensalt_extended($input) {
        $count_log2 = min($this->iteration_count_log2 + 8, 24);
        /**
         * This should be odd to not reveal weak DES keys, and the
         * maximum valid value is (2**24 - 1) which is odd anyway.
         */
        $count = (1 << $count_log2) - 1;

        $output = '_';
        $output .= $this->itoa64[$count & 0x3f];
        $output .= $this->itoa64[($count >> 6) & 0x3f];
        $output .= $this->itoa64[($count >> 12) & 0x3f];
        $output .= $this->itoa64[($count >> 18) & 0x3f];

        $output .= $this->encode64($input, 3);

        return $output;
    }

    /**
     * Generates a salt for a blowfish hash
     * @access private
     * @param string $input Random string
     * @return string Salt
     */
    private function gensalt_blowfish($input) {
        /**
         * This one needs to use a different order of characters and a
         * different encoding scheme from the one in encode64() above.
         * We care because the last character in our encoded string will
         * only represent 2 bits.  While two known implementations of
         * bcrypt will happily accept and correct a salt string which
         * has the 4 unused bits set to non-zero, we do not want to take
         * chances and we also do not want to waste an additional byte
         * of entropy.
         */
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '$2a$';
        $output .= chr(ord('0') + $this->iteration_count_log2 / 10);
        $output .= chr(ord('0') + $this->iteration_count_log2 % 10);
        $output .= '$';

        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }

    /**
     * Generates password hash using available crypt library
     * @access private
     * @param string $password Plain text password
     * @return string Password hash
     */
    private function hash_password($password) {
        $random = '';
		
        // Initalize hash variables
        $this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
            $iteration_count_log2 = 8;
        $this->iteration_count_log2 = $iteration_count_log2;

        $this->portable_hashes = $portable_hashes;

        $this->random_state = microtime();
        if (function_exists('getmypid'))
            $this->random_state .= getmypid();

        if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
            $random = $this->get_random_bytes(16);
            $hash =
                crypt($password, $this->gensalt_blowfish($random));
            if (strlen($hash) == 60)
                return $hash;
        }

        if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
            if (strlen($random) < 3)
                $random = $this->get_random_bytes(3);
            $hash =
                crypt($password, $this->gensalt_extended($random));
            if (strlen($hash) == 20)
                return $hash;
        }

        if (strlen($random) < 6)
            $random = $this->get_random_bytes(6);
        $hash =
            $this->crypt_private($password,
            $this->gensalt_private($random));
        if (strlen($hash) == 34)
            return $hash;

        /**
         * Returning '*' on error is safe here, but would _not_ be safe
         * in a crypt(3)-like function used _both_ for generating new
         * hashes and for validating passwords against existing hashes.
         */
        return '*';
    }
}
