<?php

require_once('../telesocial.class.php');
require_once 'PHPUnit/Framework.php';

class DeleteUsersTest extends PHPUnit_Framework_TestCase {
        
    function testDeleteFirstUsername() {
        $result = self::$telesocial->deleteUser(NETWORK_ID1);
        $this->assertEquals(200, $result['status']);
    }
    
    function testDeleteSecondUsername() {
        $result = self::$telesocial->deleteUser(NETWORK_ID2);
        $this->assertEquals(200, $result['status']);
    }

    function testDeleteThirdUsername() {
        $result = self::$telesocial->deleteUser(NETWORK_ID3);
        $this->assertEquals(200, $result['status']);
    }
}