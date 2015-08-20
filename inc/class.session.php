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

if ( !defined( 'LSS_LOADED' ) ) die( 'This page cannot be loaded directly' );

class Session {
    const SESSION_STARTED = true;
    const SESSION_NOT_STARTED = false;
   
    /**
	 * 
	 * @var bool State of the session
	 * 
	 */
    private $session_state = self::SESSION_NOT_STARTED;
   
    /**
     * @var resource Single instance of class
     */
    private static $m_pInstance;
   
    public function __construct() {
    	$this->start_session();
    }
    
	/**
     * Gets single instance of class
     * @access public
     * @static
     * @return resource Single instance of class 
     */
    public static function getInstance()
    {
        if (!self::$m_pInstance)
            self::$m_pInstance = new Session();

        return self::$m_pInstance;
    }
   
   
    /**
     * (Re)starts the session.
     *   
     * @return bool True if the session has been initialized, else False.
     */
    public function start_session() {
        if ( $this->session_state == self::SESSION_NOT_STARTED ) {
            $this->session_state = session_start();
            
            if ( $this->session_state == self::SESSION_NOT_STARTED )
            	$this->session_state = $this->session_started();
        }
       
        return $this->session_state;
    }
    
    /**
	 * Determines true if a session has been started
	 * 
	 * @return bool Returns true if session has been started, otherwise, false
	 */
    public function session_started() {
		if ( php_sapi_name() !== 'cli' ) {
	        if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
	            return session_status() === PHP_SESSION_ACTIVE ? true : false;
	        } else {
	            return session_id() === '' ? false : true;
	        }
	    }
	    
	    return false;
	}
   
    /**
	 * Stores data in the session.
	 * Example: $instance->foo = 'bar';
	 * 
	 * @param string $name Session key
	 * @param mixed $value Data for session key
	 * 
	 * @return
	 */
    public function __set( $name , $value ) {
        $_SESSION[$name] = $value;
    }
   
    /**
	 * Gets datas from the session.
	 * Example: echo $instance->foo;
	 * 
	 * @param string $name Session key to get data
	 * 
	 * @return mixed Session data for key
	 */
    public function __get( $name )
    {
        if ( isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
    }

    public function __isset( $name ) {
        return isset($_SESSION[$name]);
    }
   
   
    public function __unset( $name ) {
        unset( $_SESSION[$name] );
    }
   
   
    /**
     * Destroys the current session.
     *   
     * @return bool True is session has been deleted, else False.
     */
    public function destroy() {
        if ( $this->session_state == self::SESSION_STARTED ) {
            $this->session_state = !session_destroy();
            unset( $_SESSION );
           
            return !$this->session_state;
        }
       
        return false;
    }
}