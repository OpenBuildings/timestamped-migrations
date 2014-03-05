<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Migraiton Actions
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class Unit_Migration_ActionsTest extends PHPUnit_Framework_TestCase {

	public function data_parse_name()
	{
		return array(
			array('create_table_mytable', 'create_table',
				array('create_table(\'mytable\''),
				array('drop_table(\'mytable\')')
			),
			array('create_table_mytable_and_table2', 'create_table',
				array('create_table(\'mytable\'', 'create_table(\'table2\''),
				array('drop_table(\'mytable\')', 'drop_table(\'table2\')')
			),
			array('drop_table_mytable', 'drop_table',
				array('drop_table(\'mytable\')'),
				array('create_table(\'mytable\'')
			),
			array('add_col1_and_col2_and_col3_to_table1', 'add_columns',
				array('add_column(\'table1\', \'col1\'', 'add_column(\'table1\', \'col2\'', 'add_column(\'table1\', \'col3\''),
				array('remove_column(\'table1\', \'col1\'', 'remove_column(\'table1\', \'col2\'', 'remove_column(\'table1\', \'col3\''),
			),
			array('remove_col1_and_col2_from_table1', 'remove_columns',
				array('remove_column(\'table1\', \'col1\'', 'remove_column(\'table1\', \'col2\''),
				array('add_column(\'table1\', \'col1\'', 'add_column(\'table1\', \'col2\''),
			),
			array('rename_col_to_col2_in_table2', 'rename_column',
				array('rename_column(\'table2\', \'col\''),
				array('rename_column(\'table2\', \'col2\'')
			),
			array('change_col3_in_table3', 'change_column',
				array('change_column(\'table3\', \'col3\''),
				array('change_column(\'table3\', \'col3\'')
			),
		);
	}

	/**
	 * @dataProvider data_parse_name
	 */
	public function test_parse_name($name, $method_name, $up, $down)
	{
		$driver = new Migration_Driver_Mysql(Kohana::TESTING);
		$actions = $this->getMock('Migration_Actions', array($method_name), array($driver));
		$method = $actions->expects($this->once())->method($method_name);
		$actions->parse($name);


		$actions = new Migration_Actions($driver);
		$actions->parse($name);

		foreach ($up as $i => $up_action)
		{
			$this->assertArrayHasKey($i, $actions->up);
			$this->assertContains($up_action, $actions->up[$i]);
		}

		foreach ($down as $i => $down_action)
		{
			$this->assertArrayHasKey($i, $actions->down);
			$this->assertContains($down_action, $actions->down[$i]);
		}

	}
}
