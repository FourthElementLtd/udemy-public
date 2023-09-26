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
require_once 'Customweb/Payment/Endpoint/Controller/Abstract.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/SagePay/Util.php';
require_once 'Customweb/Core/Http/Response.php';
require_once 'Customweb/Core/Logger/Factory.php';


/**
 *
 * @author Mathis Kappeler
 * @Controller("process")
 *
 */
class Customweb_SagePay_Endpoint_Process extends Customweb_Payment_Endpoint_Controller_Abstract {
	const PARAMETER_STORAGE_SPACE = 'SagePay_Parameters';
	const DIRECT_3D_CALLBACK_FILE_PATH = 'direct3dcallback.vsp';
	/**
	 * @var Customweb_Core_ILogger
	 */
	private $logger;

	/**
	 * @param Customweb_DependencyInjection_IContainer $container
	 */
	public function __construct(Customweb_DependencyInjection_IContainer $container){
		parent::__construct($container);
		$this->logger = Customweb_Core_Logger_Factory::getLogger(get_class($this));
	}

	/**
	 * @Action("server")
	 */
	public function processServer(Customweb_Core_Http_IRequest $request){
		$transactionHandler = $this->getTransactionHandler();
		$transactionIdMap = $this->getTransactionId($request);
		$transactionHandler->beginTransaction();
		$transaction = $transactionHandler->findTransactionByTransactionExternalId($transactionIdMap['id']);
		$parameters = $request->getParameters();

		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());

		try {
			$this->logger->logInfo("The server notication process has been started for the transaction " . $transaction->getTransactionId() . ".");

			// In some cases the notification is executed more than once.
			if ($transaction->isAuthorizationFailed()) {
				$rs = new Customweb_Core_Http_Response();
				$rs->setLocation($transaction->getFailedUrl());
				$transactionHandler->commitTransaction();
				return $rs;
			}

			if ($transaction->isAuthorized()) {
				$rs = new Customweb_Core_Http_Response();
				$rs->setLocation($transaction->getSuccessUrl());
				$transactionHandler->commitTransaction();
				return $rs;
			}

			// In case we have authorized the transaction at the remote side, but we have not already processed the
			// 3D response, we have to do it now
			if ($transaction->is3DTransaction()) {
				$requestParameters = array();
				if (isset($parameters['MD']) && isset($parameters['PaRes'])) {
					$requestParameters['MD'] = $parameters['MD'];
					$requestParameters['PaRes'] = $parameters['PaRes'];
				}
				else if (isset($parameters['threeDSSessionData']) && isset($parameters['cres'])) {
					$requestParameters['VPSTxId'] = $parameters['threeDSSessionData'];
					$requestParameters['cres'] = $parameters['cres'];
				}
				else {
					throw new Exception("Expecting either MD + PaRes or threeDSSessionData + cres");
				}

				$response = Customweb_SagePay_Util::sendRequest($this->get3DCallbackUrl(), $requestParameters);

				if (isset($response['StatusDetail']) && $response['StatusDetail'] == '5036 : transaction not found') {
					$responseObject = new Customweb_Core_Http_Response();
					$responseObject->setLocation($transaction->getSuccessUrl());
					$transactionHandler->commitTransaction();
					return $responseObject;
				}
				$adapter->mergeResponseWithAuthorizationParameters($transaction, $response);
				$adapter->processResponse($transaction);
			}

			// Handles the PayPal Callback
			else if ($transaction->getPayPalRedirectionUrl() !== NULL) {
				$adapter->processPayPalCallback($transaction, $parameters);
			}
			else {
				throw new Exception('Invalid state');
			}
			$response = $adapter->finalizeAuthorizationRequest($transaction);
			$transactionHandler->persistTransactionObject($transaction);
			$transactionHandler->commitTransaction();

			$this->logger->logInfo("The server notification process has been finished for the transaction " . $transaction->getTransactionId() . ".");
			return $response;
		}
		catch (Exception $e) {
			$this->logger->logException($e);
			$transactionHandler->rollbackTransaction();
			$response = Customweb_Core_Http_Response::_("There was an error during processing");
			$response->setStatusCode(500);
			return $response;
		}
	}

	protected function get3DCallbackUrl(){
		return $this->getContainer()->getBean('Customweb_SagePay_Configuration')->getBaseUrl() . self::DIRECT_3D_CALLBACK_FILE_PATH;
	}

	/**
	 *
	 * @Action("direct")
	 */
	public function processDirect(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$this->logger->logInfo("The direct notifiation process has been started for the transaction " . $transaction->getTransactionId() . ".");
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
		$parameters = $request->getParameters();
		$response = $adapter->processAuthorization($transaction, $parameters);
		$this->logger->logInfo("The direct notification process has been finished for the transaction " . $transaction->getTransactionId() . ".");
		return $response;
	}

	/**
	 * @Action("authorize")
	 */
	public function processAuthorize(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$this->logger->logInfo("The authorize notification process has been started for the transaction " . $transaction->getTransactionId() . ".");
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
		$parameters = $request->getParameters();
		if (!isset($parameters['cw_hash'])) {
			throw new Exception(Customweb_I18n_Translation::__('No signature provided.'));
		}
		$transaction->checkSecuritySignature('process/authorize', $parameters['cw_hash']);
		$stored = $this->getStorageAdapter()->read(self::PARAMETER_STORAGE_SPACE, $transaction->getTransactionId());
		$response = $adapter->processAuthorization($transaction, $stored);
		$this->logger->logInfo("The authorize notification process has been finished for the transaction " . $transaction->getTransactionId() . ".");
		return $response;
	}

	/**
	 *
	 * @Action("fast")
	 */
	public function processFast(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$this->logger->logInfo("The fast notification process has been started for the transaction " . $transaction->getTransactionId() . ".");
		$storageAdapter = $this->getStorageAdapter();
		$parameters = $request->getParameters();
		$storageAdapter->write(self::PARAMETER_STORAGE_SPACE, $transaction->getTransactionId(), $parameters);
		$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(10));
		$this->logger->logInfo("The fast notification process has been finished for the transaction " . $transaction->getTransactionId() . ".");
		return $this->getRedirectResponse($transaction);
	}

	/**
	 * @Action("moto")
	 */
	public function processMoto(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$this->logger->logInfo("The MoTo process has been started for the transaction " . $transaction->getTransactionId() . ".");
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
		$parameters = $request->getParameters();
		$response = $adapter->processAuthorization($transaction, $parameters);
		$this->logger->logInfo("The MoTo notification process has been finished for the transaction " . $transaction->getTransactionId() . ".");
		return $response;
	}

	/**
	 * 
	 * @return Customweb_Storage_IBackend
	 */
	protected function getStorageAdapter(){
		return $this->getContainer()->getBean('Customweb_Storage_IBackend');
	}

	protected function getRedirectResponse(Customweb_SagePay_Authorization_Transaction $transaction){
		$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(15));
		$redirectionUrl = $this->getContainer()->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "authorize",
				array(
					'cw_transaction_id' => $transaction->getExternalTransactionId(),
					'cw_hash' => $transaction->getSecuritySignature('process/authorize')
				));

		$parameters = array();
		$parameters['Status'] = 'OK';
		$parameters['RedirectURL'] = $redirectionUrl;

		return Customweb_Core_Http_Response::_(Customweb_SagePay_Util::parseToCRLF($parameters));
	}
}