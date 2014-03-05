<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migration exceptions.
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2014 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Migration_Driver_Exception_Params extends Migration_Exception
{
	public function __construct($illigal)
	{
		return parent::__construct("Illigal params :params", array(':params' => join(', ', (array) $illigal)));
	}
}
