<?php defined('SYSPATH') OR die('No direct script access.');

class Migration_Driver_Exception_Params extends Migration_Exception
{
	public function __construct($illigal)
	{
		return parent::__construct("Illigal params :params", array(':params' => join(', ', (array) $illigal)));
	}
}
