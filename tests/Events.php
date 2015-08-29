<?php

class Events {
	private $events = array();
	private $format;
	
	private $app_id;
	
	private $unique_id;
	private $session_id;
	
	private static $flow_id = 0;
	
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
    
    public static function get_flow_id() {
		self::$flow_id++;
		
		return self::$flow_id;
	}
    
    public function __construct() {
		
	}
	
	public function set_format( $format ) {
		if ( $format != ExternalAPITest::FORMAT_JSON && $format != ExternalAPITest::FORMAT_XML )
			throw new Exception( 'Invalid format' );
			
		$this->format = $format;
		
		return true;
	}
	
	public function start( $app_id ) {
		self::$flow_id = 0;
		
		$data_generator = RandomDataGenerator::getInstance();
		
		$this->app_id = $app_id;
		$this->unique_id = $data_generator->unique_id();
		$this->session_id = $data_generator->session_id();
		
		$event = new Event('strApp', $this->session_id);
		
		$event->ID = $this->unique_id;
        $event->aid = $this->app_id;
        $event->aver = $data_generator->app_ver();
        
        $os_info = $data_generator->os_info();

        $event->osv = $os_info->version;
        $event->ossp = $os_info->service_pack;
        $event->osar = $os_info->arch;
        
        $event->osjv = $data_generator->java_version();
        
        $dotnet_info = $data_generator->dotnet_info();
        
        $event->osnet = $dotnet_info->version;
        $event->osnsp = $dotnet_info->service_pack;
        
        $event->oslng = $data_generator->lang_id();
        $event->osscn = $data_generator->screen_resolution();
        
        $cpu_info = $data_generator->cpu_info();

        $event->cnm = $cpu_info->name;
        $event->car = $cpu_info->arch;
        $event->cbr = $cpu_info->brand;
        $event->cfr = $cpu_info->freq;
        $event->ccr = $cpu_info->cores;
        
        $mem_info = $data_generator->memory_info();
        
        $event->mtt = $mem_info->total;
        $event->mfr = $mem_info->free;
        
        $disk_info = $data_generator->disk_info();
        
        $event->dtt = $disk_info->total;
        $event->dfr = $disk_info->free;
        
        $this->add( $event );
	}
	
	public function event() {
		$event = new Event('ev', $this->session_id);
		
		$event_info = RandomDataGenerator::getInstance()->event();
		
		$event->ca = $event_info->category;
		$event->nm = $event_info->name;
		
		$this->add( $event );
	}
	
	public function event_value() {
		$event = new Event('evV', $this->session_id);
		
		$event_info = RandomDataGenerator::getInstance()->event_value();
		
		$event->ca = $event_info->category;
		$event->nm = $event_info->name;
		$event->vl = $event_info->value;
		
		$this->add( $event );
	}
	
	public function event_period() {
		$event = new Event('evP', $this->session_id);
		
		$event_info = RandomDataGenerator::getInstance()->event_period();
		
		$event->ca = $event_info->category;
		$event->nm = $event_info->name;
		$event->tm = $event_info->duration;
		$event->ec = $event_info->completed;
		
		$this->add( $event );
	}
	
	public function log() {
		$event = new Event('lg', $this->session_id);
		
		$event->ms = RandomDataGenerator::getInstance()->log_message();
		
		$this->add( $event );
	}
	
	public function custom_data() {	
		$event = new Event('ctD', $this->session_id);
		
		$custom_data_info = RandomDataGenerator::getInstance()->custom_data();
		
		$event->nm = $custom_data_info->name;
		$event->vl = $custom_data_info->value;
		
		$this->add( $event );
	}
	
	public function exception() {
		$event = new Event('exC', $this->session_id);
		
		$exception_info = RandomDataGenerator::getInstance()->exception();
		
		$event->msg = $exception_info->message;
		$event->stk = $exception_info->stack_trace;
		$event->src = $exception_info->source;
		$event->tgs = $exception_info->target_site;
		
		$this->add( $event );
	}
	
	public function install() {
		$data_generator = RandomDataGenerator::getInstance();
		
		$event = new Event('ist', $this->session_id);
		
		$event->ID = $this->unique_id;
		$event->aid = $this->app_id;
		$event->aver = RandomDataGenerator::getInstance()->app_ver();
		
		$this->add( $event );
	}
	
	public function uninstall() {
		$data_generator = RandomDataGenerator::getInstance();
		
		$event = new Event('ust', $this->session_id);
		
		$event->ID = $this->unique_id;
		$event->aid = $this->app_id;
		$event->aver = RandomDataGenerator::getInstance()->app_ver();
		
		$this->add( $event );
	}
	
	public function stop() {
		$data_generator = RandomDataGenerator::getInstance();
		
		$event = new Event('stApp', $this->session_id);
		
		$this->add( $event );
	}
	
	private function add( $event ) {
		if ( is_a( $event, 'Event' ) )
			$event = $event->get_events();
		
		if ( !is_array( $event ) )
			return false;
			
		$this->events[] = $event;
		
		return true;
	}
	
	public function serialize() {
        if ( $this->format == ExternalAPITest::FORMAT_JSON )
        	return $this->serialize_json();
        else if ( $this->format == ExternalAPITest::FORMAT_XML )
        	return $this->serialize_xml();
        else
        	throw new Exception( 'Invalid format specified' );
    }
    
    private function serialize_json() {
		return json_encode( array( $this->events ) );
	}
	
	private function serialize_xml() {
		$xml = new SimpleXMLElement( '<data />' );
		
		$xml_events = $xml->addChild( 'Events' );
		
		foreach ( $this->events as $event ) {
			$event_child = $xml_events->addChild( 'Event' );
			
			foreach ( $event as $name => $value ) {
				if ( !is_string( $value ) )
					$value = strval( $value );
				
				if ( !empty( $value ) )
					$event_child->addChild( $name, $value );
				else
					$event_child->addChild( $name );
			}
		}
		
		return $xml->asXML();
	}
    
    public function unserialize($data) {
        $this->data = unserialize($data);
    }
}

class Event {
	private $table = array();
	
	public function __construct($event_code, $session_id, $flow_id = 0) {
		$this->__set( 'tp', $event_code );
		$this->__set( 'ss', $session_id );
		$this->__set( 'ts', time() );
		
		if ( is_int( $flow_id ) && $flow_id != 0 )
			$this->__set( 'fl', $flow_id );
		else
			$this->__set( 'fl', Events::get_flow_id() );
	}
	
	public function __set($name, $value) {
		if ( isset( $this->table[$name] ) )
			return;
			
		if ( !is_string( $value ) )
			$value = strval( $value );
			
		$this->table[$name] = $value;
	}
	
	public function __get($name) {
		if ( array_key_exists( $name, $this->table ) )
            return $this->table[$name];
            
        return null;
	}

	public function __isset($name) {
		return isset( $this->table[$name] );
	}
	
	public function get_events() {
		return $this->table;
	}
}