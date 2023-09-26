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

require_once 'Customweb/Payment/Authorization/ITransactionContext.php';

require_once 'Customweb/SagePay/Authorization/AbstractParameterBuilder.php';
require_once 'Customweb/Util/Url.php';
require_once 'Customweb/Payment/Util.php';
require_once 'Customweb/Util/String.php';
require_once 'Customweb/SagePay/Util.php';

class Customweb_SagePay_Authorization_Server_ParameterBuilder extends Customweb_SagePay_Authorization_AbstractParameterBuilder
{
	public function __construct(Customweb_SagePay_Authorization_Transaction $transaction, Customweb_SagePay_Configuration $configuration, array $formData, Customweb_DependencyInjection_IContainer $container) {
		parent::__construct($transaction, $configuration, $container, $formData);
	}
	
	
	public function buildParameters() {
		
		$paymentMethod = Customweb_SagePay_Method_Factory::getMethod($this->getTransaction()->getPaymentMethod(), $this->container);
		$parameters = array_merge(
			$this->getProtocolVersionParameters(),
			$this->getReferrerParameters(),
			$this->getProcessingTypeParameters(),
			$this->getVendorParameters(),
			$this->getTransactionIdParameters(),
			$this->getTransactionAmountParameters(),
			$this->getTransactionDescriptionParameters(),
			$this->getReactionUrlParameters(),
			$this->getCreateTokenParameters(),
			$this->getCustomerAddressParameters(),
			$this->getLanguageParameters(),
			$this->getBasketParamerters(),
			$this->getAccountTypeParameters(),
			$this->getGiftAidParameters(),
			$paymentMethod->getAuthorizationParameters(
				$this->getTransaction(), 
				$this->getFormData(), 
				$this->getTransaction()->getAuthorizationMethod(),
				$this->container
			)
		);

		return $parameters;
	}
	
	protected function getReactionUrlParameters(){
		$notificationUrl = $this->container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "server",
				array(
					'cw_transaction_id' => $this->getTransaction()->getExternalTransactionId()
				));
		
		return array(
			'NotificationURL' => $notificationUrl
		);
	}
}