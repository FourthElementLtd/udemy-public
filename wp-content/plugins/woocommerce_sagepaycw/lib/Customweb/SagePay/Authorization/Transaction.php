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
require_once 'Customweb/Payment/Authorization/DefaultTransaction.php';
require_once 'Customweb/SagePay/Authorization/TransactionCapture.php';
require_once 'Customweb/SagePay/Authorization/TransactionRefund.php';
require_once 'Customweb/Payment/Util.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';

class Customweb_SagePay_Authorization_Transaction extends Customweb_Payment_Authorization_DefaultTransaction {
	private $securityKey = null;
	private $threeDTransactionIdentifier = null;
	private $t3mDetails = null;
	
	/**
	 *
	 * @var boolean
	 */
	private $redirected = false;
	private $redirectTo = null;
	private $giftAid = false;
	private $clearStorage = false;
	private $oneStep = false;
		
	const AUTHORIZATION_MODE_AUTHORISE = 'authorise';
	const AUTHORIZATION_MODE_DEFERRED = 'deferred';

	public function __construct(Customweb_Payment_Authorization_ITransactionContext $transactionContext){
		parent::__construct($transactionContext);
	}

	/**
	 * This flag is used with Moto to identify if a given transaction is already
	 * redirected or not.
	 *
	 * @return boolean
	 */
	public function isRedirected(){
		return $this->redirected;
	}

	public function setRedirectTo($redirectTo){
		$this->redirectTo = $redirectTo;
		return $this;
	}

	public function getRedirectTo(){
		return $this->redirectTo;
	}

	public function setRedirected(){
		$this->redirected = true;
		return $this;
	}

	public function isMoto(){
		return $this->getAuthorizationMethod() == Customweb_Payment_Authorization_Moto_IAdapter::AUTHORIZATION_METHOD_NAME;
	}

	public function get3DTransactionIdentifier(){
		return $this->threeDTransactionIdentifier;
	}

	public function set3DTransactionIdentifier($identifier){
		$this->threeDTransactionIdentifier = $identifier;
		return $this;
	}

	public function is3DTransaction(){
		if ($this->get3DTransactionIdentifier() === NULL) {
			return false;
		}
		else {
			return true;
		}
	}

	
	public function setOneStepCapture($bool){
		return $this->oneStep = $bool;
	}
	
	public function isOneStepCapture(){
		return $this->oneStep;
	}
	
	public function isGiftAidActive(){
		return $this->giftAid;
	}

	public function setGiftAidActive($active = true){
		$this->giftAid = $active;
		return $this;
	}

	public function setSecurityKey($key){
		$this->securityKey = $key;
		return $this;
	}

	public function getSecurityKey(){
		return $this->securityKey;
	}


	public function getFailedUrl(){
		return Customweb_Util_Url::appendParameters($this->getTransactionContext()->getFailedUrl(), 
				$this->getTransactionContext()->getCustomParameters());
	}

