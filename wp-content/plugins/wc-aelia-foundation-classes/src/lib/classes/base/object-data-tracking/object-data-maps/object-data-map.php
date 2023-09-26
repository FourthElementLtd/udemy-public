<?php
namespace Aelia\WC\Object_Data_Tracking\Object_Data_Maps;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;

interface IObject_Data_Map {
	public function set_value(object $object, string $name, $value): void;
	public function get_value(object $object, $name);
	public function delete_value(object $object, $name);
}

/**
 * Implements the base structure of a data map. The class can be used to track
 * custom data linked to an object, without using dynamic properties, which are
 * going to be deprecated in PHP 8.2.
 *
 * @since 2.3.0.220730
 */
abstract class Object_Data_Map implements IObject_Data_Map {
	// A logger is unusual for a data object, but it can allow to track errors, e.g.
	// properties set incorrectly
	use Logger_Trait;

	/**
	 * Adds or replace the value of a piece of data in the data map.
	 *
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public abstract function set_value(object $object, string $name, $value): void;

	/**
	 * Returns the value of a piece of data from the data map linked to an object. If
	 * the data is not found, null is returned.
	 *
	 * @param object $object
	 * @param string $name
	 * @return void
	 */
	public abstract function get_value(object $object, $name);

	/**
	 * Deletes a piece of data from the data map linked to an object.
	 *
	 * @param object $object
	 * @param string $name
	 * @return void
	 */
	public abstract function delete_value(object $object, $name): void;
}