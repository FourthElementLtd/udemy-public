<?php

require_once 'SagePayCw/BackendFormRenderer.php';
require_once 'Customweb/Util/Url.php';
require_once 'Customweb/Payment/Authorization/DefaultInvoiceItem.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICapture.php';
require_once 'Customweb/Form/Control/IEditableControl.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICancel.php';
require_once 'Customweb/IForm.php';
require_once 'Customweb/Form.php';
require_once 'Customweb/Core/Http/ContextRequest.php';
require_once 'Customweb/Form/Control/MultiControl.php';
require_once 'Customweb/Util/Currency.php';
require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/IRefund.php';
require_once 'Customweb/Licensing/SagePayCw/License.php';



// Make sure we don't expose any info if called directly       	    					   	 	
if (!function_exists('add_action')) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit();
}

// Add some CSS and JS for admin       	    					   	 	
function woocommerce_sagepaycw_admin_add_setting_styles_scripts($hook){
	if($hook != 'post.php' && $hook != 'dashboard_page_wpsc-purchase-logs' && stripos($hook,'woocommerce-sagepaycw') === false){
		return;
	}
	wp_register_style('woocommerce_sagepaycw_admin_styles', plugins_url('resources/css/settings.css', __FILE__));
	wp_enqueue_style('woocommerce_sagepaycw_admin_styles');
	
	wp_register_script('woocommerce_sagepaycw_admin_js', plugins_url('resources/js/settings.js', __FILE__));
	wp_enqueue_script('woocommerce_sagepaycw_admin_js');
}
add_action('admin_enqueue_scripts', 'woocommerce_sagepaycw_admin_add_setting_styles_scripts');

function woocommerce_sagepaycw_admin_notice_handler(){
	if (get_transient(get_current_user_id() . '_sagepaycw_am') !== false) {
		
		foreach (get_transient(get_current_user_id() . '_sagepaycw_am') as $message) {
			$cssClass = '';
			if (strtolower($message['type']) == 'error') {
				$cssClass = 'error';
			}
			else if (strtolower($message['type']) == 'info') {
				$cssClass = 'updated';
			}
			
			echo '<div class="' . $cssClass . '">';
			echo '<p>Opayo: ' . $message['message'] . '</p>';
			echo '</div>';
		}
		delete_transient(get_current_user_id() . '_sagepaycw_am');
	}
}
add_action('admin_notices', 'woocommerce_sagepaycw_admin_notice_handler');

function woocommerce_sagepaycw_admin_show_message($message, $type){
	$existing = array();
	if (get_transient(get_current_user_id() . '_sagepaycw_am') === false) {
		$existing = get_transient(get_current_user_id() . '_sagepaycw_am');
	}
	$existing[] = array(
		'message' => $message,
		'type' => $type 
	);
	set_transient(get_current_user_id() . '_sagepaycw_am', $existing);
}

/**
 * Add the configuration menu
 */
