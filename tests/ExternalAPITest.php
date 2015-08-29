<?php

require_once( dirname( __FILE__ ) . '/LSSTestCase.php' );
require_once( ROOTDIR . '/tests/RandomDataGenerator.php' );
require_once( ROOTDIR . '/tests/Events.php' );

class ExternalAPITest extends LSSTestCase {
    const API_PATH = '/api.php?type=:format';

    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    
    private $events;

	public function __construct() {
		parent::__construct();
		
		if ( !is_callable( 'curl_init' ) )
			throw new Exception( 'The cURL PHP extension must be installed' );
			
		$this->events = new Events;
	}

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}
	
	public function testGenerateJSON() {
		$this->events->set_format( self::FORMAT_JSON );
		
		$data = $this->generate_events();
		
		$this->assertNotNull( json_decode( $data ) );
	}
	
	public function testGenerateXML() {
		$this->events->set_format( self::FORMAT_XML );
		
		$data = $this->generate_events();
		
		$this->assertInstanceOf( 'SimpleXMLElement', simplexml_load_string( $data ) );
	}
	
	public function testSendJSON() {
		$this->events->set_format( self::FORMAT_JSON );
		
		$data = $this->generate_events();
		
		$ret = $this->call_api( $this->build_api_uri( self::FORMAT_JSON ), $data );
		
		$ret_decoded = json_decode( $ret );
		
		$this->assertNotNull( json_decode( $ret ) );
	}
	
	public function testSendXML() {
		$this->events->set_format( self::FORMAT_XML );
		
		$data = $this->generate_events();
		
		$ret = $this->call_api( $this->build_api_uri( self::FORMAT_XML ), $data );
		
		$ret_decoded = simplexml_load_string( $ret );
		
		$this->assertInstanceOf( 'SimpleXMLElement', $ret_decoded );
	}
	
	private function generate_events() {
		mt_srand();
		
		$this->events->start( $this->app_id );
		
		if (mt_rand(0, 1))
			$this->events->event();
			
		if (mt_rand(0, 1))
			$this->events->event_value();
			
		if (mt_rand(0, 1))
			$this->events->event_period();
			
		if (mt_rand(0, 1))
			$this->events->log();
			
		if (mt_rand(0, 1))
			$this->events->custom_data();
			
		if (mt_rand(0, 1))
			$this->events->exception();
			
		if (mt_rand(0, 1))
			$this->events->install();
			
		if (mt_rand(0, 1))
			$this->events->uninstall();
		
		$this->events->stop();
		
		return serialize( $this->events );
	}

    private function build_api_uri( $format ) {
        if ( $format != self::FORMAT_JSON && $format != self::FORMAT_XML )
            throw new Exception( 'Paramter type is invalid' );

        $uri_path = str_replace( ':format', $format, self::API_PATH );

        $uri = rtrim( Config::getInstance()->site->url, '/' ) . '/' . ltrim( $uri_path, '/' );

        return $uri;
    }

    private function call_api( $uri, $post_data ) {
        $ch = curl_init( $uri );

        if ( !is_resource( $ch ) )
        	throw new Exception( 'Unable to initialize cURL' );
        	
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
        
        $curl_ret = curl_exec( $ch );
        
        curl_close( $ch );
        
        return $curl_ret;
    }
}