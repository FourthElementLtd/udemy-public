<?php
namespace Aelia\WC\Traits;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Singleton trait.
 *
 * @since 2.1.0.201112
 */
trait Singleton {
	protected static $_instances = array();

	/**
	 * Returns an instance of the class to which the trait is assigned.
	 *
	 * NOTE
	 * This trait uses a list of instances, rather than just one instance. This is to allow using
	 * the trait inside a base class, which is then inherited by descendant classes, who would
	 * otherwise share the same static $_instance property. Due to that, if the variable had a
	 * single value, the it would always store the instance of the first class for which
	 * Singleton::instance() is called.
	 *
	 * Example
	 * class Base {
	 *   use Singleton;
	 * }
	 *
	 * class Descendant1 extends Base { }
	 *
	 * class Descendant2 extends Base { }
	 *
	 * // This would return an instance of Descendant1
	 * Descendant1::instance();
	 * // This would ALSO return an instance of Descendant1, because the single
	 * // $_instance variable belongs to the base class, and its value is shared
	 * // by descendants
	 * Descendant2::instance();
	 *
	 * To solve this issues, both Descendant1 and Descendant2 could declare
	 * "use Singleton;". That would create a separate $_instance variable for each
	 * class.
	 * In this case, we chose the alternative approach of handling multiple singleton
	 * instances of descendant classes, indexing them by class name.
	 *
	 * @return mixed
	 */
	public static function instance() {
		$called_class = get_called_class();
		// Instantiate the called class and store the instance in the internal array
		// of singletons, before returning it
		return static::$_instances[$called_class] ?? static::$_instances[$called_class] = new static();
	}
}
