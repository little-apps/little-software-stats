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

if ( !extension_loaded('mysql') &&  !extension_loaded('mysqli') ) die( 'No MySQL extension is installed with PHP.' );

/**
 * PHP MySQL Class
 * Manage connection to MySQL database
 *
 * @package Little Software Stats
 * @author Ed Rackham
 * @link http://github.com/a1phanumeric/PHP-MySQL-Class
 */
class MySQL {
    // Base variables
    /** 
     * @var string Holds the last error
     */
    public $last_error;
    /** 
     * @var string Holds the last query
     */
    public $last_query;
    /** 
     * @var array Holds the MySQL query result
     */
    public $result;
    /**
     * @var int Holds the total number of records returned 
     */
    public $records;
    /** 
     * @var int Holds the total number of records affected 
     */
    public $affected;
    /**
     * @var array Holds raw 'arrayed' results
     */
    public $raw_results;
    /**
     * @var array Holds a single 'arrayed' result
     */
    public $arrayed_result;
    /** 
     * @var array Holds multiple 'arrayed' results (usually with a set key) 
     */
    public $arrayed_results;
    
    /**
     * @var int Total number of queries executed
     */
    public $total_queries = 0;

    /** 
     * @var string MySQL Hostname
     */
    private $hostname = MYSQL_HOST;
    /** 
     * @var string MySQL Username 
     */
    private $username = MYSQL_USER;
    /** 
     * @var string MySQL Password
     */
    private $password = MYSQL_PASS;
    /** 
     * @var string MySQL Database 
     */
    private $database = MYSQL_DB;
    /** 
     * @var string MySQL Database Prefix 
     */
    public $prefix = MYSQL_PREFIX;

    /**
     * @var resource Database Connection Link
     */
    private $db_link = null;
    
    /**
     * @var bool True if MySQL "Improved" is loaded
     */
    private $mysqli_loaded = false;
    
    /**
     * @var resource Single instance of class
     */
    private static $m_pInstance; 

    /**
     * Class Constructor
     * Assigning values to variables
     */
    function __construct() {
        $this->connect( MYSQL_PERSISTENT );
    }
    
    /**
     * Gets single instance of class
     * @access public
     * @static
     * @return resource Single instance of class 
     */
    public static function getInstance()
    {
        if ( !self::$m_pInstance )
            self::$m_pInstance = new MySQL();

        return self::$m_pInstance;
    }

    /**
     * Connects class to database
     * @access private
     * @param bool $persistant Use persistant connection? (default: false)
     * @return bool Returns true if connection has been made 
     */
    private function connect( $persistant = false ){
        $this->mysqli_loaded = extension_loaded( 'mysqli' );

        if ( $this->mysqli_loaded ) {
            if ( $persistant )
                $this->db_link = new mysqli( 'p:' . $this->hostname, $this->username, $this->password );
            else
                $this->db_link = new mysqli( $this->hostname, $this->username, $this->password );
        } else {
            if ( $persistant )
                $this->db_link = @mysql_pconnect( $this->hostname, $this->username, $this->password );
            else
                $this->db_link = @mysql_connect( $this->hostname, $this->username, $this->password );
        }

        if ( !$this->db_link ) {
        	if ( defined( 'LSS_API' ) ) {
				$message = get_error( -7 );
			} else {
				$message = __( 'Could not connect to server, error: ' ) . $this->last_error();
			}
        	
            lss_exit( $message );
        }

        if ( !$this->use_db() ) {
        	if ( defined( 'LSS_API' ) ) {
				$message = get_error( -7 );
			} else {
				$message = __( 'Cannot select database, error: ' ) . $this->last_error();
			}
        	
            lss_exit( $message );
        }

        return true;
    }

    /**
     * Select database to use
     * @access private
     * @return bool Returns true if database was selected 
     */
    private function use_db() {
        if ( $this->mysqli_loaded ) {
            return $this->db_link->select_db( $this->database );
        } else {
            if ( !@mysql_select_db( $this->database, $this->db_link ) )
                return false;
            else
                return true;
        }
        
    }
    
    private function last_error() {
        if ( $this->mysqli_loaded ) {
            if ( $this->db_link )
                return mysqli_error( $this->db_link );
            else 
                return mysqli_connect_error();
        } else {
            return mysql_error( $this->db_link );
        }
    }

