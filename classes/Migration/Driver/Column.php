<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
abstract class Migration_Driver_Column
{
	static protected $available_params = array(
		'type',
		'limit',
		'auto',
		'primary',
		'null',
		'precision',
		'first',
		'after',
		'default',
		'unsigned',
		'values',
		'comment',
	);

	protected $params = array();
	protected $name;
	protected $driver;

	public function name($name = NULL)
	{
		if ($name === NULL)
		{
			return $this->name;
		}

		$this->name = (string) $name;
		return $this;
	}

	public function __construct($name, Migration_Driver $driver)
	{
		$this->name = $name;
		$this->driver = $driver;
	}

	public function params($params = NULL)
	{
		if ($params === NULL)
		{
			return Arr::extract($this->params, self::$available_params);
		}
		$params = (array) $params;

		if (isset($params[0]))
		{
			$params = Arr::merge($this->column_params_for($params[0]), array_slice($params, 1));
		}

		if ($illigal = array_diff(array_keys($params), self::$available_params))
			throw new Migration_Driver_Exception_Params($illigal);

		$this->params = $params;

		return $this;
	}

	public function param($name, $value = NULL)
	{
		if ($value === NULL)
		{
			return Arr::get($this->params, $name, NULL);
		}

		if (array_search($name, self::$available_params) === FALSE)
			throw new Migration_Driver_Exception_Params($name);

		$this->params[$name] = $value;
		return $this;
	}

	abstract public function column_params_for($column);
	abstract public function load($table_name);
	abstract public function type();
	abstract public function sql();
}
