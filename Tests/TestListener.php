<?php

class TestListener implements PHPUnit_Framework_TestListener {
        
    private $hints = array();
    
    public function __construct() {
        $this->hints = array(
        // Conference Calls Test
            'testCreateConference' => '  You will get phone call on ('.constant('PHONE_NUMBER1').').'.PHP_EOL."\tYou need to accept call and press '1' to Create Conference",
            'testAddToConference'  => '  Telesocial will call to ('.constant('PHONE_NUMBER2').').'.PHP_EOL."\tYou need to accept call and press '1' to Join Conference",
            'testMuteFirstUser'    => '  ('.constant('PHONE_NUMBER1').') should be muted.',
            'testUnMuteFirstUser'  => '  ('.constant('PHONE_NUMBER1').') is unmuted now',
            'testMuteSecondUser'   => '  ('.constant('PHONE_NUMBER2').') should be muted.',
            'testUnMuteSecondUser' => '  ('.constant('PHONE_NUMBER2').') is unmuted now',
            'testCreateSecondConference' => '  You will get phone call on ('.constant('PHONE_NUMBER3').').'.PHP_EOL."\tYou need to accept call and press '1' to Create Second Conference",
            'testMoveCallToConference' => '  ('.constant('PHONE_NUMBER2').') will be moved to Second Conference',
            'testEndFirstConference'    => '  ('.constant('PHONE_NUMBER1').') conference will be closed now',
            'testTerminateCall'    => '  ('.constant('PHONE_NUMBER2').') will be disconnected from Second Conference now',
            'testEndSecondConference'    => '  ('.constant('PHONE_NUMBER3').') conference will be closed now',
            'testAdd2UsersTo2ndConference' => '  ('.constant('PHONE_NUMBER1').') and ('.constant('PHONE_NUMBER2').') will be connected to Second Conference.'.PHP_EOL."\tThey should listen record of First conference as greeting",
        // Users Test
            'testRegister1stUser'  => '  System will call to ('.constant('PHONE_NUMBER1').').'.PHP_EOL."\tYou need to accept call and press '1' to register this user in application",
            'testRegister2ndUser'  => '  System will call to ('.constant('PHONE_NUMBER2').').'.PHP_EOL."\tYou need to accept call and press '1' to register this user in application",
            'testRegister3rdUser'  => '  System will call to ('.constant('PHONE_NUMBER3').').'.PHP_EOL."\tYou need to accept call and press '1' to register this user in application",
            'testReAuthUser'       => '  System will try to re-auth ('.constant('PHONE_NUMBER1').').'.PHP_EOL."\tYou need to accept call and press '1' to re-auth this user in application",
        // CallsTest
            'testRecordCall'       => '  You will get call on ('.constant('PHONE_NUMBER1').') and say some phrase to record greeting',
            'testBlastCall'        => '  You will get call on ('.constant('PHONE_NUMBER1').'). System should play greeting recorded in previous test',
        
        );
    }
    
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        printf("Error while running test '%s'.".PHP_EOL, preg_replace('"^test"', '', $test->getName()));
    }
 
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
        printf("Test '%s' failed.", preg_replace('"^test"', '', $test->getName()));
    }
 
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        printf("Test '%s' is incomplete.".PHP_EOL, preg_replace('"^test"', '', $test->getName()));
    }
 
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        printf("Test '%s' has been skipped.".PHP_EOL, preg_replace('"^test"', '', $test->getName()));
    }
 
    public function startTest(PHPUnit_Framework_Test $test) {
        $testName = $test->getName();
        echo PHP_EOL;
        
        if (key_exists($testName, $this->hints)) {
            echo 'Hint: '.$this->hints[$testName].PHP_EOL;
        }
    }
 
    public function endTest(PHPUnit_Framework_Test $test, $time) {
        $testName = $test->getName();

        if (key_exists($testName, $this->hints)) {
            echo 'Press <ENTER> to proceed to next test enter <S> to stop test'.PHP_EOL;
            $fp = fopen("php://stdin","r");
            $line = rtrim(fgets($fp, 1024));
            if (!empty($line[0]) && strtolower($line[0]) == 's') {
                die('Testing interrupted by user'.PHP_EOL);
            }
        }
        printf("Test '%s' result: "/*.PHP_EOL*/, preg_replace('"^test"', '', $testName));
    }
 
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) { }
 
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) { }
}