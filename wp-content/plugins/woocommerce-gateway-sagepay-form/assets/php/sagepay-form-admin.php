<?php
			$settings_form_fields = array(
				'enabled'           => array(
				    'title'         => __( 'Enable/Disable', 'woocommerce-gateway-sagepay-form' ),
				    'label'         => __( 'Enable Opayo Form', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'description'   => '',
				    'default'       => $this->default_enabled
				),
				'initial_options' 	=> array(
					'title' 		=> __( 'Initial Setup Options', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'title',
					'description' 	=> __( '<div style="display:block; border-bottom:1px dotted #000; width:100%;"></div>', 'woocommerce-gateway-sagepay-form' )
				),
				'debugmode'         => array(
				    'title'         => __( 'Debug Mode', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'options'       => array('no'=>'No','yes'=>'Yes'),
				    'label'     	=> __( 'Enable Debug Mode', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_debug
				),
				'status'            => array(
				    'title'         => __( 'Status', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array('live'=>'Live','testing'=>'Testing', 'showpost'=>'Showpost'),
				    'description'   => __( 'Set Form Live/Testing Status.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_status,
					'desc_tip'    	=> true,
				),
				'vendor'            => array(
				    'title'         => __( 'Vendor Name', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'This should have been supplied by Opayo/SagePay when you created your account.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_vendor,
					'desc_tip'    	=> true,
				),
				'vendorpwd'         => array(
				    'title'         => __( 'LIVE Encryption Password', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'password',
				    'description'   => __( 'This should have been supplied by Opayo/SagePay when you created your account. This NOT the vendor password', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_vendorpwd,
					'desc_tip'    	=> true,
					'value' 		=> '',
				),
				'testvendorpwd'     => array(
				    'title'         => __( 'Testing Encryption Password', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'password',
				    'description'   => __( 'This should have been supplied by Opayo/SagePay when you created your account. This NOT the vendor password', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_testvendorpwd,
					'desc_tip'    	=> true,
					'value' 		=> '',
				),
				'txtype'            => array(
				    'title'         => __( "SagePay Transaction Type", 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array('PAYMENT'=>'PAYMENT','DEFERRED'=>'DEFERRED','AUTHENTICATE'=>'AUTHENTICATE'),
				    'description'   => __( "<br/>By default a PAYMENT transaction type is used to gain an authorisation from the bank, then settle that transaction early the following morning, committing the funds to be taken from your customer's card.<br/><br/>In some cases you may not wish to take the funds from the card immediately, but merely place a shadow on the customer's card to ensure they cannot subsequently spend those funds elsewhere, and then only take the money when you are ready to ship the goods. This type of transaction is called a DEFERRED transaction.<br/><br/>The AUTHENTICATE and AUTHORISE methods are specifically for use by merchants who are either (i) unable to fulfil the majority of orders in less than 6 days (or sometimes need to fulfil them after 30 days) or (ii) do not know the exact amount of the transaction at the time the order is placed (for example, items shipped priced by weight, or items affected by foreign exchange rates).<br/><br/>Unlike normal PAYMENT or DEFERRED transactions, AUTHENTICATE transactions do not obtain an authorisation at the time the order is placed. Instead the card and card holder are validated using the 3D-Secure mechanism provided by the card-schemes and card issuing banks, with a view to later authorisation.", 'woocommerce_sagepayform' ),
				    'default'       => $this->default_txtype,
					'desc_tip'    	=> true,
				),
				'basketoption'     	=> array(
				    'title'         => __( 'Basket Option', 'woocommerce-gateway-sagepay-form' ),
				    'type' 			=> 'select',
					'options' 		=> array('0'=>'Do not send the basket to Opayo','1'=>'Send the basket in standard format','2'=>'Send the basket in XML format'),
				    'label'     	=> __( '', 'woocommerce-gateway-sagepay-form' ),
				    'description' 	=> __( 'Optionally you can send the contents of the shopping cart to Opayo, this will show up in MySagePay and in certain emails.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => 1,
					'desc_tip'    	=> true,
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
				'checkout_options' 	=> array(
					'title' 		=> __( 'Checkout Options', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'title',
					'description' 	=> __( '<div style="display:block; border-bottom:1px dotted #000; width:100%;">This section controls what is shown on the checkout page.</div>', 'woocommerce-gateway-sagepay-form' )
				),
				'title'             => array(
				    'title'         => __( 'Title', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_title,
					'desc_tip'    	=> true,
				),
				'description'       => array(
				    'title'         => __( 'Description', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'textarea',
				    'description'   => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_description,
					'desc_tip'    	=> true,
				),
				'order_button_text'		=> array(
					'title' 		=> __( 'Checkout Pay Button Text', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the pay button text shown during checkout.', 'woocommerce-gateway-sagepay-form' ),
					'default' 		=> $this->default_order_button_text,
					'desc_tip'    	=> true,
				),
				'cardtypes'			=> array(
					'title' 		=> __( 'Accepted Cards', 'woocommerce-gateway-sagepay-form' ), 
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'Select which card types to accept.', 'woocommerce-gateway-sagepay-form' ), 
					'options' 		=> $this->sage_cardtypes,
					'desc_tip'    	=> true,
				),
				'sagelink' 			=> array(
					'title' 		=> __( '"What is Opayo" Link', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
					'description' 	=> __( 'Include a "What is Opayo" link on the checkout to give customers more confidence. (If the Opayo logo option is set to yes then the logo becomes the link)', 'woocommerce_sagepayform' ),
					'default' 		=> $this->default_sagelink,
					'desc_tip'    	=> true,
				),
				'sagelogo' 			=> array(
					'title' 		=> __( 'Opayo Logo', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
					'description' 	=> __( 'Include the Opayo logo on the checkout.', 'woocommerce-gateway-sagepay-form' ),
					'default' 		=> $this->default_sagelogo,
					'desc_tip'    	=> true,
				),
				'sagepay_options' 	=> array(
					'title' 		=> __( 'Opayo Options', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'title',
					'description' 	=> __( '<div style="display:block; border-bottom:1px dotted #000; width:100%;"> </div>', 'woocommerce-gateway-sagepay-form' )
				),
				'email'             => array(
				    'title'         => __( 'Vendor Email Address', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'Please enter your email address; If provided, an e-mail will be sent to this address when each transaction completes (successfully or otherwise). If you wish to use multiple email addresses, you should add them using the : (colon) character as a separator e.g. <code>me@mail1.com:me@mail2.com</code>', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_email,
					'desc_tip'    	=> true,
				),
				'sendemail'         => array(
				    'title'         => __( 'Transaction Email Status', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array('0'=>'Do not send either customer or vendor e-mails','1'=>'Send customer and vendor e-mails if addresses are provided','2'=>'Send vendor e-mail but NOT the customer e-mail'),
				    'default'       => $this->default_sendemail,
					'desc_tip'    	=> true,
				),
				'allow_gift_aid'        => array(
				    'title'         => __( 'Allow Gift Aid', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'description'   => __( 'Enable this to allow the gift aid acceptance box to appear on the payment page. This option only makes a difference if your vendor account is Gift Aid enabled.', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_allow_gift_aid,
					'desc_tip'    	=> true,
				),
				'apply_avs_cv2'     => array(
				    'title'         => __( 'AVS / CV2 Status', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array('0'=>'If AVS/CV2 enabled then check them. If rules apply, use rules.','1'=>'Force AVS/CV2 checks even if not enabled for the account. If rules apply, use rules.','2'=>'Force NO AVS/CV2 checks even if enabled on account.','3'=>'Force AVS/CV2 checks even if not enabled for the account but DON’T apply any rules.'),
				    'description'   => __( 'Using this flag you can fine tune the AVS/CV2 checks and rule set you’ve defined at a transaction level. This is useful in circumstances where direct and trusted customer contact has been established and you wish to override the default security checks.', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_apply_avs_cv2,
					'desc_tip'    	=> true,
				),
				'apply_3dsecure'    => array(
				    'title'         => __( '3D Secure Status', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'select',
				    'options'       => array(
				    					'0'=>'If 3D-Secure checks are possible and rules allow, perform the checks and apply the authorisation rules',
				    					'1'=>'Force 3D-Secure checks for this transaction if possible and apply rules for authorisation.'
				    				),
				    'description'   => __( 'Using this flag you can fine tune the 3D Secure checks and rule set you’ve defined at a transaction level. This is useful in circumstances where direct and trusted customer contact has been established and you wish to override the default security checks.', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_apply_3dsecure,
					'desc_tip'    	=> true,
				),
				'vendortxcodeprefix'=> array(
				    'title'         => __( 'VendorTXCode Prefix', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( 'Add a custom prefix to the VendorTXCode. Only use letters, numbers and _ (underscores) any other characters will be stripped from the field.', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_vendortxcodeprefix,
					'desc_tip'    	=> true,
				),
				'sagemagicvalue' 	=> array(
					'title' 		=> __( 'Card holder "Magic value" for testing 3D Secure', 'woocommerce-gateway-sagepay-form' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
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
			);

			$surcharge_form_fields = array(
				'surcharge_options' 	=> array(
					'title' 		=> __( 'Optionally Setup Surcharges', 'woocommerce-gateway-sagepay-form' ),
					'type' 			=> 'title',
					'description' 	=> __( '<div style="display:block; border-bottom:1px dotted #000; width:100%;">You can create surcharges for specific card types if required, these are shown to the customer once they have selected their card type and added to the order total.<br /><br />The format should be method|value, where method is either P for percentage or F for fixed and the surchage value eg P|5 would give a 5% surcharge, F|2.50 would give a fixed surchage of 2.50. Leave blank for no surcharge for that payment method</div>', 'woocommerce_sagepayform' )
				),
				'enablesurcharges'  => array(
				    'title'         => __( 'Opayo Surcharges', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'checkbox',
				    'options'       => array('no'=>'No','yes'=>'Yes'),
				    'label'     	=> __( 'Enable Opayo Surcharges.', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_enablesurcharges,
					'desc_tip'    	=> true,
				),
				'visasurcharges'   	=> array(
				    'title'         => __( 'Surcharge for Visa Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_VISAsurcharges
				),
				'visadebitsurcharges'=> array(
				    'title'         => __( 'Surcharge for Visa Debit / Delta Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_DELTAsurcharges
				),
				'visaelectronsurcharges'=> array(
				    'title'         => __( 'Surcharge for Visa Electron', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_UKEsurcharges
				),
				'mcsurcharges'   	=> array(
				    'title'         => __( 'Surcharge for MasterCard', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_MCsurcharges
				),
				'mcdebitsurcharges' => array(
				    'title'         => __( 'Surcharge for MasterCard Debit Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_MCDEBITsurcharges
				),
				'maestrosurcharges' => array(
				    'title'         => __( 'Surcharge for Maestro Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_MAESTROsurcharges
				),
				'amexsurcharges'   	=> array(
				    'title'         => __( 'Surcharge for American Express', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_AMEXsurcharges
				),
				'dinerssurcharges'	=> array(
				    'title'         => __( 'Surcharge for Diners Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_DCsurcharges
				),
				'jcbsurcharges' 	=> array(
				    'title'         => __( 'Surcharge for JCB Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_JCBsurcharges
				),
				'lasersurcharges' 	=> array(
				    'title'         => __( 'Surcharge for Laser Card', 'woocommerce-gateway-sagepay-form' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce-gateway-sagepay-form' ),
				    'default'       => $this->default_LASERsurcharges
				), 
			);