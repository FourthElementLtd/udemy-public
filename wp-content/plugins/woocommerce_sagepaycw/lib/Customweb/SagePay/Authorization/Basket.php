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

require_once 'Customweb/Payment/Authorization/IInvoiceItem.php';
require_once 'Customweb/Util/String.php';
require_once 'Customweb/Util/Currency.php';

class Customweb_SagePay_Authorization_Basket
{
	/**
	 * @var Customweb_Payment_Authorization_IOrderContext
	 */
	private $orderContext;
	
	public function __construct(Customweb_Payment_Authorization_IOrderContext $orderContext) {
		$this->orderContext = $orderContext;
	}
	
	public function getXmlRepresentation() {
		
		$xml = '<basket>';
		
		$discounts = $this->getDiscounts();
		$totals = $this->getProductTotals();
		$percentageIncl = 0;
		if ($discounts['discountInclTax'] > 0) {
			$percentageIncl = $discounts['discountInclTax'] / $totals['totalInclTax'];
		}
		
		$percentageExcl = 0;
		if ($discounts['discountExclTax'] > 0) {
			$percentageExcl = $discounts['discountExclTax'] / $totals['totalExclTax'];
		}
		
		
		// Line items
		foreach ($this->getOrderContext()->getInvoiceItems() as $item) {
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_PRODUCT || $item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE) {
				$xml .= $this->getLintItemXml($item, $percentageIncl, $percentageExcl);
			}
		}
		
		// Shipping costs
		$shippingCosts = $this->getShippingCosts();
		$xml .= '<deliveryNetAmount>' . $this->formatAmount($shippingCosts['costsExclTax']) . '</deliveryNetAmount>';
		$xml .= '<deliveryTaxAmount>' . $this->formatAmount($shippingCosts['costsInclTax'] - $shippingCosts['costsExclTax']) . '</deliveryTaxAmount>';
		$xml .= '<deliveryGrossAmount>' . $this->formatAmount($shippingCosts['costsInclTax']) . '</deliveryGrossAmount>';
	
		$xml .= '</basket>';
		
