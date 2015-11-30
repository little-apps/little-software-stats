<?php
require_once( dirname( __FILE__ ) . '/LSSTestCase.php' );

class FunctionsTest extends LSSTestCase {
	public function __construct() {
		parent::__construct();
		
		if ( !is_callable( 'curl_init' ) )
			throw new Exception( 'The cURL PHP extension must be installed' );
			
		echo "Doing function tests...\n";
	}

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}
	
	public function testTrEmpty() {
		$this->assertEmpty(tr(''));
	}
	
	public function testTrReturn() {
		$text = 'Hello World!';
		$this->assertSame(tr($text), gettext($text));
	}
	
	public function testTrOutput() {
		$text = 'Hello World!';
		
		$this->expectOutputString(gettext($text));
		
		_e($text);
	}
	
	public function testRedirectInvalidUrl() {
		$url = 'hxxp://www.google.com';
		
		$this->assertFalse(redirect($url));
	}
	
	public function testRedirectInvalidStatusCode() {
		mt_srand();
		
		$url = 'http://www.google.com';
		
		$this->assertFalse(redirect($url, mt_rand(0, 299)));
		$this->assertFalse(redirect($url, mt_rand(309, 600)));
	}
	
	/**
	* @runInSeparateProcess
	*/
	public function testRedirectValidHeader() {
		$this->assertFalse(headers_sent());
		
		mt_srand();
		
		$url = 'http://www.google.com';
		$status_code = mt_rand(300, 308);
		
		$this->assertTrue(redirect($url, $status_code));
		
		if (function_exists('http_response_code')) {
			$this->assertEquals(http_response_code(), $status_code);
		}
		
		$this->assertContains('Location: '.$url, headers_list());
	}
	
	public function testRedirectValidOutput() {
		$this->assertTrue(headers_sent());
		
		mt_srand();
		
		$url = 'http://www.google.com';
		$status_code = mt_rand(300, 308);
		
		$expected_output = 
			'<script type="text/javascript">' . 
            'window.location.href="'.htmlspecialchars($url).'";' . 
            '</script>' . 
            '<noscript>' . 
            '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($url).'" />' . 
            '</noscript>';
		
		$this->expectOutputString($expected_output);
		
		$this->assertTrue(redirect($url, $status_code));
	}
	
	public function testGetOptionInvalid() {
		$this->assertNull( get_option( 'non_existent_option' ) );
	}
	
	public function testGetOptionValid() {
		$this->assertNotNull( get_option( 'current_version' ) );
	}
	
	public function testSetOption() {
		$option_value = str_shuffle( 'abcdefghijklmnopqrstuvwxzy0123456789' );
		
		$this->assertTrue( set_option( 'test_option', $option_value ) );
		
		$this->assertSame( get_option( 'test_option' ), $option_value );
	}
}