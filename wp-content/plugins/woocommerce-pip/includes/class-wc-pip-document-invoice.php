<?php
/**
 * WooCommerce Print Invoices/Packing Lists
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Print
 * Invoices/Packing Lists to newer versions in the future. If you wish to
 * customize WooCommerce Print Invoices/Packing Lists for your needs please refer
 * to http://docs.woocommerce.com/document/woocommerce-print-invoice-packing-list/
 *
 * @package   WC-Print-Invoices-Packing-Lists/Document/Invoice
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * PIP Invoice class
 *
 * Invoice document object
 *
 * @since 3.0.0
 */
class WC_PIP_Document_Invoice extends WC_PIP_Document {


	/**
	 * PIP Invoice document constructor
	 *
	 * @since 3.0.0
	 * @param array $args
	 */
	public function __construct( array $args ) {

		parent::__construct( $args );

		$this->type        = 'invoice';
		$this->name        = __( 'Invoice', 'woocommerce-pip' );
		$this->name_plural = __( 'Invoices', 'woocommerce-pip' );

		$this->table_headers = array(
			'sku'      => __( 'SKU' , 'woocommerce-pip' ),
			'product'  => __( 'Product' , 'woocommerce-pip' ),
			'quantity' => __( 'Quantity' , 'woocommerce-pip' ),
			'price'    => __( 'Price' , 'woocommerce-pip' ),
			'id'       => '', // leave this blank
		);

		$this->column_widths = array(
			'sku'      => 20,
			'product'  => 53,
			'quantity' => 10,
			'price'    => 17,
		);

		$this->show_billing_address      = true;
		$this->show_shipping_address     = true;
		$this->show_terms_and_conditions = true;
		$this->show_header               = true;
		$this->show_footer               = true;
		$this->show_shipping_method      = 'yes' === get_option( 'wc_pip_invoice_show_shipping_method', 'yes' );
		$this->show_coupons_used         = 'yes' === get_option( 'wc_pip_invoice_show_coupons', 'yes' );
		$this->show_customer_details     = 'yes' === get_option( 'wc_pip_invoice_show_customer_details', 'yes' );
		$this->show_customer_note        = 'yes' === get_option( 'wc_pip_invoice_show_customer_note', 'yes' );

		// customize the header of the document
		add_action( 'wc_pip_header', array( $this, 'document_header' ), 1, 4 );

		// add a "View Invoice" link on order processing/complete emails sent to customer
		add_action( 'woocommerce_email_order_meta', array( $this, 'order_paid_email_view_invoice_link' ), 40, 3 );
	}


	/**
	 * Document header
	 *
	 * @since 3.0.0
	 * @param string $type Document type
	 * @param string $action Document action
	 * @param WC_PIP_Document $document Document object
	 * @param WC_Order $order Order object
	 */
	public function document_header( $type, $action, $document, $order ) {

		$order_id = SV_WC_Order_Compatibility::get_prop( $order, 'id' );

		// prevent duplicating this content in bulk actions
		if ( ! $order_id || 'invoice' !== $type || ( ( (int) $order_id !== (int) $this->order_id ) && has_action( 'wc_pip_header', array( $this, 'document_header' ) ) ) ) {
			return;
		}

		$view_order_url      = wc_get_endpoint_url( 'view-order', $order_id,  get_permalink( wc_get_page_id( 'myaccount' ) ) );
		$invoice_number      = $document->get_invoice_number();
		$invoice_number_html = '<span class="invoice-number">' . $invoice_number . '</span>';
		$order_number        = $order->get_order_number();

		if ( 'send_email' !== $action ) {
			$order_number_html = '<a class="order-number hidden-print" href="' . $view_order_url . '" target="_blank">' . $order_number . '</a>' . '<span class="order-number visible-print-inline">' . $order_number . '</span>';
		} else {
			$order_number_html = '<span class="order-number">' . $order_number . '</span>';
		}

		// note: this is deliberately loose, do not use !== to compare invoice number and order number
		if ( 'yes' !== get_option( 'wc_pip_use_order_number', 'no' ) || $invoice_number != $order_number ) {
			/* translators: Placeholders:  %1$s - invoice number, %2$s - order number */
			$heading = sprintf( '<h3 class="order-info">' . esc_html__( 'Invoice %1$s for order %2$s', 'woocommerce-pip' ) . '</h3>', $invoice_number_html, $order_number_html );
		} else {
			/* translators: Placeholder: %s - order number */
			$heading = sprintf( '<h3 class="order-info">' . esc_html__( 'Invoice for order %s', 'woocommerce-pip' ) . '</h3>', $order_number_html );
		}

		if ( $date_created = SV_WC_Order_Compatibility::get_date_created( $order ) ) {

			/* translators: Placeholder:  %s - order date */
			$heading .= sprintf( '<h5 class="order-date">' . esc_html__( 'Order Date: %s', 'woocommerce-pip' ) . '</h5>', $date_created->date_i18n( wc_date_format() ) );
		}

		/**
		 * Filter the document heading
		 *
		 * @see wc_pip_get_merge_tags() for a list of merge tags supported
		 *
		 * @since 3.0.5
		 * @param string $heading the heading text, supports also merge tags
		 * @param string $type \WC_PIP_Document type
		 * @param string $action if the document is printed or sent by email ('print' or 'send_email')
		 * @param \WC_Order $order the order associated to this document
		 * @param string $invoice_number the invoice number
		 */
		echo wc_pip_parse_merge_tags( apply_filters( 'wc_pip_document_heading', $heading, $type, $action, $order, $invoice_number ), $type, $order );
	}


