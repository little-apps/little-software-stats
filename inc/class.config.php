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
 * @filesource
 */
 
if ( basename( $_SERVER['PHP_SELF'] ) == 'class.config.php' )
    die( 'This page cannot be loaded directly' );

if ( !defined( 'ROOTDIR' ) ) 
	define( 'ROOTDIR', realpath( dirname( __FILE__ ) . '/../' ) );

/**
 * Config Class
 * Read (only) configuration options
 *
 * @package Little Software Stats
 */
class Config {
	/**
	* 
	* @var array Configuration options
	* 
	*/
	private $config = array();
	
	/**
     * @var resource Single instance of class
     */
    private static $m_pInstance;
	
	public function __construct( $config = false ) {
		$this->load_config();
	}
	
	/**
     * Gets single instance of class
     * @access public
     * @static
     * @return resource Single instance of class 
     */
    public static function getInstance() {
        if (!self::$m_pInstance)
            self::$m_pInstance = new Config();

        return self::$m_pInstance;
    }
	
	private function load_config( $config ) {
		if ( empty( $config ) || !is_array( $config ) ) {
			$config_path = ROOTDIR . '/inc/config.php';
		
			if ( !file_exists( $config_path ) )
				throw new Exception("File 'inc/config.php' does not exist");
				
			if ( @filesize( $config_path ) == 0 )
				throw new Exception("File 'inc/config.php' is empty");
				
			$this->config = require( $config_path );
		} else {
			$this->config = $config;
		}
		
		foreach ($this->config as &$val) {
			$val = $this->array_to_object( $val );
		}
		
		return true;
	}
	
	public function __get( $key ) {
		if ( isset( $this->config[$key] ) ) {
			return $this->config[$key];
		}
	}
	
	/**
	 * Converts single-dimension array to object
	 * @param mixed $array Array to convert
	 * 
	 * @return mixed Returns convert array or original variable
	 */
	private function array_to_object( $array ) {
		if (is_array($array)) {
			$obj = new stdClass;
			
			foreach ( $array as $k => $v) {
				$obj->{$k} = $v;
			}
			
			return $obj;
		} else {
			return $array;
		}
	}
	
	public function __isset( $key ) {
		return isset( $this->config[$key] );
	}
}