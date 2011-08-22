<?php

require_once('../telesocial.class.php');
require_once 'PHPUnit/Framework.php';

class UsersTest extends PHPUnit_Framework_TestCase {

    protected static $telesocial;
    protected static $registration_check;

    public static function setUpBeforeClass() {
        self::$telesocial = new Telesocial_API_Connect(SERVER_NAME, API_KEY);
    }

    /**
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage Expected parameters are empty
     * @expectedExceptionCode 100
     *
     */
    function testRegisterUserBlankUsername() {
        $result = self::$telesocial->registerUser('', PHONE_NUMBER1);
    }

    /**
     * @expectedException TelesocialApiException
     * @expectedExceptionMessage Invalid phone number format
     * @expectedExceptionCode 200
     *
     */
    function testRegisterUserInvalidPhone() {
        $result = self::$telesocial->registerUser(NETWORK_ID1, str_repeat('A', 10));
        $this->assertFalse($result);
    }

    function testRegister1stUser() {
        $result = self::$telesocial->registerUser(constant('NETWORK_ID1'), constant('PHONE_NUMBER1'));
        $this->assertEquals(201, $result['status']);
    }

    function testRegister2ndUser() {
        $result = self::$telesocial->registerUser(constant('NETWORK_ID2'), constant('PHONE_NUMBER2'));
        $this->assertEquals(201, $result['status']);
    }

    function testRegister3rdUser() {
        $result = self::$telesocial->registerUser(constant('NETWORK_ID3'), constant('PHONE_NUMBER3'));
        $this->assertEquals(201, $result['status']);
    }

    /**
     * @depends testRegister1stUser
     *
     */
    function testCheckRegistrationStatus() {
        $result = self::$telesocial->checkUserRegistration(NETWORK_ID1);
        self::$registration_check = $result;
        $this->assertTrue($result);
    }

    /**
     * @depends testRegister1stUser
     *
     */
    function testCheckRegistrationStatusRelated() {
        $result = self::$telesocial->checkUserRegistration(NETWORK_ID1);
        $this->assertEquals($result, self::$registration_check);
    }

    /**
     * @depends testCheckRegistrationStatusRelated
     */
    function testCheckRegistrationResult() {
        $this->assertTrue(self::$registration_check);
    }
}