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
require_once 'SagePayCw/Util.php';
require_once 'Customweb/Core/Url.php';
require_once 'SagePayCw/ContextRequest.php';
require_once 'SagePayCw/Controller/Abstract.php';



/**
 *
 * @author Nico Eigenmann
 *
 */
class SagePayCw_Controller_Failure extends SagePayCw_Controller_Abstract {

	public function indexAction(){
		$parameters = SagePayCw_ContextRequest::getInstance()->getParameters();
		$failedTransactionId = null;
		if (isset($parameters['cwtid'])) {
			$failedTransactionId = $parameters['cwtid'];
			$option = SagePayCw_Util::getCheckoutUrlPageId();
			try{
				$failedTransaction = $this->loadTransaction($parameters);
								
				if (!$failedTransaction->getTransactionObject()->isAuthorizationFailed()) {
					header('Location: ' . get_permalink(SagePayCw_Util::getPermalinkIdModified($option)));
					die();
				}
				$payment_page = Customweb_Core_Url::_(get_permalink(SagePayCw_Util::getPermalinkIdModified($option)))->appendQueryParameters(
					array(
						'sagepaycwftid' => $failedTransactionId,
						'sagepaycwftt' => SagePayCw_Util::computeTransactionValidateHash($failedTransaction)
					))->toString();
				header('Location: ' . $payment_page);
				die();
			}
			catch(Exception $e) {
				header('Location: ' . get_permalink(SagePayCw_Util::getPermalinkIdModified($option)));
				die();
			}			
		}
		
		else {
			$option = SagePayCw_Util::getCheckoutUrlPageId();
			header('Location: ' . get_permalink(SagePayCw_Util::getPermalinkIdModified($option)));
			die();
		}
	}
}