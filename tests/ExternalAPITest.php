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
		
		echo "Doing external API tests...\n";
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
		
		//echo "Data to be sent: " . $data . PHP_EOL;
		
		$ret = $this->call_api( $this->build_api_uri( self::FORMAT_JSON ), $data );
		
		//echo "API Returned: " . $ret . PHP_EOL;
		
		$ret_decoded = json_decode( $ret );
		
		$this->assertNotNull( $ret_decoded );
		
		$this->assertEquals( 1, $ret_decoded->status_code );
		$this->assertEquals( 'Success', $ret_decoded->status_message );
	}
	
	public function testSendXML() {
		$this->events->set_format( self::FORMAT_XML );
		
		$data = $this->generate_events();
		
		//echo "Data to be sent: " . $data . PHP_EOL;
		
		$ret = $this->call_api( $this->build_api_uri( self::FORMAT_XML ), $data );
		
		//echo "API Returned: " . $ret . PHP_EOL;
		
		$ret_decoded = simplexml_load_string( $ret );
		
		$this->assertInstanceOf( 'SimpleXMLElement', $ret_decoded );
		
		$this->assertEquals( 1, (int)$ret_decoded->Code );
		$this->assertEquals( 'Success', strval( $ret_decoded->Message ) );
	}
	
	private function generate_events() {
		mt_srand();
		
		$this->events->start( $this->app_id );
		
		$this->events->event();
			
		$this->events->event_value();
			
		$this->events->event_period();
			
		$this->events->log();
			
		$this->events->custom_data();
			
		$this->events->exception();
			
		$this->events->install();
			
		$this->events->uninstall();
		
		$this->events->stop();
		
		return $this->events->serialize();
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