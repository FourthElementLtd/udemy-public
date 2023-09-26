<?php

namespace MC4WP\User_Sync;

use MC4WP_MailChimp_Subscriber;
use WP_User;
use WP_User_Query;
use Exception;

/**
 * Class UserRepository
 *
 * @package MC4WP\Sync
 */
class Users {

	private $list_id = '';

	/**
	 * @var string
	 */
	private $role = '';

	/**
	 * @var array
	 */
	private $field_map = array();

	/**
	 * @var Tools
	 */
	private $tools;

	/**
	 * @param string $list_id
	 * @param string $role
	 * @param array $field_map
	 */
	public function __construct( $list_id, $role = '', $field_map = array() ) {
		$this->list_id = $list_id;
		$this->role = $role;
		$this->field_map = $field_map;

		$this->tools = new Tools();
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function get( array $args = array() ) {
		$args['role'] = $this->role;
		$user_query = new WP_User_Query( $args );
		return $user_query->get_results();
	}

	/**
    * Counts all users matching the given role
    *
	 * @return int
	 */
	public function count() {
		global $wpdb;

		$sql = "SELECT COUNT(u.ID) FROM $wpdb->users u";
		$params = array();
		$prefix = is_multisite() ? $wpdb->get_blog_prefix( get_current_blog_id() ) : $wpdb->prefix;

		if( '' !== $this->role ) {
			$sql .= " INNER JOIN $wpdb->usermeta um2 ON um2.user_id = u.ID AND um2.meta_key = %s AND um2.meta_value LIKE %s";
			$params[] = $prefix . 'capabilities';
			$params[] = '%%' . $this->role . '%%';
		}

		if( is_multisite() ) {
			$sql .= " RIGHT JOIN {$wpdb->usermeta} um4 ON um4.user_id = u.ID AND um4.meta_key = %s";
			$params[] = $prefix . 'capabilities';
		}

		// now get number of users with meta key
		$query = empty( $params ) ? $sql : $wpdb->prepare( $sql, $params );
		$count = $wpdb->get_var( $query );
		return (int) $count;
	}

	/**
	 * @param string $mailchimp_id
	 * @return WP_User|null;
	 */
	public function get_user_by_mailchimp_id( $mailchimp_id ) {
		$args = array(
			'meta_key'     => '_mc4wp_sync_mailchimp_id',
			'meta_value'   => $mailchimp_id,
		);

		return $this->get_first_user( $args );
	}

    /**
     * @param string $email_address
     *
     * @return WP_User|null;
     */
    public function get_user_by_email_address( $email_address ) {
        $args = array(
            'user_email' => $email_address,
        );

        return $this->get_first_user( $args );
    }

	/**
	 * @return WP_User
	 */
	public function get_current_user() {
		return wp_get_current_user();
	}

	/**
	 * @param array $args
	 *
	 * @return null|WP_User
	 */
	public function get_first_user( array $args = array() ) {
		$args['number'] = 1;
		$users = $this->get( $args );

		if( empty( $users ) ) {
			return null;
		}

		return $users[0];
	}

    /**
     * @param int|WP_User $user_id
     * @return int
     */
	public function id( $user_id ) {
        if( $user_id instanceof WP_User ) {
            $user_id = $user_id->ID;
        }

        return $user_id;
    }

	/**
	 * @param int|WP_User $user
	 * @return WP_User
	 *
	 * @throws Exception
	 */
	public function user( $user ) {

		if( ! is_object( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if( ! $user instanceof WP_User ) {
			throw new Exception( sprintf( 'Invalid user ID: %d', $user ) );
		}

		return $user;
	}



	/**
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function should( WP_User $user ) {
		// Only handle user if it has a valid email address
		if( '' === $user->user_email || ! is_email( $user->user_email ) ) {
			return false;
		}

		$sync = true;

		// if role is set, make sure user has that role or don't sync
		if( ! empty( $this->role ) && ! in_array( $this->role, $user->roles ) ) {
			$sync = false;
		}

		/**
		 * Filters whether a user should be synchronized with Mailchimp or not.
		 *
		 * @param boolean $sync
		 * @param WP_User $user
		 */
		return (bool) apply_filters( 'mc4wp_user_sync_should_sync_user', $sync, $user );
	}

    /**
     * @param int $user_id
     * @param string $email_address
     */
    public function set_mailchimp_email_address( $user_id, $email_address ) {
        $user_id = $this->id( $user_id );
        update_user_meta( $user_id, '_mc4wp_sync_mailchimp_email_address', $email_address );
    }

    /**
     * @param int $user_id
     */
    public function delete_mailchimp_email_address( $user_id ) {
        $user_id = $this->id( $user_id );
        delete_user_meta( $user_id, '_mc4wp_sync_mailchimp_email_address' );
    }

    /**
     * @param int $user_id
     * @return string
     */
    public function get_mailchimp_email_address( $user_id ) {
        $user_id = $this->id( $user_id );
        $email_address = get_user_meta( $user_id, '_mc4wp_sync_mailchimp_email_address', true );
        return is_string( $email_address ) ? $email_address : '';
    }

    /**
     * @param int $user_id
     * @return string
     */
    public function get_mailchimp_id( $user_id ) {
        $user_id = $this->id( $user_id );
		$mailchimp_id = get_user_meta( $user_id, '_mc4wp_sync_mailchimp_id', true );
        return is_string( $mailchimp_id ) ? $mailchimp_id : '';
    }

	/**
	 * @param int $user_id
	 * @param string $mailchimp_id
	 */
	public function set_mailchimp_id( $user_id, $mailchimp_id ) {
        $user_id = $this->id( $user_id );
		update_user_meta( $user_id, '_mc4wp_sync_mailchimp_id', $mailchimp_id );
	}

	/**
	 * @param int $user_id
	 */
	public function delete_mailchimp_id( $user_id ) {
        $user_id = $this->id( $user_id );
		delete_user_meta( $user_id, '_mc4wp_sync_mailchimp_id' );
	}

	/**
	 * @param WP_User $user
	 * @return array
	 */
	public function get_user_merge_fields( WP_User $user ) {
		$settings = get_settings();
		$merge_fields = array();

		if( ! empty( $user->first_name ) ) {
            $merge_fields['FNAME'] = $user->first_name;
		}

		if( ! empty( $user->last_name ) ) {
            $merge_fields['LNAME'] = $user->last_name;
		}

		if( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
            $merge_fields['NAME'] = sprintf( '%s %s', $user->first_name, $user->last_name );
		}

		// Do we have mapping rules for user fields to mailchimp fields?
		if( ! empty( $this->field_map ) ) {

			// loop through mapping rules
			foreach( $this->field_map as $rule ) {
				// skip broken settings
				if ( empty( $rule['mailchimp_field'] ) || empty( $rule['user_field'] ) ) {
					continue;
				}

				// get field value
				$value = $this->tools->get_user_field( $user, $rule['user_field'] );

				if ( is_string( $value ) ) {

					// skip field is value is empty & setting to skip empty user fields is enabled
					if ( $value === '' && $settings['skip_empty_user_fields'] ) {
						continue;
					}

                    $merge_fields[ $rule['mailchimp_field'] ] = $value;
				}
			}
		}

		return $merge_fields;
	}

	/**
	 * @param WP_User $user
	 *
	 * @return MC4WP_MailChimp_Subscriber
	 */
    public function user_to_subscriber( WP_User $user ) {
        $subscriber = new MC4WP_MailChimp_Subscriber();
        $subscriber->email_address = $user->user_email;
        $subscriber->merge_fields = $this->get_user_merge_fields( $user );

        // set all fields we don't want to modify to null so they are omitted from the PATCH request
        $subscriber->status = null;
        $subscriber->tags = null;
        $subscriber->interests = null;
        $subscriber->email_type = null;

        /**
         * Filter data that is sent to Mailchimp
         *
         * @param MC4WP_MailChimp_Subscriber $subscriber
         * @param WP_User $user
         */
        $subscriber = apply_filters( 'mc4wp_user_sync_subscriber_data', $subscriber, $user );

        /**
         * @deprecated
         * @see mc4wp_user_sync_subscriber_data
         */
        $subscriber = apply_filters( 'mailchimp_sync_subscriber_data', $subscriber, $user );

        return $subscriber;
    }

    /**
     * @param WP_User $user
     * @return string
     */
    public function user_to_hash( WP_User $user ) {
        $subscriber = $this->user_to_subscriber( $user );
        return $this->subscriber_to_hash( $subscriber );
    }

    /**
     * @param MC4WP_MailChimp_Subscriber $subscriber
     * @return string
     */
    public function subscriber_to_hash( MC4WP_MailChimp_Subscriber $subscriber ) {
        return md5( json_encode( $subscriber->to_array() ) );
    }

    public function get_hash( $user_id ) {
        return (string) get_user_meta( $user_id, '_mc4wp_sync_hash', true );
    }

    public function set_hash( $user_id, $hash ) {
        update_user_meta( $user_id, '_mc4wp_sync_hash', $hash );
    }

}
