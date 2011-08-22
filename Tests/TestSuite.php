<?php

require_once 'PHPUnit/Framework.php';

function __autoload($class_name) {
    require_once($class_name . '.php');
}

class TestSuite {
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('TestSuite');

        $suite->addTestSuite('BaseTest');
        $suite->addTestSuite('UsersTest');
        $suite->addTestSuite('CallsTest');
        $suite->addTestSuite('ConferenceCallTest');
        $suite->addTestSuite('DeleteUsersTest');

        return $suite;
    }
}