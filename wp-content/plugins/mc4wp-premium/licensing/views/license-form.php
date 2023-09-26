
<h3><?php _e('Plugin license', 'mailchimp-for-wp'); ?></h3>

<form method="post">
	<table class="form-table">
		<tr valign="top">
			<th><?php _e('License key', 'mailchimp-for-wp'); ?></th>
			<td>
				<input type="text" class="regular-text" name="mc4wp_license_key" placeholder="<?php esc_attr_e('Enter your license key..', 'mailchimp-for-wp'); ?>" value="<?php echo esc_attr($license->key); ?>" <?php if ($license->activated) {
    echo 'readonly';
} ?> />
				<input class="button" type="submit" name="action" value="<?php echo($license->activated ? 'deactivate' : 'activate'); ?>" />
				<p class="description">
					<?php echo sprintf(__('The license key received when purchasing Mailchimp for WordPress Premium. <a href="%s">You can find it here</a>.', 'mailchimp-for-wp'), 'https://my.mc4wp.com/licenses'); ?>
				</p>
			</td>
		</tr>
		<tr valign="top">
			<th><?php _e('License status', 'mailchimp-for-wp'); ?></th>
			<td>
				<?php
                if ($license->activated) {
                    echo sprintf('<p><span class="mc4wp-status positive">%s</span> - %s</p>', __('ACTIVE', 'mailchimp-for-wp'), __('you are receiving plugin updates', 'mailchimp-for-wp'));
                } else {
                	echo sprintf('<p><span class="mc4wp-status negative">%s</span> - %s</p>', __('INACTIVE', 'mailchimp-for-wp'), __('you are <strong>not</strong> receiving plugin updates right now', 'mailchimp-for-wp'));
                } ?>
			</td>
		</tr>
	</table>

	<p>
		<input type="submit" class="button button-primary" name="action" value="<?php _e('Save Changes'); ?>" />
	</p>

	<input type="hidden" name="_mc4wp_action" value="save_license" />
	<?php wp_nonce_field( '_mc4wp_action', '_wpnonce' ); ?>
</form>
