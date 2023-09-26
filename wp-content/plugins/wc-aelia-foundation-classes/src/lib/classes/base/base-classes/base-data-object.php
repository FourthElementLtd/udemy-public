<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;

/**
 * Implements a base class for data objects. This is a "poor man's replacement"
 * for TypeScript interfaces, used to improve data type consistency.
 *
 * @since 2.1.9.210525
 */
abstract class Base_Data_Object implements \JsonSerializable {
	// A logger is unusual for a data object, but it can allow to track errors, e.g.
	// properties set incorrectly
	use Logger_Trait;

	/**
	 * Constructor
	 *
	 * @param array $args
	 * @param bool $ignore_extra_properties Indicates if additional properties, that
	 * don't match the data description, should be ignored. When set to false, passing
	 * additional properties will throw an error.
	 */
	public function __construct(array $args, $ignore_extra_properties = false) {
		$invalid_props = [];
		foreach($args as $key => $value) {
			$setter_method = "set_{$key}";
			// If a setter method exists for a property, use it
			if(\method_exists($this, $setter_method)) {
				$this->$setter_method($value);
			}
			// Assign the value to each matching property of this class
			elseif(property_exists($this, $key)) {
				$this->$key = $value;
			}
			else {
				// Keep track of invalid properties
				$invalid_props[$key] = $value;
			}
		}

		// Throw an error if invalid properties were used to initialise the object
		if(!empty($invalid_props) && !$ignore_extra_properties) {
			$this->get_logger()->error(__('Invalid properties passed to data object constructor.', Definitions::TEXT_DOMAIN), [
				'Class' => get_class($this),
				'Invalid Properties' => $invalid_props,
			]);

			throw new \InvalidArgumentException(sprintf(__('Invalid properties passed to data object constructor. Class: "%1$s". Invalid properties (JSON): "%2$s".', Definitions::TEXT_DOMAIN), get_class($this), json_encode($invalid_props)));
		}
	}

	/**
	 * Magic method to access properties.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		$getter_method = "get_{$name}";
		if(\method_exists($this, $getter_method)) {
			return $this->$getter_method();
		}

		if(property_exists($this, $name)) {
			return $this->$name;
		}

		$this->get_logger()->error(__('Invalid property accessed via __get().', Definitions::TEXT_DOMAIN), [
			'Class' => get_class($this),
			'Property' => $name,
		]);

		throw new \InvalidArgumentException(sprintf(__('Invalid property accessed via __get(): "%1$s::%2$s".', Definitions::TEXT_DOMAIN), get_class($this), $name));
	}

	/**
	 * Magic method to set properties.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$setter_method = "set_{$name}";
		if(\method_exists($this, $setter_method)) {
			return $this->$setter_method($value);
		}

		if(property_exists($this, $name)) {
			$this->$name = $value;
		}
		else {
			$this->get_logger()->error(__('Invalid property accessed via __set().', Definitions::TEXT_DOMAIN), [
				'Class' => get_class($this),
				'Property' => $name,
				'Value' => $value,
			]);

			throw new \InvalidArgumentException(sprintf(__('Invalid property accessed via __set(): "%1$s::%2$s".', Definitions::TEXT_DOMAIN), get_class($this), $name));
		}
	}

	/**
	 * Magic method to check for the presence of properties.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		return property_exists($this, $name);
	}

	/**
	 * Returns the properties of the data object, so that it can be serialized
	 * by calling json_encode(), even if the object contains private or protected
	 * properties.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array	{
		return get_object_vars($this);
	}
}
