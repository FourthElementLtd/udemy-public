<?php

			$this->form_fields = array(
				'enabled'           => array(
				    'title'         => __( 'Enable/Disable', 'woocommerce-gateway-sagepay-form' ),
				    'label'         => __( 'Enable Opayo Direct for WooCommerce', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'description'   => '',
				    'default'       => 'no'
				),
				'title'             => array(
				    'title'         => __( 'Title', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => __( 'Credit Card via Opayo', 'woocommerce-gateway-sagepay-form' )
				),
				'description'       => array(
				    'title'         => __( 'Description', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'textarea',
				    'description'   => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'Pay via Credit / Debit Card with Opayo secure card processing.'
				),
				'order_button_text'		=> array(
					'title' 		=> __( 'Checkout Pay Button Text', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the pay button text shown during checkout.', 'woocommerce-gateway-sagepay-form' ),
					'default' 		=> __( 'Pay securely with Opayo', 'woocommerce-gateway-sagepay-form' ),
					'desc_tip'    	=> true,
				),
				'vendor'      		=> array(
				    'title'         => __( 'Opayo Vendor Name', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'Used to authenticate your site. This should contain the Vendor Name supplied by Opayo when your account was created.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => ''
				),
				'status'            => array(
				    'title'         => __( 'Status', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array('live'=>'Live','testing'=>'Testing','developer' => 'Development (DO NOT USE)' ),
				    'description'   => __( 'Set Direct Live/Testing Status.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'testing'
				),
				'txtype'            => array(
				    'title'         => __( 'Status', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array('PAYMENT'=>'Take Payment Immediately','DEFERRED'=>'Deferred Payment','AUTHENTICATE'=>'Authenticate Only'),
				    'description'   => __( 'Normally this should be set to "Take Payment Immediately"', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'PAYMENT'
				),
				'cardtypes'			=> array(
					'title' 		=> __( 'Accepted Cards', 'woocommerce-gateway-sagepay-form' ), 
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'         	=> 'width: 350px;', 
										'description' 	=> __( 'Select which card types to accept.If you choose to include PayPal then make sure your PayPal account is setup correctly. See <a href="https://docs.woocommerce.com/document/sagepay-form/#section-5" target="_blank">https://docs.woocommerce.com/document/sagepay-form/#section-5</a> for more information.', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> '',
					'options' 		=> $this->set_cardtypes(),
				),
				'vpsprotocol' 		=> array(
					'title' 		=> __( 'VPS Protocol', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'VPS Protocol setting. <strong>If you are using Protocol 4.00 you MUST turn on 3D Secure in MySagePay</strong>', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> '4.00',
					'options' 		=> array(
							'4.00'	=> 'Protocol 4.00 (Default, required for 3D Secure 2.0)'
						),
				),
				'applyavscv2' 		=> array(
					'title' 		=> __( 'Address and CV2 checks', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'Address and CV2 Settings.', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> '',
					'options' 		=> array(
							'0'		=> 'When AVS/CV2 enabled then check them. If rules apply, use rules. (default)',
							'1'		=> 'Force AVS/CV2 checks even when not enabled for the account. If rules apply, use rules',
							'2'		=> 'Force NO AVS/CV2 checks even when enabled on account.',
							'3'		=> 'Force AVS/CV2 checks even when not enabled for the account and DON’T apply any rules.'
						),
				),
				'3dsecure' 			=> array(
					'title' 		=> __( '3D Secure', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( '3D Secure Settings.', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> '',
					'options' 		=> array(
							'0'		=> 'If 3D-Secure checks are possible and rules allow, perform the checks and apply the authorisation rules. (default)',
							'1'		=> 'Force 3D-Secure checks for this transaction if possible and apply rules for authorisation.',
							'3'		=> 'Force 3D-Secure checks for this transaction if possible but ALWAYS obtain an auth code, irrespective of rule base.'
						),
				),
				'threeDSMethod' 	=> array(
					'title' 		=> __( '3D Secure Method', 'woocommerce_sagepayform' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( '3D Secure Method.', 'woocommerce_sagepayform' ), 
					'default' 		=> '0',
					'options' 		=> array(
							'0'		=> 'Use iFrames, customer stays on site.',
							'1'		=> 'Do not use iFrames, customer is redirected to 3D Secure page.'
						),
				),
				'3dsecure_tracking' => array(
					'title' 		=> __( 'Append URLs with utm_nooverride=1 code', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'Append utm_nooverride=1 to 3D Secure URLs', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> '',
					'options' 		=> array(
							'0'		=> 'Do not append (default)',
							'1' 	=> 'Append utm_nooverride=1',
						),
				),
				'secure_token' 	=> array(
					'title' 		=> __( 'Require 3D Secure for Token Payments', 'woocommerce-gateway-sagepay-form' ), 
					'label' 		=> __( 'Require 3D Secure if rules allow when paying with a token (does not affect subscription renewals or authorization payments)', 'woocommerce-gateway-sagepay-form' ), 
					'type' 			=> 'checkbox', 
					'description' 	=> __( '', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> 'no'
				),
				'tokens'     		=> array(
				    'title'         => __( 'Enable Tokens', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Enable Tokens, used for saving cards at Opayo - makes checking out faster and useful for Subscriptions and Pre-Orders etc. <strong>IMPORTANT: To use this option please contact Opayo to confirm that tokens are enabled on your account.</strong>', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_tokens
				),		
				'tokensmessage'     => array(
				    'title'         => __( 'Show customers a message about tokens', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'options'       => array('no'=>'No','yes'=>'Yes'),
				    'label'     	=> __( 'Leave empty for no message', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Optionally show a message to your customers explaining how saved cards works. An example message might be : <br />"Saving your card details allows you to checkout faster in the future. Card details are stored securely at Opayo, we do not have access, and you can delete them from your account at anytime."', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_tokens_message
				),
				'customer'     		=> array(
				    'title'         => __( 'Enable Customer XML', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Customer fields can be passed using a CustomerXML document to provide more accurate fraud screening. (Default: Yes)', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'yes'
				),
				'acctInfo'     		=> array(
				    'title'         => __( 'Enable Account Information XML', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( "The Account Information XML contains information about the Cardholder's online account on your website. (Default: Yes)", 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'yes'
				),
				'merchantRiskIndicator' => array(
				    'title'         => __( 'Enable Merchant Risk Indicator XML', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( "The Merchant Risk Indicator contains information about the cardholder's specific purchases. (Default: Yes)", 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'yes'
				),
				'giftaid'     		=> array(
				    'title'         => __( 'Enable Gift Aid option for UK customers', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( '<strong>IMPORTANT: Only of use if your vendor account is Gift Aid enabled</strong>', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'no'
				),
				'giftaidmessage'    => array(
				    'title'         => __( 'Gift Aid message for UK customers', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'text',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Explain Gift Aid to your customer, the message will show to UK customers only and will be displayed under the Gift Aid checkbox at checkout.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => __( "We're a charity and if you are a UK taxpayer we can reclaim 25p for every £1 you give us. Gift Aid is a UK government scheme where charities can reclaim money on your contribution from the HM Revenue & Customs.", 'woocommerce-gateway-sagepay-form' ),
				),
				'basketoption'     	=> array(
				    'title'         => __( 'Basket Option', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('0'=>'Do not send the basket to Opayo','1'=>'Send the basket in standard format','2'=>'Send the basket in XML format'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Optionally you can send the contents of the shopping cart to Opayo, this will show up in MySagePay and in certain emails. Use the Standard option if you need to import from MySagePay to Sage Accounting software', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => '1'
				),
				'basketarray'     	=> array(
				    'title'         => __( 'Select which fields you want to include in the basket', 'woocommerce-gateway-sagepay-form' ), 
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'         	=> 'width: 350px;', 
					'options' 		=> array('product'=>'Send product lines','shipping'=>'Send shipping details','discount'=>'Send any coupon or discount details'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Optionally you remove certain fields from the basket, this may help with any issues you are seeing with importing in to Sage Accounts', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => array('product','shipping','discount')
				),
				'negativediscount'  => array(
				    'title'         => __( 'Send Discounts as negative values', 'woocommerce-gateway-sagepay-form' ), 
					'type' 			=> 'select',
					'options' 		=> array('0'=>'No','1'=>'Yes',),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'If you are importing from MySagePay to Sage Accounts then set this to Yes.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => '0'
				),	
				'debug'     		=> array(
				    'title'         => __( 'Debug Mode', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'options'       => array('no'=>'No','yes'=>'Simple logging', 'full'=>'Log everything'),
				    'label'     	=> __( 'Enable Debug Mode', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Optionally log transaction information sent to and received from Opayo. If you are experiencing difficulties you can log everything - this will produce very large log files if left switched on.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 'no'
				),
				'notification'		=> array(
				    'title'         => __( 'Notification Email Address', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'Add an email address that will be notified in the event of a failure', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => get_bloginfo( 'admin_email' )
				),
				'advanced'          => array(
				    'title'         => __( 'Advanced Settings', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'title',
				    'description'	=> '<div style="display:block; border-bottom:1px dotted #000; width:100%;"></div>'
				),
				'template' 			=> array(
					'title' 		=> __( 'Checkout card template', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'Choose the credit card form template. Make sure to check your checkout form after changing this option.', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> 'default',
					'options' 		=> array(
							'default'		=> 'Default form. (default)',
							'alternate'		=> 'Form with seperated Expiry Month and Year fields'
						),
				),
				'sagelinebreak' 	=> array(
					'title' 		=> __( 'Line Break', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'Line Break settings, used for decrypting messages from Opayo. Do not change unless you are having issues, see docs for more information.', 'woocommerce-gateway-sagepay-form' ), 
					'default' 		=> '0',
					'options' 		=> array(
							'0'		=> 'Default',
							'1'		=> 'Use PHP_EOL',
							'2'		=> 'Use n',
							'3'		=> 'Use r'
						),
				),
				'defaultpostcode'	=> array(
				    'title'         => __( 'Default Postcode for Elavon users', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'Leave this blank unless you are using Elavon - See docs for more information.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => ''
				),
				'nullipaddress'		=> array(
				    'title'         => __( 'Default IP Address', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'If an IP Address can not be determined for the customer, use this IP Address as a fallback', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => WC_Sagepay_Common_Functions::get_icanhazip()
				),
				'vendortxcodeprefix'=> array(
				    'title'         => __( 'VendorTXCode Prefix', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'Add a custom prefix to the VendorTXCode. Only use letters, numbers and _ (underscores) any other characters will be stripped from the field.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_vendortxcodeprefix
				),
				'sagepaytransinfo'	=> array(
				    'title'         => __( 'Additional Transaction Information', 'woocommerce-gateway-sagepay-form' ),
				    'description'   => __( 'Include the transaction information received from Sage in the admin emails', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'default'       => false
				),
				'log_header' 	=> array(
				    'title'         => __( 'Log Headers', 'woocommerce-gateway-sagepay-form' ),
				    'label'         => __( 'Enable logging of header for Opayo Direct transactions', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'description'   => '',
				    'default'       => 'no'
				),
				'removeTokenActionScheduler' 	=> array(
				    'title'         => __( 'Remove Expired Tokens', 'woocommerce-gateway-sagepay-form' ),
				    'label'         => __( 'Automatically remove expired tokens from Opayo and from WooCommerce', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'description'   => 'Historically Opayo would automatically delete Tokens on your behalf when the card expiry date had been reached. Moving forward Opayo will not delete Tokens when the cards expire. This change has been implemented following a change to scheme rules regarding the acceptance of expired cards, expired tokens may now be accepted by the issuer. Opayo will continue to store Tokens on your account until you request a Token to be removed. As you are billed for Tokens stored on your account, if you do not remove unwanted Tokens this may increase your monthly billing.',
				    'default'       => 'no'
				),

			);