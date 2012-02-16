<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    OpenBuildings/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Migration_Driver_Mysql_Column extends Migration_Driver_Column
{
	/**
	 * Valid types
	 * @var array
	 */
	static protected $types = array
	(
		'primary_key' => array('type' => 'INT', 'null' => FALSE, 'auto' => TRUE, 'primary' => TRUE),
		'string' => array('type' => 'VARCHAR', 'limit' => 255),
		'text' => array('type' => 'TEXT'),
		'integer' => array('type' => 'INT'),
		'float' => array('type' => 'FLOAT', 'limit' => 10, 'precision' => 2),
		'decimal' => array('type' => 'DECIMAL', 'limit' => 10, 'precision' => 2),
		'datetime' => array('type' => 'DATETIME'),
		'timestamp' => array('type' => 'TIMESTAMP'),
		'time' => array('type' => 'TIME'),
		'date' => array('type' => 'DATE'),
		'binary' => array('type' => 'BLOB', 'limit' => 255),
		'boolean' => array('type' => 'TINYINT', 'limit' => 1, 'null' => FALSE, 'default' => 0),
	);

	static protected $native_types = array
	(
		'CHAR' => 'string',
		'VARCHAR' => 'string',
		'TEXT' => 'text',
		'INT' => 'integer',
		'INTEGER' => 'integer',
		'TINYINT' => 'boolean',
		'FLOAT' => 'float',
		'DECIMAL' => 'decimal',
		'DATETIME' => 'datetime',
		'TIMESTAMP' => 'timestamp',
		'TIME' => 'time',
		'DATE' => 'date',
		'BLOB' => 'binary',
	);

	public function column_params_for($column)
	{
		return Arr::get(self::$types, $column, array());
	}

	public function load($table_name)
	{
		if (is_string($table_name))
		{
			try 
			{
				$result = $this->driver->pdo->query("SHOW COLUMNS FROM `$table_name` LIKE '{$this->name}'");
			} 
			catch (PDOException $e) 
			{
				$result = NULL;
			}

			if ( ! $result OR $result->rowCount() !== 1)
			{
				throw new Migration_Exception("Column :column was not found in table :table", array(':column' => $this->name, ':table' => $this->name));
			}
			
			$result = $result->fetchObject();
		}
		else
		{
			$result = $table_name;
		}

		$limit = preg_match('/([^\(]+)(\((\d+)\))?( UNSIGNED)?/', $result->Type, $type);

		$this->params(array(
			'type' => $type[1],
			'limit' => Arr::get($type, 3),
			'unsigned' => isset($type[4]) ? TRUE : NULL,
			'null' => $result->Null == 'NO' ? TRUE : FALSE,
			'default' => $result->Default ? $result->Default : NULL,
			'auto' => $result->Extra == 'auto_increment',
			'primary' => $result->Key == 'PRI',
		));
		
		return $this;
	}

	public function type()
	{
		return Arr::get(self::native_types, $this->type);
	}

	public function sql()
	{
		extract(Arr::extract($this->params, Migration_Driver_Column::$available_params));

		return join(' ', array_filter(array(
			"`{$this->name}`",
			$type, 
			$limit ? ( $precision ? ( "({$limit}, {$precision})" ) : "({$limit})") : NULL, 
			$default ? ("DEFAULT ".$this->driver->pdo->quote($default)) : NULL,
			$unsigned ? ("UNSIGNED") : NULL,
			$null !== NULL ? ($null ? "NULL" : "NOT NULL") : NULL,
			$auto ? ("AUTO_INCREMENT") : NULL,
			$after ? ("AFTER `{$after}`") : NULL,
			$first ? ("FIRST") : NULL,
		)));
	}

}
