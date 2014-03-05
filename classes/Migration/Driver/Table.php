<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Driver
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
abstract class Migration_Driver_Table
{
	public $columns = array();
	public $options = array();

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

	abstract public function load();
	abstract public function params(array $columns = NULL, array $options = NULL);
	abstract public function sql();
}
