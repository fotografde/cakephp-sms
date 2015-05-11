<?php
/**
 * Group test
 */
class AllSmsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Sms plugin tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectoryRecursive($path . DS . 'Lib');
		return $Suite;
	}

}
