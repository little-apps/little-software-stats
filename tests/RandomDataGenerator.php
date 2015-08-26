<?php
class RandomDataGenerator {
	/**
     * @var resource Single instance of class
     */
    private static $m_pInstance;
    
	/**
     * Gets single instance of class
     * @access public
     * @static
     * @return resource Single instance of class 
     */
    public static function getInstance( ) {
        if (!self::$m_pInstance)
            self::$m_pInstance = new self;

        return self::$m_pInstance;
    }
    
    private $os_versions = 
    	array(
    		'Windows XP' => array(0, 1, 2, 3), 
    		'Windows Vista' => array(0, 1, 2), 
    		'Windows 7' => array(0, 1), 
    		'Windows Server 2008' => array(0, 1, 2), 
    		'Windows Server 2008 R2' => array(0, 1), 
    		'Windows 8' => array(0), 
    		'Windows 8.1' => array(0), 
    		'Windows Server 2012' => array(0), 
    		'Windows Server 2012 R2' => array(0), 
    		'Windows 10' => array(0)
    	);
    	
    private $os_archs = array(32, 64);
    
	private $java_versions = array('1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7');
	private $dotnet_versions = 
		array(
			'2.0' => array(0, 1, 2), 
			'3.0' => array(0), 
			'3.5' => array(0, 1), 
			'4.0' => array(0),
			'4.5' => array(0),
			'4.5.1' => array(0),
			'4.5.2' => array(0),
			'4.6' => array(0)
		);
		
	private $lang_ids = array(1033, 1055, 1049, 1040, 1034);
	private $screen_resolutions = array('800x600', '1024x768', '1600x1200', '1680x1050');
	private $cpus = array('Intel' => array('Core i7', 'Core i5', 'Core i3', 'Pentium IV'), 'AMD' => array('Athlon', 'Athlon 64', 'Athlon II', 'Phenom', 'Phenom II'));

	private $cpu_num_cores = array(1, 2, 4, 6);
	private $cpu_archs = array(32, 64);
	private $cpu_freqs = array(1000, 1500, 2000, 2500, 3000, 3500, 4000);
	
	private $memory_totals;
    
    private $disk_totals = array(256, 512, 1024, 1536, 2048, 3072, 4096);
    
    private $events = 
    	array(
    		'Buttons' => array( 'Cancel', 'OK', 'Retry' ),
    		'Features' => array( 'Feature One', 'Feature Two', 'Feature Three' )
    	);
    
    private $event_values = 
    	array(
    		// Category => array( 'names' => array( Events ), 'values' => array( Values ) )
    		'Actions' => array( 'names' => array( 'Sending', 'Loading', 'Restarting' ), 'values' => array( 'True', 'False', 'None' ) )
    	);
    	
    private $event_periods = 
    	array(
    		'Actions' => array( 'Sending', 'Loading', 'Restarting' )
    	);
    	
    private $log_messages = array( 'Hello World!', 'Foo Bar' );
    
	public function __construct() {
		// Must be assigned in constructor
		$this->memory_totals = range(1, 24);
	}
	
	public function app_ver() {
		return mt_rand(0, 5) . '.' . mt_rand(1, 30);
	}
	
	public function unique_id() {
		return strtoupper( md5( uniqid() . mt_rand(0, 99999) ) );
	}
	
	public function session_id() {
		return strtoupper( md5( uniqid() . mt_rand(0, 99999) ) );
	}
	
	public function timestamp() {
		$min = time() - 2592000;
		$max = time();
		
		return mt_rand($min, $max);
	}
	
	public function event_timestamp( $timestamp ) {
		if ( !is_numeric( $timestamp ) )
			throw new Exception( 'Parameter must be a number. Instead it is ' . gettype($timestamp) . ' with value ' . var_export($timestamp, true) );
			
		if ( !is_integer( $timestamp ) )
			$timestamp = intval( $timestamp );
		
		return mt_rand( $timestamp, $timestamp + 3600 );
	}
	
	public function os_info() {
		$os_info = new StdClass;
		
		$os_info->version = $this->os_version();
		$os_info->service_pack = $this->os_service_pack( $os_info->version );
		$os_info->arch = $this->os_architecture();
		
		return $os_info;
	}
	
	public function os_version() {
		return array_rand( $this->os_versions );
	}
	
	public function os_service_pack( $os_version ) {
		if ( !isset( $this->os_versions[$os_version] ) )
			throw new Exception( 'OS version not found' );
			
		return $this->array_rand_value( $this->os_versions[$os_version] );
	}
	
	public function os_architecture() {
		return $this->array_rand_value( $this->os_archs );
	}
	
	public function dotnet_info() {
		$dotnet_info = new StdClass;
		
		$dotnet_info->version = $this->dotnet_version();
		$dotnet_info->service_pack = $this->dotnet_service_pack( $dotnet_info->version );
		
		return $dotnet_info;
	}
	
