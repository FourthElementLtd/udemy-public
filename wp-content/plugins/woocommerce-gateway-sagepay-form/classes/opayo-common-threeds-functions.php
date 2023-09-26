<?php

    /**
     * WC_Opayo_Common_Threeds_Functions class.
     *
     * 3D Secure Functions Common to Opayo Gateways.
     */
    class WC_Opayo_Common_Threeds_Functions {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            add_action( 'password_reset', array( $this, 'update_user_password_reset_date'), 10, 2 );

        }

        /**
         * Return challenge window size
         *
         * 01 = 250 x 400
         * 02 = 390 x 400
         * 03 = 500 x 600
         * 04 = 600 x 400
         * 05 = Full screen
         */
        public static function get_challenge_window_size( $width, $height ) {

            if( $width <= '250' ) {
                return 'Small';
            }

            if( $width <= '390' ) {
                return 'Medium';
            }

            if( $width <= '500' ) {
                return 'Large';
            }

            if( $width <= '600' ) {
                return 'ExtraLarge';
            }

            return 'FullScreen';

        }

        /**
         * Get IP Address
         */
        public static function get_ipaddress( $nullipaddress = NULL ) {

            $ipaddresses = self::get_ipaddresses();

            // Remove IPv6 addresses, Opayo does not support IPv6 yet
            foreach( $ipaddresses as $lable => $ipaddress ) {
                if ( !filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                    $cleaned_ipaddresses[] = self::isValidIP( $ipaddress );
                }
            }

            // IPv4 IP Address present, return
            if( isset( $cleaned_ipaddresses[0] ) ) {
                return $cleaned_ipaddresses[0];
            }

            return $nullipaddress;

        }

        /**
         * [get_ipaddresses description]
         * @return [type] [description]
         */
        public static function get_ipaddresses() {
            $ipaddresses = array();

            if( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
                $ipaddresses['HTTP_CF_CONNECTING_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
            } 

            if ( isset($_SERVER['HTTP_CLIENT_IP'] ) ) {
                $ipaddresses['HTTP_CLIENT_IP'] = $_SERVER['HTTP_CLIENT_IP'];
            }

            if ( isset($_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $ipaddresses['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            if ( isset($_SERVER['HTTP_X_FORWARDED'] ) ) {
                $ipaddresses['HTTP_X_FORWARDED'] = $_SERVER['HTTP_X_FORWARDED'];
            }

            if ( isset($_SERVER['HTTP_FORWARDED_FOR'] ) ) {
                $ipaddresses['HTTP_FORWARDED_FOR'] = $_SERVER['HTTP_FORWARDED_FOR'];
            }

            if ( isset($_SERVER['HTTP_FORWARDED'] ) ) {
                $ipaddresses['HTTP_FORWARDED'] = $_SERVER['HTTP_FORWARDED'];
            }

            if ( isset($_SERVER['REMOTE_ADDR'] ) ) {
                $ipaddresses['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            }
    
            // Testing
            // $ipaddresses['REMOTE_ADDR'] = "2001:0db8:85a3:0000:0000:8a2e:0370:7334,7334";
            
            // Validate IP Addresses
            foreach( $ipaddresses as $lable => $ipaddress ) {
                $ipaddresses[ $lable ] = self::isValidIP( $ipaddress );
            }

            return $ipaddresses;

        }

        /**
         * [isValidIP description]
         * @param  [type]  $ipaddress [description]
         * @return boolean            [description]
         */
        public static function isValidIP( $ipaddress ) {

            // If the IP address is valid send it back
            if( filter_var( $ipaddress, FILTER_VALIDATE_IP ) ) {
                return $ipaddress;
            }

            // Clean up the IP6 address
            if ( strpos( $ipaddress, ':' ) !== false ) {

                // Make an array of the chunks
                $ip = explode( ":", $ipaddress );

                // Only the first 8 chunks count
                $ip = array_slice( $ip, 0, 8 );

                // Make sure each chunk is 4 characters long and only contains letters and numbers
                foreach( $ip as &$value ) {
                    $value = substr( $value, 0, 4 );
                    $value = preg_replace( '/\W/', '', $value );
                }

                unset( $value );

                // Combine the chunks and return the IP6 address
                return implode( ":", $ip );

            }

            // Clean up the IP4 address
            if ( strpos( $ipaddress, '.' ) !== false ) {

                // Make an array of the chunks
                $ip = explode( ".", $ipaddress );

                // Only the first 4 chunks count
                $ip = array_slice( $ip, 0, 4 );

                // Make sure each chunk is 3 characters long and only contains numbers
                foreach( $ip as &$value ) {
                    $value = substr( $value, 0, 3 );
                    $value = preg_replace( '/\D/', '', $value );
                }

                unset( $value );

                // Combine the chunks and return the IP4 address
                return implode( ".", $ip );

            }

            // Fallback
            return $ipaddress;
        }

        /**
         * { function_description }
         *
         * @param      <type>  $ip_address  The ip address
         */
        public static function check_ipaddress( $ip_address, $nullipaddress ) {

            if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
                return $ip_address;
            }

            return $nullipaddress;
        }

        /**
         * The account ID, if applicable, of your customers account on your website.
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_acctID( $data, $order ) {
            
            if( 0 !== $order->get_user_id() ) {
                $data["acctID"] = $order->get_user_id();
            }

            return $data;              
        }

        /**
         * Additional information about the Cardholder's account that has been provided by you. E.g. How long has the cardholder had the account on your website.
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_acctInfo( $data, $order ) {

            // Length of time that the cardholder has had their online account with you.
            $data = self::get_chAccAgeInd( $data, $order );

            // Date that the cardholder opened their online account with you.
            $data = self::get_chAccDate( $data, $order );

            // Number of purchases with this cardholder account during the previous six months.
            $data = self::get_nbPurchaseAccount( $data, $order );

            // Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous 24 hours.
            $data = self::get_txnActivityDay( $data, $order );

            // Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous year.
            $data = self::get_txnActivityYear( $data, $order );

            // Indicates if the Cardholder Name on the account is identical to the shipping Name used for this transaction.
            $data = self::get_shipNameIndicator( $data, $order );

            return $data;              
        }

        /**
         * Merchant's assessment of the level of fraud risk for the specific authentication for both the cardholder and the authentication being conducted. 
         * E.g. Are you shipping goods to the cardholder's billing address, is this a first-time order or reorder.
         *
         * deliveryTimeframe :
         * Indicates the merchandise delivery timeframe.
         *
         *     ElectronicDelivery   = Electronic Delivery
         *     SameDayShipping      = Same day shipping
         *     OvernightShipping    = Overnight shipping
         *     TwoDayOrMoreShipping = Two-day or more shipping
         *
         * shipIndicator :
         * Indicates shipping method chosen for the transaction. 
         * You must choose the Shipping Indicator code that most accurately describes the cardholder's specific transaction, not their general business. 
         * If one or more items are included in the sale, use the Shipping Indicator code for the physical goods, 
         * or if all digital goods, use the Shipping Indicator code that describes the most expensive item.
         * 
         *     CardholderBillingAddress             = Ship to cardholder's billing address
         *     OtherVerifiedAddress                 = Ship to another verified address on file with merchant
         *     DifferentToCardholderBillingAddress  = Ship to address that is different than the cardholder's billing address
         *     LocalPickUp                          = 'Ship to Store / Pick-up at local store (Store address shall be populated in shipping address fields)
         *     DigitalGoods                         = Digital goods (includes online services, electronic gift cards and redemption codes)
         *     NonShippedTickets                    = Travel and Event tickets, not shipped
         *     Other                                = Other (for example, Gaming, digital services not shipped, e-media subscriptions, etc.)
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_merchantRiskIndicator( $data, $order ) {

            $deliveryEmailAddress   = NULL;
            $deliveryTimeframe      = NULL;
            $shipIndicator          = NULL;

            if ( $order->has_shipping_address() ) {

                $deliveryTimeframe  = "TwoDayOrMoreShipping";

                $billing_address = array(
                        $order->get_billing_address_1(),
                        $order->get_billing_address_2(),
                        $order->get_billing_city(),
                        $order->get_billing_state(),
                        $order->get_billing_postcode(),
                        $order->get_billing_country(),
                    );

                $shipping_address = array(
                        $order->get_shipping_address_1(),
                        $order->get_shipping_address_2(),
                        $order->get_shipping_city(),
                        $order->get_shipping_state(),
                        $order->get_shipping_postcode(),
                        $order->get_shipping_country(),
                    );

                if( MD5( json_encode($billing_address) ) === MD5( json_encode($shipping_address) ) ) {
                    $shipIndicator = "CardholderBillingAddress";
                } else {
                    $shipIndicator = "DifferentToCardholderBillingAddress";
                }

            } else {

                if( $order->has_downloadable_item() ) {
                    $deliveryEmailAddress   = $order->get_billing_email();
                    $deliveryTimeframe      = "ElectronicDelivery";
                    $shipIndicator          = "DigitalGoods";
                }

            }
            
            $deliveryEmailAddress   = apply_filters( 'woocommerce_opayo_get_merchantRiskIndicator_deliveryEmailAddress', $deliveryEmailAddress, $data, $order );
            $deliveryTimeframe      = apply_filters( 'woocommerce_opayo_get_merchantRiskIndicator_deliveryTimeframe', $deliveryTimeframe, $data, $order );
            $shipIndicator          = apply_filters( 'woocommerce_opayo_get_merchantRiskIndicator_shipIndicator', $shipIndicator, $data, $order );

            if( !is_null( $deliveryEmailAddress ) ) {
                $data["merchantRiskIndicator"]["deliveryEmailAddress"] = $deliveryEmailAddress;
            }

            if( !is_null( $deliveryTimeframe ) ) {
                $data["merchantRiskIndicator"]["deliveryTimeframe"] = $deliveryTimeframe;
            }

            if( !is_null( $shipIndicator ) ) {
                $data["merchantRiskIndicator"]["shipIndicator"]     = $shipIndicator;
            }

            return $data;              
        }

        /**
         * Information about how you authenticated the cardholder before or during the transaction. 
         * E.g. Did your customer log into their online account on your website, using two-factor authentication, or did they log in as a guest.
         *
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_threeDSRequestorAuthenticationInfo( $data, $order ) {

            if( 0 === $order->get_user_id() ) {
                $threeDSReqAuthMethod = "NoThreeDSRequestorAuthentication";
            } else {
                $threeDSReqAuthMethod = "LoginWithThreeDSRequestorCredentials";
            }

            $threeDSReqAuthMethod = apply_filters( 'woocommerce_opayo_get_threeDSRequestorAuthenticationInfo', $threeDSReqAuthMethod, $data, $order );

            $data["threeDSRequestorAuthenticationInfo"] = array(
                                                                "threeDSReqAuthMethod" => $threeDSReqAuthMethod,
                                                            );

            return $data;              
        }

        /**
         * Length of time that the cardholder has had their online account with you.
         * 
         * GuestCheckout            = No account (guest check-out)
         * CreatedDuringTransaction = Created during this transaction
         * LessThanThirtyDays       = Less than 30 days
         * ThirtyToSixtyDays        = 30-60 days
         * MoreThanSixtyDays        = More than 60 days
         *
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_chAccAgeInd( $data, $order ) {

            if( 0 !== $order->get_user_id() ) {

                // Get User Object
                $user_object = get_userdata( $order->get_user_id() );

                // Get user registration date and format it.
                $registered_date  = $user_object->user_registered;
                $datetime         = new DateTime( $registered_date );
                $registered_date  = $datetime->format('Ymd');
                $days_registered  = intval( ( strtotime( 'now' ) - strtotime( $registered_date ) ) / 86400 );

                $orders_today_array = get_posts(
                                            array(
                                                'numberposts' => -1,
                                                'meta_key'    => '_customer_user',
                                                'meta_value'  => $order->get_user_id(),
                                                'post_type'   => wc_get_order_types(),
                                                'post_status' => array( 'wc-completed', 'wc-processing' ),
                                                'date_query'  => array(
                                                    'after' => '1 day ago'
                                                )
                                            )
                                        );
                $orders_today = count( $orders_today_array );

                if ( $days_registered < 1 && 0 === $orders_today ) {
                    $chAccAgeInd = "CreatedDuringTransaction";
                } elseif ( $days_registered < 30 ) {
                    $chAccAgeInd = "LessThanThirtyDays";
                } elseif ( $days_registered >= 30 && $days_registered <= 60 ) {
                    $chAccAgeInd = "ThirtyToSixtyDays";
                } else {
                    $chAccAgeInd = "MoreThanSixtyDays";
                }
                
            } else {
                $chAccAgeInd = "GuestCheckout";
            }

            $data["acctInfo"]["chAccAgeInd"] = apply_filters( 'woocommerce_opayo_get_chAccAgeInd', $chAccAgeInd, $data, $order );

            return $data;              
        }

        /**
         * Date that the cardholder opened their online account with you.
         * If no date is stored in user meta then we use date of first order and then add that to user meta
         * Format yearmonthday eg 20210522
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_chAccDate( $data, $order ) {

            if( 0 !== $order->get_user_id() ) {
                // Get User Object
                $user_object = get_userdata( $order->get_user_id() );
                
                // Get user registration date and format it.
                $registered_date  = $user_object->user_registered;
                $datetime         = new DateTime( $registered_date );
                $registered_date  = $datetime->format('Ymd');

                $data["acctInfo"]["chAccDate"] = $registered_date;
            }

            return $data;              
        }

        /**
         * Number of purchases with this cardholder account during the previous six months
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_nbPurchaseAccount( $data, $order ) {

            if( 0 !== $order->get_user_id() ) {
                $orders_count_array = get_posts(
                                            array(
                                                'numberposts' => -1,
                                                'meta_key'    => '_customer_user',
                                                'meta_value'  => $order->get_user_id(),
                                                'post_type'   => wc_get_order_types(),
                                                'post_status' => array( 'wc-completed', 'wc-processing' ),
                                                'date_query'  => array(
                                                    'after' => '6 month ago'
                                                )
                                            )
                                        );
                if( !is_null( $orders_count_array ) ) {
                    $data["acctInfo"]["nbPurchaseAccount"] = count( $orders_count_array );
                }
            }

            return $data;              
        }

        /**
         * Indicates if the Cardholder Name on the account is identical to the shipping Name used for this transaction.
         *
         * FullMatch    = Account Name identical to shipping Name
         * NoMatch      = Account Name different than shipping Name
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_shipNameIndicator( $data, $order ) {

            if ( $order->has_shipping_address() ) {

                if( md5( $order->get_billing_first_name().$order->get_billing_last_name() ) === md5( $order->get_shipping_first_name().$order->get_shipping_last_name() ) ) {
                    $shipNameIndicator = "FullMatch";
                } else {
                    $shipNameIndicator = "NoMatch";
                }

                $data["acctInfo"]["shipNameIndicator"] = apply_filters( 'woocommerce_opayo_get_shipNameIndicator', $shipNameIndicator, $data, $order );

            }

            return $data;

        }

        /**
         * Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous 24 hours.
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_txnActivityDay( $data, $order ) {

            if( 0 !== $order->get_user_id() ) {
                $orders = get_posts(
                                    array(
                                        'numberposts' => -1,
                                        'meta_key'    => '_customer_user',
                                        'meta_value'  => $order->get_user_id(),
                                        'post_type'   => wc_get_order_types(),
                                        'post_status' => array_keys( wc_get_order_statuses() ),
                                        'date_query'  => array(
                                            'after' => '1 day ago'
                                        )
                                    )
                                );
                if( !is_null( $orders ) ) {
                    $data["acctInfo"]["txnActivityDay"] = apply_filters( 'woocommerce_opayo_get_txnActivityDay', count($orders), $data, $order );
                }
            }
            return $data;

        }

        /**
         * Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous year.
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function get_txnActivityYear( $data, $order ) {

            if( 0 !== $order->get_user_id() ) {
                $orders = get_posts(
                                    array(
                                        'numberposts' => -1,
                                        'meta_key'    => '_customer_user',
                                        'meta_value'  => $order->get_user_id(),
                                        'post_type'   => wc_get_order_types(),
                                        'post_status' => array_keys( wc_get_order_statuses() ),
                                        'date_query'  => array(
                                            'after' => '1 year ago'
                                        )
                                    )
                                );

                if( !is_null( $orders ) ) {
                    // Max 999
                    $count = count($orders);
                    if( count($orders) >= 999 ) {
                        $count = 999;
                    }
                    $data["acctInfo"]["txnActivityYear"] = apply_filters( 'woocommerce_opayo_get_txnActivityYear', $count, $data, $order );
                }
            }

            return $data;

        }

        /**
         * { function_description }
         *
         * @param      <type>  $user      The user
         * @param      <type>  $new_pass  The new pass
         */
        public static function update_user_password_reset_date( $user, $new_pass ) {

            $datetime     = new DateTime( now() );
            $update_date  = $datetime->format('Y-m-d');

            // Update the user meta with the new password date
            update_user_meta( $user->ID, '_user_updated_password_date', $update_date );
            
        }
    }