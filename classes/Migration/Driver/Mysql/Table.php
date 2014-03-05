<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Migration_Driver_Mysql_Table extends Migration_Driver_Table
{

	public function params(array $columns = NULL, array $options = NULL)
	{
		$columns = (array) $columns;
		$this->options = Arr::get( (array) $options, 'options');

		if (Arr::get((array) $options, 'id', TRUE))
		{
			$columns = array_merge(array('id' => 'primary_key'), $columns);
		}

		foreach ($columns as $column_name => $params)
		{
			$this->columns[$column_name] = $this->driver->column($column_name)->params($params);
		}

		return $this;
	}

	public function load()
	{
		$this->columns = array();
		$this->options = array();
		$this->keys = array();

		$result = $this->driver->pdo->query("SHOW TABLE STATUS LIKE '{$this->name}'");

		if ($result->rowCount() !== 1)
		{
			throw new Migration_Exception(":table does not exist", array(':table' => $this->name));
		}

		$table->options[] = 'ENGINE='.$result->fetchObject()->Engine;

		$fields_reuslt = $this->driver->pdo->query("SHOW COLUMNS FROM `{$this->name}`");

		while($result = $fields_reuslt->fetchObject())
		{
			$this->columns[$result->Field] = $this->driver->column($result->Field)->load($result);
		}
		return $this;
	}

	public function sql()
	{
		$primary_keys = array();
		$columns = array();

		foreach ($this->columns as $column)
		{
			$columns[] = $column->sql();
			if ($column->param('primary'))
			{
				$primary_keys[] = "`".$column->name()."`";
			}
		}

		if ($primary_keys)
		{
			$columns[] = 'PRIMARY KEY ('.join(', ', $primary_keys).')';
		}

		return join(' ', array_filter(array(
			"CREATE TABLE `{$this->name}`",
			'('. join(', ', $columns).')',
			$this->options
		)));
	}
}
