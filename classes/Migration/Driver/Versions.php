<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Migration_Driver_Versions
{
	abstract public function set($version);
	abstract public function clear($version);
	abstract public function init();
	abstract public function get();
	abstract public function clear_all();
}
