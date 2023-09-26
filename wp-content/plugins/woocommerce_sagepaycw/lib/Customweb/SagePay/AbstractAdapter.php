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

require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/SagePay/Configuration.php';
require_once 'Customweb/SagePay/Authorization/Transaction.php';

class Customweb_SagePay_AbstractAdapter
{
	/**
	 * Configuration object.
	 *
	 * @var Customweb_SagePay_Configuration
	 */
	private $configuration;
	protected $container;
	
	public function __construct(Customweb_Payment_IConfigurationAdapter $configuration, Customweb_DependencyInjection_IContainer $container) {
		$this->configuration = new Customweb_SagePay_Configuration($configuration);
		$this->container = $container;
	}
	
	/**
	 * Returns the configuration object.
	 *
	 * @return Customweb_SagePay_Configuration
	 */
	public function getConfiguration() {
		return $this->configuration;
	}
	
	
	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->getConfiguration()->getBaseUrl();
	}
}