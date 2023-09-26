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

require_once 'Customweb/Payment/BackendOperation/Form/Abstract.php';
require_once 'Customweb/Core/Url.php';
require_once 'Customweb/Form/Element.php';
require_once 'Customweb/Form/ElementGroup.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/Form/Control/Html.php';
require_once 'Customweb/Core/Http/Client/Factory.php';
require_once 'Customweb/Form/WideElement.php';
require_once 'Customweb/Core/Http/Request.php';



/**
 * @BackendForm
 */
class Customweb_SagePay_BackendOperation_Form_Setup extends Customweb_Payment_BackendOperation_Form_Abstract {

	public function getTitle(){
		return Customweb_I18n_Translation::__("Setup");
	}

	public function getElementGroups(){
		return array(
			$this->getSetupGroup(),
			$this->getIpElementGroup()
		);
	}

	private function getIpElementGroup(){
		$request = new Customweb_Core_Http_Request(new Customweb_Core_Url("http://www.customweb.com/my-ip.php"));
		$client = Customweb_Core_Http_Client_Factory::createClient();
		$response = $client->send($request);

		$ip = "";
		if ($response->getStatusCode() == 200) {
			$ip = $response->getBody();
		}
		else {
			$ip = Customweb_I18n_Translation::__("Couldn't obtain IP address.") . " HTTP Response Code: " . $response->getStatusCode();
		}

		$group = new Customweb_Form_ElementGroup();

		$control = new Customweb_Form_Control_Html('ipaddress', $ip);
		$element = new Customweb_Form_Element('IP Address', $control);
		$element->setDescription(
				Customweb_I18n_Translation::__("Please enter this IP in the Opayo Backend > Settings > Valid IPs"));
		$element->setRequired(false);

			$group->addElement($element) ;
			return $group;
	}

	private function getSetupGroup(){
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle(Customweb_I18n_Translation::__("Short Installation Instructions:"));

		$control = new Customweb_Form_Control_Html('description',
				Customweb_I18n_Translation::__(
						'This is a brief instruction of the main and most important installation steps, which need to be performed when installing the Opayo module. For detailed instructions regarding additional and optional settings, please refer to the enclosed instructions in the zip. '));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);

		$control = new Customweb_Form_Control_Html('steps', $this->createOrderedList($this->getSteps()));

		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		return $group;
	}

	private function getSteps(){
		return array(
			Customweb_I18n_Translation::__('Enter the Opayo Vendor Name.'),
			Customweb_I18n_Translation::__('Add your Server IP to the section Valid IP in the Opayo backend.'),
			Customweb_I18n_Translation::__('Activate the payment method and test. ')
		);
	}

	private function createOrderedList(array $steps){
		$list = '<ol>';
		foreach ($steps as $step) {
			$list .= "<li>$step</li>";
		}
		$list .= '</ol>';
		return $list;
	}
}