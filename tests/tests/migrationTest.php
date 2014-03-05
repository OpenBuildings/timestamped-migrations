<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Migraiton
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class Unit_MigrationTest extends PHPUnit_Framework_TestCase {

	public function test_load_driver()
	{
		$migration = new Migration_One(array('type' => 'mysql'));
		$this->assertInstanceOf('Migration_Driver_Mysql', $migration->driver());
	}

	public function test_driver_class()
	{
		$this->setExpectedException('PHPUnit_Framework_Error');
		$migration = new Migration_One(array('type' => 'mysql'));
		$dummy_driver = new stdClass;
		$migration->driver($dummy_driver);
	}

	public function data_driver_calling()
	{
		return array(
			array('create_table', array('table', array())),
			array('drop_table', array('table')),
			array('change_table', array('table', array())),
			array('rename_table', array('table', 'table2')),
			array('add_column', array('table', 'name', array())),
			array('rename_column', array('table', 'name', 'name2')),
			array('change_column', array('table', 'name', array())),
			array('remove_column', array('table', 'name')),
			array('add_index', array('table', 'name', 'column', 'type')),
			array('remove_index', array('table', 'name')),
			array('execute', array('sql', array())),
		);
	}

	/**
	 * @dataProvider data_driver_calling
	 */
	public function test_driver_calling($method, $args)
	{
		$logger = $this->getMock('Migration_Logger', array('log'));
		$logger->expects($this->exactly(4))->method('log');

		$migration = new Migration_One(array('type' => 'mysql', 'log' => array($logger, 'log')));

		$driver = $this->getMock('Migration_Driver_Mysql', array($method), array(Kohana::TESTING));
		$driver->expects($this->once())->method($method);

		$migration->driver($driver);
		call_user_func_array(array($migration, $method), $args);

		$migration->dry_run(TRUE);
		call_user_func_array(array($migration, $method), $args);
	}
}