function woocommerce_sagepaycw_menu(){
	add_menu_page('Opayo', __('Opayo', 'woocommerce_sagepaycw'), 
			'manage_woocommerce', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw_options');
	
	if (isset($_REQUEST['page']) && strpos($_REQUEST['page'], 'woocommerce-sagepaycw') !== false) {
		$container = SagePayCw_Util::createContainer();
		if ($container->hasBean('Customweb_Payment_BackendOperation_Form_IAdapter')) {
			$adapter = $container->getBean('Customweb_Payment_BackendOperation_Form_IAdapter');
			foreach ($adapter->getForms() as $form) {
				add_submenu_page('woocommerce-sagepaycw', 'Opayo ' . $form->getTitle(), $form->getTitle(), 
						'manage_woocommerce', 'woocommerce-sagepaycw-' . $form->getMachineName(), 
						'woocommerce_sagepaycw_extended_options');
			}
		}
	}
	
	add_submenu_page(null, 'Opayo Capture', 'Opayo Capture', 'manage_woocommerce', 
			'woocommerce-sagepaycw_capture', 'woocommerce_sagepaycw_render_capture');
	add_submenu_page(null, 'Opayo Cancel', 'Opayo Cancel', 'manage_woocommerce', 
			'woocommerce-sagepaycw_cancel', 'woocommerce_sagepaycw_render_cancel');
	add_submenu_page(null, 'Opayo Refund', 'Opayo Refund', 'manage_woocommerce', 
			'woocommerce-sagepaycw_refund', 'woocommerce_sagepaycw_render_refund');
}
add_action('admin_menu', 'woocommerce_sagepaycw_menu');

function woocommerce_sagepaycw_render_cancel(){
	
	
	
	

	$request = Customweb_Core_Http_ContextRequest::getInstance();
	$query = $request->getParsedQuery();
	$post = $request->getParsedBody();
	$transactionId = $query['cwTransactionId'];
	
	if (empty($transactionId)) {
		wp_redirect(get_option('siteurl') . '/wp-admin');
		exit();
	}
	
	$transaction = SagePayCw_Util::getTransactionById($transactionId);
	$orderId = $transaction->getPostId();
	$url = str_replace('>orderId', $orderId, get_admin_url() . 'post.php?post=>orderId&action=edit');
	if ($request->getMethod() == 'POST') {
		if (isset($post['cancel'])) {
			$adapter = SagePayCw_Util::createContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_ICancel');
			if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_ICancel)) {
				throw new Exception("No adapter with interface 'Customweb_Payment_BackendOperation_Adapter_Service_ICancel' provided.");
			}
			
			try {
				$adapter->cancel($transaction->getTransactionObject());
				woocommerce_sagepaycw_admin_show_message(
						__("Successfully cancelled the transaction.", 'woocommerce_sagepaycw'), 'info');
			}
			catch (Exception $e) {
				woocommerce_sagepaycw_admin_show_message($e->getMessage(), 'error');
			}
			SagePayCw_Util::getEntityManager()->persist($transaction);
		}
		wp_redirect($url);
		exit();
	}
	else {
		if (!$transaction->getTransactionObject()->isCancelPossible()) {
			woocommerce_sagepaycw_admin_show_message(__('Cancel not possible', 'woocommerce_sagepaycw'), 'info');
			wp_redirect($url);
			exit();
		}
		if (isset($_GET['noheader'])) {
			require_once (ABSPATH . 'wp-admin/admin-header.php');
		}
		
		echo '<div class="wrap">';
		echo '<form method="POST" class="sagepaycw-line-item-grid" id="cancel-form">';
		echo '<table class="list">
				<tbody>';
		echo '<tr>
				<td class="left-align">' . __('Are you sure you want to cancel this transaction?', 'woocommerce_sagepaycw') . '</td>
			</tr>';
		echo '<tr>
				<td colspan="1" class="left-align"><a class="button" href="' . $url . '">' . __('No', 'woocommerce_sagepaycw') . '</a></td>
				<td colspan="1" class="right-align">
					<input class="button" type="submit" name="cancel" value="' . __('Yes', 'woocommerce_sagepaycw') . '" />
				</td>
			</tr>
								</tfoot>
			</table>
		</form>';
		
		echo '</div>';
	}
	
	
}