	public function dotnet_version() {
		return array_rand( $this->dotnet_versions );
	}
	
	public function dotnet_service_pack( $dotnet_version ) {
		if ( !isset( $this->dotnet_versions[$dotnet_version] ) )
			throw new Exception( '.NET version not found' );
			
		return $this->array_rand_value( $this->dotnet_versions[$dotnet_version] );
	}
	
	public function java_version() {
		return $this->array_rand_value( $this->java_versions );
	}
	
	public function lang_id() {
		return $this->array_rand_value( $this->lang_ids );
	}
	
	public function screen_resolution() {
		return $this->array_rand_value( $this->screen_resolutions );
	}
	
	public function cpu_info() {
		$cpu_info = new StdClass;
		
		$cpu_info->brand = $this->cpu_brand();
		$cpu_info->name = $this->cpu_name( $cpu_info->brand );
		$cpu_info->cores = $this->cpu_cores();
		$cpu_info->freq = $this->cpu_frequency();
		$cpu_info->arch = $this->cpu_architecture();
		
		return $cpu_info;
	}
	
	public function cpu_brand() {
		return array_rand( $this->cpus );
	}
	
	public function cpu_name( $cpu_brand ) {
		if ( !isset( $this->cpus[$cpu_brand] ) )
			throw new Exception( 'CPU brand not found' );
			
		return $this->array_rand_value( $this->cpus[$cpu_brand] );
	}
	
	public function cpu_cores() {
		return $this->array_rand_value( $this->cpu_num_cores );
	}
	
	public function cpu_architecture() {
		return $this->array_rand_value( $this->cpu_archs );
	}
	
	public function cpu_frequency() {
		return $this->array_rand_value( $this->cpu_freqs );
	}
	
	public function memory_info() {
		$mem_info = new StdClass;;
		
		$mem_info->total = $this->memory_total();
		$mem_info->free = $this->memory_free( $mem_info->total );
		
		return $mem_info;
	}
	
	public function memory_total() {
		$mem_total_megabytes = $this->array_rand_value( $this->memory_totals ) * 1024;
		
		return $mem_total_megabytes;
	}
	
	public function memory_free( $mem_total_megabytes ) {
		return mt_rand( 1, $mem_total_megabytes );
	}
	
	public function disk_info() {
		$disk_info = new StdClass;;
		
		$disk_info->total = $this->disk_total();
		$disk_info->free = $this->disk_free( $disk_info->total );
		
		return $disk_info;
	}
	
	public function disk_total() {
		$disk_total_megabytes = $this->array_rand_value( $this->disk_totals );
		
		return $disk_total_megabytes;
	}
	
	public function disk_free( $disk_total_megabytes ) {
		$disk_free_megabytes = mt_rand(1, $disk_total_megabytes);
		
		return $disk_free_megabytes;
	}
	
	public function event() {
		$event_info = new StdClass;
		
		$event_info->category = array_rand( $this->events );
		$event_info->name = $this->array_rand_value( $this->events[$event_info->category] );
		
		return $event_info;
	}
	
	public function event_period() {
		$event_period_info = new StdClass;
		
		$event_period_info->category = array_rand( $this->event_periods );
		$event_period_info->name = $this->array_rand_value( $this->event_periods[$event_period_info->category] );
		$event_period_info->duration = mt_rand(1, 3600);
		$event_period_info->completed = (bool)mt_rand(0, 1);
		
		return $event_period_info;
	}
	
	public function event_value() {
		$event_value_info = new StdClass;
		
		$event_value_info->category = array_rand( $this->event_values );
		$event_value_info->name = $this->array_rand_value( $this->event_values[$event_value_info->category]['names'] );
		$event_value_info->value = $this->array_rand_value( $this->event_values[$event_value_info->category]['values'] );
		
		return $event_value_info;
	}
	
	public function log_message() {
		return $this->array_rand_value( $this->log_messages );
	}
	
	public function custom_data() {
		$custom_data = new StdClass;
		
		$custom_data->name = 'Email';
		$custom_data->value = $this->random_string(8) . '@email.com';
		
		return $custom_data;
	}
	
	public function exception() {
		$exception_info = new StdClass;
		
		$exception_info->message = $this->random_string(50);
		$exception_info->stack_trace = $this->random_string(20);
		$exception_info->source = $this->random_string(10);
		$exception_info->target_site = $this->random_string(10);
		
		return $exception_info;
	}
	
	private function random_string($length) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	    $string = '';

	    for ($p = 0; $p < $length; $p++) {
	        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
	    }

	    return $string;
	}
	
	private function array_rand_value($array) {
	    if (!is_array($array))
	        throw new Exception( 'Parameter must be array' );
	        
	    if ( empty( $array ) )
	    	throw new Exception( 'Parameter is empty' );
	    
	    $key = array_rand($array);
	    return $array[$key];
	}
}