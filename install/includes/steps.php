<?php

$upgrade_notice = array( 'type' => 'info', 'value' => '' );

$config_existing = array();

if (file_exists(rtrim(preg_replace('#/_install/$#', '', BASE_PATH), '/').'/inc/config.php')) {
    $config_existing = include_once rtrim(preg_replace('#/_install/$#', '', BASE_PATH), '/').'/inc/config.php';
    
    if ( empty( $config_existing ) ) {
		// Using v0.1 configuration
		
		$config_existing = 
			array(
				'site' => array(
					'url' => ( defined( 'SITE_URL' ) ? strval( SITE_URL ) : '' ),
					'path' => ( defined( 'SITE_PATH' ) ? strval( SITE_PATH ) : '' ),
					'geoip_path' => ( defined( 'SITE_GEOIP_PATH' ) ? strval( SITE_GEOIP_PATH ) : '' ),
					'geoipv6_path' => ( defined( 'SITE_GEOIPV6_PATH' ) ? strval( SITE_GEOIPV6_PATH ) : '' ),
					'debug' => ( defined( 'SITE_DEBUG' ) ? boolval( SITE_DEBUG ) : false ),
					'csrf' => ( defined( 'SITE_CSRF' ) ? boolval( SITE_CSRF ) : true ),
					'header_ip_address' => true
				),
				'mysql' => array(
					'host' => ( defined( 'MYSQL_HOST' ) ? strval( MYSQL_HOST ) : '' ),
					'user' => ( defined( 'MYSQL_USER' ) ? strval( MYSQL_USER ) : '' ),
					'pass' => ( defined( 'MYSQL_PASS' ) ? strval( MYSQL_PASS ) : '' ),
					'db' => ( defined( 'MYSQL_DB' ) ? strval( MYSQL_DB ) : '' ),
					'prefix' => ( defined( 'MYSQL_PREFIX' ) ? strval( MYSQL_PREFIX ) : '' ),
					'persistent' => ( defined( 'MYSQL_PERSISTENT' ) ? boolval( MYSQL_PERSISTENT ) : false )
				)
			);
	}
    
    $upgrade_notice = array('type' => 'info', 'value' => '<p style="font-weight: bold; background-color: #A7A7A7; padding: 5px; border: 1px solid #000;">It appears Little Software Stats has already been installed. If this is the case, please use the <a href="upgrade.php">upgrade utility</a> instead.</p>');
}