    /**
     * Executes MySQL query
     * (Always make sure you are using mysql_real_escape_string on user supplied input before calling this function)
     * @access public
     * @param string $sql_query Query to execute
     * @return bool Returns true if execution was successful
     */
    public function execute_sql( $sql_query ) {
        $this->last_query = $sql_query;
        
        $this->total_queries++;
        
        if ( $this->mysqli_loaded ) {
            if( $this->result = $this->db_link->query( $sql_query ) ) {
                $this->records = $this->db_link->affected_rows;
                $this->affected = $this->db_link->affected_rows;
                return true;
            } else {
                $this->last_error = $this->last_error();
                return false;
            }
        } else {
            if( $this->result = mysql_query( $sql_query, $this->db_link ) ) {
                $this->records = @mysql_num_rows( $this->result );
                $this->affected = @mysql_affected_rows( $this->db_link );
                return true;
            } else {
                $this->last_error = $this->last_error();
                return false;
            }
        }
        
    }
    
    /** 
     * Gets MySQL version
     * @access public
     * @return string MySQL version
     */
    public function get_db_version() {
        if ( $this->mysqli_loaded )
            return preg_replace( '/[^0-9.].*/', '', $this->db_link->server_info );
        else
            return preg_replace( '/[^0-9.].*/', '', mysql_get_server_info( $this->db_link ) );
    }

    /**
     * Adds a record to the database based on the array key names
     * @access public
     * @param array $vars Variables to insert
     * @param type $table Table to insert variables into
     * @param array $exclude Column(s) to exclude
     * @return bool Returns true if insert was successful 
     */
    public function insert( $vars, $table, $exclude = '' ) {
        if ( !isset( $vars ) || !is_array( $vars ) )
            return false;
        
        if ( !isset( $table ) || !is_string( $table ) )
            return false;
        
        // Catch Exceptions
        if( $exclude == '' )
            $exclude = array();

        array_push( $exclude, 'MAX_FILE_SIZE' );

        // Prepare Variables
        $vars = $this->secure_data( $vars );

        $query = 'INSERT INTO `' . $this->prefix . $table . '` SET ';
        foreach( $vars as $key => $value ) {
            if( in_array( $key, $exclude ) )
                continue;
            $query .= '`' . $key . '` = "' . $value . '", ';
        }

        $query = substr( $query, 0, -2 );

        if ( $this->execute_sql( $query ) )
            return true;
        else
            return false;
    }

    /**
     * Inserts a record or updates a record if it already exists
     * @access public
     * @param array $insert_vars Variables to be inserted
     * @param array $update_vars Variables to be updated
     * @param string $table Table to insert or update variables
     * @param array $exclude Variables to be excluded
     * @return bool Returns true if record was inserted or updated 
     */
    public function insert_or_update( $insert_vars, $update_vars, $table, $exclude = '' ) {
        if ( !isset( $insert_vars ) || !is_array( $insert_vars ) )
            return false;

        if ( !isset( $update_vars ) || !is_array( $update_vars ) )
            return false;

        if ( !isset( $table ) || !is_string( $table ) )
            return false;

        // Catch Exceptions
        if ( $exclude == '' )
            $exclude = array();

        array_push( $exclude, 'MAX_FILE_SIZE' );

        // Prepare Variables
        $insert_vars = $this->secure_data( $insert_vars );
        $update_vars = $this->secure_data( $update_vars );

        $query = 'INSERT INTO `' . $this->prefix . $table . '` SET ';
        foreach( $insert_vars as $key => $value ) { 
            if ( in_array( $key, $exclude ) )
                continue;
            $query .= '`' . $key . '` = "' . $value . '", ';
        }

        $query = substr( $query, 0, -2 );

        $query .= " ON DUPLICATE KEY UPDATE ";

        foreach ( $update_vars as $key => $value ) {
            if ( in_array( $key, $exclude ) )
                continue;
            $query .= '`' . $key . '` = "' . $value . '", ';
        }

        $query = substr( $query, 0, -2 );

        if( $this->execute_sql( $query ) )
            return true;
        else
            return false;
    }

    /**
     * Deletes a record from the database
     * @access public
     * @param string $table Table to delete from
     * @param array $where Column(s) to search for
     * @param string $limit Limit of how many records
     * @param bool $use_like Use like to search
     * @return bool Returns true if delete was successful 
     */
    public function delete( $table, $where = '', $limit = '', $use_like = false ){
        $query = 'DELETE FROM `' . $this->prefix . $table . '` WHERE ';

        if ( is_array( $where ) && $where != '' ) {
            // Prepare Variables
            $where = $this->secure_data( $where );

            foreach ( $where as $key => $value ) {
                if ( $use_like )
                    $query .= '`' . $key . '` LIKE "%' . $value . '%" AND ';
                else
                    $query .= '`' . $key . '` = "' . $value . '" AND ';
            }

            $query = substr( $query, 0, -5 );
        }

        if ( $limit != '' ) {
            $query .= ' LIMIT ' .$limit;
        }

        if ( $this->execute_sql( $query ) )
            return true;
        else
            return false;
    }

