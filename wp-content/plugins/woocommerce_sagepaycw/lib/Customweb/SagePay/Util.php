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

require_once 'Customweb/Util/Currency.php';
require_once 'Customweb/Http/Request.php';

/**
 * This util class some basic functions for SagePay.
 * 
 * @author Thomas Hunziker
 *
 */
final class Customweb_SagePay_Util {	
	
	private function __construct() {
		// prevent any instantiation of this class	
	}
	
	
	public static function formatAmount($amount, $currency) {
		return Customweb_Util_Currency::formatAmount($amount, $currency, '.', '');
	}

	
	public static function getCleanLanguageCode($lang) {
		$supportedLanguages = array('de_DE','en_US','fr_FR','da_DK',
			'cs_CZ','es_ES','hr_HR','it_IT','hu_HU','nl_NL',
			'no_NO','pl_PL','pt_PT','ru_RU','ro_RO','sk_SK',
			'sl_SI','fi_FI','sv_SE','tr_TR','el_GR','ja_JP'
		);
		return Customweb_Payment_Util::getCleanLanguageCode($lang,$supportedLanguages);
	}
	
	public static function sendRequest($url, $postParameters) {
		// Decode parameters
		$decoded = array();
		foreach ($postParameters as $key => $value) {
			$decoded[$key] = utf8_decode($value);
		}
		 
		$client = Customweb_Core_Http_Client_Factory::createClient();
		
		$request = new Customweb_Core_Http_Request($url);
		$request->setBody($decoded);
		$request->setMethod('POST');
		$request->setContentType('application/x-www-form-urlencoded');
		$response = $client->send($request);
		
		if ($response->getStatusCode() != '200') {
			throw new Exception("The server response with a invalid HTTP status code (status code != 200).");
		}

		$body = trim($response->getBody());
		$pairs = explode("\r\n", $body);
				
		$response = array();
		foreach ($pairs as $pair) {
			
			$start = strpos($pair, '=');
			if ($start === false) {
				throw new Exception("The response format is not valid. Response: " . $body);
			}
			$response[substr($pair, 0, $start)] = substr($pair, $start + 1);
		}
		
		return $response;
	}
	
	public static function parseToCRLF($parameters) {
		$output = '';
		foreach ($parameters as $key => $value) {
			$output .= $key . '=' . $value . "\r\n";
		}
		return $output;
	}	
}