		return $xml;		
	}
	
	public function getBasicReperesentation() {
		
		$discounts = $this->getDiscounts();
		$totals = $this->getProductTotals();
		$percentageIncl = 0;
		if ($discounts['discountInclTax'] > 0) {
			$percentageIncl = $discounts['discountInclTax'] / $totals['totalInclTax'];
		}
		
		$percentageExcl = 0;
		if ($discounts['discountExclTax'] > 0) {
			$percentageExcl = $discounts['discountExclTax'] / $totals['totalExclTax'];
		}		
		
		$count = 0;
		$basket = '';
		foreach ($this->getOrderContext()->getInvoiceItems() as $item) {
			switch($item->getType()) {
				case Customweb_Payment_Authorization_IInvoiceItem::TYPE_PRODUCT:
				case Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE:
					$basket .= $this->getLineItemBasic($item, $percentageIncl, $percentageExcl).':';
					$count++;
					break;
				case Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING:
					$basket .= $this->getLineItemBasic($item, 0, 0).':';
					$count++;
					break;
				default:
					break;
			}
			
			
		}
		$basket = $count.':'.rtrim($basket, ':');
		return $basket;
		
		
	}
	
	protected function getLineItemBasic(Customweb_Payment_Authorization_IInvoiceItem $item, $percentageIncl, $percentageExcl) {
		$totalItemAmountInclTax = floatval($item->getAmountIncludingTax());
		$totalItemAmountExclTax = ($totalItemAmountInclTax / ($item->getTaxRate() / 100 + 1));
		
		$totalItemAmountInclTax = $totalItemAmountInclTax - $totalItemAmountInclTax * $percentageIncl;
		$totalItemAmountExclTax = $totalItemAmountExclTax - $totalItemAmountExclTax * $percentageExcl;
		
		$sku = $this->filterSku($item->getSku());
		$itemString = '';
		if(!empty($sku)) {
			$itemString .= '['.$sku.']';
		}
		// @formater:off
		
		$itemString .= $this->filterName($item->getName()).':'.
				$item->getQuantity().':'.
				$this->formatAmount($totalItemAmountExclTax / $item->getQuantity()).':'.
				$this->formatAmount(($totalItemAmountInclTax - $totalItemAmountExclTax) / $item->getQuantity()).':'.
				$this->formatAmount($totalItemAmountInclTax / $item->getQuantity()).':'.
				$this->formatAmount($totalItemAmountInclTax);
		// @formater:on
		return $itemString;
	}
	
	
	protected function formatAmount($amount) {
		return Customweb_Util_Currency::formatAmount($amount, $this->getOrderContext()->getCurrencyCode(), '.', '');
	}
	
	protected function getLintItemXml(Customweb_Payment_Authorization_IInvoiceItem $item, $percentageIncl, $percentageExcl) {
		$xml = '<item>';
		
		$totalItemAmountInclTax = floatval($item->getAmountIncludingTax());
		$totalItemAmountExclTax = ($totalItemAmountInclTax / ($item->getTaxRate() / 100 + 1));
		
		// Apply discounts:
		$totalItemAmountInclTax = $totalItemAmountInclTax - $totalItemAmountInclTax * $percentageIncl;
		$totalItemAmountExclTax = $totalItemAmountExclTax - $totalItemAmountExclTax * $percentageExcl;
		
		$sku = Customweb_Util_String::substrUtf8($this->filterSku($item->getSku()), 0, 12);
		
		$xml .= '<description>' . Customweb_Util_String::substrUtf8($this->filterName($item->getName()), 0, 100) . '</description>';
		if (!empty($sku)) {
			$xml .= '<productSku>' . $sku . '</productSku>';
		}
		$xml .= '<quantity>' . round(floatval($item->getQuantity())) . '</quantity>';
		$xml .= '<unitNetAmount>' . $this->formatAmount($totalItemAmountExclTax / $item->getQuantity()) . '</unitNetAmount>';
		$xml .= '<unitTaxAmount>' . $this->formatAmount(($totalItemAmountInclTax - $totalItemAmountExclTax) / $item->getQuantity()) . '</unitTaxAmount>';
		$xml .= '<unitGrossAmount>' . $this->formatAmount($totalItemAmountInclTax / $item->getQuantity()) . '</unitGrossAmount>';
		$xml .= '<totalGrossAmount>' . $this->formatAmount($totalItemAmountInclTax) . '</totalGrossAmount>';
		
		$xml .= '</item>';
		
		return $xml;
	}
	
	/**
	 * @return Customweb_Payment_Authorization_IOrderContext
	 */
	public function getOrderContext() {
		return $this->orderContext;
	}

	protected function filterName($string) {
		$string = strip_tags($string);
		$string = preg_replace('/[^A-Za-z0-9[:space:]-]+/', '', $string);
		$string = preg_replace('/[[:space:]]{2,}/', '', $string);
		$string = str_replace('&', '&amp;', $string);
		return $string;
	}

	protected function filterSku($string) {
		$string = strip_tags($string);
		$string = preg_replace('/[^A-Za-z0-9[:space:]-]+/', '', $string);
		$string = preg_replace('/[[:space:]]{2,}/', '', $string);
		return $string;
	}
	
	protected function getShippingCosts() {
	
		$costsExclTax = 0;
		$costsInclTax = 0;
		foreach ($this->getOrderContext()->getInvoiceItems() as $item) {
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING) {
	
				$costsExclTax += ($item->getAmountIncludingTax() / ($item->getTaxRate() / 100 + 1));
				$costsInclTax += $item->getAmountIncludingTax();
			}
		}
	
		return array(
			'costsExclTax' => $costsExclTax,
			'costsInclTax' => $costsInclTax,
		);
	
	}
	
	protected function getDiscounts() {
	
		$costsExclTax = 0;
		$costsInclTax = 0;
		foreach ($this->getOrderContext()->getInvoiceItems() as $item) {
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT) {
	
				$costsExclTax += ($item->getAmountIncludingTax() / ($item->getTaxRate() / 100 + 1));
				$costsInclTax += $item->getAmountIncludingTax();
			}
		}
	
		return array(
			'discountExclTax' => $costsExclTax,
			'discountInclTax' => $costsInclTax,
		);
	
	}
	
	protected function getProductTotals() {
	
		$costsExclTax = 0;
		$costsInclTax = 0;
		foreach ($this->getOrderContext()->getInvoiceItems() as $item) {
			if ($item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_PRODUCT
				|| $item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_FEE) {
	
				$costsExclTax += ($item->getAmountIncludingTax() / ($item->getTaxRate() / 100 + 1));
				$costsInclTax += $item->getAmountIncludingTax();
			}
		}
	
		return array(
			'totalExclTax' => $costsExclTax,
			'totalInclTax' => $costsInclTax,
		);
	
	}
	
}