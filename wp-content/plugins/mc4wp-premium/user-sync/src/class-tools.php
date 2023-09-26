<?php

namespace MC4WP\User_Sync;

use WP_User;

class Tools {

	/**
	 * Returns the translated role of the current user. If that user has
	 * no role for the current blog, it returns false.
	 *
	 * @param WP_User $user
	 * @return string The name of the current role
	 **/
	public function get_user_role( WP_User $user ) {
		global $wp_roles;
		$roles = $user->roles;
		$role = $roles[0];
		return isset( $wp_roles->role_names[$role] ) ? translate_user_role( $wp_roles->role_names[$role] ) : '';
	}

	/**
	 * @param WP_User $user
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function get_user_field( WP_User $user, $field_name) {

		$magic_fields = array( 'role' );

		if( in_array( $field_name, $magic_fields, true ) ) {
			return $this->get_user_magic_field( $user, $field_name );
		}

		// default to empty string
		$value = '';

		// does user have this property?
		if( $user->has_prop( $field_name ) ) {

			// get value
			$value = $user->get( $field_name );

			// convert array to comma-separated string
			if( is_array( $value ) ) {
				$value = join( ', ', $value );
			}
		}

		// revert back to string if value is not a scalar type by now
		$value = is_scalar($value) ? (string) $value : '';

		/**
		 * Filters the field value that is returned for unknown fields
		 *
		 * @param string $value
		 * @param string $field_name
		 * @param WP_User $user
		 */
		$value = apply_filters( 'mc4wp_user_sync_get_user_field', $value, $field_name, $user );

		/**
		 * @deprecated
		 * @see mc4wp_user_sync_get_user_field
		 */
		$value = apply_filters( 'mailchimp_sync_get_user_field', $value, $field_name, $user );

		return $value;
	}

	/**
	 * @param WP_User $user
	 * @param string $field_name
	 *
	 * @return string|bool
	 */
	public function get_user_magic_field( WP_User $user, $field_name ) {
		switch( $field_name ) {
			case 'role':
				return $this->get_user_role( $user );
				break;
		}

		return false;
	}


}
