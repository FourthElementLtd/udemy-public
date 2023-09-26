<?php
	/**
	 * Opayo_Pi_Remote_Post
	 */
	class Opayo_Pi_Remote_Post extends WC_Gateway_Opayo_Pi {

		protected $data;
		protected $url;
		protected $authorization;
		protected $auth_method;

		public function __construct( $data, $url, $authorization = NULL, $auth_method = NULL ) {

			parent::__construct();

			$this->data 			= $data;
			$this->url 				= $url;
			$this->authorization 	= $authorization;
			$this->auth_method 		= $auth_method;

			$this->settings 		= get_option( 'woocommerce_opayopi_settings' );

			if ( is_null( $this->authorization ) ) {
                $this->authorization = base64_encode( $this->Integration_Key . ':' . $this->Integration_Password );
            }

            if( is_array( $this->data ) ) {
                $this->data = json_encode( $this->data );
            }

		}

        function post() {

            $headers = array(
                "Cache-Control" => "no-cache",
                "Content-Type"  => "application/json"
            );

            if( !is_null( $this->auth_method ) ) {
            	$headers["Authorization"] = $this->auth_method . " " . $this->authorization;
			}

            $post = array(
                        'method'        => 'POST',
                        'timeout'       => 150,
                        'redirection'   => 5,
                        'httpversion'   => '1.0',
                        'blocking'      => true,
                        'headers'       => array(
                                                "Authorization" => $this->auth_method . " " . $this->authorization,
                                                "Cache-Control" => "no-cache",
                                                "Content-Type"  => "application/json"
                                            ),
                        'body'          => $this->data,
                        'cookies'       => array()
                    );

            $result = wp_remote_post( $this->url, $post );

            if( is_wp_error( $result ) ) {
                // Log errors
                $this->pi_debug( $this->data, $this->id, __('Opayo Pi Request ', 'woocommerce-gateway-sagepay-form'), FALSE );
                $this->pi_debug( $result->get_error_message(), $this->id, __('Remote Post Error : ', 'woocommerce-gateway-sagepay-form'), FALSE );
                return NULL;
            } else {
                if ( $this->debug == true || $this->status == 'test' ) {
                    $this->pi_debug( json_decode( $this->data, TRUE ), $this->id, __('Opayo Pi Request ', 'woocommerce-gateway-sagepay-form'), FALSE );
                    $this->pi_debug( json_decode( $result['body'], TRUE ), $this->id, __('Opayo Pi Return ', 'woocommerce-gateway-sagepay-form'), FALSE );
                }
                return json_decode( $result['body'], TRUE );
            }
        }

	} // End class