	/**
	 * Get order item data
	 *
	 * @since 3.0.0
	 * @param string $item_id Item id
	 * @param array $item Item data
	 * @param WC_Product $product Product object
	 * @return array
	 */
	protected function get_order_item_data( $item_id, $item, $product ) {

		$item_meta = $this->get_order_item_meta_html( $item_id, $item, $product );

		/**
		 * Filters the document table cells.
		 *
		 * @since 3.0.0
		 * @param string $table_row_cells The table row cells.
		 * @param string $type WC_PIP_Document type
		 * @param string $item_id Item id
		 * @param array $item Item data
		 * @param \WC_Product $product Product object
		 * @param \WC_Order $order Order object
		 */
		return apply_filters( 'wc_pip_document_table_row_cells', array(
			'sku'      => $this->get_order_item_sku_html( $product, $item ),
			'product'  => $this->get_order_item_name_html( $product, $item ) . ( $item_meta ? '<br>' . $item_meta : '' ),
			'quantity' => $this->get_order_item_quantity_html( $item_id, $item ),
			'price'    => $this->get_order_item_price_html( $item_id, $item ),
			'id'       => $this->get_order_item_id_html( $item_id ),
		), $this->type, $item_id, $item, $product, $this->order );
	}


	/**
	 * Returns the table footer.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_table_footer() {

		$rows = array();

		if ( $this->order instanceof WC_Order ) {


			// Normalize order item totals
			foreach ( $this->order->get_order_item_totals() as $key => $data ) {

				$rows[ $key ] = array(
					$key    => '<strong class="order-' . $key . '">' . $data['label'] . '</strong>',
					'value' => $data['value'],
				);
			}

			/**
			 * Filters the document table footer.
			 *
			 * @since 3.0.0
			 *
			 * @param array $rows Footer rows and cells
			 * @param string $type PIP Document type
			 * @param int $order_id \WC_Order id
			 */
			$rows = apply_filters( 'wc_pip_document_table_footer', $rows, $this->type, $this->order_id );
		}

		return $rows;
	}


	/**
	 * Get a URL to display and print an invoice
	 *
	 * @since 3.0.0
	 *
	 * @param string $context Generate link for context. Use 'admin' for admin or 'myaccount' frontend
	 * @return string Unescaped URL
	 */
	public function get_print_invoice_url( $context = 'admin' ) {

		if ( ! $this->order instanceof WC_Order ) {
			return '';
		}

		return wp_nonce_url( add_query_arg( array(
				'wc_pip_action'   => 'print',
				'wc_pip_document' => 'invoice',
				'order_id'        => $this->order_id,
			), 'myaccount' === $context ? wc_get_page_permalink( 'myaccount' ) : ''
		), 'wc_pip_document' );
	}


	/**
	 * Add a link to view invoice on WC Order status emails
	 *
	 * @since 3.0.0
	 * @param \WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function order_paid_email_view_invoice_link( $order, $sent_to_admin, $plain_text ) {

		$customer_user_id = $order->get_user_id();

		// Sanity check.
		if ( ! is_numeric( $customer_user_id ) || 0 === (int) $customer_user_id ) {
			return;
		}

		/** this filter is documented in /includes/class-wc-pip-handler.php */
		if ( false === wc_pip()->get_handler_instance()->customer_can_view_invoices( $customer_user_id ) ) {
			return;
		}

		// Bail out if this is an admin email, if the order is not paid, or if the
		// user viewing this is not logged in, or does not match the order customer.
		if ( $sent_to_admin || ! $order->is_paid() || ! is_user_logged_in() || ( (int) $customer_user_id !== (int) get_current_user_id() ) ) {
			return;
		}

		$invoice_url = esc_url( $this->get_print_invoice_url( 'myaccount' ) );

		if ( $plain_text ) {
			/* translators: Placeholder: %s - invoice plain url */
			$button = "\n\n" . sprintf( __( 'View your invoice: %s', 'woocommerce-pip' ), $invoice_url ) . "\n\n";
		} else {
			$button = '<br><br><a class="button wc_pip_view_invoice" href="' . $invoice_url . '" target="_blank">' . __( 'View your invoice.', 'woocommerce-pip' ) . '</a><br><br>';
		}

		/** this filter is documented in /includes/class-wc-pip-frontend.php */
		echo apply_filters( 'wc_pip_view_invoice_button_html', $button, 'send_email', $invoice_url, $this );
	}


}
