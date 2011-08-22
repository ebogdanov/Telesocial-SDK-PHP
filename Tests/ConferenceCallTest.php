<?php

require_once('../telesocial.class.php');
require_once 'PHPUnit/Framework.php';

class ConferenceCallTest extends PHPUnit_Framework_TestCase {

    protected static $telesocial;
    protected static $mediaId;
    protected static $conferenceId1;
    protected static $conferenceId2;
    
    public static function setUpBeforeClass() {
        self::$telesocial = new Telesocial_API_Connect(SERVER_NAME, API_KEY);
    }
    
    function testCreateMediaID() {
        $result = self::$telesocial->createMedia(NETWORK_ID1);
        $this->assertArrayHasKey('mediaId', $result);
        self::$mediaId = $result['mediaId'];
    }
    
    /**
     * @depends testCreateMediaID
     *
     */
    function testCreateConference() {
        // with recording
        $result = self::$telesocial->createConference(NETWORK_ID1, self::$mediaId);
        self::$conferenceId1 = $result['conferenceId'];
        $this->assertArrayHasKey('conferenceId', $result);
    }

    /**
     * @depends testCreateConference
     *
     */
    function testAddToConference() {
        $result = self::$telesocial->addToConference(NETWORK_ID2, self::$conferenceId1);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
        $this->assertEquals($result['uri'], '/api/rest/conference/'.self::$conferenceId1.'/'.NETWORK_ID2);
    }

    /**
     * @depends testCreateConference
     *
     */
    function testMuteFirstUser() {
        $result = self::$telesocial->muteCall(self::$conferenceId1, NETWORK_ID1);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
        $this->assertEquals($result['uri'], '/api/rest/conference/'.self::$conferenceId1.'/'.NETWORK_ID1);
    }

    /**
     * @depends testMuteFirstUser
     *
     */
    function testUnMuteFirstUser() {
        $result = self::$telesocial->unMuteCall(self::$conferenceId1, NETWORK_ID1);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
        $this->assertEquals($result['uri'], '/api/rest/conference/'.self::$conferenceId1.'/'.NETWORK_ID1);
    }

    /**
     * @depends testCreateConference
     *
     */
    function testMuteSecondUser() {
        $result = self::$telesocial->muteCall(self::$conferenceId1, NETWORK_ID2);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
        $this->assertEquals($result['uri'], '/api/rest/conference/'.self::$conferenceId1.'/'.NETWORK_ID2);
    }

    /**
     * @depends testMuteSecondUser
     *
     */
    function testUnMuteSecondUser() {
        $result = self::$telesocial->unMuteCall(self::$conferenceId1, NETWORK_ID2);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
        $this->assertEquals($result['uri'], '/api/rest/conference/'.self::$conferenceId1.'/'.NETWORK_ID2);
    }

    /**
     * @depends testCreateConference     
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage Missing or invalid parameters.
     * @expectedExceptionCode 102
     *
     */
    function testAddUnkownUser() {
        $result = self::$telesocial->addToConference('test_user-'.uniqid().'-'.time(), self::$conferenceId1);
    }
    
    function testCreateSecondConference() {
        // without recording
        $result = self::$telesocial->createConference(NETWORK_ID3);
        self::$conferenceId2 = $result['conferenceId'];
        $this->assertArrayHasKey('conferenceId', $result);
    }

    /**
     * @depends testCreateConference
     * @depends testCreateSecondConference
     *
     */
    function testMoveCallToConference() {
        $result = self::$telesocial->moveCall(self::$conferenceId2, self::$conferenceId1, NETWORK_ID2);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId2, $result['conferenceId']);
    }
    
    /**
     * @depends testCreateConference     
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage The specified network ID is not associated with the application identified by the application key.
     * @expectedExceptionCode 200
     *
     */
    function testTerminateUnknownNetworkId() {
        $result = self::$telesocial->hangupCall('test_user-'.uniqid().'-'.time(), self::$conferenceId1);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
    }

    /**
     * @depends testCreateConference     
     * 
     * 
     */
    function testEndFirstConference() {
        $result = self::$telesocial->closeConference(self::$conferenceId1);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
    }

    /**
     * @depends testMoveCallToConference
     *
     */
    function testTerminateCall() {
        $result = self::$telesocial->hangupCall(NETWORK_ID2, self::$conferenceId2);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId2, $result['conferenceId']);
    }
        
    
    /**
     * @depends testCreateSecondConference
     *
     */
    function testAdd2UsersTo2ndConference() {
        $result = self::$telesocial->addToConference(array(NETWORK_ID2, NETWORK_ID1), self::$conferenceId1, self::$mediaId);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId1, $result['conferenceId']);
    }
    
    
    /**
     * @depends testCreateSecondConference
     *
     */
    function testEndSecondConference() {
        $result = self::$telesocial->closeConference(self::$conferenceId2);
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId2, $result['conferenceId']);
    }

    /**
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage Unexpected reply from server
     * @expectedExceptionCode 200
     */
    function testCloseUnkownConference() {
        $result = self::$telesocial->closeConference(uniqid());
        $this->assertArrayHasKey('conferenceId', $result);
        $this->assertEquals(self::$conferenceId2, $result['conferenceId']);
    }
    
    /**
     * @depends testCreateMediaID
     *
     */ 
    function testDownloadMP3File() {
        $result = self::$telesocial->getMediaStatus(self::$mediaId);
        $this->assertArrayHasKey('mediaId', $result);
        $this->assertGreaterThan($result['fileSize'], 0);
        $fileSize = $result['fileSize'];
                
        mkdir(dirname(__FILE__).'/tmp/', 0777);
        $localPath = dirname(__FILE__).'/tmp/'.basename($result['downloadUrl']);
        $this->assertTrue(is_writable($localPath));
        $result = self::$telesocial->downloadMP3File($result['downloadUrl']);
        $this->assertEquals($result, $fileSize);
    }
    
    /**
     * @depends testCreateMediaID
     *
     */ 
    function testRemoveMedia() {
        $result = self::$telesocial->removeMedia(self::$mediaId);
        $this->assertArrayHasKey('mediaId', $result);
    }
}