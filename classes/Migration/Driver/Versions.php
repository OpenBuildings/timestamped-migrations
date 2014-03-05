<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Mysql Driver
 *
 * @package    Despark/timestamped-migrations
 * @author		 Matías Montes
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Migration_Driver_Versions
{
	abstract public function set($version);
	abstract public function clear($version);
	abstract public function init();
	abstract public function get();
	abstract public function clear_all();
}
