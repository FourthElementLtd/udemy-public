<?php
			$this->form_fields = array(

				'enabled'           		=> array(
											    'title'         => __( 'Enable/Disable', 'woocommerce-gateway-sagepay-form' ),
											    'label'         => __( 'Enable Opayo Pi for WooCommerce', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'checkbox',
											    'description'   => '',
											    'default'       => 'no'
											),

				'title'             		=> array(
											    'title'         => __( 'Title', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'text',
											    'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => __( 'Credit Card via Opayo', 'woocommerce-gateway-sagepay-form' )
											),

				'description'      			=> array(
											    'title'         => __( 'Description', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'textarea',
											    'description'   => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'Pay via Credit / Debit Card with Opayo secure card processing.'
											),
				'order_button_text'			=> array(
												'title' 		=> __( 'Checkout Pay Button Text', 'woocommerce-gateway-sagepay-form' ),
												'type' 			=> 'text',
												'description' 	=> __( 'This controls the pay button text shown during checkout.', 'woocommerce-gateway-sagepay-form' ),
												'default' 		=> $this->default_order_button_text,
												'desc_tip'    	=> true,
											),

				'vendor'   					=> array(
											    'title'         => __( 'Vendor Name', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'text',
											    'description'   => __( 'Used to authenticate your site. This should contain the Vendor Name supplied by Opayo/Sage Pay when your account was created.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'sandbox'
											),

				'Integration_Key'      		=> array(
											    'title'         => __( 'Live Integration Key', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'password',
											    'description'   => __( 'Created in MySagePay.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => ''
											),

				'Integration_Password'  	=> array(
											    'title'         => __( 'Live Integration Password', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'password',
											    'description'   => __( 'Created in MySagePay.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => ''
											),

				'Test_Integration_Key'  	=> array(
											    'title'         => __( 'Test Integration Key', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'password',
											    'description'   => __( 'Created in MySagePay', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'hJYxsw7HLbj40cB8udES8CDRFLhuJ8G54O6rDpUXvE6hYDrria'
											),
				'Test_Integration_Password' => array(
											    'title'         => __( 'Test Integration Password', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'password',
											    'description'   => __( 'Created in MySagePay', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'o2iHSrFybYMZpmWOQMuhsXP52V4fBtpuSDshrKDSWsBY1OiN6hwd9Kb12z4j5Us5u'
											),
				'status'            		=> array(
											    'title'         => __( 'Live/Testing', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'select',
											    'options'       => array('live'=>'Live','testing'=>'Testing'),
											    'description'   => __( 'Set Pi Live/Testing Status.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'testing'
											),
				'txtype'            		=> array(
											    'title'         => __( 'Transaction Type', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'select',
											    'options'       => array('Payment'=>'Take Payment Immediately (Default)','Deferred'=>'Deferred'),
											    'description'   => __( 'Normally this should be set to "Take Payment Immediately"', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'Payment'
											),
				'debug'     				=> array(
											    'title'         => __( 'Debug Mode', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'checkbox',
											    'options'       => array('no'=>'No','yes'=>'Yes'),
											    'label'     	=> __( 'Enable Debug Mode', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'no'
											),
				'cardtypes'					=> array(
												'title' 		=> __( 'Accepted Cards', 'woocommerce-gateway-sagepay-form' ), 
												'type' 			=> 'multiselect',
												'class'			=> 'chosen_select',
												'css'         	=> 'width: 350px;', 
																	'description' 	=> __( 'Select which card types to accept.', 'woocommerce-gateway-sagepay-form' ), 
												'default' 		=> '',
												'options' 		=> $this->cardtypes,
											),
				'tokens'     				=> array(
											    'title'         => __( 'Enable Tokens', 'woocommerce-gateway-sagepay-form' ),
											    'type' 			=> 'select',
												'options' 		=> array('yes'=>'Yes','no'=>'No'),
											    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
											    'description' 	=> __( 'Enable Tokens, used for saving cards at Opayo - makes checking out faster and useful for Subscriptions and Pre-Orders etc. <strong>IMPORTANT: To use this option please contact Opayo to confirm that tokens are enabled on your account.</strong>', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => $this->default_tokens
											),
				'checkout_form'            	=> array(
											    'title'         => __( 'Checkout Form', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'select',
											    'options'       => array('woocommerce'=>'Use the WooCommerce checkout form','dropin'=>'Use the Opayo drop in checkout form'),
											    'description'   => __( 'Defaults to WooCommerce checkout form. Drop in form can reduce PCI requirements', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => 'woocommerce'
											),
				'advanced'          		=> array(
											    'title'         => __( 'Advanced Settings', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'title',
											    'description'	=> '<div style="display:block; border-bottom:1px dotted #000; width:100%;"></div>'
											),
				'vendortxcodeprefix'		=> array(
											    'title'         => __( 'VendorTXCode Prefix', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'text',
											    'description'   => __( 'Add a custom prefix to the VendorTXCode. Only use letters, numbers and _ (underscores) any other characters will be stripped from the field.', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => $this->default_vendortxcodeprefix
											),
				'notification'				=> array(
											    'title'         => __( 'Notification Email Address', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'text',
											    'description'   => __( 'Add an email address that will be notified in the event of a failure', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => get_bloginfo( 'admin_email' )
											),
				'nullipaddress'				=> array(
											    'title'         => __( 'Default IP Address', 'woocommerce-gateway-sagepay-form' ),
											    'type'          => 'text',
											    'description'   => __( 'If an IP Address can not be determined for the customer, use this IP Address as a fallback', 'woocommerce-gateway-sagepay-form' ),
											    'default'       => $this->default_nullipaddress
											),
				'pimagicvalue' 				=> array(
												'title' 		=> __( 'Card holder "Magic value" for testing 3D Secure', 'woocommerce-gateway-sagepay-form' ),
												'type'			=> 'select', 
												'description' 	=> __( 'Use this to test 3D Secure 2.0. This option is only used during testing. See docs for more information.', 'woocommerce-gateway-sagepay-form' ), 
												'default' 		=> 'NO',
												'options' 		=> array(
														'No Magic Value'		=> 'NO',
														'SUCCESSFUL'			=> 'SUCCESSFUL',
														'NOTAUTH'				=> 'NOTAUTH',
														'CHALLENGE'				=> 'CHALLENGE',
														'PROOFATTEMPT'			=> 'PROOFATTEMPT',
														'NOTENROLLED'			=> 'NOTENROLLED',
														'TECHNICALDIFFICULTIES' => 'TECHNICALDIFFICULTIES',
														'STATUS201DS'			=> 'STATUS201DS',
														'ERROR'					=> 'ERROR',
													),
											),
			);