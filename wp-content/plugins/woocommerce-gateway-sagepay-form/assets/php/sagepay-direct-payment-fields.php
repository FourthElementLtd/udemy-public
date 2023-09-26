<?php
		$checked = 1;
		?>
		<fieldset>
			<?php
				$allowed = array(
				    'a' => array(
				        'href' => array(),
				        'title' => array()
				    ),
				    'br' => array(),
				    'em' => array(),
				    'strong' => array(),
				    'span'	=> array(
				    	'class' => array(),
				    ),
				);
				if ( $this->description ) {
					echo apply_filters( 'wc_sagepaydirect_description', wpautop( wp_kses( $this->description, $allowed ) ) );
				}
				if ( $this->saved_cards && is_user_logged_in() && ( $customer_id = get_user_meta( get_current_user_id(), '_sagepaydirect_customer_id', true ) ) && is_string( $customer_id ) && ( $cards = $this->get_saved_cards( $customer_id ) ) ) {
					?>
					<p class="form-row form-row-wide">
						<a class="<?php echo apply_filters( 'wc_sagepaydirect_manage_saved_cards_class', 'button' ); ?>" style="float:right;" href="<?php echo apply_filters( 'wc_sagepaydirect_manage_saved_cards_url', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>#saved-cards"><?php _e( 'Manage cards', 'woocommerce-gateway-sagepaydirect' ); ?></a>
						<?php if ( $cards ) : ?>
							<?php foreach ( (array) $cards as $card ) :
								if ( 'card' !== $card->object ) {
									continue;
								}
								?>
								<label for="sagepaydirect_card_<?php echo $card->id; ?>">
									<input type="radio" id="sagepaydirect_card_<?php echo $card->id; ?>" name="sagepaydirect_card_id" value="<?php echo $card->id; ?>" <?php checked( $checked, 1 ) ?> />
									<?php printf( __( '%s card ending in %s (Expires %s/%s)', 'woocommerce_sagepayform' ), $card->brand, $card->last4, $card->exp_month, $card->exp_year ); ?>
								</label>
								<?php $checked = 0; endforeach; ?>
						<?php endif; ?>
						<label for="new">
							<input type="radio" id="new" name="sagepaydirect_card_id" <?php checked( $checked, 1 ) ?> value="new" />
							<?php _e( 'Use a new credit card', 'woocommerce_sagepayform' ); ?>
						</label>
					</p>
					<?php
				}
			?>
			<div class="sagepaydirect_new_card" <?php if ( $checked === 0 ) : ?>style="display:none;"<?php endif; ?>
				data-description=""
				data-amount="<?php echo esc_attr( $this->get_sagepaydirect_amount( WC()->cart->total ) ); ?>"
				data-name="<?php echo esc_attr( sprintf( __( '%s', 'woocommerce_sagepayform' ), get_bloginfo( 'name' ) ) ); ?>"
				data-label="<?php esc_attr_e( 'Confirm and Pay', 'woocommerce_sagepayform' ); ?>"
				data-currency="<?php echo esc_attr( strtolower( get_woocommerce_currency() ) ); ?>"
				data-image="<?php echo esc_attr( $this->sagepaydirect_checkout_image ); ?>"
				data-bitcoin="<?php echo esc_attr( $this->bitcoin ? 'true' : 'false' ); ?>"
				>
				<?php if ( ! $this->sagepaydirect_checkout ) : ?>
					<?php $this->credit_card_form( array( 'fields_have_names' => false ) ); ?>
				<?php endif; ?>
			</div>
		</fieldset>