$steps = array(

    // Step 1
    array(
        // Step name
        'name' => 'Select your language',

        // Items we're going to display
        'fields' => array(

             $upgrade_notice,

            // Simple text
            array(
                'type' => 'info',
                'value' => 'To begin, please select the preferred language and click on "Next".',
            ),

            // Language selection drop down box
            // PHP Setup wizard will automatically scan for available languages and display them
            array(
                'type' => 'language',
                'label' => 'Language',
                'name' => 'language',
            ),

            // Checkbox
            array(
                'type' => 'info',
                'value' => 'By clicking "Next", you agree to the terms &amp; conditions of the <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU General Public License v3</a>.'
            ),

        ),
    ),

    // Step 2
    array(
        // Step name
        'name' => 'Server requirements',

        // Items we're going to display
        'fields' => array(

            // Simple text
            array(
                'type' => 'info',
                'value' => 'Before proceeding with the full installation, we will carry out some tests on your server configuration to ensure that you are able to install and run our software.
                    Please ensure you read through the results thoroughly and do not proceed until all the required tests are passed.',
            ),

            // Check PHP configuration
            array(
                'type' => 'php-config',
                'label' => 'Required PHP settings',
                'items' => array(
                    'php_version' => array('>=5.0', 'PHP Version'), // PHP version must be at least 5.0
                    'short_open_tag' => null, // Display the value for "short_open_tag" setting
                    'register_globals' => false, // "register_globals" must be disabled
                    'safe_mode' => false, // "safe_mode" must be disabled
                    'allow_url_fopen' => true, // "allow_url_fopen" must be enabled
                ),
            ),

            // Check loaded PHP modules
            array(
                'type' => 'php-modules',
                'label' => 'Required PHP modules',
                'items' => array(
                    'mysql' => array(true, 'MySQL'), // make sure "mysql" module is loaded
                    'gd' => array(true, 'GD'), // make sure "GD" module is loaded
                    'hash' => array(true, 'Hash'), // make sure "hash" module is loaded
                    'json' => array(true, 'JSON'), // make sure "json" module is loaded
                    'session' => array(true, 'Session'), // make sure "session" module is loaded
                    'SimpleXML' => array(true, 'SimpleXML'), // make sure "simplexml" module is loaded
                    'zlib' => array(true, 'ZLib'), // make sure "zlib" module is loaded
                    'mbstring' => array(true, 'MBString'), // make sure "mbstring" module is loaded
                ),
            ),

            // Verify folder/file permissions
            array(
                'type' => 'file-permissions',
                'label' => 'Folders and files',
                'items' => array(
                    'inc/config.php' => 'write', // make sure "config.php" file is writable
                ),
            ),
        ),
    ),

    // Step 4
    array(
        // Step name
        'name' => 'Folder paths',

        // Items we're going to display
        'fields' => array(

            // Simple text
            array(
                'type' => 'info',
                'value' => 'We have automatically predefined the paths required by the system. Please make sure everything is correct before you continue on to the next step.',
            ),

            // Text box
            array(
                'type' => 'text',
                'label' => 'Website URL',
                'name' => 'virtual_path',
                'default' => rtrim(preg_replace('#/install/$#', '', VIRTUAL_PATH), '/').'/', // set default value
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),

            // Text box
            array(
                'type' => 'text',
                'label' => 'Installation path',
                'name' => 'system_path',
                'default' => rtrim(preg_replace('#/install/$#', '', BASE_PATH), '/').'/',
                'validate' => array(
                    array('rule' => 'required'), // make it required
                    array('rule' => 'validate_system_path'), // run "validate_system_path" function the "includes/validation.php" file upon form submission
                ),
            ),
			
			// Text box
            array(
                'type' => 'text',
                'label' => 'GeoIP.dat database path',
                'name' => 'geoipdb_path',
                'default' => rtrim(preg_replace('#/install/$#', '', BASE_PATH), '/').'/geoipdb/GeoIP.dat',
                'validate' => array(
                    array('rule' => 'required'), // make it required
                    array('rule' => 'validate_file'), // run "validate_file" function the "includes/validation.php" file upon form submission
                ),
            ),
			
			// Text box
            array(
                'type' => 'text',
                'label' => 'GeoIPv6.dat database path',
                'name' => 'geoipdbv6_path',
                'default' => rtrim(preg_replace('#/install/$#', '', BASE_PATH), '/').'/geoipdb/GeoIPv6.dat',
                'validate' => array(
                    array('rule' => 'required'), // make it required
                    array('rule' => 'validate_file'), // run "validate_file" function the "includes/validation.php" file upon form submission
                ),
            ),
        ),
    ),

    // Step 5
    array(
        // Step name
        'name' => 'Database settings',

        // Items we're going to display
        'fields' => array(

            // Simple text
            array(
                'type' => 'info',
                'value' => 'Specify your database settings here. Please note that the database for our software must be created prior to this step. If you have not created one yet, do so now.',
            ),

            // Text box
            array(
                'label' => 'Database hostname',
                'name' => 'db_hostname',
                'type' => 'text',
                'default' => ( isset( $config_existing['mysql']['host'] ) ? $config_existing['mysql']['host'] : 'localhost' ),
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),

            // Text box
            array(
                'label' => 'Database username',
                'name' => 'db_username',
                'type' => 'text',
                'default' => ( isset( $config_existing['mysql']['user'] ) ? $config_existing['mysql']['user'] : '' ),
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),

            // Text box
            array(
                'label' => 'Database password',
                'name' => 'db_password',
                'type' => 'text',
                'default' => ( isset( $config_existing['mysql']['pass'] ) ? $config_existing['mysql']['pass'] : '' ),
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),

            // Text box
            array(
                'label' => 'Database name',
                'name' => 'db_name',
                'type' => 'text',
                'default' => ( isset( $config_existing['mysql']['db'] ) ? $config_existing['mysql']['db'] : '' ),
                'highlight_on_error' => false,
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array(
                        'rule' => 'database', // system will automatically verify database connection details based on the provided values
                        'params' => array(
                            'db_host' => 'db_hostname',
                            'db_user' => 'db_username',
                            'db_pass' => 'db_password',
                            'db_name' => 'db_name'
                        )
                    ),
                ),
            ),

            // Text box
            array(
                'label' => 'Database prefix',
                'name' => 'db_prefix',
                'type' => 'text',
                'default' => ( isset( $config_existing['mysql']['prefix'] ) ? $config_existing['mysql']['prefix'] : '' ),
            ),
            
            // Checkbox
            array(
            	'label' => '',
                'name' => 'db_persistent',
                'type' => 'checkbox',
                'items' => array(
                    'db_persistent' => 'Enable MySQL Persistent connections'
                )
            ),
        ),
    ),

    // Step 6
    array(
        // Step name
        'name' => 'Ready to install',

        // Items we're going to display
        'fields' => array(

            // Simple text
            array(
                'type' => 'info',
                'value' => 'We are now ready to proceed with installation. At this step we will attempt to create all required tables and populate them with data. Should something go wrong, go back to the Database Settings step and make sure everything is correct.',
            ),
        ),

        // Callback functions that will be executed
        'callbacks' => array(
            array('name' => 'install'), // run "install" function the "includes/callbacks.php" file upon successful form submission
        ),
    ),

    // Step 7
    array(
        // Step name
        'name' => 'Administrator account',

        // Items we're going to display
        'fields' => array(
            // Simple text
            array(
                'type' => 'info',
                'value' => 'Database tables have been successfully created and populated with data!',
            ),
            array(
                'type' => 'info',
                'value' => 'You may now set up an administrator account for yourself. This will allow you to manage the website through the control panel.',
            ),

            // Text box
            array(
                'label' => 'Email',
                'name' => 'user_email',
                'type' => 'text',
                'default' => '',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'valid_email'), // make sure email address is valid
                ),
            ),

            // Text box
            array(
                'label' => 'Username',
                'name' => 'user_username',
                'type' => 'text',
                'default' => '',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'alpha_dash'), // make sure username is valid
                    array('rule' => 'no_spaces'), // make sure username has no spaces
                    array('rule' => 'min_length', 'params' => 5), // make sure username does not contain less than 5 characters
                    array('rule' => 'max_length', 'params' => 20), // make sure username does not contain more than 20 characters
                ),
            ),

            // Text box
            array(
                'label' => 'Password',
                'name' => 'user_password',
                'type' => 'text',
                'default' => '',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'alpha_numeric'), // make sure only alpha-numeric characters are provided
                    array('rule' => 'min_length', 'params' => 5), // make sure password does not contain less than 5 characters
                    array('rule' => 'max_length', 'params' => 20), // make sure password does not contain more than 20 characters
                ),
            ),

            // Text box
            array(
                'label' => 'Password (confirm)',
                'name' => 'user_password2',
                'type' => 'text',
                'default' => '',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'matches', 'params' => 'user_password'), // make sure password text boxes match each other
                ),
            ),
        ),
    ),

    // Step 8
    array(
        // Step name
        'name' => 'Application information',

        // Items we're going to display
        'fields' => array(

            // Simple text
            array(
                'type' => 'info',
                'value' => 'You may now set up a application. This will allow data to be sent to Little Software Stats',
            ),

            // Text box
            array(
                'label' => 'Application ID',
                'name' => 'app_id',
                'type' => 'text',
                'default' => md5(microtime(true).mt_rand(10000,90000)),
                'attributes' => array('readonly' => 'true'), // make it "readonly"
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),

            // Text box
            array(
                'label' => 'Application Name',
                'name' => 'app_name',
                'type' => 'text',
                'default' => '',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),
        ),
    ),
    
    // Step 9
    array(
        // Step name
        'name' => 'Additional information',

        // Items we're going to display
        'fields' => array(

            // Simple text
            array(
                'type' => 'info',
                'value' => 'Some additional information you may want to specify',
            ),
            
            // Checkbox
            array(
                'name' => 'rewrite_enabled',
                'label' => '',
                'type' => 'checkbox',
                'items' => array(
                    'rewrite_enabled' => 'Enable URL Rewriting Support'
                )
            ),

            // Checkbox
            array(
                'name' => 'recaptcha_enabled',
                'label' => '',
                'type' => 'checkbox',
                'items' => array(
                    'recaptcha_enabled' => 'Enable reCAPTCHA Support'
                )
            ),

            // Text box
            array(
                'label' => 'reCAPTCHA Public Key',
                'name' => 'recaptcha_publickey',
                'type' => 'text',
                'default' => '',
            ),
            
            // Text box
            array(
                'label' => 'reCAPTCHA Private Key',
                'name' => 'recaptcha_privatekey',
                'type' => 'text',
                'default' => '',
            ),
            
            // Dropdown menu
            array(
                'label' => 'Mail Protocol',
                'name' => 'mail_protocol',
                'type' => 'select',
                'default' => 'mail',
                'items' => array(
                    'mail' => 'PHP Mail()',
                    'smtp' => 'SMTP',
                    'sendmail' => 'Sendmail',
                ),
            ),
            
            // Text box
            array(
                'label' => 'SMTP Server',
                'name' => 'smtp_server',
                'type' => 'text',
                'default' => 'localhost',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'no_spaces'), // make sure no spaces in value
                ),
            ),
            
            // Text box
            array(
                'label' => 'SMTP Port',
                'name' => 'smtp_port',
                'type' => 'text',
                'default' => '25',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'numeric'), // make sure only numeric characters are provided
                    array('rule' => 'min_value', 'params' => 1), // make sure port is not less than 1
                    array('rule' => 'max_value', 'params' => 65534), // make sure port is not more than 65534
                ),
            ),
            
            // Text box
            array(
                'label' => 'SMTP Username',
                'name' => 'smtp_username',
                'type' => 'text',
                'default' => 'joe.blow',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'no_spaces'), // make sure no spaces in value
                ),
            ),
            
            // Text box
            array(
                'label' => 'SMTP Password',
                'name' => 'smtp_password',
                'type' => 'text',
                'default' => 'password',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                    array('rule' => 'no_spaces'), // make sure no spaces in value
                ),
            ),
            
            // Text box
            array(
                'label' => 'Sendmail Path',
                'name' => 'sendmail_path',
                'type' => 'text',
                'default' => '/usr/sbin/sendmail',
                'validate' => array(
                    array('rule' => 'required'), // make it "required"
                ),
            ),
            
            // Dropdown menu
            array(
                'label' => 'GeoIP Service',
                'name' => 'geoip_service',
                'type' => 'select',
                'default' => 'database',
                'items' => array(
                    'database' => 'Use MaxMind GeoLite Country Database',
                    'api' => 'Use GeoIPs API (Requires API key from http://www.geoips.com/auth/register)',
                ),
            ),
            
            // Text box
            array(
                'label' => 'GeoIPs API Key',
                'name' => 'geoip_apikey',
                'type' => 'text',
                'default' => '',
                'validate' => array(
                    //array('rule' => 'alpha_numeric'), // Key is alpha numeric
                    //array('rule' => 'exact_length', 'params' => 32), // Keys are exactly 32 characters
                ),
            ),
        ),

        // Callback functions that will be executed
        'callbacks' => array(
            array('name' => 'setup'), // run "setup" function the "includes/callbacks.php" file upon successful form submission
        ),
    ),

    // Step 10
    array(
        // Step name
        'name' => 'Completed',

        // Items we're going to display
        'fields' => array(
            // Simple text
            array(
                'type' => 'info',
                'value' => 'Administrator\'s account has been successfully created.',
            ),
            array(
                'type' => 'info',
                'value' => 'Your website is available at <a href="'.rtrim(isset($_SESSION['params']['virtual_path']) ? $_SESSION['params']['virtual_path'] : '', '/').'" target="_blank">'.rtrim(isset($_SESSION['params']['virtual_path']) ? $_SESSION['params']['virtual_path'] : '', '/').'</a>'
            ),
            array(
                'type' => 'info',
                'value' => 'Your installation details:',
            ),
            array(
                'type' => 'info',
                'value' => 'Username: '.(isset($_SESSION['params']['user_username']) ? $_SESSION['params']['user_username'] : '').'<br/>
                    Password: '.(isset($_SESSION['params']['user_password']) ? $_SESSION['params']['user_password'] : ''),
            ),
            array(
                'type' => 'info',
                'value' => 'Application ID: '.(isset($_SESSION['params']['app_id']) ? $_SESSION['params']['app_id'] : '').'<br/>
                    Application Name: '.(isset($_SESSION['params']['app_name']) ? $_SESSION['params']['app_name'] : ''),
            ),
        ),
    ),
);
