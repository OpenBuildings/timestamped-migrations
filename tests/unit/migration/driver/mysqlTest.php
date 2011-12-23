<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Migraiton Actions
 * @group extensions
 * @group extensions.timestamped-migrations
 * @package Timestamped Migraitons
 */
class Unit_Migration_Driver_MysqlTest extends Unittest_Extra_Database_TestCase {

	public function test_schema_control()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('CREATE TABLE IF NOT EXISTS schema_version (version int)'));
		$driver->expects($this->at(1))->method('execute')->with($this->equalTo('INSERT INTO schema_version SET version = ?'), $this->equalTo(array(100)));
		$driver->expects($this->at(2))->method('execute')->with($this->equalTo('DELETE FROM schema_version WHERE version = ?'), $this->equalTo(array(100)));
		$driver->expects($this->at(3))->method('execute')->with($this->equalTo('DELETE FROM schema_version'));

		$driver->generate_schema();
		$driver->set_executed(100);
		$driver->set_unexecuted(100);
		$driver->clear_migrations();
	}

	public function test_get_executed_migrations()
	{
		$driver = new Migration_Driver_Mysql(Kohana::TESTING);
		$driver->generate_schema()->clear_migrations()->set_executed(100)->set_executed(200)->set_executed(300);
		$this->assertEquals(array(100, 200, 300), $driver->get_executed_migrations());
	}

	public function test_create_table()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->logicalAnd(
			$this->stringContains('CREATE TABLE `test`', false),
			$this->stringContains('`id` int  NOT NULL  AUTO_INCREMENT', false),
			$this->stringContains('`field1` int  NULL', false),
			$this->stringContains('`field2` varchar (255)  NOT NULL', false),
			$this->stringContains('ENGINE=innoDB', false),
			$this->stringContains('PRIMARY KEY ( `id`))', false)
		));

		$driver->expects($this->at(1, 2))->method('execute')->with(
			$this->stringContains('PRIMARY KEY ( `field1`))', false)
		);

		$driver->expects($this->at(3))->method('execute')->with(
			$this->stringContains('CREATE TABLE IF NOT EXISTS', false)
		);

		$driver->create_table("test", array('field1' => 'integer', 'field2' => array('string', 'null' => false)), array('engine' => 'innoDB'));
		$driver->create_table("test", array('field1' => 'integer', 'field2' => array('string', 'null' => false)), 'field1');
		$driver->create_table("test", array('field1' => 'integer', 'field2' => array('string', 'null' => false)), array('primary_key' => 'field1'));
		$driver->create_table("test", array(), array('if_not_exists' => true));
	}


	public function test_drop_table()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('DROP TABLE `table1`'));
		$driver->expects($this->at(1))->method('execute')->with($this->equalTo('DROP TABLE IF EXISTS `table1`'));
		
		$driver->drop_table('table1');
		$driver->drop_table('table1', true);
	}

	public function test_rename_table()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('RENAME TABLE `table1` TO `table2`'));
		
		$driver->rename_table('table1', 'table2');
	}

	public function test_add_column()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('ALTER TABLE `table1` ADD COLUMN  `field1` int  NULL '));
		$driver->add_column('table1', 'field1', 'integer');
	}

	public function test_rename_column()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute', 'get_column'), array(Kohana::TESTING));
		$driver->expects($this->once())->method('get_column')->will($this->returnValue('integer'));
		$driver->expects($this->once())->method('execute')->with($this->equalTo('ALTER TABLE `table1` CHANGE `field1`  `integer` int  NULL '));
		$driver->rename_column('table1', 'field1', 'integer');
	}

	public function test_change_column()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('ALTER TABLE `table1` MODIFY  `field1` int  NOT NULL '));
		$driver->change_column('table1', 'field1', array('integer', 'null' => false));
	}

	public function test_remove_column()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('ALTER TABLE `table1` DROP COLUMN `field1`'));
		$driver->remove_column('table1', 'field1');
	}

	public function test_add_index()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));


		$driver->expects($this->at(0))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table1` ADD INDEX `index1` ( `field1`, `field2`)')
		);

		$driver->expects($this->at(1))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table2` ADD UNIQUE INDEX `index2` ( `field1`)')
		);

		$driver->expects($this->at(2))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table3` ADD PRIMARY KEY `index2` ( `field1`)')
		);

		$driver->expects($this->at(3))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table4` ADD FULLTEXT `index2` ( `field1`)')
		);

		$driver->expects($this->at(4))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table5` ADD SPATIAL `index2` ( `field1`)')
		);

		$driver->add_index("table1", 'index1', array('field1', 'field2'));
		$driver->add_index("table2", 'index2', 'field1', 'unique');
		$driver->add_index("table3", 'index2', 'field1', 'primary');
		$driver->add_index("table4", 'index2', 'field1', 'fulltext');
		$driver->add_index("table5", 'index2', 'field1', 'spatial');
	}

	public function test_remove_index()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('ALTER TABLE `table1` DROP INDEX `index1`'));
		$driver->remove_index('table1', 'index1');
	}

	public function data_compile_column()
	{
		return array(
			array('string', ' `field2` varchar (255)  NULL '),
			array('string[100]', ' `field2` varchar (100)  NULL '),
			array('text', ' `field2` text   NULL '),
			array('integer[big]', ' `field2` bigint  NULL '),
			array('integer', ' `field2` int  NULL '),
			array('integer[normal]', ' `field2` int  NULL '),
			array('integer[small]', ' `field2` smallint  NULL '),
			array('boolean', ' `field2` tinyint (1)  NULL '),
			array(array('integer'), ' `field2` int  NULL '),
			array(array('integer', 'auto' => true), ' `field2` int  NULL  AUTO_INCREMENT '),
			array(array('integer', 'null' => false), ' `field2` int  NOT NULL '),
			array(array('integer', 'unsigned' => true, 'null' => false), ' `field2` int  UNSIGNED  NOT NULL '),
			array(array('integer', 'primary' => true), ' `field2` int  NULL  PRIMARY KEY '),
			array(array('integer', 'after' => 'field2'), ' `field2` int  NULL  AFTER `field2` '),
			array(array('integer', 'default' => 3), ' `field2` int  DEFAULT \'3\'  NULL '),
		);
	}

	/**
	 * @dataProvider data_compile_column
	 */
	public function test_compile_column($type, $result)
	{
		$driver = new Migration_Driver_Mysql(Kohana::TESTING);
		$this->assertEquals($result, $driver->compile_column('field2', $type, true));
	}

}