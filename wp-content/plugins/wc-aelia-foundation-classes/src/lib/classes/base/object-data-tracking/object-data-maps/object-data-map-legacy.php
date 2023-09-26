<?php
namespace Aelia\WC\Object_Data_Tracking\Object_Data_Maps;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * This class tracks auxiliary data by simply "tacking" dynamic properties
 * against the objects, as it was done in PHP 8.1 and earlier.
 *
 * @since 2.3.0.220730
 */
class Object_Data_Map_Legacy extends Object_Data_Map {
	/**
	 * Adds or replace the value of a piece of data in the data map.
	 *
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function set_value(object $object, string $name, $value): void {
		global $track_props;

		$track_props = false;
		$object->{$name} = $value;
		$track_props = true;
	}

	/**
	 * Returns the value of a piece of data from the data map linked to an object. If
	 * the data is not found, null is returned.
	 *
	 * @param object $object
	 * @param string $name
	 * @return mixed
	 */
	public function get_value(object $object, $name) {
		return property_exists($object, $name) ? $object->{$name} : null;
	}

	/**
	 * Deletes a piece of data from the data map linked to an object.
	 *
	 * @param object $object
	 * @param string $name
	 * @return void
	 */
	public function delete_value(object $object, $name): void {
		unset($object->{$name});
	}
}