function woocommerce_sagepaycw_render_capture(){
	
	
	
	$request = Customweb_Core_Http_ContextRequest::getInstance();
	$query = $request->getParsedQuery();
	$post = $request->getParsedBody();
	$transactionId = $query['cwTransactionId'];
	
	if (empty($transactionId)) {
		wp_redirect(get_option('siteurl') . '/wp-admin');
		exit();
	}
	
	$transaction = SagePayCw_Util::getTransactionById($transactionId);
	$orderId = $transaction->getPostId();
	$url = str_replace('>orderId', $orderId, get_admin_url() . 'post.php?post=>orderId&action=edit');
	if ($request->getMethod() == 'POST') {
		
		if (isset($post['quantity'])) {
			
			$captureLineItems = array();
			$lineItems = $transaction->getTransactionObject()->getUncapturedLineItems();
			foreach ($post['quantity'] as $index => $quantity) {
				if (isset($post['price_including'][$index]) && floatval($post['price_including'][$index]) != 0) {
					$originalItem = $lineItems[$index];
					if ($originalItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$priceModifier = -1;
					}
					else {
						$priceModifier = 1;
					}
					$captureLineItems[$index] = new Customweb_Payment_Authorization_DefaultInvoiceItem($originalItem->getSku(), 
							$originalItem->getName(), $originalItem->getTaxRate(), $priceModifier * floatval($post['price_including'][$index]), 
							$quantity, $originalItem->getType());
				}
			}
			if (count($captureLineItems) > 0) {
				$adapter = SagePayCw_Util::createContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_ICapture');
				if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_ICapture)) {
					throw new Exception("No adapter with interface 'Customweb_Payment_BackendOperation_Adapter_Service_ICapture' provided.");
				}
				
				$close = false;
				if (isset($post['close']) && $post['close'] == 'on') {
					$close = true;
				}
				try {
					$adapter->partialCapture($transaction->getTransactionObject(), $captureLineItems, $close);
					woocommerce_sagepaycw_admin_show_message(
							__("Successfully added a new capture.", 'woocommerce_sagepaycw'), 'info');
				}
				catch (Exception $e) {
					woocommerce_sagepaycw_admin_show_message($e->getMessage(), 'error');
				}
				SagePayCw_Util::getEntityManager()->persist($transaction);
			}
		}
		
		wp_redirect($url);
		exit();
	}
	else {
		if (!$transaction->getTransactionObject()->isPartialCapturePossible()) {
			woocommerce_sagepaycw_admin_show_message(__('Capture not possible', 'woocommerce_sagepaycw'), 'info');
			
			wp_redirect($url);
			exit();
		}
		if (isset($_GET['noheader'])) {
			require_once (ABSPATH . 'wp-admin/admin-header.php');
		}
		
		echo '<div class="wrap">';
		echo '<form method="POST" class="sagepaycw-line-item-grid" id="capture-form">';
		echo '<input type="hidden" id="sagepaycw-decimal-places" value="' .
				 Customweb_Util_Currency::getDecimalPlaces($transaction->getTransactionObject()->getCurrencyCode()) . '" />';
		echo '<input type="hidden" id="sagepaycw-currency-code" value="' . strtoupper($transaction->getTransactionObject()->getCurrencyCode()) .
				 '" />';
		echo '<table class="list">
					<thead>
						<tr>
						<th class="left-align">' . __('Name', 'woocommerce_sagepaycw') . '</th>
						<th class="left-align">' . __('SKU', 'woocommerce_sagepaycw') . '</th>
						<th class="left-align">' . __('Type', 'woocommerce_sagepaycw') . '</th>
						<th class="left-align">' . __('Tax Rate', 'woocommerce_sagepaycw') . '</th>
						<th class="right-align">' . __('Quantity', 
				'woocommerce_sagepaycw') . '</th>
						<th class="right-align">' . __('Total Amount (excl. Tax)', 'woocommerce_sagepaycw') . '</th>
						<th class="right-align">' . __('Total Amount (incl. Tax)', 'woocommerce_sagepaycw') . '</th>
						</tr>
				</thead>
				<tbody>';
		foreach ($transaction->getTransactionObject()->getUncapturedLineItems() as $index => $item) {
			
			$amountExcludingTax = Customweb_Util_Currency::formatAmount($item->getAmountExcludingTax(), 
					$transaction->getTransactionObject()->getCurrencyCode());
			$amountIncludingTax = Customweb_Util_Currency::formatAmount($item->getAmountIncludingTax(), 
					$transaction->getTransactionObject()->getCurrencyCode());
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
				$amountExcludingTax = $amountExcludingTax * -1;
				$amountIncludingTax = $amountIncludingTax * -1;
			}
			echo '<tr id="line-item-row-' . $index . '" class="line-item-row" data-line-item-index="' . $index, '" >
						<td class="left-align">' . $item->getName() . '</td>
						<td class="left-align">' . $item->getSku() . '</td>
						<td class="left-align">' . $item->getType() . '</td>
						<td class="left-align">' . round($item->getTaxRate(), 2) . ' %<input type="hidden" class="tax-rate" value="' . $item->getTaxRate() . '" /></td>
						<td class="right-align"><input type="text" class="line-item-quantity" name="quantity[' . $index . ']" value="' . $item->getQuantity() . '" /></td>
						<td class="right-align"><input type="text" class="line-item-price-excluding" name="price_excluding[' . $index . ']" value="' .
					 $amountExcludingTax . '" /></td>
						<td class="right-align"><input type="text" class="line-item-price-including" name="price_including[' . $index . ']" value="' .
					 $amountIncludingTax . '" /></td>
					</tr>';
		}
		echo '</tbody>
				<tfoot>
					<tr>
						<td colspan="6" class="right-align">' . __('Total Capture Amount', 'woocommerce_sagepaycw') . ':</td>
						<td id="line-item-total" class="right-align">' . Customweb_Util_Currency::formatAmount(
				$transaction->getTransactionObject()->getCapturableAmount(), $transaction->getTransactionObject()->getCurrencyCode()) .
				 strtoupper($transaction->getTransactionObject()->getCurrencyCode()) . '
					</tr>';
		
		if ($transaction->getTransactionObject()->isCaptureClosable()) {
			
			echo '<tr>
					<td colspan="7" class="right-align">
						<label for="close-transaction">' . __('Close transaction for further captures', 'woocommerce_sagepaycw') . '</label>
						<input id="close-transaction" type="checkbox" name="close" value="on" />
					</td>
				</tr>';
		}
		
		echo '<tr>
				<td colspan="2" class="left-align"><a class="button" href="' . $url . '">' . __('Back', 'woocommerce_sagepaycw') . '</a></td>
				<td colspan="5" class="right-align">
					<input class="button" type="submit" value="' . __('Capture', 'woocommerce_sagepaycw') . '" />
				</td>
			</tr>
			</tfoot>
			</table>
		</form>';
		
		echo '</div>';
	}
	
	
}

