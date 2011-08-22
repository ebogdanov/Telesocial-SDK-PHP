<?php

require_once('../telesocial.class.php');
require_once 'PHPUnit/Framework.php';

class BaseTest extends PHPUnit_Framework_TestCase {

    function testJSONDecodeExists() {
        $this->assertEquals(true, function_exists('json_decode') && extension_loaded('json'));
    }
    
    function testJSONDecodeValue() {
        $str = '{"test":{"answer":"Hello","msg":"World!","code":400}}';
        $test = json_decode($str, true);
        $this->assertArrayHasKey('test', $test, 'JSON decoding array failed');
    }
    
    function testPHPEval() {
        eval('$b=100;');
        $this->assertSame($b, 100, 'Eval() failed');
    }
    
    function testCurlSupport() {
        $this->assertTrue(extension_loaded('curl'), 'cURL library will not be used');
    }
    
    function testOpenSSLSupport() {
        $this->assertTrue(extension_loaded('openssl'), 'SSL connections are not supported');
    }
    
    function testGetObjectArrayFunctionExists() {
        $this->assertTrue( function_exists('get_object_to_array') );
    }
    
    /**
     * @depends testGetObjectArrayFunctionExists
     *
     */
    function testGetObjectArray() {
        $obj = new stdClass();
        $obj->test1 = array('test1' => 400);
        $this->assertArrayHasKey('test1', get_object_to_array($obj), 'Object to Array conversion failed');
    }
        
    function testClassNameExists() {
        $this->assertTrue(class_exists('Telesocial_API_Connect'), 'Bit_Mouth_API_Connect Class is not found.');
    }
    
    /**
     * @depends testClassNameExists
     *
     */
    function testExceptionNameExists() {
        $this->assertTrue(class_exists('TelesocialApiException'), 'TelesocialApiException Class is not found.');
    }
    
    /**
     * @depends testExceptionNameExists
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage Server Host is not specified
     * @expectedExceptionCode 100
     *
     */
    function testEmptyServerNameException() {
        $class = new Telesocial_API_Connect('', '');
    }
    
    /**
     * @depends testEmptyServerNameException
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage API Key is not specified
     * @expectedExceptionCode 100
     */
    function testEmptyAPIKeyException() {
        $class = new Telesocial_API_Connect(SERVER_NAME, '');
    }
    
    /**
     * @depends testEmptyAPIKeyException
     *
     */
    public function testVersion() {
        $class = new Telesocial_API_Connect(SERVER_NAME, API_KEY);
        $version = $class->getVersion();
        $this->assertEquals('01.02.06', $version);
    }
}