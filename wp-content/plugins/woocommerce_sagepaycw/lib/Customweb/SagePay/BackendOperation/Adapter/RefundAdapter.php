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

require_once 'Customweb/SagePay/AbstractMaintenanceAdapter.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/IRefund.php';
require_once 'Customweb/Util/Invoice.php';
require_once 'Customweb/SagePay/BackendOperation/Adapter/RefundParameterBuilder.php';

/**
 * 
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_SagePay_BackendOperation_Adapter_RefundAdapter extends Customweb_SagePay_AbstractMaintenanceAdapter implements Customweb_Payment_BackendOperation_Adapter_Service_IRefund {
	
	const REFUND_SERVICE_PATH = 'refund.vsp';

	public function refund(Customweb_Payment_Authorization_ITransaction $transaction){
		if (!($transaction instanceof Customweb_SagePay_Authorization_Transaction)) {
			throw new Exception("The given transaction is not instanceof Customweb_SagePay_Authorization_Transaction.");
		}
		$items = $transaction->getNonRefundedLineItems();
		return $this->partialRefund($transaction, $items, true);
	}
	
	public function partialRefund(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){
		if (!($transaction instanceof Customweb_SagePay_Authorization_Transaction)) {
			throw new Exception("The given transaction is not instanceof Customweb_SagePay_Authorization_Transaction.");
		}

		// Check the transaction state
		$transaction->refundByLineItemsDry($items, $close);
		
		// Check if we are in correct state
		if ($transaction->getAuthorizationMode() == Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			$captures = $transaction->getCaptures();
			if (count($captures) > 1) {
				throw new Exception(Customweb_I18n_Translation::__("A transaction of type 'deferred' can have only one capture, but there are multiple captures. This transaction is corrupted."));
			}
		}
		
		$isLegacy = false;
		foreach ($transaction->getCaptures() as $capture) {
			if ($capture->getRefundedAmount() !== null) {
				$isLegacy = true;
				break;
			}
		}
		if ($isLegacy) {
			$this->refundByAmount($transaction, $items, $close);
		}
		else {
			$this->refundByLineItems($transaction, $items, $close);
		}
	}
	
	protected function refundByLineItems(Customweb_SagePay_Authorization_Transaction $transaction, array $items, $close) {
	
		$remainingItemsToRefund = $items;
		$refunds = array();
		foreach ($transaction->getCaptures() as $capture) {
			/* @var $capture Customweb_SagePay_Authorization_TransactionCapture */
			$refundableItems = $capture->getRefundableItems();
	
			$residualLineItems = Customweb_Util_Invoice::substractLineItems($refundableItems, $remainingItemsToRefund);
			$itemsToRefundForThisCapture = Customweb_Util_Invoice::substractLineItems($refundableItems, $residualLineItems);
	
			$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($itemsToRefundForThisCapture);
			$parameters = $this->getServiceParameters($transaction, $capture, $amount);
			$response = $this->processServiceRequest(
					$this->getServiceUrl($transaction),
					$parameters
			);
			
			$message = Customweb_I18n_Translation::__("Refund added with refund id !refundId and authorization code !authorizationCode.", array(
				'!refundId' => $parameters['VendorTxCode'],
				'!authorizationCode' => $response['TxAuthNo']
			));
			
			$refundItem = $transaction->refundByLineItems($itemsToRefundForThisCapture, $close, $message);
			$refundItem->setCapture($capture);
			$refundItem->setRefundId($parameters['VendorTxCode']);
			$refundItem->setResponseParameters(array_merge($response, $parameters));
				
			$capture->addRefund($refundItem);
	
			$remainingItemsToRefund = Customweb_Util_Invoice::substractLineItems($remainingItemsToRefund, $itemsToRefundForThisCapture);
	
			// In case we have no remaing items, we stop.
			if (count($remainingItemsToRefund) <= 0) {
				break;
			}
		}
	
	}
	
	/**
	 * @param Customweb_SagePay_Authorization_Transaction $transaction
	 * @param Customweb_Payment_Authorization_IInvoiceItem[] $items
	 * @param boolean $close
	 * @deprecated Only here for legacy purposes.
	 */
	protected function refundByAmount(Customweb_SagePay_Authorization_Transaction $transaction, array $items, $close) {

		// In case of authorise may be multiple captures exists, hence we may need to refund multiple captures:
		$residualAmount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);
		
		foreach($transaction->getCaptures() as $capture) {
			// Check if we are finished with refunding
			if ($residualAmount <= 0) {
				break;
			}
		
			$amountToRefund = $residualAmount;
			$refundableAmount = $capture->getAmount() - $capture->getRefundedAmount();
			if ($refundableAmount < $residualAmount) {
				$amountToRefund = $refundableAmount;
			}
		
			if ($amountToRefund <= 0) {
				continue;
			}
		
			$residualAmount = $residualAmount - $amountToRefund;
		
			$closeRefund = false;
			if ($close) {
				if ($residualAmount <= 0) {
					$closeRefund = true;
				}
			}
		
			$transaction->refundDry($amountToRefund, $closeRefund);
			$this->refundACapture($transaction, $capture, $amountToRefund, $closeRefund, $items);
		}
		
	}
	
	

	/**
	 * This method refunds a given capture with over the given amount.
	 *
	 * @param Customweb_SagePay_Authorization_Transaction $transaction
	 * @param Customweb_SagePay_Authorization_TransactionCapture $capture
	 * @param float $amount
	 * @param boolean $close
	 * @deprecated
	 */
	protected function refundACapture(Customweb_SagePay_Authorization_Transaction $transaction,
			Customweb_SagePay_Authorization_TransactionCapture $capture, $amount, $close, $items) {
	
		$parameters = $this->getServiceParameters($transaction, $capture, $amount);
		$response = $this->processServiceRequest(
				$this->getServiceUrl($transaction),
				$parameters
		);
	
		$message = Customweb_I18n_Translation::__("Refund added with refund id !refundId and authorization code !authorizationCode.", array(
			'!refundId' => $parameters['VendorTxCode'],
			'!authorizationCode' => $response['TxAuthNo']
		));
	
		$refundItems = Customweb_Util_Invoice::getItemsByReductionAmount($items, $amount, $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode());
		$refundItem = $transaction->refundByLineItems($refundItems, $close, $message);
		
		$capture->setRefundedAmount(($capture->getRefundedAmount() + $amount));
		$refundItem->setRefundId($parameters['VendorTxCode']);
		$refundItem->setResponseParameters(array_merge($response, $parameters));
	}
	
	
	protected function getServiceUrl($transaction) {
		return $this->getBaseUrl() . self::REFUND_SERVICE_PATH;
	}
	
	protected function getServiceParameters($transaction, $capture, $amount) {
		$builder = new Customweb_SagePay_BackendOperation_Adapter_RefundParameterBuilder($transaction, $this->getConfiguration());
		return $builder->buildParameters($capture, $amount);
	}
	
	
		
}