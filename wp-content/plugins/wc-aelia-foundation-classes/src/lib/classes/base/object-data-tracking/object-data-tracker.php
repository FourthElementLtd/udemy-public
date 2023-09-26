<?php
namespace Aelia\WC\Object_Data_Tracking;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;
use Aelia\WC\Object_Data_Tracking\Object_Data_Maps\Object_Data_Map;
use Aelia\WC\Object_Data_Tracking\Object_Data_Maps\Object_Data_Map_Legacy;
use Aelia\WC\Object_Data_Tracking\Object_Data_Maps\Object_Data_Map_WeakMap;
use Aelia\WC\Traits\Singleton;

class Object_Data_Tracker {
	// A logger is unusual for a data object, but it can allow to track errors, e.g.
	// properties set incorrectly
	use Logger_Trait;

	/**
	 * Stores the data map instance used to track the data linked to an object.
	 *
	 * @var Object_Data_Map
	 */
	protected static $_data_map;

	/**
	 * Returns the data map that will be used to store the auxiliary data linked to an object.
	 *
	 * @return Object_Data_Map
	 */
	protected static function get_data_map(): Object_Data_Map {
		if(empty(static::$_data_map)) {
			// Use the WeakMap from PHP 8.2 onward, and the legacy data map for earlier versions
			self::$_data_map = version_compare(phpversion(), '8.2', '>=') ? new Object_Data_Map_WeakMap() : new Object_Data_Map_Legacy();
		}

		return static::$_data_map;
	}

	/**
	 * Adds or replace the value of a piece of data in the data map.
	 *
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public static function set_value(object $object, string $name, $value): void {
		self::get_data_map()->set_value($object, $name, $value);
	}

	/**
	 * Returns the value of a piece of data from the data map linked to an object. If
	 * the data is not found, null is returned.
	 *
	 * @param object $object
	 * @param string $name
	 * @return mixed
	 */
	public static function get_value(object $object, $name) {
		return self::get_data_map()->get_value($object, $name);
	}

	/**
	 * Deletes a piece of data from the data map linked to an object.
	 *
	 * @param object $object
	 * @param string $name
	 * @return void
	 */
	public static function delete_value(object $object, string $name): void {
		self::get_data_map()->delete_value($object, $name);
	}
}