<?php
/**
  * You are allowed to use this API in your web application.
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

require_once 'Customweb/Payment/Authorization/DefaultTransactionCapture.php';

class Customweb_SagePay_Authorization_TransactionCapture extends Customweb_Payment_Authorization_DefaultTransactionCapture {

	private $externalTransactionId;
	
	private $transactionAuthenticationNumber;
	
	private $securityKey;
	
	private $vendorTransactionCode;
	
	private $refundedAmount = 0;
	
	/**
	 * @var Customweb_SagePay_Authorization_TransactionRefund
	 */
	private $refunds = array();
	
	public function getExternalTransactionId() {
		return $this->externalTransactionId;
	}
	
	public function setExternalTransactionId($externalTransactionId) {
		$this->externalTransactionId = $externalTransactionId;
		return $this;
	}
	
	public function getTransactionAuthenticationNumber() {
		return $this->transactionAuthenticationNumber;
	}
	
	public function setTransactionAuthenticationNumber($transactionAuthenticationNumber) {
		$this->transactionAuthenticationNumber = $transactionAuthenticationNumber;
		return $this;
	}
	
	public function getSecurityKey() {
		return $this->securityKey;
	}
	
	public function setSecurityKey($securityKey) {
		$this->securityKey = $securityKey;
		return $this;
	}
	
	public function getVendorTransactionCode() {
		return $this->vendorTransactionCode;
	}
	
	public function setVendorTransactionCode($vendorTransactionCode) {
		$this->vendorTransactionCode = $vendorTransactionCode;
		return $this;
	}
	
	public function getRefundedAmount() {
		return $this->refundedAmount;
	}
	
	public function setRefundedAmount($refundedAmount) {
		$this->refundedAmount = $refundedAmount;
		return $this;
	}
	

	/**
	 * Adds a refund to this capture.
	 *
	 * @param Customweb_SagePay_Authorization_TransactionRefund $refund
	 * @return Customweb_SagePay_Authorization_TransactionCapture
	 */
	public function addRefund(Customweb_SagePay_Authorization_TransactionRefund $refund) {
		$this->refunds[$refund->getRefundId()] = $refund;
		return $this;
	}
	
	/**
	 * Returns a list of refund objects linked with this capture.
	 *
	 * @return Customweb_SagePay_Authorization_TransactionRefund[]
	 */
	public function getRefunds() {
		return $this->refunds;
	}
	
	/**
	 * Returns a list of line items of this capture, which can be refund. A line
	 * item can be refunded, when it is not already refunded. The amounts are
	 * reduced based on previous refunds. If a item is completly refunded, this
	 * method will not return it anymore.
	 *
	 * @return Customweb_Payment_Authorization_IInvoiceItem[]
	 */
	public function getRefundableItems() {
		$items = $this->getCaptureItems();
		foreach ($this->getRefunds() as $refund) {
			$items = Customweb_Util_Invoice::substractLineItems($items, $refund->getRefundItems());
		}
	
		return $items;
	}
	
}