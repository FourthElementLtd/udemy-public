<?php
namespace MC4WP\User_Sync\Admin;

defined( 'ABSPATH' ) or exit;

/** @var array $available_mailchimp_fields */
?>
<div class="wrap" id="mc4wp-admin">

	<p class="mc4wp-breadcrumbs">
		<span class="prefix"><?php echo esc_html__( 'You are here: ', 'mailchimp-for-wp' ); ?></span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mailchimp-for-wp' ) ); ?>">Mailchimp for WordPress</a> &rsaquo;
		<span class="current-crumb"><strong>User Sync</strong></span>
	</p>

	<div class="main-content mc4wp-row">

		<!-- Main Content -->
		<div class="main-content col mc4wp-col-4">
			<h1 class="mc4wp-page-title">Mailchimp User Sync</h1>

            <?php if (isset($_GET['webhook-created']) && (int) $_GET['webhook-created'] === 0 ) {
                echo '<div class="notice notice-warning"><p>';
                echo sprintf( __( 'Could not create webhook in your Mailchimp account. This usually happens when your website is not publicly accessible. Check the <a href="%s">debug log</a> for the error message.', 'mailchimp-for-wp' ), admin_url( 'admin.php?page=mailchimp-for-wp-other' ) );
                echo '</p></div>';
            } ?>

            <p>
                <?php echo esc_html__( 'User Sync allows you to synchronize WordPress user fields with Mailchimp subscriber fields.', 'mailchimp-for-wp' ); ?>
                <?php echo esc_html__( 'With this feature enabled, when a user updates their profile on this WordPress site the corresponding subscriber in Mailchimp will be updated as well.', 'mailchimp-for-wp' ); ?>
            </p>
            <p>
                <?php echo sprintf( __( 'Please note that this does not subscribe or unsubscribe your users. We recommend using one of our <a href="%s">sign-up integrations</a> for that.'), admin_url( 'admin.php?page=mailchimp-for-wp-integrations' ) ); ?>
            </p>


			<form method="post" id="settings-form">
                <input type="hidden" name="_mc4wp_action" value="save_user_sync_settings" />
				<?php wp_nonce_field( '_mc4wp_action', '_wpnonce' ); ?>
                <?php settings_errors(); ?>

				<table class="form-table">
                    <tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Enable User Sync?', 'mailchimp-sync' ); ?></th>
						<td class="nowrap">
							<label><input type="radio" name="<?php echo $this->name_attr( 'enabled' ); ?>" value="1" <?php checked( $this->options['enabled'], 1 ); ?> /> <?php echo esc_html__( 'Yes', 'mailchimp-sync' ); ?></label> &nbsp;&nbsp;&nbsp;
							<label><input type="radio" name="<?php echo $this->name_attr( 'enabled' ); ?>" value="0" <?php checked( $this->options['enabled'], 0 ); ?> /> <?php echo esc_html__( 'No', 'mailchimp-sync' ); ?></label>
							<p  class="description"><?php echo esc_html__( 'Select "yes" if you want to synchronize your WordPress user fields with Mailchimp.', 'mailchimp-sync' ); ?></p>
						</td>
					</tr>
                    </tbody>
                    <tbody data-showif='{"element":"mc4wp_user_sync[enabled]","value":1,"hide":false}'>
					<tr valign="top">
						<th scope="row"><?php echo esc_html__( 'Mailchimp Audience', 'mailchimp-sync' ); ?></th>
						<td>
							<?php if( empty( $lists ) ) {
								printf( wp_kses( __( 'No lists found, <a href="%s">are you connected to MailChimp</a>?', 'mailchimp-for-wp' ), array( 'a' => array( 'href' => '' ) ) ), admin_url( 'admin.php?page=mailchimp-for-wp' ) ); ?>
							<?php } else { ?>

							<select name="<?php echo $this->name_attr( 'list' ); ?>" class="widefat">
								<option disabled <?php selected( $this->options['list'], '' ); ?>><?php echo esc_html__( 'Select a list..', 'mailchimp-sync' ); ?></option>
								<?php foreach( $lists as $list ) { ?>
									<option value="<?php echo esc_attr( $list->id ); ?>" <?php selected( $this->options['list'], $list->id ); ?>><?php echo esc_html( $list->name ); ?></option>
								<?php } ?>
							</select>
							<?php } ?>

							<p class="description"><?php echo esc_html__( 'Select the Mailchimp audience to synchronize changes to.' ,'mailchimp-sync' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
                            <?php echo esc_html__( 'Role to sync', 'mailchimp-sync' ); ?>
                            <small style="display: block; font-weight: normal; margin: 6px 0;"><?php echo esc_html__( 'optional', 'mailchimp-sync' ); ?></small>
                        </th>
						<td>
							<select name="<?php echo $this->name_attr('role'); ?>" id="role-select">
								<option value="" <?php selected( $this->options['role'], '' ); ?>><?php echo esc_html__( 'All roles', 'mailchimp-sync' ); ?></option>
								<?php
								$roles = get_editable_roles();
								foreach( $roles as $key => $role ) {
								    echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $this->options['role'], $key, false ), esc_html( $role['name'] ) );
								}
								?>
							</select>

							<p class="description"><?php echo esc_html__( 'Select a specific user role to synchronize. Users without this role will be ignored.', 'mailchimp-sync' ); ?></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<label><?php echo esc_html__( 'Send Additional Fields', 'mailchimp-sync' ); ?></label>
							<small style="display: block; font-weight: normal; margin: 6px 0;"><?php echo esc_html__( 'optional', 'mailchimp-sync' ); ?></small>
						</th>
						<td class="mc4wp-sync-field-map">
							<?php
							if( ! isset( $selected_list ) ) {
								echo '<p class="description">' , esc_html__( 'Please select a MailChimp list first (and then save your settings).', 'mailchimp-sync' ) , '</p>';
							} else {
								echo '<div>';
								foreach( $this->options['field_map'] as $index => $rule ) {
								?>
								<div class="field-map-row" style="margin-bottom: 4px;">
									<select class="user-field" name="<?php echo $this->name_attr( '[field_map]['.$index.'][user_field]' ); ?>">
										<option value="" disabled <?php selected( $rule['user_field'], '' ); ?>><?php echo esc_html__( 'User field', 'mailchimp-sync' ); ?></option>

										<?php
										foreach($meta_keys as $k) {
											echo sprintf( '<option %s>%s</option>', selected($k, $rule['user_field'], false), esc_html($k));
										}
										?>
									</select>

									&nbsp; <?php echo esc_html__( 'to', 'mailchimp-sync' ); ?> &nbsp;

									<select name="<?php echo $this->name_attr( '[field_map]['.$index.'][mailchimp_field]' ); ?>" class="mailchimp-field">
										<option value="" disabled <?php selected( $rule['mailchimp_field'], '' ); ?>><?php echo esc_html__( 'MailChimp field', 'mailchimp-sync' ); ?></option>
										<?php foreach( $available_mailchimp_fields as $field ) { ?>
                                            <?php if ($field->tag === 'EMAIL') { continue; } ?>
											<option value="<?php echo esc_attr( $field->tag ); ?>" <?php selected( $field->tag, $rule['mailchimp_field'] ); ?>>
												<?php echo strip_tags( $field->name ); ?>
											</option>
										<?php } ?>
									</select>
									<input type="button" value="&times;" class="button remove-row" />
								</div>
								<?php
								} // end foreachß
								?>

								</div>
								<p><input type="button" class="button add-row" value="&plus; <?php echo esc_attr__( 'Add line', 'mailchimp-sync' ); ?>" style="margin-left:0; "/></p>

								<p class="description">
									<?php printf( wp_kses( __( 'Specify <a href="%s">what Mailchimp field each user fields maps to</a>. Email address is always synchronized.', 'mailchimp-sync' ),  array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ), 'https://www.mc4wp.com/kb/syncing-custom-user-fields-mailchimp/#utm_source=wp-plugin&utm_medium=mailchimp-sync&utm_campaign=settings-page', '"user meta"' ); ?>
								</p>
								<p class="description">
									<?php _e( 'Please note that empty or missing user field values will overwrite the field value in Mailchimp unless you select "Yes" in the "Skip empty user fields" setting below.', 'mailchimp-sync\'' ); ?>
								</p>

							<?php } ?>
						</td>
					</tr>

					<tr id="skip-empty-user-fields">
						<th scope="row"><?php echo esc_html__( 'Skip empty user fields?', 'mailchimp-sync' ); ?></th>
						<td class="nowrap">
							<label><input type="radio" name="<?php echo $this->name_attr( 'skip_empty_user_fields' ); ?>" value="1" <?php checked( $this->options['skip_empty_user_fields'], 1 ); ?> /> <?php echo esc_html__( 'Yes', 'mailchimp-sync' ); ?></label> &nbsp;&nbsp;&nbsp;
							<label><input type="radio" name="<?php echo $this->name_attr( 'skip_empty_user_fields' ); ?>" value="0" <?php checked( $this->options['skip_empty_user_fields'], 0 ); ?> /> <?php echo esc_html__( 'No', 'mailchimp-sync' ); ?></label>
							<p class="description"><?php echo esc_html__( 'Select "Yes" to skip empty field values, ensuring data in Mailchimp is not cleared out.', 'mailchimp-sync' ); ?></p>
						</td>
					</tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Enable webhook?', 'mailchimp-sync' ); ?></th>
                        <td class="nowrap">
                            <label><input type="radio" name="<?php echo $this->name_attr( 'webhook_enabled' ); ?>" value="1" <?php checked( $this->options['webhook_enabled'], 1 ); ?> /> <?php echo esc_html__( 'Yes', 'mailchimp-sync' ); ?></label> &nbsp;&nbsp;&nbsp;
                            <label><input type="radio" name="<?php echo $this->name_attr( 'webhook_enabled' ); ?>" value="0" <?php checked( $this->options['webhook_enabled'], 0 ); ?> /> <?php echo esc_html__( 'No', 'mailchimp-sync' ); ?></label>
                            <p class="description"><?php echo esc_html__( 'Select "yes" to synchronize changes from Mailchimp to WordPress too. This only works if your website is publicly accessible.', 'mailchimp-sync' ); ?></p>
                        </td>
                    </tr>

                    </tbody>
				</table>

				<?php submit_button(); ?>


			<?php if( $this->options['enabled'] && '' !== $this->options['list'] ) { ?>

                <hr style="margin: 50px 0;"/>

				<?php
				echo '<h2>', esc_html__( 'Background processing', 'mailchimp-sync' ), '</h2>';
				echo '<p>', sprintf( __( 'The plugin is currently monitoring changes in your user fields and will automatically synchronize these changes with the <strong>%s</strong> audience in Mailchimp.', 'mailchimp-sync' ), $selected_list->name ), '</p>';

				$number_of_pending_jobs = count( $queue->all() );
				echo '<p>';
				echo sprintf( wp_kses( __( 'There are <strong>%d</strong> background jobs waiting to be processed.', 'mailchimp-sync' ), array( 'strong' => array() ) ), $number_of_pending_jobs );

				if ( $number_of_pending_jobs > 0 ) {
				    echo ' ';
					echo sprintf( __( 'Next run is scheduled at %s', 'mailchimp-sync' ), gmdate( get_option('time_format'), wp_next_scheduled( 'mc4wp_user_sync_process_queue' ) + (get_option('gmt_offset', 0) * 3600) ) );
				}
                echo '</p>';

                if( $number_of_pending_jobs > 0 ) {
                    echo '<p><a class="button" href="', add_query_arg( array( '_mc4wp_action' => 'process_user_sync_queue', '_wpnonce' => wp_create_nonce( '_mc4wp_action' ) ) ), '">', esc_html__( 'Process', 'mailchimp-sync' ), '</a></p>';
                }

                echo '<p class="description">', sprintf( wp_kses( __( 'Keep an eye on the <a href="%s">debug log</a> for any errors in the background sync process.', 'mailchimp-sync' ), array( 'a' => array( 'href' => array() ) ) ), admin_url( 'admin.php?page=mailchimp-for-wp-other' ) ), '</p>';
                echo '<hr style="margin: 40px 0;" />';
				?>


				<h2><?php echo esc_html__( 'Manual Synchronization', 'mailchimp-sync' ); ?></h2>
				<p><?php echo esc_html__( 'Clicking the following button will perform a manual re-sync of all users matching the given role criteria.', 'mailchimp-sync' ); ?></p>
				<div id="wizard">
					<?php echo esc_html__( 'Please enable JavaScript to use the Synchronisation Wizard.', 'mailchimp-sync' ); ?>
				</div>

			<?php } ?>
            </form>

		<hr style="margin: 40px 0;" />

		<?php
			if (count($this->options['field_map']) > 1) {
				echo '<h3>', esc_html__( 'Debug user data', 'mailchimp-sync' ) , '</h3>';
				echo '<p>', esc_html__('Use the form below to view what data will be sent to Mailchimp based on your current field map settings.', 'mailchimp-sync'), '</p>';
				echo '<form method="get" action="', admin_url( 'admin.php'), '">';
				echo '<input type="hidden" name="page" value="mailchimp-for-wp-user-sync">';
				echo '<input type="hidden" name="debug-field-map" value=1>';
				echo '<input class="regular-text" type="text" placeholder="', esc_attr__('Enter ID of user to debug', 'mailchimp-sync' ), '" name="user_id">';
				echo '<input class="button button-secondary" type="submit" value="', esc_attr__('Debug user', 'mailchimp-sync'), '">';
				echo '</form>';
			}
			?>

		<br style="margin: 40px 0;" />

		<!-- / Main Content -->
		</div>

		<!-- Sidebar -->
		<div class="mc4wp-sidebar mc4wp-col mc4wp-col-2">
			<?php include MC4WP_PLUGIN_DIR . '/includes/views/parts/admin-sidebar.php'; ?>
		</div>

	<!-- / Row -->
	</div>

	<?php
	/**
	 * @ignore
	 */
	do_action( 'mc4wp_admin_footer' );
	?>

</div>