	public function getSuccessUrl(){
		return Customweb_Util_Url::appendParameters($this->getTransactionContext()->getSuccessUrl(), 
				$this->getTransactionContext()->getCustomParameters());
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Customweb_Payment_Authorization_DefaultTransaction::isCaptureClosable()
	 */
	public function isCaptureClosable(){
		if (parent::isCaptureClosable()) {
			// In case we have authorize via deferred, we can do only one capture!
			if ($this->getAuthorizationMode() == self::AUTHORIZATION_MODE_DEFERRED) {
				return false;
			}
			else {
				return true;
			}
		}
		return false;
	}

	public function getPayPalRedirectionUrl(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['PayPalRedirectURL'])) {
			return $params['PayPalRedirectURL'];
		}
		else {
			return NULL;
		}
	}

	protected function buildNewCaptureObject($captureId, $amount, $status = NULL){
		return new Customweb_SagePay_Authorization_TransactionCapture($captureId, $amount, $status);
	}

	protected function buildNewRefundObject($refundId, $amount, $status = NULL){
		return new Customweb_SagePay_Authorization_TransactionRefund($refundId, $amount, $status);
	}

	protected function getTransactionSpecificLabels(){
		$labels = array();
		
		$params = $this->getAuthorizationParameters();
		
		
		if (isset($params['StatusDetail']) && !empty($params['StatusDetail'])) {
			$labels['status_details'] = array(
				'label' => Customweb_I18n_Translation::__('Status Message'),
				'value' => strip_tags($params['StatusDetail']),
				'description' => Customweb_I18n_Translation::__(
						'This message is returned by Opayo for this transaction.')
			);
		}
		
		if (isset($params['TxAuthNo']) && !empty($params['TxAuthNo'])) {
			$labels['TxAuthNo'] = array(
				'label' => Customweb_I18n_Translation::__('Opayo Authorisation Code'),
				'value' => $params['TxAuthNo'] 
			);
		}
		
		if (isset($params['AddressResult']) && !empty($params['AddressResult'])) {
			$labels['address_result'] = array(
				'label' => Customweb_I18n_Translation::__('Address Check Result'),
				'value' => $this->formatFraudCheckResults($params['AddressResult']),
				'description' => Customweb_I18n_Translation::__(
						"The specific result of the checks on the cardholder's address from the AVS/CV2 checks.") 
			);
		}
		if (isset($params['PostCodeResult']) && !empty($params['PostCodeResult'])) {
			$labels['post_code_result'] = array(
				'label' => Customweb_I18n_Translation::__('Post Code Check Result'),
				'value' => $this->formatFraudCheckResults($params['PostCodeResult']),
				'description' => Customweb_I18n_Translation::__(
						"The specific result of the checks on the cardholder's postcode from the AVS/CV2 checks.") 
			);
		}
		
		if (isset($params['CV2Result']) && !empty($params['CV2Result'])) {
			$labels['cv2_result'] = array(
				'label' => Customweb_I18n_Translation::__('CV2 Check Result'),
				'value' => $this->formatFraudCheckResults($params['CV2Result']),
				'description' => Customweb_I18n_Translation::__("The specific result of the checks on the cardholder's CV2.") 
			);
		}
		
		if (isset($params['FraudResponse']) && !empty($params['FraudResponse'])) {
			$labels['fraud_response'] = array(
				'label' => Customweb_I18n_Translation::__('Fraud Result'),
				'value' => $this->formatFraudCheckResults($params['FraudResponse']),
				'description' => Customweb_I18n_Translation::__(
						"'Accept' means ReD recommends that the transaction can be accepted. 'Deny' means ReD recommends that the transaction shoulb be rejected. 'Not checked' means ReD did not perform any fraud checking for this particular transaction.") 
			);
		}
		
		if (isset($params['CardType']) && !empty($params['CardType'])) {
			$labels['card_type'] = array(
				'label' => Customweb_I18n_Translation::__('Card Type'),
				'value' => strip_tags($params['CardType']) 
			);
		}
		
		$cardNumber = $this->getCardNumber();
		if ($cardNumber !== NULL) {
			$labels['card_number'] = array(
				'label' => Customweb_I18n_Translation::__('Card Number'),
				'value' => $cardNumber 
			);
		}
		
		if (isset($params['ExpiryDate']) && !empty($params['ExpiryDate'])) {
			$ed = Customweb_Payment_Util::extractExpiryDate($params['ExpiryDate']);
			$labels['expiry_date'] = array(
				'label' => Customweb_I18n_Translation::__('Card Expiry'),
				'value' => $ed['month'] . '/' . $ed['year'] 
			);
		}
		
		if (isset($params['PayerID']) && !empty($params['PayerID'])) {
			$labels['paypal_payerid'] = array(
				'label' => Customweb_I18n_Translation::__('PayPal Payer ID'),
				'value' => $params['PayerID'] 
			);
		}
		
		if (isset($params['AddressStatus']) && !empty($params['AddressStatus'])) {
			$labels['address_status'] = array(
				'label' => Customweb_I18n_Translation::__('PayPal Address Status'),
				'value' => $this->formatFraudCheckResults($params['AddressStatus']) 
			);
		}
		
		if (isset($params['PayerStatus']) && !empty($params['PayerStatus'])) {
			$labels['payer_status'] = array(
				'label' => Customweb_I18n_Translation::__('PayPal Payer Status'),
				'value' => $this->formatFraudCheckResults($params['PayerStatus']) 
			);
		}
		
		if ($this->isGiftAidActive()) {
			$labels['gift_aid'] = array(
				'label' => Customweb_I18n_Translation::__('Gift Aid'),
				'value' => Customweb_I18n_Translation::__('Active'),
				'description' => Customweb_I18n_Translation::__("The tax of this transaction is donated to a charity.") 
			);
		}
		
		if ($this->isMoto()) {
			$labels['moto'] = array(
				'label' => Customweb_I18n_Translation::__('Mail Order / Telephone Order (MoTo)'),
				'value' => Customweb_I18n_Translation::__('Yes') 
			);
		}
		
		if ($this->t3mDetails != null) {
			$i = 0;
			foreach ($this->t3mDetails as $rule) {
				$labels['t3m_' . $i++] = array(
					'label' => $rule['label'],
					'value' => $rule['value']
				);
			}
		}
		
		return $labels;
	}
	
	/**
	 * Sets the 3rd Man fraud screening results
	 *
	 * @param array $t3mDetails (array of rules ('label', 'value')
	 */
	public function setT3mResults($t3mDetails){
		$this->t3mDetails = $t3mDetails;
		return $this;
	}

	public function extractDisplayNameForToken(){
		$params = $this->getAuthorizationParameters();
		
		$cardNumber = $this->getCardNumber();
		if ($cardNumber !== NULL) {
			$alias = $cardNumber;
			if (isset($params['ExpiryDate']) && !empty($params['ExpiryDate'])) {
				$ed = Customweb_Payment_Util::extractExpiryDate($params['ExpiryDate']);
				$alias .= ' ' . $ed['month'] . '/' . $ed['year'];
			}
			return $alias;
		}
		else {
			return $this->getToken();
		}
	}

	public function getCardNumber(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['Last4Digits']) && !empty($params['Last4Digits'])) {
			return str_repeat("X", 12) . strip_tags($params['Last4Digits']);
		}
		else {
			return NULL;
		}
	}

	public function getCardHolderName(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['CardHolder']) && !empty($params['CardHolder'])) {
			return $params['CardHolder'];
		}
		else {
			return NULL;
		}
	}

	public function getCardExpiryMonth(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['ExpiryDate']) && !empty($params['ExpiryDate'])) {
			$ed = Customweb_Payment_Util::extractExpiryDate($params['ExpiryDate']);
			return $ed['month'];
		}
		else {
			return NULL;
		}
	}

	public function getCardExpiryYear(){
		$params = $this->getAuthorizationParameters();
		if (isset($params['ExpiryDate']) && !empty($params['ExpiryDate'])) {
			$ed = Customweb_Payment_Util::extractExpiryDate($params['ExpiryDate']);
			return $ed['year'];
		}
		else {
			return NULL;
		}
	}

	public function getToken(){
		if ($this->getTransactionContext()->getAlias() !== NULL && $this->getTransactionContext()->getAlias() !== 'new') {
			return $this->getTransactionContext()->getAlias()->getToken();
		}
		
		$params = $this->getAuthorizationParameters();
		if (isset($params['Token']) && !empty($params['Token'])) {
			return $params['Token'];
		}
		return null;
	}

	public function getAuthorizationMode(){
		$params = $this->getAuthorizationParameters();
		if (!isset($params['Status']) || empty($params['Status'])) {
			throw new Exception("Could not get authorization status.");
		}
		
		$status = strtoupper($params['Status']);
		
		if ($status == 'AUTHENTICATED' || $status == 'REGISTERED') {
			return self::AUTHORIZATION_MODE_AUTHORISE;
		}
		else {
			return self::AUTHORIZATION_MODE_DEFERRED;
		}
	}

	private function formatFraudCheckResults($state){
		$state = strtoupper($state);
		
		switch ($state) {
			case 'NOTPROVIDED':
				return Customweb_I18n_Translation::__('Not provided');
			case 'NOTCHECKED':
				return Customweb_I18n_Translation::__('Not checked');
			case 'MATCHED':
				return Customweb_I18n_Translation::__('Matched');
			case 'NOTMATCHED':
				return Customweb_I18n_Translation::__('Not matched');
			case 'ACCEPT':
				return Customweb_I18n_Translation::__('Accept');
			case 'DENY':
				return Customweb_I18n_Translation::__('Deny');
			case 'VERIFIED':
				return Customweb_I18n_Translation::__('Verified');
			case 'UNVERIFIED':
				return Customweb_I18n_Translation::__('Unverified');
			case 'CONFIRMED':
				return Customweb_I18n_Translation::__('Confirmed');
			case 'UNCONFIRMED':
				return Customweb_I18n_Translation::__('Unconfirmed');
			
			case 'NONE':
			default:
				return '-';
		}
	}

	public function setClearStorage($value){
		$this->clearStorage = $value;
	}
	
	public function isClearStorage(){
		return $this->clearStorage;
	}
}