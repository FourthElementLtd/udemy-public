<?php
/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */
require_once 'Customweb/I18n/Translation.php';

/**
 *
 * @Bean
 */
class Customweb_SagePay_Configuration {
	
	/**
	 *
	 * @var Customweb_Payment_IConfigurationAdapter
	 */
	private $configurationAdapter = null;

	public function __construct(Customweb_Payment_IConfigurationAdapter $configurationAdapter){
		$this->configurationAdapter = $configurationAdapter;
	}

	/**
	 *
	 * @return Customweb_Payment_IConfigurationAdapter
	 */
	public function getConfigurationAdapter(){
		return $this->configurationAdapter;
	}

	public function getVendorName(){
		return $this->configurationAdapter->getConfigurationValue('vendor');
	}

	public function getTransactionIdSchema(){
		return $this->configurationAdapter->getConfigurationValue('transaction_id_schema');
	}
	
	public function is3dV2(){
		if ($this->configurationAdapter->existsConfiguration('threed_version')) {
			$giftAid = strtolower($this->getConfigurationAdapter()->getConfigurationValue('threed_version'));
			if ($giftAid == 'v2') {
				return true;
			}
		}
		return false;
	}
	
	public function isGiftAidEnabled(){
		if ($this->configurationAdapter->existsConfiguration('gift_aid')) {
			$giftAid = strtolower($this->getConfigurationAdapter()->getConfigurationValue('gift_aid'));
			if ($giftAid == 'enabled') {
				return true;
			}
		}
		return false;
	}

	public function isTestMode(){
		$operation_mode = strtolower($this->configurationAdapter->getConfigurationValue('operation_mode'));
		if ($operation_mode == 'test') {
			return true;
		}
		else {
			return false;
		}
	}

	public function isLiveMode(){
		$operation_mode = strtolower($this->configurationAdapter->getConfigurationValue('operation_mode'));
		if ($operation_mode == 'live') {
			return true;
		}
		else {
			return false;
		}
	}

	public function isSimulationMode(){
		$operation_mode = strtolower($this->configurationAdapter->getConfigurationValue('operation_mode'));
		if ($operation_mode == 'simulation') {
			return true;
		}
		else {
			return false;
		}
	}

	public function getBaseUrl(){
		// Since we use protocol version 3.00, it is not possible to use the simultion mode, it doesn't support
		// protocol version 3.00!
		if ($this->isLiveMode()) {
			return 'https://live.sagepay.com/gateway/service/';
		}
		
		else if ($this->isTestMode()) {
			return 'https://test.sagepay.com/gateway/service/';
		}
		else {
			throw new Exception("An invalid operation mode is configured. It must either live or test.");
		}
	}

	public function getAdministrationUrl(){
		// Since we use protocol version 3.00, it is not possible to use the simultion mode, it doesn't support
		// protocol version 3.00!
		if ($this->isLiveMode()) {
			return 'https://live.sagepay.com/access/access.htm';
		}
		
		else if ($this->isTestMode()) {
			return 'https://test.sagepay.com/access/access.htm';
		}
		else {
			throw new Exception("An invalid operation mode is configured. It must either live or test.");
		}
	}

	public function getUsername(){
		return $this->configurationAdapter->getConfigurationValue('username');
	}

	public function getPassword(){
		return $this->configurationAdapter->getConfigurationValue('password');
	}

	public function isThirdManEnabled(){
		return strtolower($this->getConfigurationAdapter()->getConfigurationValue('T3M')) == 'on';
	}
	
	public function isSingleStepDirectCapture(){
		return strtolower($this->getConfigurationAdapter()->getConfigurationValue('direct_capture_type')) == 'single';
	}

	public function isMotoCvcCheckEnabled(){
		if ($this->getConfigurationAdapter()->existsConfiguration('moto_cvc_check') &&
				 strtolower($this->getConfigurationAdapter()->getConfigurationValue('moto_cvc_check')) == 'enabled') {
			return true;
		}
		else {
			return false;
		}
	}

	public function getTransactionDescription($language){
		$description = $this->configurationAdapter->getConfigurationValue('description', $language);
		if (empty($description)) {
			$description = Customweb_I18n_Translation::__("You have not set a transaction description.");
		}
		return $description;
	}

	public function getDeferredAuthorizationType(){
		return strtolower($this->configurationAdapter->getConfigurationValue('deferred_authorization_type'));
	}

	public function getBasketMode(){
		return $this->configurationAdapter->getConfigurationValue('send_basket'); 
	}
}