function woocommerce_sagepaycw_render_refund(){
	
	
	
	$request = Customweb_Core_Http_ContextRequest::getInstance();
	$query = $request->getParsedQuery();
	$post = $request->getParsedBody();
	$transactionId = $query['cwTransactionId'];
	
	if (empty($transactionId)) {
		wp_redirect(get_option('siteurl') . '/wp-admin');
		exit();
	}
	
	$transaction = SagePayCw_Util::getTransactionById($transactionId);
	$orderId = $transaction->getPostId();
	$url = str_replace('>orderId', $orderId, get_admin_url() . 'post.php?post=>orderId&action=edit');
	if ($request->getMethod() == 'POST') {
		
		if (isset($post['quantity'])) {
			
			$refundLineItems = array();
			$lineItems = $transaction->getTransactionObject()->getNonRefundedLineItems();
			foreach ($post['quantity'] as $index => $quantity) {
				if (isset($post['price_including'][$index]) && floatval($post['price_including'][$index]) != 0) {
					$originalItem = $lineItems[$index];
					if ($originalItem->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
						$priceModifier = -1;
					}
					else {
						$priceModifier = 1;
					}
					$refundLineItems[$index] = new Customweb_Payment_Authorization_DefaultInvoiceItem($originalItem->getSku(), 
							$originalItem->getName(), $originalItem->getTaxRate(), $priceModifier * floatval($post['price_including'][$index]), 
							$quantity, $originalItem->getType());
				}
			}
			if (count($refundLineItems) > 0) {
				$adapter = SagePayCw_Util::createContainer()->getBean('Customweb_Payment_BackendOperation_Adapter_Service_IRefund');
				if (!($adapter instanceof Customweb_Payment_BackendOperation_Adapter_Service_IRefund)) {
					throw new Exception("No adapter with interface 'Customweb_Payment_BackendOperation_Adapter_Service_IRefund' provided.");
				}
				
				$close = false;
				if (isset($post['close']) && $post['close'] == 'on') {
					$close = true;
				}
				try {
					$adapter->partialRefund($transaction->getTransactionObject(), $refundLineItems, $close);
					woocommerce_sagepaycw_admin_show_message(
							__("Successfully added a new refund.", 'woocommerce_sagepaycw'), 'info');
				}
				catch (Exception $e) {
					woocommerce_sagepaycw_admin_show_message($e->getMessage(), 'error');
				}
				SagePayCw_Util::getEntityManager()->persist($transaction);
			}
		}
		wp_redirect($url);
		exit();
	}
	else {
		if (!$transaction->getTransactionObject()->isPartialRefundPossible()) {
			woocommerce_sagepaycw_admin_show_message(__('Refund not possible', 'woocommerce_sagepaycw'), 'info');
			wp_redirect($url);
			exit();
		}
		if (isset($query['noheader'])) {
			require_once (ABSPATH . 'wp-admin/admin-header.php');
		}
		
		echo '<div class="wrap">';
		echo '<form method="POST" class="sagepaycw-line-item-grid" id="refund-form">';
		echo '<input type="hidden" id="sagepaycw-decimal-places" value="' .
				 Customweb_Util_Currency::getDecimalPlaces($transaction->getTransactionObject()->getCurrencyCode()) . '" />';
		echo '<input type="hidden" id="sagepaycw-currency-code" value="' . strtoupper($transaction->getTransactionObject()->getCurrencyCode()) .
				 '" />';
		echo '<table class="list">
					<thead>
						<tr>
						<th class="left-align">' . __('Name', 'woocommerce_sagepaycw') . '</th>
						<th class="left-align">' . __('SKU', 'woocommerce_sagepaycw') . '</th>
						<th class="left-align">' . __('Type', 'woocommerce_sagepaycw') . '</th>
						<th class="left-align">' . __('Tax Rate', 'woocommerce_sagepaycw') . '</th>
						<th class="right-align">' . __('Quantity', 
				'woocommerce_sagepaycw') . '</th>
						<th class="right-align">' . __('Total Amount (excl. Tax)', 'woocommerce_sagepaycw') . '</th>
						<th class="right-align">' . __('Total Amount (incl. Tax)', 'woocommerce_sagepaycw') . '</th>
						</tr>
				</thead>
				<tbody>';
		foreach ($transaction->getTransactionObject()->getNonRefundedLineItems() as $index => $item) {
			$amountExcludingTax = Customweb_Util_Currency::formatAmount($item->getAmountExcludingTax(), 
					$transaction->getTransactionObject()->getCurrencyCode());
			$amountIncludingTax = Customweb_Util_Currency::formatAmount($item->getAmountIncludingTax(), 
					$transaction->getTransactionObject()->getCurrencyCode());
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
				$amountExcludingTax = $amountExcludingTax * -1;
				$amountIncludingTax = $amountIncludingTax * -1;
			}
			echo '<tr id="line-item-row-' . $index . '" class="line-item-row" data-line-item-index="' . $index, '" >
					<td class="left-align">' . $item->getName() . '</td>
					<td class="left-align">' . $item->getSku() . '</td>
					<td class="left-align">' . $item->getType() . '</td>
					<td class="left-align">' . round($item->getTaxRate(), 2) . ' %<input type="hidden" class="tax-rate" value="' . $item->getTaxRate() . '" /></td>
					<td class="right-align"><input type="text" class="line-item-quantity" name="quantity[' . $index . ']" value="' . $item->getQuantity() . '" /></td>
					<td class="right-align"><input type="text" class="line-item-price-excluding" name="price_excluding[' . $index . ']" value="' .
					 $amountExcludingTax . '" /></td>
					<td class="right-align"><input type="text" class="line-item-price-including" name="price_including[' . $index . ']" value="' .
					 $amountIncludingTax . '" /></td>
				</tr>';
		}
		echo '</tbody>
				<tfoot>
					<tr>
						<td colspan="6" class="right-align">' . __('Total Refund Amount', 'woocommerce_sagepaycw') . ':</td>
						<td id="line-item-total" class="right-align">' . Customweb_Util_Currency::formatAmount(
				$transaction->getTransactionObject()->getRefundableAmount(), $transaction->getTransactionObject()->getCurrencyCode()) .
				 strtoupper($transaction->getTransactionObject()->getCurrencyCode()) . '
						</tr>';
		
		if ($transaction->getTransactionObject()->isRefundClosable()) {
			echo '<tr>
					<td colspan="7" class="right-align">
						<label for="close-transaction">' . __('Close transaction for further refunds', 'woocommerce_sagepaycw') . '</label>
						<input id="close-transaction" type="checkbox" name="close" value="on" />
					</td>
				</tr>';
		}
		
		echo '<tr>
				<td colspan="2" class="left-align"><a class="button" href="' . $url . '">' . __('Back', 'woocommerce_sagepaycw') . '</a></td>
				<td colspan="5" class="right-align">
					<input class="button" type="submit" value="' . __('Refund', 'woocommerce_sagepaycw') . '" />
				</td>
			</tr>
		</tfoot>
		</table>
		</form>';
		
		echo '</div>';
	}
	
	
}

