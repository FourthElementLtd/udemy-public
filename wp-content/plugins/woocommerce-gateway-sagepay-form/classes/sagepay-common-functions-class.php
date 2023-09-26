<?php

    /**
     * WC_Sagepay_Common_Functions class.
     *
     * Functions Common to all SagePay Gateways.
     */
    class WC_Sagepay_Common_Functions {

        private static $default_basket = array(
                                            'product'   =>'product',
                                            'shipping'  =>'shipping',
                                            'discount'  =>'discount'
                                            );
        private static $payment_method = array('sagepayform','sagepaydirect','sagepayserver','sagepaypi');

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            // Add security check column to orders page in admin
            add_action( 'admin_init' , array( $this, 'sage_manage_edit_shop_order_columns' ), 10, 2 );

            // Add security check details
            add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'security_check_admin_init') , 2 );

            // Add authorised order status to wc_order_is_editable
            add_filter( 'wc_order_is_editable', array( $this, 'autorised_is_editable' ), 10, 2 );

            // Error messages
            add_action( 'woocommerce_before_cart', array( $this, 'sagepay_woocommerce_before_cart' ) );

            // 3D Secure template changes
            add_filter( 'wc_get_template', array( $this, 'get_order_receipt_template' ), 10, 5 );

            // Delete tokens from Sage
            add_action( 'woocommerce_payment_token_deleted', array( $this, 'delete_token' ), 10, 2 );

        }

        /**
         * [get_order_receipt_template description]
         * @param  [type] $template      [description]
         * @param  [type] $template_name [description]
         * @param  [type] $args          [description]
         * @param  [type] $template_path [description]
         * @param  [type] $default_path  [description]
         * @return [type]                [description]
         */
        function get_order_receipt_template( $template, $template_name, $args, $template_path, $default_path ) {
            $path_name          = SAGEPLUGINPATH;
            $threeds_template   = 'assets/templates/order-receipt.php';
            
            if( $template_name === 'checkout/order-receipt.php' && isset( $args['order'] ) && in_array( $args['order']->get_payment_method(), array('sagepaydirect', 'opayopi' ) ) && is_checkout() ) {
                $template = $path_name . $threeds_template;
            }
            
            return $template;
        }

        /**
         * [autorised_is_editable description]
         * @param  [type] $status [description]
         * @param  [type] $order  [description]
         * @return [type]         [description]
         */
        function autorised_is_editable( $status, $order ) {

            if( $order->get_status() == 'authorised' ) {
                return true;
            } else {
                return $status;
            }

        }

        /**
         * Returns the current WC version.
         */
        public static function wc_version() {
            return get_option( 'woocommerce_version' );
        }

        /**
         * Replace unwanted characters
         */
        public static function unwanted_array() {
            return array( 
                '–'=>'-','Š'=>'S','š'=>'s','Ž'=>'Z','ž'=>'z', 
                'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A', 
                'Å'=>'A','Æ'=>'A','Ç'=>'C','È'=>'E','É'=>'E', 
                'Ê'=>'E','Ë'=>'E','Ì'=>'I','Í'=>'I','Î'=>'I', 
                'Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O', 
                'Õ'=>'O','Ö'=>'O','Ø'=>'O','Ù'=>'U','Ú'=>'U',
                'Û'=>'U','Ü'=>'U','Ý'=>'Y','Þ'=>'B','ß'=>'Ss', 
                'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
                'å'=>'a','æ'=>'a','ç'=>'c','è'=>'e','é'=>'e', 
                'ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i',
                'ï'=>'i','ð'=>'o','ñ'=>'n','ò'=>'o','ó'=>'o', 
                'ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ù'=>'u',
                'ú'=>'u','û'=>'u','ý'=>'y','ý'=>'y','þ'=>'b', 
                'ÿ'=>'y' 
            );
        }

        /**
         * Additional unwanted charaters
         * Only used for returned values, not whole output
         */
        public static function additional_unwanted_array() {
            return array( '/' => ' ' );
        }

        /**
         * Returns the link to sage
         */
        public static function link() {
            return 'https://www.opayo.co.uk/about-us';
        }

        /**
         * [sagepay_debug description]
         * @param  Array   $tolog   contents for log
         * @param  String  $id      payment gateway ID
         * @param  String  $message additional message for log
         * @param  boolean $start   is this the first log entry for this transaction
         */
        public static function sagepay_debug( $tolog, $id, $message = NULL, $start = FALSE ) {

            if( !isset( $logger ) ) {
                $logger      = new stdClass();
                $logger->log = new WC_Logger();
            }

            /**
             * If this is the start of the logging for this transaction add the header
             */
            if( $start ) {

                $logger->log->add( $id, __('', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('SagePay Log', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('' .date('d M Y, H:i:s'), 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('', 'woocommerce-gateway-sagepay-form') );

            }

            /**
             * Make sure we mask the card number
             */
            if( isset( $tolog["CardNumber"] ) && $tolog["CardNumber"] != '' ) {
                $tolog["CardNumber"] = substr( $tolog["CardNumber"], 0, 4 ) . str_repeat( "*", strlen($tolog["CardNumber"]) - 8 ) . substr( $tolog["CardNumber"], -4 );
            }
            if( isset( $tolog['DATA']["CardNumber"] ) && $tolog['DATA']["CardNumber"] != '' ) {
                $tolog['DATA']["CardNumber"] = substr( $tolog['DATA']["CardNumber"], 0, 4 ) . str_repeat( "*", strlen($tolog['DATA']["CardNumber"]) - 8 ) . substr( $tolog['DATA']["CardNumber"], -4 );
            }

            /**
             * Unset the CV2 number
             */
            if( isset( $tolog['DATA']["CV2"] ) && $tolog['DATA']["CV2"] != '' ) {
                $tolog['DATA']["CV2"] = "***";
            }
            if( isset( $tolog["CV2"] ) && $tolog['DATA']["CV2"] != '' ) {
                $tolog['DATA']["CV2"] = "***";
            }

            /**
             * Make sure we mask the card number
             */


            /**
             * Unset the CV2 number
             */
            if( isset( $tolog["CV2"] ) && $tolog["CV2"] != '' ) {
                $tolog["CV2"] = "***";
            }

            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );
            $logger->log->add( $id, $message );
            $logger->log->add( $id, print_r( $tolog, TRUE ) );
            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );

        }

        /**
         * [sagepay_debug description]
         * @param  Array   $tolog   contents for log
         * @param  String  $id      payment gateway ID
         * @param  String  $message additional message for log
         * @param  boolean $start   is this the first log entry for this transaction
         */
        public static function sagepay_full_debug( $tolog, $id, $message = NULL ) {

            if( !isset( $logger ) ) {
                $logger      = new stdClass();
                $logger->log = new WC_Logger();
            }

            /**
             * If this is the start of the logging for this transaction add the header
             */
            if( $start ) {

            }

            /**
             * Make sure we mask the card number
             */
            if( isset( $tolog["CardNumber"] ) && $tolog["CardNumber"] != '' ) {
                $tolog["CardNumber"] = substr( $tolog["CardNumber"], 0, 4 ) . str_repeat( "*", strlen($tolog["CardNumber"]) - 8 ) . substr( $tolog["CardNumber"], -4 );
            }

            if( isset( $tolog["sagepaydirect-card-number"] ) && $tolog["sagepaydirect-card-number"] != '' ) {
                $tolog["sagepaydirect-card-number"] = substr( $tolog["sagepaydirect-card-number"], 0, 4 ) . str_repeat( "*", strlen($tolog["sagepaydirect-card-number"]) - 8 ) . substr( $tolog["sagepaydirect-card-number"], -4 );
            }

            /**
             * Unset the CV2 number
             */
            if( isset( $tolog["CV2"] ) && $tolog["CV2"] != '' ) {
                $tolog["CV2"] = "***";
            }

            if( isset( $tolog["sagepaydirect-card-cvc"] ) && $tolog["sagepaydirect-card-cvc"] != '' ) {
                $tolog["sagepaydirect-card-cvc"] = "***";
            }

            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );
            $logger->log->add( $id, $message );
            $logger->log->add( $id, print_r( $tolog, TRUE ) );
            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );

        }

        /**
         * Returns the order formatted for sagepay basket.
         */
        public static function sagepay_state( $country, $state  ) {

            if ( $country == 'US' ) {
                return $state;
            } else {
                return '';
            }

        }

        /**
         * [get_basket description]
         * @param  [type] $option [Basket Option from settings]
         * @param  [type] $order  [WC Order ID]
         * @return [type]         [NULL or the basket in the required format]
         */
        public static function get_basket( $option, $order_id ) {

            if( !isset($option) ) {
                $option = 0;
            }

            switch ( $option ) {
                case 0:
                    return;
                    break;
                case 1:
                    return self::sagepay_basket( $order_id );
                    break;
                case 2:
                    return self::sagepay_basket_xml( $order_id );
                    break;
                default:
                   return self::sagepay_basket( $order_id );
            }

        }

        /**
         * Returns the order formatted for sagepay basket.
         */
        public static function sagepay_basket( $order_id ) {

            $order = new WC_Order( $order_id );

            // Get the payment method for this order
            $payment_method = $order->get_payment_method();
            $settings       = get_option( 'woocommerce_'.$payment_method.'_settings' );

            // Set the basket array for this order
            $basket_array = WC_Sagepay_Common_Functions::$default_basket;
            if( isset( $settings['basketarray'] ) && $settings['basketarray'] != '' ) {
                $basket_array   = $settings['basketarray'];
            }

            // negativediscount
            $negativediscount = '0';
            if( isset( $settings['negativediscount'] ) && $settings['negativediscount'] != '' ) {
                $negativediscount   = $settings['negativediscount'];
            }

            // Cart Contents for SagePay 'Basket'
            $sagepay_basket = array();

            // Cart Contents
            $item_loop = 0;
            if ( sizeof( $order->get_items() ) > 0 && in_array( 'product', $basket_array ) ) {

                foreach ( $order->get_items() as $item ) {

                    if ( $item['qty'] ) {

                        $_product = $item->get_product();

                        $item_loop++;

                        // Get Item Name
                        $item_name = esc_attr( $item->get_name() );

                        // Add the SKU is there is one
                        if ( $_product->get_sku() ) {
                            $item_name = '[' . preg_replace( "/[^a-zA-Z0-9-.]+/", "", $_product->get_sku() ) . ']' .  $item_name;
                        }
                        
                        // Add the Product Meta
                        $meta_display = '';

                        foreach ( $item->get_formatted_meta_data() as $meta_key => $meta ) {
                            $meta_display .= ' ( ' . $meta->display_key . ':' . $meta->value . ' )';
                        }

                        if ( $meta_display ) {
                            $meta_display    = apply_filters( 'sagepay_include_meta', $meta_display, $_product, $item );
                            $item_name      .= $meta_display;
                        }

                        // Maybe strip HTML tags
                        $item_name = self::maybe_strip_tags( $item_name, TRUE );

                        $sagepay_basket[] =
                            str_replace( ':', ' ', $item_name )                                                                 // Description
                            . ':' . $item['qty']                                                                                // Quantity
                            . ':' . number_format( $order->get_item_total( $item, false ), 2, '.', '' )                         // Ex Tax
                            . ':' . number_format( $order->get_item_tax( $item ), 2, '.', '' )                                  // Tax Amount
                            . ':' . number_format( $order->get_item_total( $item, true ), 2, '.', '' )                          // Inc Tax
                            . ':' . number_format( $order->get_line_total( $item, true ), 2, '.', '' )                          // Total line cost
                        ;
                    }
                }
            }

            // Shipping Cost
            $total_shipping      = $order->get_shipping_total();
            $shipping_tax        = $order->get_shipping_tax();

            // Maybe remove 0 shipping from basket
            $remove_shipping_line_basket = apply_filters( 'woocommerce_sagepay_remove_shippingline_basket', false, $order );
            if( $remove_shipping_line_basket ) {
                $basket_array = array_diff( $basket_array, array('shipping') );
            }

            if ( in_array( 'shipping', $basket_array ) ) {
                $shipping_method = apply_filters( 'woocommerce_sagepay_basket_shippingline_title', __( 'Shipping', 'woocommerce-gateway-sagepay-form' ), $order );
                $sagepay_basket[] = 
                    $shipping_method                                                                                            // Description
                    . ':' . 1                                                                                                   // Quantity
                    . ':' . number_format( $total_shipping, 2, '.', '' )                                                        // Ex Tax
                    . ':' . number_format( $shipping_tax, 2, '.', '' )                                                          // Tax Amount
                    . ':' . number_format( $total_shipping + $shipping_tax, 2, '.', '' )                                        // Inc Tax
                    . ':' . number_format( $total_shipping + $shipping_tax, 2, '.', '' )                                        // Total line cost
                ;
                $item_loop++;
            }
            
            // Coupon Cost
            if( 0 != $order->get_total_discount() && in_array( 'discount', $basket_array ) ) {

                // Add negative symbol to discount line maybe
                $negativesymbol = '';
                if( $negativediscount != '0' ) {
                    $negativesymbol = '-';
                }

                $discount_title = apply_filters( 'woocommerce_sagepay_basket_discountline_title', __( 'Discount', 'woocommerce-gateway-sagepay-form' ), $order );
                $sagepay_basket[] =
                    $discount_title                                                                                             // Description
                    . ':' . 1                                                                                                   // Quantity
                    . ':' . $negativesymbol . number_format( $order->get_total_discount(), 2, '.', '' )                         // Ex Tax
                    . ':' . $negativesymbol . number_format( '0', 2, '.', '' )                                                  // Tax Amount
                    . ':' . $negativesymbol . number_format( $order->get_total_discount(), 2, '.', '' )                         // Inc Tax
                    . ':' . $negativesymbol . number_format( $order->get_total_discount(), 2, '.', '' )                         // Total line cost
                ;
                $item_loop++;
            }

            $sagepay_basket = $item_loop . ':' . implode( ':', $sagepay_basket );
            $sagepay_basket = str_replace( "\n", "", $sagepay_basket );
            $sagepay_basket = str_replace( "\r", "", $sagepay_basket );

            $sagepay_basket = strtr( $sagepay_basket, self::unwanted_array() );
            
            if( mb_strlen( $sagepay_basket ) > 7500 ) {
                $sagepay_basket = NULL;
            }

            return $sagepay_basket;

        }
        
        /**
         * Returns the order formatted for sagepay basket.
         */
        public static function sagepay_basket_xml( $order_id ) {

            $order = new WC_Order( $order_id );

            // Get the payment method for this order
            $payment_method = $order->get_payment_method();
            $settings       = get_option( 'woocommerce_'.$payment_method.'_settings' );

            // Set the basket array for this order
            $basket_array = WC_Sagepay_Common_Functions::$default_basket;
            if( isset( $settings['basketarray'] ) && $settings['basketarray'] != '' ) {
                $basket_array   = $settings['basketarray'];
            }

            // negativediscount
            $negativediscount = '0';
            if( isset( $settings['negativediscount'] ) && $settings['negativediscount'] != '' ) {
                $negativediscount   = $settings['negativediscount'];
            }

            $xml         = '';
            $basketxml   = '';
            $discountxml = '';
            $shippingxml = '';

            // Cart Contents
            $item_loop = 0;
            if ( sizeof( $order->get_items() ) > 0 && in_array( 'product', $basket_array ) ) {
                foreach ( $order->get_items() as $item ) {
                    if ( $item['qty'] ) {

                        $_product = $item->get_product();

                        $item_loop++;

                        // Get Item Name
                        $item_name = esc_attr( $item->get_name() );
                        
                        // Add the Product Meta
                        $meta_display = '';
                        foreach ( $item->get_formatted_meta_data() as $meta_key => $meta ) {
                            $meta_display .= ' ( ' . $meta->display_key . ':' . $meta->value . ' )';
                        }

                        if ( $meta_display ) {
                            $meta_display    = apply_filters( 'sagepay_include_meta', $meta_display, $_product, $item );
                            $item_name      .= $meta_display;
                        }

                        $item_name = strtr( $item_name, self::unwanted_array() );
                        $item_name = str_replace( array("\r", "\n"), '', $item_name );
                        // $item_name = strtr( html_entity_decode( mb_convert_encoding( $item_name, 'UTF-8', 'ASCII' ), ENT_QUOTES, 'UTF-8' ), self::unwanted_array() );

                        // Maybe strip HTML tags
                        $item_name = self::maybe_strip_tags( $item_name, TRUE );

                        $basketxml .= '<item>' . "\r\n";
                        $basketxml .= '<description>' . mb_substr( $item_name, 0, 100 ) . '</description>' . "\r\n";
                        if ( $_product->get_sku() && mb_strlen( $_product->get_sku() ) <= 12 ) {
                            $sku        = preg_replace( "/[^.a-zA-Z0-9-]+/", " ", $_product->get_sku() );
                            $basketxml .= '<productSku>' . strtr( $sku, self::unwanted_array() ) . '</productSku>' . "\r\n";
                        }
                        $basketxml .= '<quantity>' . $item['qty'] . '</quantity>' . "\r\n";
                        $basketxml .= '<unitNetAmount>' . number_format( $order->get_item_total( $item, false ), 2, '.', '' ) . '</unitNetAmount>' . "\r\n"; 
                        $basketxml .= '<unitTaxAmount>' . number_format( $order->get_item_tax( $item ), 2, '.', '' ) . '</unitTaxAmount>' . "\r\n"; 
                        $basketxml .= '<unitGrossAmount>' . number_format( $order->get_item_total( $item, true ), 2, '.', '' ) . '</unitGrossAmount>' . "\r\n";
                        $basketxml .= '<totalGrossAmount>' . number_format( $order->get_line_total( $item, true ), 2, '.', '' ) . '</totalGrossAmount>' . "\r\n";
                        $basketxml .= '</item>' . "\r\n";
                    }
                }
            }

            // Coupon Cost
            if( 0 != $order->get_total_discount() && in_array( 'discount', $basket_array ) ) {
                $discountxml = '<discounts>' . "\r\n";
                $discountxml .= '<discount>' . "\r\n";
                if( $negativediscount == '0' ) {
                    $discountxml .= '<fixed>' . number_format( $order->get_total_discount(), 2, '.', '' ) . '</fixed>' . "\r\n";
                } else {
                    $discountxml .= '<fixed>-' . number_format( $order->get_total_discount(), 2, '.', '' ) . '</fixed>' . "\r\n";
                }
                $discountxml .= '<description>' . self::get_coupon_description( $order->get_coupon_codes() ) . '</description>' . "\r\n";
                $discountxml .= '</discount>' . "\r\n"; 
                $discountxml .= '</discounts>' . "\r\n";
            }

            // Shipping costs
            $total_shipping = $order->get_shipping_total();
            $shipping_tax   = $order->get_shipping_tax();
            if ( $total_shipping && in_array( 'shipping', $basket_array ) ) {
                $shippingxml .= '<deliveryNetAmount>' . number_format( $total_shipping, 2, '.', '' ) . '</deliveryNetAmount>' . "\r\n";
                $shippingxml .= '<deliveryTaxAmount>' . number_format( $shipping_tax, 2, '.', '' ) . '</deliveryTaxAmount>' . "\r\n"; 
                $shippingxml .= '<deliveryGrossAmount>' . number_format( $total_shipping + $shipping_tax, 2, '.', '' ) . '</deliveryGrossAmount> ' . "\r\n";
            } 

            // Bulid the XML
            $xml  = '<basket>' . "\r\n";
            $xml .= $basketxml;
            $xml .= $shippingxml;
            $xml .= $discountxml;
            $xml .= '</basket>';

            // $xml = strtr( $xml, self::unwanted_array() );
            
            if( mb_strlen( $xml ) > 20000 ) {
                $xml = NULL;
            }

            return $xml;

        }

        /**
         * Coupon Description
         */
        private static function get_coupon_description( $used_coupons ) {
            return implode(", ",$used_coupons);
        }

        /**
         * Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
         */
        public static function get_icon( $cardtypes, $sagelink, $sagelogo, $id) {

            $icon = '';

            if ( ! empty( $cardtypes ) ) {

                if ( get_option('woocommerce_force_ssl_checkout')=='no' ) {

                    // display icons for the selected card types
                    foreach ( $cardtypes as $card_type ) {

                        // Clean up $card_type
                        // $image_name = str_replace('debit','', strtolower($card_type) );
                        // $image_name = trim( $image_name );
                        $image_name = str_replace(' ','-',$card_type);

                        $icon .= '<img src="' . esc_url( SAGEPLUGINURL . 'assets/images/' . strtolower( $image_name ) . '.svg' ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />';
                    }

                } else {

                    // display icons for the selected card types
                    foreach ( $cardtypes as $card_type ) {

                        // Clean up $card_type
                        // $image_name = str_replace('debit','', strtolower($card_type) );
                        // $image_name = trim( $image_name );
                        $image_name = str_replace(' ','-',$card_type);

                        $icon .= '<img src="' . esc_url( WC_HTTPS::force_https_url( SAGEPLUGINURL ) . 'assets/images/' . strtolower( $image_name ) . '.svg' ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />';
                    }

                }

            } else {

                if ( get_option('woocommerce_force_ssl_checkout')=='no' ) {
                    // use icon provided by filter
                    $icon = '<img src="' . esc_url( SAGEPLUGINURL . 'assets/images/cards.png' ) . '" alt="' . __( 'Credit card logos', 'woocommerce-gateway-sagepay-form' ) . '" />';        
                } else {
                    // use icon provided by filter
                    $icon = '<img src="' . esc_url( WC_HTTPS::force_https_url( SAGEPLUGINURL . 'assets/images/cards.png' ) ) . '" alt="' . __( 'Credit card logos', 'woocommerce-gateway-sagepay-form' ) . '" />';     
                }

            }
            
            /**
             * Add SagePay link
             */
            if ( $sagelink == '1' && $sagelogo != '1' ) {
                $what_is_sagepay = sprintf( '<a href="%1$s" class="about_sagepayform" style="float: right; line-height: 12px; font-size: 0.83em;" onclick="javascript:window.open(\'%1$s\',\'What is SagePay\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is SagePay?', 'woocommerce-gateway-sagepay-form' ) . '">' . esc_attr__( 'What is Opayo?', 'woocommerce-gateway-sagepay-form' ) . '</a>', esc_url( self::link() ) );
            } else {
                $what_is_sagepay = '';
            }

            /**
             * Add SagePay logo
             */
            if ( $sagelogo == '1' ) {

                if( $sagelink == '1' ) {

                    if ( get_option('woocommerce_force_ssl_checkout')=='no' ) {
                        // use icon provided by filter
                        $icon = $icon . sprintf( '<a href="%1$s" onclick="javascript:window.open(\'%1$s\',\'What is Opayo\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is Opayo?', 'woocommerce-gateway-sagepay-form' ) . '">' . '<img src="' . esc_url( SAGEPLUGINURL . 'assets/images/sagepaylogo.png' ) . '" alt="Payments By Opayo" />' . '</a>', esc_url( self::link() ) );        
                    } else {
                        // use icon provided by filter
                        $icon = $icon . sprintf( '<a href="%1$s" onclick="javascript:window.open(\'%1$s\',\'What is Opayo\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is Opayo?', 'woocommerce-gateway-sagepay-form' ) . '">' . '<img src="' . esc_url( WC_HTTPS::force_https_url( SAGEPLUGINURL . 'assets/images/sagepaylogo.png' ) ) . '" alt="Payments By Opayo" />' . '</a>', esc_url( self::link() ) );       
                    }

                } else {

                    if ( get_option('woocommerce_force_ssl_checkout')=='no' ) {
                        // use icon provided by filter
                        $icon = $icon . '<img src="' . esc_url( SAGEPLUGINURL . 'assets/images/sagepaylogo.png' ) . '" alt="Payments By Opayo" style="float: right;"/>';           
                    } else {
                        // use icon provided by filter
                        $icon = $icon . '<img src="' . esc_url( WC_HTTPS::force_https_url( SAGEPLUGINURL . 'assets/images/sagepaylogo.png' ) ) . '" alt="Payments By Opayo" style="float: right;"/>';      
                    }

                }

            }

            $icon_output = "<div id='" . $id . "-card-icons'>" . $icon . $what_is_sagepay . "</div>";

            return apply_filters( 'woocommerce_gateway_icon', $icon_output, $id );
        }

        /**
         * Wrapper around @see WC_Order::get_order_currency() for versions of WooCommerce prior to 2.1.
         */
        public static function get_order_currency( $order ) {

            if ( method_exists( $order, 'get_currency' ) ) {
                $order_currency = $order->get_currency();
            } else {
                $order_currency = get_woocommerce_currency();
            }

            return $order_currency;
        }

        /**
         * Build the $vendortxcode for Sage.
         * Max length is 40 characters
         * MUST be unique for each transaction
         * Uses $order->order_key, $order->id, $order->get_order_number() and date('is', time() ) (current minutes and seconds)
         * 
         * Send $order->order_key to match order when returning from Sage
         * Send $order->id to retrive order when returning from Sage
         * Send $order->get_order_number() for easier tracking in MySagePay 
         *
         * @return $vendortxcode
         */
        public static function build_vendortxcode( $order, $id, $prefix = 'wc_' ) {

            // WooCommerce 3.0 compatibility 
            $order_id   = $order->get_id();
            $order_key  = $order->get_order_key();
            $order_key  = str_replace( 'wc_', $prefix, $order_key );

            $vendortxcode =  $order_key . '-' . str_replace( '#' , '' , $order_id ) . '-';

            // if $order_id and $order->get_order_number() don't match then add $order->get_order_number()
            if( str_replace( '#' , '' , $order_id ) != $order->get_order_number() ) {
                $vendortxcode .= str_replace( '#' , '' , $order->get_order_number() ) . '-';
            }

            // Add minutes and seconds to the end of the $vendortxcode to make it unique.
            $txcode_salt = !is_null( NONCE_SALT ) && NONCE_SALT != '' ? NONCE_SALT : '1]O3Nu5VDC7nm|eBkca*c>2Ki>,z8-}w;oD=Ttuw`k7A*:QR+';
            $vendortxcode .= MD5( date('is', time() ) . $txcode_salt );

            // Clean up the $vendortxcode for SAGE Line 50
            $vendortxcode = str_replace( 'order_', '', $vendortxcode );

            // Let sites filter $vendortxcode
            $vendortxcode = apply_filters( 'woocommerce_' . $id . '_vendortxcode', $vendortxcode, $order );

            /**
             * Replace everything that's not allowed!
             * A-Z,a-z,0-9,_,- 
             * see http://www.sagepay.co.uk/file/25041/download-document/FORM_Integration_and_Protocol_Guidelines_270815.pdf
             */
            $vendortxcode = preg_replace( '/[^0-9a-zA-Z_\-]/', "", $vendortxcode );

            // Make sure it's not over 40 characters
            if ( strlen($vendortxcode) > 40 ) {
                $vendortxcode = substr( $vendortxcode, 0, 40 );
            }

            return $vendortxcode;

        }

        /**
         * Add Security Check column to orders page in admin
         */
        function sage_manage_edit_shop_order_columns( $columns ) {
            add_filter( 'manage_edit-shop_order_columns', 'security_check_column_admin_init' );
        }

        /**
         * Add sage security responses to order rows
         */
        function security_check_admin_init( $column ) {
            global $post, $woocommerce, $the_order;

            if ( $column === 'sage_security' ) {

                $AddressResult  = NULL;
                $PostCodeResult = NULL;
                $CV2Result      = NULL;
                $SecureStatus   = NULL;

                if( '' !== get_post_meta( $post->ID, '_sageresult', TRUE ) ) {

                    $result         = get_post_meta( $post->ID, '_sageresult', TRUE );
                    $AddressResult  = isset( $result['AddressResult'] ) ? $result['AddressResult'] : NULL;
                    $PostCodeResult = isset( $result['PostCodeResult'] ) ? $result['PostCodeResult'] : NULL;
                    $CV2Result      = isset( $result['CV2Result'] ) ? $result['CV2Result'] : NULL;
                    $SecureStatus   = isset( $result['3DSecureStatus'] ) ? $result['3DSecureStatus'] : NULL;

                }
                
                if( is_null( $AddressResult ) && '' !== get_post_meta( $post->ID, '_AddressResult', TRUE ) ) {
                    $AddressResult = strtoupper( get_post_meta( $post->ID, '_AddressResult', TRUE ) );
                }

                if( is_null( $PostCodeResult ) && '' !== get_post_meta( $post->ID, '_PostCodeResult', TRUE ) ) {
                    $PostCodeResult = strtoupper( get_post_meta( $post->ID, '_PostCodeResult', TRUE ) );
                }

                if( is_null( $CV2Result ) && '' !== get_post_meta( $post->ID, '_CV2Result', TRUE ) ) {
                    $CV2Result = strtoupper( get_post_meta( $post->ID, '_CV2Result', TRUE ) );
                }

                if( is_null( $SecureStatus ) && '' !== get_post_meta( $post->ID, '_3DSecureStatus', TRUE ) ) {
                    $SecureStatus = strtoupper( get_post_meta( $post->ID, '_3DSecureStatus', TRUE ) );
                }

                if( !is_null( $AddressResult ) ) {

                    switch ( $AddressResult ) {
                        case 'MATCHED':
                            $addressclass = 'sagepay-ok';
                            break;
                        case 'NOTMATCHED':
                            $addressclass = 'sagepay-fail';
                            break;
                        case 'NOTPROVIDED':
                        case 'NOTCHECKED':
                            $addressclass = 'sagepay-check';
                            break;
                        default :
                            $addressclass = 'sagepay-ok';
                            break;
                    }

                    printf( '<span class="%s tips" data-tip="%s">%s</span>', $addressclass,__( 'Address check ', 'woocommerce-gateway-sagepay-form' ) . strtolower( $AddressResult ), 'A' );

                }

                if( !is_null( $PostCodeResult ) ) {

                    switch ( $PostCodeResult ) {
                        case 'MATCHED':
                            $postcodeclass = 'sagepay-ok';
                            break;
                        case 'NOTMATCHED':
                            $postcodeclass = 'sagepay-fail';
                            break;
                        case 'NOTPROVIDED':
                        case 'NOTCHECKED':
                            $postcodeclass = 'sagepay-check';
                            break;
                        default :
                            $postcodeclass = 'sagepay-ok';
                            break;
                    }

                    printf( '<span class="%s tips" data-tip="%s">%s</span>', $postcodeclass,__( 'Postcode check ', 'woocommerce-gateway-sagepay-form' ) . strtolower( $PostCodeResult ), 'P' );

                }

                if( !is_null( $CV2Result ) ) {

                    switch ( $CV2Result ) {
                        case 'MATCHED':
                            $cv2class = 'sagepay-ok';
                            break;
                        case 'NOTMATCHED':
                            $cv2class = 'sagepay-fail';
                            break;
                        case 'NOTPROVIDED':
                        case 'NOTCHECKED':
                            $cv2class = 'sagepay-check';
                            break;
                        default :
                            $cv2class = 'sagepay-ok';
                            break;

                    }

                    printf( '<span class="%s tips" data-tip="%s">%s</span>', $cv2class,__( 'CV2 check ', 'woocommerce-gateway-sagepay-form' ) . strtolower( $CV2Result ), 'C' );

                } 

                if( !is_null( $SecureStatus ) ) {

                    switch ( $SecureStatus ) {
                        case 'OK':
                        case 'AUTHENTICATED':
                            $secureclass = 'sagepay-ok';
                            break;
                        case 'NOTAUTHED':
                            $secureclass = 'sagepay-fail';
                            break;
                        case 'INCOMPLETE':
                        case 'NOTCHECKED':
                        case 'ERROR': 
                        case 'ATTEMPTONLY': 
                        case 'NOAUTH': 
                        case 'CANTAUTH': 
                        case 'MALFORMED': 
                        case 'INVALID':
                        case 'NOTAVAILABLE':
                            $secureclass = 'sagepay-check';
                            break;
                        default :
                            $secureclass = 'sagepay-ok';
                            break;
                    }

                    printf( '<span class="%s tips" data-tip="%s">%s</span>', $secureclass,__( '3D secure check ', 'woocommerce-gateway-sagepay-form' ) . strtolower( $SecureStatus ), '3' );

                }   

            }

        }

        /**
         * check_shipping_address
         */
        public static function check_shipping_address( $order, $field ) {

            $order_id = $order->get_id();

            // Do we need a shipping address?
            $show_shipping          = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
            $shipping_address_index = get_post_meta( $order_id, '_shipping_address_index', TRUE );

            if( isset( $shipping_address_index ) && $shipping_address_index != '' ) {
                $show_shipping = true;
            }

            // Keep the original $field name
            $original_field = $field;

            $field  = 'get_' . $field;
            $result = $order->$field();

            // No shipping address so use billing address
            if ( !$show_shipping ) {
                $field = str_replace( 'shipping_', 'billing_', $field );
                $return = $order->$field();                
            } 

            if ( get_option( 'woocommerce_ship_to_countries' ) === 'disabled' || $result == '' || !isset( $result ) ) {
                // Don't replace an empty shipping_address_2
                if( $original_field == 'shipping_address_2' ) {
                    $return = '';
                } else {
                    $field = str_replace( 'shipping_', 'billing_', $field );
                    $return = $order->$field();
                }

            } else {
                $return = $result;
            }

            return WC_Sagepay_Common_Functions::clean_sagepay_args( $return );

        }

        /**
         * [sagepay_woocommerce_before_cart description]
         * Add Sage message to cart, update order notes and status.
         */
        public static function sagepay_woocommerce_before_cart() {

            $order_id = WC()->session->order_awaiting_payment;
            $settings = get_option( 'woocommerce_sagepayform_settings' );

            if ( isset( $_GET["crypt"] ) && $settings['enabled'] == 'yes' ) {

                $crypt = $_GET["crypt"];

                $sagepay_return_data   = WC_Sagepay_Common_Functions::decrypt( $crypt, WC_Sagepay_Common_Functions::get_vendor_password() );

                $sagepay_return_values = WC_Sagepay_Common_Functions::getTokens( $sagepay_return_data );

                if( isset( $sagepay_return_values["StatusDetail"] ) ) {
                    wc_print_notice( $sagepay_return_values["StatusDetail"], "error" );

                    $order = new WC_Order( $order_id );
                    $order->update_status( 'cancelled', wc_clean( $sagepay_return_values["StatusDetail"] . '<br />' ) );
                }

            }
        }

        /**
         * [get_vendor_password description]
         * @return [type] [description]
         */
        public static function get_vendor_password() {

            $settings = get_option( 'woocommerce_sagepayform_settings' );

            $vendorpwd = $settings['vendorpwd'];

            if ( $settings['status'] == 'testing' && $settings['testvendorpwd'] ) {
                $vendorpwd = $settings['testvendorpwd'];
            } 
                
            return $vendorpwd;

        }

        /**
         * Protocol 3 Encryption function
         * @param  [type] $securekey [description]
         * @param  [type] $input     [description]
         * @return [type]            [description]
         *
         * This function requires php mcrypt/openssl_encrypt
         * Use openssl_encrypt if possible, mcrypt has been abandoned and is deprecated in PHP 7.1
         */
        public static function encrypt( $input, $securekey ) {

            $encrypt        = NULL;
            $cipher_method  = WC_Sagepay_Common_Functions::get_cipher_method();

            if( function_exists('openssl_encrypt') ) {
                $encrypt = "@" . bin2hex( openssl_encrypt( $input, $cipher_method, $securekey, OPENSSL_RAW_DATA, $securekey ) );
            } else {
                $pkinput = WC_Sagepay_Common_Functions::addPKCS5Padding( $input );
                $encrypt = "@" . bin2hex( mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $securekey, $pkinput, MCRYPT_MODE_CBC, $securekey) );             
            }

            return $encrypt;
            
        }

        /**
         * Protocol 3 Decryption function
         * @param  [type] $securekey [description]
         * @param  [type] $input     [description]
         * @return [type]            [description]
         *
         * This function requires php mcrypt/openssl_encrypt
         * Use openssl_encrypt if possible, mcrypt has been abandoned and is deprecated in PHP 7.1
         */
        public static function decrypt( $input,$securekey ) {

            $cipher_method = WC_Sagepay_Common_Functions::get_cipher_method();

            // remove the first char which is @ to flag this is AES encrypted
            $input = substr($input,1);
       
            // HEX decoding
            $input = pack( 'H*', trim( $input ) );

            // $input = pack( 'H*', trim( sprintf( '%u', CRC32( $input ) ) ) );

            if( function_exists('openssl_encrypt') ) {
                return openssl_decrypt( $input, $cipher_method, $securekey, OPENSSL_RAW_DATA, $securekey );
            } else {
                return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $securekey, $input, MCRYPT_MODE_CBC, $securekey);
            }

        }

        /**
         * [get_cipher_method description]
         * @return [type] [description]
         */
        public static function get_cipher_method() {

            $cipher_method = NULL;

            if( in_array( 'AES-128-CBC', openssl_get_cipher_methods() ) ) {
                $cipher_method = 'AES-128-CBC';
            }

            if( in_array( 'aes-128-cbc', openssl_get_cipher_methods() ) ) {
                $cipher_method = 'aes-128-cbc';
            }

            return $cipher_method;
        }

        public static function addPKCS5Padding( $input ) {
           $blocksize = 16;
           $padding = "";

           // Pad input to an even block size boundary
           $padlength = $blocksize - (strlen($input) % $blocksize);
           for($i = 1; $i <= $padlength; $i++) {
              $padding .= chr($padlength);
           }
   
           return $input . $padding;
        }

        /**
         * getTokens function.
         *
         * @access public
         * @param mixed $query_string
         * @return void
         */
        public static function getTokens( $query_string ) {

            $output = '';

            if ( isset($query_string) && $query_string != '' ) {
                // List the possible tokens
                $tokens = array(
                    "Status",
                    "StatusDetail",
                    "VendorTxCode",
                    "VPSTxId",
                    "TxAuthNo",
                    "Amount",
                    "AVSCV2",
                    "AddressResult",
                    "PostCodeResult",
                    "CV2Result",
                    "GiftAid",
                    "3DSecureStatus",
                    "CAVV",
                    "CardType",
                    "Last4Digits",
                    "Surcharge",
                    "DeclineCode",
                    "BankAuthCode"
                );

                // Initialise arrays
                $output = array();
                $tokens_found = array();

                // Get the next token in the sequence
                for ( $i = count( $tokens ) - 1; $i >= 0; $i-- ) {
                    // Find the position in the string
                   $start = strpos( $query_string, $tokens[$i] );

                    // If token is present record its position and name
                    if ( $start !== false ) {

                        if( !isset($tokens_found[$i]) ) {
                            $tokens_found[$i] = new StdClass();
                        }

                        $tokens_found[$i]->start = $start;
                        $tokens_found[$i]->token = $tokens[$i];
                    }

                }

                // Sort in order of position
                sort( $tokens_found );

                // Go through the result array, getting the token values
                for ( $i = 0; $i < count( $tokens_found ); $i++ ) {
                    // Get the start point of the value
                    $valueStart = $tokens_found[$i]->start + strlen( $tokens_found[ $i ]->token ) + 1;

                   // Get the length of the value
                   if ( $i == ( count( $tokens_found ) - 1 ) ) {
                    $output[$tokens_found[ $i ]->token] = substr( $query_string, $valueStart );
                    } else {
                        $valueLength = $tokens_found[ $i + 1 ]->start - $tokens_found[ $i ]->start - strlen( $tokens_found[ $i ]->token) - 2;
                        $output[ $tokens_found[ $i ]->token] = substr( $query_string, $valueStart, $valueLength );
                    }

                }

            }

            // Return the output array
            return $output;

        }

        /**
         * developer_logging function.
         *
         * @access public
         * @param mixed $function
         * @return void
         */
        public static function developer_logging( $tolog, $id = 'sagepaydirect_dev', $message = NULL ) {
            $current_user = wp_get_current_user();

            if( !$current_user->user_email === 'andrew@chromeorange.co.uk' ) {
                return;
            }

            if( !isset( $logger ) ) {
                $logger      = new stdClass();
                $logger->log = new WC_Logger();
            }

            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );
            isset( $message ) ? $logger->log->add( $id, $message ) : NULL;
            $logger->log->add( $id, print_r( $tolog, TRUE ) );
            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );

        }

        /**
         * [delete_token description]
         * @param  [type] $token_id [description]
         * @param  [type] $token    [description]
         * @return [type]           [description]
         */
        public function delete_token( $token_id, $token ) {
            if ( 'sagepaydirect' === $token->get_gateway_id() ) {
                self::delete_token_from_sagepaydirect( $token );
            }

            if( 'sagepaypi' === $token->get_gateway_id() ) {
                self::delete_token_from_sagepaypi( $token );
            }
        }

        /**
         * [delete_token_from_sagepaydirect description]
         * @param  [type] $token [description]
         * @return [type]        [description]
         */
        public function delete_token_from_sagepaydirect( $token ) {

            // Get settings
            $settings = get_option( 'woocommerce_sagepaydirect_settings' );

            $data = array( 
                'VPSProtocol'   => $settings['vpsprotocol'],
                'TxType'        => 'REMOVETOKEN',
                'Vendor'        => $settings['vendor'],
                'Token'         => $token->get_token(),
            );

            if( 'live' === $settings['status'] ) {
                $url = 'https://live.sagepay.com/gateway/service/removetoken.vsp';
            } else {
                $url = 'https://test.sagepay.com/gateway/service/removetoken.vsp';
            }

            // Debugging
            if ( $settings['debug'] == true || $settings['status'] != 'live' ) {
                $to_log         = $data;
                $to_log['URL']  = $url;
                self::sagepay_debug( $to_log, 'sagepaydirect', __('Sent to SagePay : ', 'woocommerce-gateway-sagepay-form'), TRUE );
            }

            // Convert $data array to query string for Sage
            if( is_array( $data) ) {
                // Convert the $data array for Sage
                $data = http_build_query( $data, '', '&', PHP_QUERY_RFC3986 );
            }

            $res = wp_remote_post( $url, array(
                                                'method'        => 'POST',
                                                'timeout'       => 45,
                                                'redirection'   => 5,
                                                'httpversion'   => '1.0',
                                                'blocking'      => true,
                                                'headers'       => array('Content-Type'=> 'application/x-www-form-urlencoded'),
                                                'body'          => $data,
                                                'cookies'       => array()
                                            )
                                        );

            if( is_wp_error( $res ) ) {

                // Debugging
                if ( $settings['debug'] == true || $settings['status'] != 'live' ) {
                    self::sagepay_debug( $res->get_error_message(), 'sagepaydirect', __('Remote Post Error : ', 'woocommerce-gateway-sagepay-form'), FALSE );
                }

            } else {

                // Debugging
                if ( $settings['debug'] == true || $settings['status'] != 'live' ) {
                    self::sagepay_debug( $res['body'], 'sagepaydirect', __('SagePay Direct Return : ', 'woocommerce-gateway-sagepay-form'), FALSE );
                }

            }

        }

        /**
         * [get_customerxml description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         *
         * <customer> 
         *       <customerMiddleInitial>W</customerMiddleInitial> 
         *       <customerBirth>1983-01-01</customerBirth> 
         *       <customerWorkPhone>020 1234567</customerWorkPhone> 
         *       <customerMobilePhone>0799 1234567</customerMobilePhone> 
         *       <previousCust>0</previousCust> 
         *       <timeOnFile>10</timeOnFile> 
         *       <customerId>CUST123</customerId>
         * </customer>
         */
        public static function get_customerxml( $order_id ) {

                $order          = new WC_Order( $order_id );
                $customerxml    = NULL;

                $customer_id        = $order->get_customer_id();
                $previous_customer  = get_user_meta( $customer_id, 'paying_customer', TRUE );

                if( isset( $customer_id ) ) {

                    $customerxml  = '<customer>';
                    $customerxml .= '<previousCust>' . get_user_meta( $customer_id, 'paying_customer', TRUE ) . '</previousCust>'; 
                    $customerxml .= '<customerId>' . $customer_id . '</customerId>';
                    $customerxml .= '</customer>';

                }

                return $customerxml;

        }

        /**
         * [clean_sagepay_args description]
         * @param  [type] $value [description]
         * @return [type]        [description]
         */
        public static function clean_sagepay_args( $value ) {

            $value = strtr( $value, WC_Sagepay_Common_Functions::unwanted_array() );

            if ( function_exists( 'mb_convert_encoding' ) ) {
                $value = mb_convert_encoding( $value, 'UTF8' );
            }

            return $value;

        }

        /**
         * [clean_args description]
         * @param  [type] $args [description]
         * @return [type]       [description]
         */
        public static function clean_args( $args ) {

            foreach( $args as $param => $value ) {
                
                // Remove all the non-english things
                $value = strtr( $value, WC_Sagepay_Common_Functions::unwanted_array() );
                
                if ( function_exists( 'mb_convert_encoding' ) ) {
                    $value = mb_convert_encoding( $value, 'ISO-8859-1', 'UTF-8' );
                } elseif ( function_exists( 'iconv' ) ) {
                    $value = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
                }

                $args[$param] = $value;

            }

            return $args;

        }

        public static function maybe_strip_tags( $item_name, $strip ) {

            // Should be TRUE, use filter to return FALSE to turn this off.
            $strip = apply_filters( 'woocommerce_opayo_basket_maybe_strip_tags', $strip );

            if( $strip ) {
                return strip_tags( $item_name );
            }

            return $item_name;
        }

        /**
         * [get_icanhazip description]
         * @return [type] [description]
         */
        public static function get_icanhazip() {

            if( get_option('wc_icanhazip') ) {
                return get_option('wc_icanhazip');
            }

            $params = array(
                        'method'        => 'POST',
                        'timeout'       => apply_filters( 'woocommerce_opayo_post_timeout', 45 ),
                        'httpversion'   => '1.1',
                        'headers'       => array('Content-Type'=> 'application/x-www-form-urlencoded'),
                        'body'          => NULL,
                    );

            $res = wp_remote_post( 'https://icanhazip.com', $params );

            if( is_wp_error( $res ) ) {
                $reponse = __( 'IP Address can not be obtained. Contact Opayo.', 'woocommerce-gateway-sagepay-form' );
            } else {
                update_option( 'wc_icanhazip', $res['body'] );
                $reponse = sprintf( __( 'Enter this IP Address in to MySagePay : %s', 'woocommerce-gateway-sagepay-form' ), wc_clean( $res['body'] ) );
            }

            return $reponse;
        }

    }

    $WC_Sagepay_Common_Functions = new WC_Sagepay_Common_Functions();

    function security_check_column_admin_init( $columns ) {
        global $woocommerce;
                
        $columns["sage_security"] = __( 'Checks', 'woocommerce-gateway-sagepay-form' );
                
        return $columns;

    }