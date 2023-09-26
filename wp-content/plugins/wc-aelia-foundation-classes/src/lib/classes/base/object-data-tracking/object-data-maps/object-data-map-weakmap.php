<?php
namespace Aelia\WC\Object_Data_Tracking\Object_Data_Maps;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use stdClass;

/**
 * This class tracks auxiliary data by using the new WeakMap class, introduced
 * in PHP 8.2.
 *
 * @since 2.3.0.220730
 */
class Object_Data_Map_WeakMap extends Object_Data_Map {
	protected $_weakmap;

	/**
	 * Returns the instance of the WeakMap used to track the auxiliary data for objects.
	 *
	 * @return WeakMap
	 */
	protected function get_map(): \WeakMap {
		return $this->_weakmap ?? $this->_weakmap = new \WeakMap();
	}

	/**
	 * Returns the object that contains the data for the object, or initialise a new one.
	 *
	 * @param object $object
	 * @return stdClass
	 */
	protected function get_data_container(object $object): stdClass {
		$weakmap = $this->get_map();
		return $weakmap[$object] = $weakmap[$object] ?? new stdClass();
	}

	/**
	 * Adds or replace the value of a piece of data in the data map.
	 *
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function set_value(object $object, string $name, $value): void {
		$this->get_data_container($object)->{$name} = $value;
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
		return $this->get_data_container($object)->{$name} ?? null;
	}

	/**
	 * Deletes a piece of data from the data map linked to an object.
	 *
	 * @param object $object
	 * @param string $name
	 * @return void
	 */
	public function delete_value(object $object, $name): void {
		unset($this->get_data_container($object)->{$name});
	}
}