    /**
     * Selects row(s) from table
     * @access public
     * @param string $table Table to select from
     * @param array $where Column(s) to search for
     * @param string $order_by Column to order by
     * @param string $limit Number of rows that can be returned
     * @param bool $use_like Use 'LIKE' instead of '='
     * @param string $operand Operand to use ('AND' or 'OR')
     * @return bool Returns true if select was successful, otherwise, false if nothing was found
     */
    public function select( $table, $where = '', $order_by = '', $limit = '', $use_like = false, $operand = 'AND' ) {
        // Catch Exceptions
        if( trim( $table ) == '' )
            return false;

        $query = 'SELECT * FROM `' . $this->prefix . $table . '` WHERE ';

        if ( is_array( $where ) && $where != '' ) {
            // Prepare Variables
            $where = $this->secure_data( $where );

            foreach ( $where as $key => $value ) {
                if( $use_like )
                    $query .= '`' . $key . '` LIKE "%' . $value . '%" ' . $operand . ' ';
                else
                    $query .= '`' . $key . '` = "' . $value . '" ' . $operand . ' ';
            }

            $query = substr( $query, 0, -5 );
        } else {
            $query = substr( $query, 0, -7 );
        }

        if ( $order_by != '' )
            $query .= ' ORDER BY ' . $order_by;

        if( $limit != '' )
            $query .= ' LIMIT ' .$limit;

        if ( $this->execute_sql( $query ) ) {
            if( $this->records == 1 ) 
                $this->array_result();
            else if ( $this->records > 1 )
                $this->array_results();
            else
                return false;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Selects distinct column from table
     * @access public
     * @param string $column Column to select
     * @param string $table Table to select from
     * @param array $where Column(s) to search for
     * @param string $order_by Column to order by
     * @param string $limit Number of rows that can be returned
     * @param bool $use_like Use 'LIKE' instead of '='
     * @param string $operand Operand to use ('AND' or 'OR')
     * @return bool Returns true if select was successful, otherwise, false if nothing was found
     */
    public function select_distinct( $column, $table, $where = '', $order_by = '', $limit = '', $use_like = false, $operand = 'AND' ) {
        // Catch Exceptions
        if( trim( $table ) == '' && trim( $column ) == '' )
            return false;

        // Prepare Variables
        $column = $this->secure_data( $column );
        $table = $this->secure_data( $table );

        $query = 'SELECT DISTINCT `' . $column . '` FROM `' . $this->prefix . $table . '` WHERE ';

        if( is_array( $where ) && $where != '' ) {
            // Prepare Variables
            $where = $this->secure_data( $where );

            foreach( $where as $key => $value ) {
                if ( $use_like )
                    $query .= '`' . $key . '` LIKE "%' . $value . '%" ' . $operand . ' ';
                else
                    $query .= '`' . $key . '` = "' . $value . '" ' . $operand . ' ';
            }

            $query = substr( $query, 0, -5 );
        } else {
            $query = substr( $query, 0, -7 );
        }

        if( $order_by != '' )
            $query .= ' ORDER BY ' . $order_by;

        if( $limit != '' )
            $query .= ' LIMIT ' .$limit;

        if( $this->execute_sql( $query ) ) {
            if( $this->records == 1 ) 
                $this->array_result();
            else if( $this->records > 1 )
                $this->array_results();
            else
                return false;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Selects column count from table
     * @access public
     * @param string $table Table
     * @param string $column Columns seperated by ',' (default: '*')
     * @param array $where Column(s) to search for (default: '')
     * @param bool $use_like If true uses 'LIKE' instead of '=' to search (default: false)
     * @return int|bool Returns count or false if there was an error 
     */
    public function select_count( $table, $column = '*', $where = '', $use_like = false, $operand = 'AND' ) {
        // Catch Exceptions
        if( trim( $table ) == '' )
            return false;

        // Prepare Variables
        $column = $this->secure_data( $column );
        $table = $this->secure_data( $table );

        $query = 'SELECT COUNT(' . $column . ') AS count FROM `' . $this->prefix . $table . '` WHERE ';

        if ( is_array( $where ) && $where != '' ) {
            // Prepare Variables
            $where = $this->secure_data( $where );

            foreach ( $where as $key => $value ) {
                if ( $use_like )
                    $query .= '`' . $key . '` LIKE "%' . $value . '%" ' . $operand . ' ';
                else
                    $query .= '`' . $key . '` = "' . $value . '" ' . $operand . ' ';
            }

            $query = substr( $query, 0, -5 );
        } else {
            $query = substr( $query, 0, -7 );
        }

        if ( $this->execute_sql( $query ) ) {
            $this->array_result();
            return intval( $this->arrayed_result['count'] );
        } else {
            return false;
        }
    }

    /**
     * Selects sessions for specified time period
     * @access public
     * @param string $app_id Application ID
     * @param string $app_ver Application version
     * @param int $start Start time (in Unix time)
     * @param int $end Stop time (in Unix time)
     * @param string $column Column(s) to select (default is '*')
     * @param bool $distinct If true, only selects distinct column (default is false)
     * @param bool $count If true, returns number of rows (default is false)
     * @param array $where Column(s) to search for (default: '')
     * @return int|bool|array Returns list of sessions or number of rows found, otherwise, false if error occurred
     */
    public function select_sessions( $app_id, $app_ver, $start, $end, $column = '*', $distinct = false, $count = false, $where = '' ) {
        if ( !is_numeric( $start ) || !is_numeric( $end ) )
            return false;

        $start = intval( $start );
        $end = intval( $end );

        // Prepare variables
        $app_id = $this->secure_data( $app_id );
        $app_ver = $this->secure_data( $app_ver );
        $column = $this->secure_data( $column );

        if ( $count )
            $query = 'SELECT COUNT(' . ( ( $distinct ) ? ( 'DISTINCT ' ) : ( '' ) ) . $column . ') AS count FROM `' . $this->prefix . 'sessions` ';
        else
            $query = 'SELECT ' . ( ( $distinct ) ? ( 'DISTINCT ' ) : ( '' ) ) . $column . ' FROM `' . $this->prefix . 'sessions` ';

        $query .= "WHERE `ApplicationId` = '" . $app_id."' " . ( ( $app_ver != 'all') ? ( "AND `ApplicationVersion` = '" . $app_ver . "' " ) : ( '' ) );
        $query .= "AND `StartApp` BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ") ";

        if ( is_array( $where ) && $where != '' ) {
            // Prepare Variables
            $where = $this->secure_data( $where );

            foreach ( $where as $key => $value ) {
                $query .= 'AND `' . $key . '` = "' . $value . '" ';
            }
        }

        if( $this->execute_sql( $query ) ) {
            if ( $count ) {
                $this->array_result();
                return intval( $this->arrayed_result['count'] );
            } else {
                $sessions = array();

                if ( $this->records == 1 ) 
                    $sessions[] = $this->array_result();
                else if( $this->records > 1 )
                    $sessions = $this->array_results();

                return $sessions;
            }
        } else {
            return false;
        }
    }


    /**
     * Selects events using application ID, version, event table, and specified time period
     * @access public
     * @param string $event_table Event table
     * @param string $app_id Application ID
     * @param string $app_ver Application version
     * @param int $start Start time (in Unix time)
     * @param int $end Stop time (in Unix time)
     * @param bool $count If true, returns number of rows (default is false)
     * @param array $where Column(s) to search for (default: '')
     * @return int|bool|array Returns list of sessions or number of rows found, otherwise, false if error occurred
     */
    public function select_events( $event_table, $app_id, $app_ver, $start, $end, $count = false, $where = '', $group_by = '' ) {
        if ( !is_numeric( $start ) || !is_numeric( $end ) )
            return false;

        $start = intval( $start );
        $end = intval( $end );
        
        // Ensure table exists
        $valid_tables = array('event', 'eventvalue', 'eventperiod', 'log', 'customdata', 'exception', 'install', 'uninstall');
        
        if ( !in_array( $event_table, $valid_tables ) )
        	return false;

        // Prepare variables
        $app_id = $this->secure_data( $app_id );
        $app_ver = $this->secure_data( $app_ver );

        $query = "SELECT " . ( ( $count ) ? ( "COUNT(*) AS count" ) : ( "*" ) ) . " ";
        $query .= 'FROM `' . $this->prefix . 'events_' . $event_table . '` AS e ';
        $query .= 'INNER JOIN `' . $this->prefix . 'sessions` AS s ON e.SessionId = s.SessionId ';
        $query .= "WHERE s.ApplicationId = '" . $app_id . "' " . ( ( $app_ver != 'all') ? ( "AND s.ApplicationVersion = '" . $app_ver . "' " ) : ( '' ) );
        $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ") ";

        if ( is_array( $where ) && $where != '' ) {
            // Prepare Variables
            $where = $this->secure_data( $where );

            foreach ( $where as $key => $value ) {
                $query .= 'AND `' . $key . '` = "' . $value . '" ';
            }
        }
        
        if ( $group_by != '' )
            $query .= 'GROUP BY ' . $group_by;

        if ( $this->execute_sql( $query ) ) {
            if ( $count ) {
                $this->array_result();
                return intval( $this->arrayed_result['count'] );
            } else {
                $events = array();
                if( $this->records == 1 ) 
                    $events[] = $this->array_result();
                else if($this->records > 1)
                    $events = $this->array_results();
                return $events;
            }
        } else {
            return false;
        }
    }


    /**
     * Updates a record in the database based on WHERE
     * @access public
     * @param string $table Table to update
     * @param array $set Variables to update
     * @param array $where Column(s) to search for
     * @param array $exclude Column(s) to exclude
     * @return bool Returns true if update was successful 
     */
    public function update( $table, $set, $where, $exclude = '' ) {
        // Catch Exceptions
        if( trim( $table ) == '' || !is_array( $set ) || !is_array( $where  ) )
            return false;

        if ( $exclude == '')
            $exclude = array();

        array_push( $exclude, 'MAX_FILE_SIZE' );

        $set = $this->secure_data( $set );
        $where = $this->secure_data( $where );

        $query = 'UPDATE `' . $this->prefix . $table . '` SET ';

        foreach( $set as $key => $value ) {
            if ( in_array( $key, $exclude ) )
                continue;
            $query .= '`' . $key . '` = "' . $value . '", ';
        }

        $query = substr( $query, 0, -2 );

        $query .= ' WHERE ';

        foreach ( $where as $key => $value ) {
            $query .= '`' . $key . '` = "' . $value . '" AND ';
        }

        $query = substr( $query, 0, -5 );

        if ( $this->execute_sql( $query ) )
            return true;
        else
            return false;
    }

    /**
     * 'Arrays' a single result
     * @access public
     * @return array|bool Returns a single row or false if no rows were found
     */
    public function array_result() {
        if ( $this->records == 0 )
            return false;

        if ( $this->mysqli_loaded ) {
            if ( !is_object( $this->result ) )
                return false;
            
            $this->arrayed_result = $this->result->fetch_assoc() or die( $this->last_error() );
            
            $this->result->free();
        } else {
            $this->arrayed_result = mysql_fetch_assoc( $this->result ) or die( mysql_error( $this->db_link ) );
        
            mysql_free_result( $this->result );
        }
        
        return $this->arrayed_result;
    }

    /**
     * 'Arrays' multiple result
     * @access public
     * @return array|bool Returns multiple rows or false if no rows were found
     */
    public function array_results() {
        if ($this->records == 0)
            return false;

        $this->arrayed_results = array();
        
        if ( $this->mysqli_loaded ) {
            if ( !is_object( $this->result ) )
                return false;
            
            while ( $data = $this->result->fetch_assoc() ) {
                $this->arrayed_results[] = $data;
            }
            
            $this->result->free();
        } else {
            while ( $data = mysql_fetch_assoc( $this->result ) ){
                $this->arrayed_results[] = $data;
            }

            mysql_free_result( $this->result );
        }

        return $this->arrayed_results;
    }

    /**
     * Performs a 'mysql_real_escape_string' on the entire array/string
     * @access public
     * @param array|string $data Data to prevent from SQL injection
     * @return array|string Returns SQL injection stripped data
     */
    public function secure_data( $data ){
        if( is_array( $data ) ){
            foreach ( $data as $key=>$val ) {
                if( !is_array( $data[$key] ) ) {
                    if ( $this->mysqli_loaded )
                        $data[$key] = $this->db_link->real_escape_string( $val );
                    else
                        $data[$key] = mysql_real_escape_string( $val, $this->db_link );
                }
            }
        } else {
            if ( $this->mysqli_loaded )
                $data = $this->db_link->real_escape_string( $data );
            else
                $data = mysql_real_escape_string( $data, $this->db_link );
        }
        return $data;
    }
    
    /**
	* Closes MySQLi/MySQL connection
	* Please note that this will cause all future SQL queries to fail
	* 
	* @return
	*/
    public function close() {
		if ( $this->mysqli_loaded ) {
			$this->db_link->close();
		} else {
			mysql_close( $this->db_link );
		}
	}
}
