<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Migraiton Actions
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class Unit_Migration_Driver_MysqlTest extends PHPUnit_Framework_TestCase {

	public function test_create_table()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->logicalAnd(
			$this->stringContains('CREATE TABLE `test`'),
			$this->stringContains('`id` INT NOT NULL AUTO_INCREMENT'),
			$this->stringContains('`field1` INT'),
			$this->stringContains('`field2` VARCHAR (255) NOT NULL'),
			$this->stringContains('ENGINE=innoDB'),
			$this->stringContains('PRIMARY KEY (`id`)')
		));

		$driver->expects($this->at(1))->method('execute')->with(
			$this->stringContains('PRIMARY KEY (`field1`)', false)
		);

		$driver->create_table("test", array(
			'field1' => 'integer',
			'field2' => array('string', 'null' => false)
		), array('options' => 'ENGINE=innoDB'));

		$driver->create_table("test", array(
			'field1' => 'primary_key',
			'field2' => array('string', 'null' => false)
		), array('id' => false));
	}


	public function test_drop_table()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('DROP TABLE `table1`'));
		$driver->drop_table('table1');
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
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('ALTER TABLE `table1` ADD COLUMN `field1` INT'));
		$driver->expects($this->at(1))->method('execute')->with($this->equalTo('ALTER TABLE `table1` DROP PRIMARY KEY, ADD COLUMN `field1` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`field1`)'));

		$driver->add_column('table1', 'field1', 'integer');
		$driver->add_column('table1', 'field1', 'primary_key');
	}

	public function test_rename_column()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute', 'column'), array(Kohana::TESTING));

		$column = $this->getMock('Migration_Driver_Mysql_Column', array('load'), array('field2', $driver));
		$column->expects($this->once())->method('load')->will($this->returnValue($column));

		$driver->expects($this->once())->method('column')->will($this->returnValue($column->params('integer')));
		$driver->expects($this->once())->method('execute')->with($this->equalTo('ALTER TABLE `table1` CHANGE `field1` `field2` INT'));
		$driver->rename_column('table1', 'field1', 'field2');
	}

	public function test_change_column()
	{
		$driver = $this->getMock('Migration_Driver_Mysql', array('execute'), array(Kohana::TESTING));
		$driver->expects($this->at(0))->method('execute')->with($this->equalTo('ALTER TABLE `table1` MODIFY `field1` INT NOT NULL'));
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
			$this->equalTo('ALTER TABLE `table1` ADD INDEX `index1` (`field1`, `field2`)')
		);

		$driver->expects($this->at(1))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table2` ADD UNIQUE INDEX `index2` (`field1`)')
		);

		$driver->expects($this->at(2))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table3` ADD PRIMARY KEY `index2` (`field1`)')
		);

		$driver->expects($this->at(3))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table4` ADD FULLTEXT `index2` (`field1`)')
		);

		$driver->expects($this->at(4))->method('execute')->with(
			$this->equalTo('ALTER TABLE `table5` ADD SPATIAL `index2` (`field1`)')
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
			array('string', '`field2` VARCHAR (255)'),
			array(array('string', 'limit' => 100, 'null' => FALSE), '`field2` VARCHAR (100) NOT NULL'),
			array('text', '`field2` TEXT'),
			array(array('type' => 'BIGINT'), '`field2` BIGINT'),
			array('integer', '`field2` INT'),
			array('boolean', '`field2` TINYINT (1) DEFAULT \'0\' NOT NULL'),
			array('primary_key', '`field2` INT NOT NULL AUTO_INCREMENT'),
			array('decimal', '`field2` DECIMAL (10, 2)'),
			array(array('decimal', 'limit' => 7), '`field2` DECIMAL (7, 2)'),
			array(array('decimal', 'limit' => 8, 'precision' => 10), '`field2` DECIMAL (8, 10)'),
			array(array('decimal', 'precision' => 10), '`field2` DECIMAL (10, 10)'),
			array(array('integer'), '`field2` INT'),
			array(array('integer', 'auto' => TRUE), '`field2` INT AUTO_INCREMENT'),
			array(array('integer', 'null' => FALSE), '`field2` INT NOT NULL'),
			array(array('integer', 'unsigned' => TRUE, 'null' => FALSE), '`field2` INT UNSIGNED NOT NULL'),
			array(array('integer', 'unsigned' => TRUE, 'null' => FALSE, 'default' => 5), '`field2` INT UNSIGNED DEFAULT \'5\' NOT NULL'),
			array(array('integer', 'unsigned' => TRUE, 'null' => FALSE, 'default' => '0'), '`field2` INT UNSIGNED DEFAULT \'0\' NOT NULL'),
			array(array('integer', 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0), '`field2` INT UNSIGNED DEFAULT \'0\' NOT NULL'),
			array(array('integer', 'primary' => TRUE), '`field2` INT'),
			array(array('integer', 'after' => 'field2'), '`field2` INT AFTER `field2`'),
			array(array('integer', 'after' => 'field2', 'comment' => 'ABCDE'), '`field2` INT COMMENT \'ABCDE\' AFTER `field2`'),
			array(array('integer', 'first' => TRUE), '`field2` INT FIRST'),
			array(array('integer', 'first' => TRUE, 'comment' => 'ABCDE'), '`field2` INT COMMENT \'ABCDE\' FIRST'),
			array(array('integer', 'default' => 3), '`field2` INT DEFAULT \'3\''),
		);
	}

	/**
	 * @dataProvider data_compile_column
	 */
	public function test_compile_column($type, $result)
	{
		$driver = new Migration_Driver_Mysql(Kohana::TESTING);
		$this->assertEquals($result, $driver->column('field2')->params($type)->sql());
	}
}