function woocommerce_sagepaycw_extended_options(){
	$container = SagePayCw_Util::createContainer();
	$request = Customweb_Core_Http_ContextRequest::getInstance();
	$query = $request->getParsedQuery();
	$formName = substr($query['page'], strlen('woocommerce-sagepaycw-'));
	
	$renderer = new SagePayCw_BackendFormRenderer();
	
	if ($container->hasBean('Customweb_Payment_BackendOperation_Form_IAdapter')) {
		$adapter = $container->getBean('Customweb_Payment_BackendOperation_Form_IAdapter');
		
		foreach ($adapter->getForms() as $form) {
			if ($form->getMachineName() == $formName) {
				$currentForm = $form;
				break;
			}
		}
		if ($currentForm === null) {
			if (isset($query['noheader'])) {
				require_once (ABSPATH . 'wp-admin/admin-header.php');
			}
			return;
		}
		
		if ($request->getMethod() == 'POST') {
			
			$pressedButton = null;
			$body = stripslashes_deep($request->getParsedBody());
			foreach ($form->getButtons() as $button) {
				
				if (array_key_exists($button->getMachineName(), $body['button'])) {
					$pressedButton = $button;
					break;
				}
			}
			$formData = array();
			foreach ($form->getElements() as $element) {
				$control = $element->getControl();
				if (!($control instanceof Customweb_Form_Control_IEditableControl)) {
					continue;
				}
				$dataValue = $control->getFormDataValue($body);
				if ($control instanceof Customweb_Form_Control_MultiControl) {
					foreach (woocommerce_sagepaycw_array_flatten($dataValue) as $key => $value) {
						$formData[$key] = $value;
					}
				}
				else {
					$nameAsArray = $control->getControlNameAsArray();
					if (count($nameAsArray) > 1) {
						$tmpArray = array(
							$nameAsArray[count($nameAsArray) - 1] => $dataValue 
						);
						$iterator = count($nameAsArray) - 2;
						while ($iterator > 0) {
							$tmpArray = array(
								$nameAsArray[$iterator] => $tmpArray 
							);
							$iterator--;
						}
						if (isset($formData[$nameAsArray[0]])) {
							$formData[$nameAsArray[0]] = array_merge_recursive($formData[$nameAsArray[0]], $tmpArray);
						}
						else {
							$formData[$nameAsArray[0]] = $tmpArray;
						}
					}
					else {
						$formData[$control->getControlName()] = $dataValue;
					}
				}
			}
			$adapter->processForm($currentForm, $pressedButton, $formData);
			wp_redirect(Customweb_Util_Url::appendParameters(get_admin_url(null,'admin.php'), $request->getParsedQuery()));
			die();
		}
		
		if (isset($query['noheader'])) {
			require_once (ABSPATH . 'wp-admin/admin-header.php');
		}
		
		$currentForm = null;
		foreach ($adapter->getForms() as $form) {
			if ($form->getMachineName() == $formName) {
				$currentForm = $form;
				break;
			}
		}
		
		if ($currentForm->isProcessable()) {
			$currentForm = new Customweb_Form($currentForm);
			$currentForm->setRequestMethod(Customweb_IForm::REQUEST_METHOD_POST);
			$currentForm->setTargetUrl(
					Customweb_Util_Url::appendParameters(get_admin_url(null,'admin.php'), 
							array_merge($request->getParsedQuery(), array(
								'noheader' => 'true' 
							))));
		}
		echo '<div class="wrap">';
		echo $renderer->renderForm($currentForm);
		echo '</div>';
	}
}

