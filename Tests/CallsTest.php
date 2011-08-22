<?php

require_once('../telesocial.class.php');
require_once 'PHPUnit/Framework.php';

class CallsTest extends PHPUnit_Framework_TestCase {
    
    protected static $telesocial;
    protected static $mediaId;
    
    public static function setUpBeforeClass() {
        self::$telesocial = new Telesocial_API_Connect(SERVER_NAME, API_KEY);
    }
    
    function testCreateMediaID() {
        $result = self::$telesocial->createMedia(NETWORK_ID1);
        $this->assertArrayHasKey('mediaId', $result);
        self::$mediaId = $result['mediaId'];
    }
    
    function testUploadGrantRequest() {
        $result = self::$telesocial->uploadGrantRequest(self::$mediaId);
        $this->assertArrayHasKey('grantid', $result);
        $this->assertGreaterThan(0, $result['grantid']);
    }
    
    function testRecordCall() {
        $result = self::$telesocial->recordCall(NETWORK_ID1, self::$mediaId);
        $this->assertArrayHasKey('mediaId', $result);
    }
    
    function testBlastCall() {
        $result = self::$telesocial->blastCall(NETWORK_ID2, self::$mediaId);
        $this->assertArrayHasKey('mediaId', $result);
    }
    
    function testRemoveMedia() {
        $result = self::$telesocial->removeMedia(self::$mediaId);
        $this->assertArrayHasKey('mediaId', $result);
    }
}