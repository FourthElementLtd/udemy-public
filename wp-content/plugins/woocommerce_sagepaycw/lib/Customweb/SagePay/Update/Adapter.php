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

require_once 'Customweb/Core/DateTime.php';
require_once 'Customweb/Payment/Authorization/ITransactionHistoryItem.php';
require_once 'Customweb/Payment/Update/IAdapter.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/SagePay/AbstractAdapter.php';
require_once 'Customweb/Core/Http/Client/Factory.php';
require_once 'Customweb/SagePay/Endpoint/Process.php';
require_once 'Customweb/Payment/Authorization/DefaultTransactionHistoryItem.php';
require_once 'Customweb/Core/Http/Request.php';
require_once 'Customweb/Core/Logger/Factory.php';



/**
 *
 * @author Sebastian Bossert
 * @Bean
 *
 */
class Customweb_SagePay_Update_Adapter extends Customweb_SagePay_AbstractAdapter implements Customweb_Payment_Update_IAdapter {

	public function updateTransaction(Customweb_Payment_Authorization_ITransaction $transaction){
		
		if(!$transaction->isAuthorizationFailed() && !$transaction->isAuthorized()){
			$adapter = $this->container->getBean('Customweb_Payment_Authorization_IAdapterFactory')->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
			$stored = $this->getStorageAdapter()->read(Customweb_SagePay_Endpoint_Process::PARAMETER_STORAGE_SPACE, $transaction->getTransactionId());
			if($stored !== null){
				$adapter->processAuthorization($transaction, $stored);
				return;
			}			
			
		}
		if($transaction->isClearStorage() && !$transaction->isOneStepCapture()){
			$this->getStorageAdapter()->remove(Customweb_SagePay_Endpoint_Process::PARAMETER_STORAGE_SPACE, $transaction->getTransactionId());
			$transaction->setClearStorage(false);
		}
		
		if($transaction->isAuthorized() && $this->getConfiguration()->isThirdManEnabled()){
			try {
				$transactionDetails = $this->getTransactionDetails($transaction);
				if ($transactionDetails === false) {
					// error message is set in getTransactionDetails, fail silently
					return;
				}
				$xml = $this->buildXmlForRequest($transaction, 'getT3MDetail', $transactionDetails['id'], 't3mtxid');
				$responseXml = $this->getResponseXml($xml);
				$error = $responseXml->errorcode;
				if ($error != '0000') {
					$this->handleError($transaction, (string) $responseXml->error);
					return;
				}
				if (property_exists($responseXml, 't3mresults')) {
					$details = $this->formatT3m($transactionDetails, $responseXml->t3mresults->rule);
					$transaction->setT3mResults($details);
				}
				else {
					$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(15));
				}
			}
			catch (Exception $e) {
				Customweb_Core_Logger_Factory::getLogger(get_class($this))->logException($e);
				$this->handleError(Customweb_I18n_Translation::__('Failed to update transaction: @error', array('@error' => $e->getMessage())));
			}
		}
	}

	private function formatT3m(array $rough, SimpleXMLElement $rules){
		$t3m = array();
		$t3m[] = array(
			'label' => Customweb_I18n_Translation::__('3rd Man: Action'),
			'value' => (string) $rough['action'] 
		);
		$t3m[] = array(
			'label' => Customweb_I18n_Translation::__('3rd Man: Score'),
			'value' => (string) $rough['score'] 
		);
		foreach ($rules as $rule) {
			$t3m[] = array(
				'label' => '3rd Man: ' . (string) $rule->description,
				'value' => (string) $rule->score 
			);
		}
		return $t3m;
	}

	private function handleError(Customweb_SagePay_Authorization_Transaction $transaction, $message){
		$transaction->addErrorMessage($message);
		$transaction->addHistoryItem(
				new Customweb_Payment_Authorization_DefaultTransactionHistoryItem($message, 
						Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_LOG));
	}

	private function getTransactionDetails(Customweb_SagePay_Authorization_Transaction $transaction){
		$params = $transaction->getAuthorizationParameters();
		$xml = $this->buildXmlForRequest($transaction, 'getTransactionDetail', $params['VPSTxId'], 'vpstxid');
		$response = $this->getResponseXml($xml);
		$error = $response->errorcode;
		if ($error != '0000') {
			$this->handleError($transaction, Customweb_I18n_Translation::__('Failed to fetch transaction details: @error', array('@error' => (string)$response->error)));
			return false;
		}
		if (property_exists($response, 't3mid')) {
			return array(
				'id' => $response->t3mid,
				'score' => $response->t3mscore,
				'action' => $response->t3maction 
			);
		}
		
		if (isset($response->t3maction) && $response->t3maction == 'NORESULT') {
			$transaction->addHistoryItem(
					new Customweb_Payment_Authorization_DefaultTransactionHistoryItem(Customweb_I18n_Translation::__('Tried to update 3rd man result, but no result present right now.'),
							Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_LOG));
			$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(30));
			return false;
		}
		
		$this->handleError($transaction, Customweb_I18n_Translation::__('An unknown error has occurred while trying to obtain The 3rd Man results.'));
		return false;
	}

	private function getResponseXml($xml){
		$config = $this->getConfiguration();
		$request = new Customweb_Core_Http_Request($config->getAdministrationUrl());
		$request->setMethod('POST');
		$request->setContentType('application/x-www-form-urlencoded');
		$request->setBody('XML=' . $this->tagify($xml, 'vspaccess'));
		$response = Customweb_Core_Http_Client_Factory::createClient()->send($request);
		$responseXml = new SimpleXMLElement($response->getBody());
		return $responseXml;
	}

	private function buildXmlForRequest(Customweb_SagePay_Authorization_Transaction $transaction, $command, $id, $idTag){
		$config = $this->getConfiguration();
		$xml = $this->tagify($command, 'command');
		$xml .= $this->tagify($config->getVendorName(), 'vendor');
		$xml .= $this->tagify($config->getUsername(), 'user');
		$xml .= $this->tagify($id, $idTag);
		$signature = md5($xml . $this->tagify($config->getPassword(), 'password'));
		$xml .= $this->tagify($signature, 'signature');
		return $xml;
	}

	private function tagify($content, $tag){
		return '<' . $tag . '>' . $content . '</' . $tag . '>';
	}
	
	/**
	 *
	 * @return Customweb_Storage_IBackend
	 */
	protected function getStorageAdapter(){
		return $this->container->getBean('Customweb_Storage_IBackend');
	}
}