function woocommerce_sagepaycw_array_flatten($array){
	$return = array();
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			$return = array_merge($return, woocommerce_sagepaycw_array_flatten($value));
		}
		else {
			$return[$key] = $value;
		}
	}
	return $return;
}

/**
 * Setup the configuration page with the callbacks to the configuration API.
 */
function woocommerce_sagepaycw_options(){
	if (!current_user_can('manage_woocommerce')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	require_once 'Customweb/Licensing/SagePayCw/License.php';
Customweb_Licensing_SagePayCw_License::run('i9ihipu38vr06bl9');
	echo '<div class="wrap">';
	
	echo '<form method="post" action="options.php" enctype="multipart/form-data">';
	settings_fields('woocommerce-sagepaycw');
	do_settings_sections('woocommerce-sagepaycw');
	
	echo '<p class="submit">';
	echo '<input type="submit" name="submit" id="submit" class="button-primary" value="' . __('Save Changes') . '" />';
	echo '</p>';
	
	echo '</form>';
	echo '</div>';
}



/**
 * Register Settings
 */
function woocommerce_sagepaycw_admin_init(){
	add_settings_section('woocommerce_sagepaycw', 'Opayo Basics', 
			'woocommerce_sagepaycw_section_callback', 'woocommerce-sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_vendor');
	
	add_settings_field('woocommerce_sagepaycw_vendor', __("Opayo Vender Name", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_vendor', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_operation_mode');
	
	add_settings_field('woocommerce_sagepaycw_operation_mode', __("Operation Mode", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_operation_mode', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_deferred_authorization_type');
	
	add_settings_field('woocommerce_sagepaycw_deferred_authorization_type', __("Deferred Authorization Type", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_deferred_authorization_type', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_direct_capture_type');
	
	add_settings_field('woocommerce_sagepaycw_direct_capture_type', __("Direct Capture Type", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_direct_capture_type', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_description');
	
	add_settings_field('woocommerce_sagepaycw_description', __("Description of the order", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_description', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_transaction_id_schema');
	
	add_settings_field('woocommerce_sagepaycw_transaction_id_schema', __("Transaction ID Prefix", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_transaction_id_schema', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_send_basket');
	
	add_settings_field('woocommerce_sagepaycw_send_basket', __("Basket", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_send_basket', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_gift_aid');
	
	add_settings_field('woocommerce_sagepaycw_gift_aid', __("Gift Aid", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_gift_aid', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_T3M');
	
	add_settings_field('woocommerce_sagepaycw_T3M', __("The 3rd Man", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_T3M', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_username');
	
	add_settings_field('woocommerce_sagepaycw_username', __("Username", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_username', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_password');
	
	add_settings_field('woocommerce_sagepaycw_password', __("Password", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_password', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_threed_version');
	
	add_settings_field('woocommerce_sagepaycw_threed_version', __("3D Version", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_threed_version', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_review_input_form');
	
	add_settings_field('woocommerce_sagepaycw_review_input_form', __("Review Input Form", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_review_input_form', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_order_identifier');
	
	add_settings_field('woocommerce_sagepaycw_order_identifier', __("Order Identifier", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_order_identifier', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	register_setting('woocommerce-sagepaycw', 'woocommerce_sagepaycw_log_level');
	
	add_settings_field('woocommerce_sagepaycw_log_level', __("Log Level", 'woocommerce_sagepaycw'), 'woocommerce_sagepaycw_option_callback_log_level', 'woocommerce-sagepaycw', 'woocommerce_sagepaycw');
	
}
add_action('admin_init', 'woocommerce_sagepaycw_admin_init');

function woocommerce_sagepaycw_section_callback(){}



function woocommerce_sagepaycw_option_callback_vendor() {
	echo '<input type="text" name="woocommerce_sagepaycw_vendor" value="' . htmlspecialchars(get_option('woocommerce_sagepaycw_vendor', ''),ENT_QUOTES) . '" />';
	
	echo '<br />';
	echo __("Used to authenticate your site. This should contain the Opayo Vendor Name supplied by Opayo when your account was created.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_operation_mode() {
	echo '<select name="woocommerce_sagepaycw_operation_mode">';
		echo '<option value="test"';
		 if (get_option('woocommerce_sagepaycw_operation_mode', "test") == "test"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Test Mode", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="live"';
		 if (get_option('woocommerce_sagepaycw_operation_mode', "test") == "live"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Live Mode", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("You can switch between the different environments, by selecting the corresponding operation mode.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_deferred_authorization_type() {
	echo '<select name="woocommerce_sagepaycw_deferred_authorization_type">';
		echo '<option value="deferred"';
		 if (get_option('woocommerce_sagepaycw_deferred_authorization_type', "deferred") == "deferred"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Use normal deferred authorization", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="authenticate"';
		 if (get_option('woocommerce_sagepaycw_deferred_authorization_type', "deferred") == "authenticate"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Use authenticate authorization", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Opayo supports two types of deferred authorization. The deferred authorization allows only one capture per transaction, but it guarantees the payment, because a reservation is added on the customer's card. In case of authenticate you can do multiple captures per transaction, but there is no reservation of the amount on the card.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_direct_capture_type() {
	echo '<select name="woocommerce_sagepaycw_direct_capture_type">';
		echo '<option value="single"';
		 if (get_option('woocommerce_sagepaycw_direct_capture_type', "two_step") == "single"){
			echo ' selected="selected" ';
		}
	echo '>' . __("During Authorization", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="two_step"';
		 if (get_option('woocommerce_sagepaycw_direct_capture_type', "two_step") == "two_step"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Two Step", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Here you can select how the direct capture process is done. Either we first authorize the transaction and capture it automatically later (Two Step). Or it is done immediately within the authorization (During authorization). During the authorization means we use the transaction Type 'PAYMENT', we also process the feedback from Opayo immediately. This can lead to issues, if your shop takes a long time to process an order. (e.g. send confirmation mail, update stock, etc.) Two Step uses the Transaction Type 'DEFERRED'.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_description() {
	echo '<textarea name="woocommerce_sagepaycw_description">' . get_option('woocommerce_sagepaycw_description', 'Your order description') . '</textarea>';
	
	echo '<br />';
	echo __("The description of goods purchased is displayed on the Opayo Server payment page as the customer enters their card details.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_transaction_id_schema() {
	echo '<input type="text" name="woocommerce_sagepaycw_transaction_id_schema" value="' . htmlspecialchars(get_option('woocommerce_sagepaycw_transaction_id_schema', 'order_{id}'),ENT_QUOTES) . '" />';
	
	echo '<br />';
	echo __("Here you can insert a transaction prefix. The prefix allows you to change the transaction number that is transmitted to Opayo. The prefix must contain the tag {id}. It will then be replaced by the order number (e.g. name_{id}).", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_send_basket() {
	echo '<select name="woocommerce_sagepaycw_send_basket">';
		echo '<option value="xml"';
		 if (get_option('woocommerce_sagepaycw_send_basket', "none") == "xml"){
			echo ' selected="selected" ';
		}
	echo '>' . __("XML", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="basic"';
		 if (get_option('woocommerce_sagepaycw_send_basket', "none") == "basic"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Basic", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="none"';
		 if (get_option('woocommerce_sagepaycw_send_basket', "none") == "none"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Do not send basket", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("During the checkout the basket can be sent to Opayo. It can be sent as XML, Basic.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_gift_aid() {
	echo '<select name="woocommerce_sagepaycw_gift_aid">';
		echo '<option value="enabled"';
		 if (get_option('woocommerce_sagepaycw_gift_aid', "disabled") == "enabled"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Enabled", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="disabled"';
		 if (get_option('woocommerce_sagepaycw_gift_aid', "disabled") == "disabled"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Disabled", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("By enabling the gife aid option the customer can ticke a box during the checkout process to indicate she or he wish to donate the tax.This option requires that the your Opayo account has enabled the gift aid option.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_T3M() {
	echo '<select name="woocommerce_sagepaycw_T3M">';
		echo '<option value="on"';
		 if (get_option('woocommerce_sagepaycw_T3M', "off") == "on"){
			echo ' selected="selected" ';
		}
	echo '>' . __("On", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="off"';
		 if (get_option('woocommerce_sagepaycw_T3M', "off") == "off"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Off", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Should results from The 3rd Man fraud screening be polled and saved on the transaction?", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_username() {
	echo '<input type="text" name="woocommerce_sagepaycw_username" value="' . htmlspecialchars(get_option('woocommerce_sagepaycw_username', ''),ENT_QUOTES) . '" />';
	
	echo '<br />';
	echo __("The username used for administrative requests.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_password() {
	echo '<input type="password" name="woocommerce_sagepaycw_password" value="' . htmlspecialchars(get_option('woocommerce_sagepaycw_password', ''),ENT_QUOTES) . '" />';
	
	echo '<br />';
	echo __("The password used for administrative requests.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_threed_version() {
	echo '<select name="woocommerce_sagepaycw_threed_version">';
		echo '<option value="v1"';
		 if (get_option('woocommerce_sagepaycw_threed_version', "v1") == "v1"){
			echo ' selected="selected" ';
		}
	echo '>' . __("3D v1 (VPSProtocol 3.00)", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="v2"';
		 if (get_option('woocommerce_sagepaycw_threed_version', "v1") == "v2"){
			echo ' selected="selected" ';
		}
	echo '>' . __("3D v2 (VPSProtocol 4.00)", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Should we request 3D v1 or 3D v2? Please contact Opayo support directly for information on which to configure.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_review_input_form() {
	echo '<select name="woocommerce_sagepaycw_review_input_form">';
		echo '<option value="active"';
		 if (get_option('woocommerce_sagepaycw_review_input_form', "active") == "active"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Activate input form in review pane.", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="deactivate"';
		 if (get_option('woocommerce_sagepaycw_review_input_form', "active") == "deactivate"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Deactivate input form in review pane.", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Should the input form for credit card data rendered in the review pane? To work the user must have JavaScript activated. In case the browser does not support JavaScript a fallback is provided. This feature is not supported by all payment methods.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_order_identifier() {
	echo '<select name="woocommerce_sagepaycw_order_identifier">';
		echo '<option value="postid"';
		 if (get_option('woocommerce_sagepaycw_order_identifier', "ordernumber") == "postid"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Post ID of the order", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="ordernumber"';
		 if (get_option('woocommerce_sagepaycw_order_identifier', "ordernumber") == "ordernumber"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Order number", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Set which identifier should be sent to the payment service provider. If a plugin modifies the order number and can not guarantee it's uniqueness, select Post Id.", 'woocommerce_sagepaycw');
}

function woocommerce_sagepaycw_option_callback_log_level() {
	echo '<select name="woocommerce_sagepaycw_log_level">';
		echo '<option value="error"';
		 if (get_option('woocommerce_sagepaycw_log_level', "error") == "error"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Error", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="info"';
		 if (get_option('woocommerce_sagepaycw_log_level', "error") == "info"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Info", 'woocommerce_sagepaycw'). '</option>';
	echo '<option value="debug"';
		 if (get_option('woocommerce_sagepaycw_log_level', "error") == "debug"){
			echo ' selected="selected" ';
		}
	echo '>' . __("Debug", 'woocommerce_sagepaycw'). '</option>';
	echo '</select>';
	echo '<br />';
	echo __("Messages of this or a higher level will be logged.", 'woocommerce_sagepaycw');
}

