<?php

/**
 * Class MC4WP_Lock
 *
 * @see https://symfony.com/doc/current/components/lock.html
 */
class MC4WP_Lock {
	private $ttl;
	private $option_name;
	private $acquired = false;

	public function __construct( $name, $ttl = 300 ) {
		$this->option_name = 'mc4wp_lock_' . $name;
		$this->ttl = $ttl;
	}

	private function ensure_row_exists() {
		global $wpdb;
		$sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s;", $this->option_name );
		$count = (int) $wpdb->get_var( $sql );
		if ( $count === 1 ) {
			return;
		}

		$sql = $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES(%s, '0', 'no');", $this->option_name );
		$wpdb->query( $sql );
	}

	/**
	 * @return bool
	 */
	public function acquire() {
		global $wpdb;
		$this->ensure_row_exists();
		$current_time = time();
		$max_time = $current_time - $this->ttl;
		$sql = $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value < %d;", $current_time, $this->option_name, $max_time );
		$success = ( (int) $wpdb->query( $sql ) ) === 1;
		if ( true === $success ) {
			$this->acquired = true;
		}
		return $success;
	}

	/**
	 * @return bool
	 */
	public function release() {
		if ( true !== $this->acquired ) {
			return false;
		}

		global $wpdb;
		$sql = $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = '0' WHERE option_name = %s;", $this->option_name );
		$success = ( (int) $wpdb->query( $sql ) ) === 1;
		if ( $success ) {
			$this->acquired = false;
		}
		return $success;
	}
}
