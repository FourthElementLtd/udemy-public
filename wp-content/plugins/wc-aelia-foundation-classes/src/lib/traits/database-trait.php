<?php
namespace Aelia\WC\AFC\Traits;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
* Implements common methods for data definition and manipulation.
*
* @since 2.4.9.230616
*/
trait Database_Trait {
	/**
	 * Suppresses all error message. This method is mainly used as a workaround
	 * to prevent warning from being raised when START TRANSACTION, COMMIT and
	 * ROLLBACK queries are executed.
	 *
	 * @return void
	 */
	protected function suppress_errors(): void {
		set_error_handler(function() { /* ignore errors */ });
	}

	/**
	 * Restores the error handler, which will display errors again.
	 *
	 * @return void
	 */
	protected function enable_errors(): void {
		restore_error_handler();
	}

	/**
	 * Starts a database transaction.
	 *
	 * @return bool
	 */
	protected function start_transaction() {
		// Suppressing errors is necessary because the WPDB class doesn't expect
		// a transaction command and tries to fetch a result set after running the
		// query, triggering a warning
		$this->suppress_errors();
		$result = $this->exec('START TRANSACTION');
		$this->enable_errors();

		return $result;
	}

	/**
	 * Rolls back a database transaction.
	 *
	 * @return bool
	 */
	protected function rollback_transaction() {
		// Suppressing errors is necessary because the WPDB class doesn't expect
		// a transaction command and tries to fetch a result set after running the
		// query, triggering a warning
		$this->suppress_errors();
		$result = $this->exec('ROLLBACK');
		$this->enable_errors();

		return $result;
	}

	/**
	 * Commits a database transaction.
	 *
	 * @return bool
	 */
	protected function commit_transaction() {
		// Suppressing errors is necessary because the WPDB class doesn't expect
		// a transaction command and tries to fetch a result set after running the
		// query, triggering a warning
		$this->suppress_errors();
		$result = $this->exec('COMMIT');
		$this->enable_errors();

		return $result;
	}

	/**
	 * Executes a non-query SQL statement (i.e. INSERT, UPDATE, DELETE).
	 *
	 * @param string sql The statement to execute.
	 * @return int
	 * @see wpdb::query()
	 */
	protected function exec($sql) {
		global $wpdb;

		return $wpdb->query($sql);
	}

	/**
	 * Checks if a column exists in a table.
	 *
	 * @param string table The table name.
	 * @param string column The column name.
	 * @since 1.4.10.150209
	 */
	protected function column_exists($table, $column) {
		global $wpdb;
		$SQL = "
			SELECT COUNT(*)
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE
				(TABLE_NAME = %s) AND
				(COLUMN_NAME = %s);
		";

		return $wpdb->get_var($wpdb->prepare(
			$SQL,
			$table,
			$column
		));
	}

	/**
	 * Adds a column to a table.
	 *
	 * @param string table The table name.
	 * @param string column The column name.
	 * @param string column_type The column type.
	 * @param string collate Collation settings. If left empty, the default
	 * settings will be taken from global $wpdb object. Used only for text
	 * columns.
	 * @options string Additional options for column creation (e.g. UNSIGNED,
	 * NOT NULL, etc).
	 * @since 1.4.10.150209
	 */
	protected function add_column($table, $column, $column_type, $collate = '', $options = '') {
		global $wpdb;

		if(in_array(strtoupper($column_type), array('CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT'))) {
			if(empty($collate) && $wpdb->has_cap('collation')) {
				if(!empty($wpdb->charset)) {
					$collate .= "CHARACTER SET $wpdb->charset";
				}
				if(!empty($wpdb->collate)) {
					$collate .= " COLLATE $wpdb->collate";
				}
			}
			$column_type .= ' ' . $collate;
		}

		$SQL = "
			ALTER TABLE {$table}
			ADD COLUMN {$column} {$column_type} {$options}
		";
		return $wpdb->query($SQL);
	}

	/**
	 * Executes a query SQL statement (i.e. SELECT).
	 *
	 * @param string sql The statement to execute.
	 * @return array
	 * @see wpdb::query()
	 */
	protected function select($sql, $output_type = OBJECT) {
		global $wpdb;

		return $wpdb->get_results($sql, $output_type);
	}

	/**
	 * Returns the database version.
	 *
	 * @param string $plugin_slug
	 * @return string
	 */
	protected function get_database_version(string $plugin_slug): string {
		$database_version_key = $plugin_slug . '-database-version';
		return (string)get_option($database_version_key);
	}

	/**
	 * Sets the database version.
	 *
	 * @var string $plugin_slug
	 * @var string $database_version
	 * @return string
	 */
	protected function set_database_version(string $plugin_slug, string $database_version): void {
		$database_version_key = $plugin_slug . '-database-version';
		update_option($database_version_key, $database_version, false);
	}
}
