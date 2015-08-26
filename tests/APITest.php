<?php

require_once( dirname( __FILE__ ) . '/LSSTestCase.php' );
require_once( ROOTDIR . '/tests/RandomDataGenerator.php' );
require_once( ROOTDIR . '/inc/class.api.php' );


class APITest extends LSSTestCase {
	private $unique_id;
	private $session_id;
	private $timestamp;
	
	public function __construct() {
		parent::__construct();
	}
	
	
	public function setUp() {
		parent::setUp();
	}
	
	public function tearDown() {
		parent::tearDown();
	}

	private function start_app() {
		$data_generator = RandomDataGenerator::getInstance();
		
		$this->unique_id = $data_generator->unique_id();
		$this->session_id = $data_generator->session_id();
		$this->timestamp = $data_generator->timestamp();
		
		$os_info = $data_generator->os_info();
		$dotnet_info = $data_generator->dotnet_info();
		$cpu_info = $data_generator->cpu_info();
		$disk_info = $data_generator->disk_info();
		$mem_info = $data_generator->memory_info();
		
		$ret_start = API::getInstance()->start_app( 
			$this->app_id,
			$data_generator->app_ver(),
			$this->unique_id,
			$this->session_id,
			$this->timestamp,
			$os_info->version,
			$os_info->service_pack,
			$os_info->arch,
			$data_generator->java_version(),
			$dotnet_info->version,
			$dotnet_info->service_pack,
			$data_generator->lang_id(),
			$data_generator->screen_resolution(),
			$cpu_info->name,
			$cpu_info->brand,
			$cpu_info->freq,
			$cpu_info->cores,
			$cpu_info->arch,
			$mem_info->total,
			$mem_info->free,
			$disk_info->total,
			$disk_info->free
		);
		
		$this->assertEquals(1, $ret_start);
	}
	
	private function stop_app() {
		$this->assertEquals( 1, API::getInstance()->stop_app( $this->timestamp + 3600 , $this->session_id ) );
	}
	
	public function testEvent() {
		$this->start_app();
		
		$event_info = RandomDataGenerator::getInstance()->event();
		
		$this->assertEquals( 1, API::getInstance()->event( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $event_info->category, $event_info->name ) );
		
		$this->stop_app();
	}
	
	public function testEventPeriod() {
		$this->start_app();
		
		$event_period_info = RandomDataGenerator::getInstance()->event_period();
		
		$this->assertEquals( 1, API::getInstance()->event_value( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $event_period_info->category, $event_period_info->name, $event_period_info->duration, $event_period_info->completed ) );
		
		$this->stop_app();
	}
	
	public function testEventValue() {
		$this->start_app();
		
		$event_value_info = RandomDataGenerator::getInstance()->event_value();
		
		$this->assertEquals( 1, API::getInstance()->event_value( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $event_value_info->category, $event_value_info->name, $event_value_info->value ) );
		
		$this->stop_app();
	}
	
	public function testLog() {
		$this->start_app();
		
		$log_message = RandomDataGenerator::getInstance()->log_message();
		
		$this->assertEquals( 1, API::getInstance()->log( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $log_message ) );
		
		$this->stop_app();
	}
	
	public function testCustomData() {
		$this->start_app();
		
		$custom_data = RandomDataGenerator::getInstance()->custom_data();
		
		$this->assertEquals( 1, API::getInstance()->custom_data( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $custom_data->name, $custom_data->value ) );
		
		$this->stop_app();
	}
	
	public function testException() {
		$this->start_app();
		
		$exception_info = RandomDataGenerator::getInstance()->exception();
		
		$this->assertEquals( 1, API::getInstance()->exception( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $exception_info->message, $exception_info->stack_trace, $exception_info->source, $exception_info->target_site ) );
		
		$this->stop_app();
	}
	
	public function testInstall() {
		$this->start_app();
		
		$app_ver = RandomDataGenerator::getInstance()->app_ver();
		
		$this->assertEquals( 1, API::getInstance()->exception( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $this->app_id, $app_ver ) );
		
		$this->stop_app();
	}
	
	public function testUninstall() {
		$this->start_app();
		
		$app_ver = RandomDataGenerator::getInstance()->app_ver();
		
		$this->assertEquals( 1, API::getInstance()->exception( RandomDataGenerator::getInstance()->event_timestamp( $this->timestamp ), $this->session_id, $this->app_id, $app_ver ) );
		
		$this->stop_app();
	}
	
	
}