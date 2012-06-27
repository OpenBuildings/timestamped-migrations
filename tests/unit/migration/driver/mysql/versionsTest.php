<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Migraiton Actions
 * @group extensions
 * @group extensions.timestamped-migrations
 * @package Timestamped Migraitons
 */
class Unit_Migration_Driver_Mysql_VersionsTest extends Unittest_Extra_FuncTest_TestCase {

	public function test_versions()
	{
		$driver = new Migration_Driver_Mysql(Kohana::TESTING);
		$versions = $driver->versions()->init();

		$versions->clear_all()->set(100)->set(200)->set(300);
		$this->assertEquals(array(100, 200, 300), $versions->get());
		$versions->clear(200);
		$this->assertEquals(array(100, 300), $versions->get());
	}
}