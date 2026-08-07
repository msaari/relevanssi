<?php
/**
 * /lib/tabs/help-tab.php
 *
 * Renders the comprehensive Help & Support tab in Relevanssi settings.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the unified Help & Support tab using resource matrices and support tracking systems.
 *
 * @global array $relevanssi_variables The global Relevanssi plugin variables array.
 * @return void Writes layout markup directly to the screen.
 */
function relevanssi_help_tab() {
	global $relevanssi_variables;

	$is_premium = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;

	// Process support ticket actions if the premium pipeline is functional.
	if ( $is_premium ) {
		$support_email = $relevanssi_variables['autoupdate']->get_remote_license();

		if ( isset( $_REQUEST['relevanssi_support_form'] ) ) {
			check_admin_referer( 'relevanssi_support_form', 'relevanssi_support_form' );
			rlv_help_tab_send_email( $_REQUEST, $support_email );
		}
	}

	?>
	<div id="relevanssi_help_tab_wrap" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Help & Support', 'relevanssi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Comprehensive technical reference logs, quick resource links, and developer assistance ticketing.', 'relevanssi' ); ?></p>

		<!-- Section 1: External Documentation & Account Resource Matrices -->
		<div class="relevanssi-settings-row" style="margin-top: 20px; margin-bottom: 24px;">
			<div style="display: flex; gap: 16px; flex-wrap: wrap; width: 100%;">

				<div class="relevanssi-card" style="display: flex; flex-direction: column; flex: 1; min-width: 220px; padding: 20px; box-sizing: border-box;">
					<span class="dashicons dashicons-editor-help" style="font-size: 28px; width: 28px; height: 28px; color: #005885; margin-bottom: 12px;"></span>
					<h2 style="margin: 0 0 8px 0; font-size: 15px;"><?php esc_html_e( 'User Manual', 'relevanssi' ); ?></h2>
					<p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.4; color: #646970;">
						<?php esc_html_e( 'Step-by-step instructions for installation sequences, shortcode expansion, and weight customization rules.', 'relevanssi' ); ?>
					</p>
					<a href="https://www.relevanssi.com/user-manual/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-top: auto; display: inline-flex; align-items: center; gap: 4px; align-self: flex-start;">
						<?php esc_html_e( 'Open Manual', 'relevanssi' ); ?> <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span>
					</a>
				</div>

				<div class="relevanssi-card" style="display: flex; flex-direction: column; flex: 1; min-width: 220px; padding: 20px; box-sizing: border-box;">
					<span class="dashicons dashicons-category" style="font-size: 28px; width: 28px; height: 28px; color: #46b450; margin-bottom: 12px;"></span>
					<h2 style="margin: 0 0 8px 0; font-size: 15px;"><?php esc_html_e( 'Knowledge Base', 'relevanssi' ); ?></h2>
					<p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.4; color: #646970;">
						<?php esc_html_e( 'Detailed articles covering advanced developer features, plugin compatibility, and specific page builder setups.', 'relevanssi' ); ?>
					</p>
					<a href="https://www.relevanssi.com/category/knowledge-base/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-top: auto; display: inline-flex; align-items: center; gap: 4px; align-self: flex-start;">
						<?php esc_html_e( 'Browse Articles', 'relevanssi' ); ?> <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span>
					</a>
				</div>

				<div class="relevanssi-card" style="display: flex; flex-direction: column; flex: 1; min-width: 220px; padding: 20px; box-sizing: border-box;">
					<span class="dashicons dashicons-clipboard" style="font-size: 28px; width: 28px; height: 28px; color: #996800; margin-bottom: 12px;"></span>
					<h2 style="margin: 0 0 8px 0; font-size: 15px;"><?php esc_html_e( 'Release Notes', 'relevanssi' ); ?></h2>
					<p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.4; color: #646970;">
						<?php esc_html_e( 'Find the release notes for all versions of Relevanssi here.', 'relevanssi' ); ?>
					</p>
					<a href="https://www.relevanssi.com/release-notes/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-top: auto; display: inline-flex; align-items: center; gap: 4px; align-self: flex-start;">
						<?php esc_html_e( 'View Changelogs', 'relevanssi' ); ?> <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span>
					</a>
				</div>

				<div class="relevanssi-card" style="display: flex; flex-direction: column; flex: 1; min-width: 220px; padding: 20px; box-sizing: border-box;">
					<span class="dashicons dashicons-admin-network" style="font-size: 28px; width: 28px; height: 28px; color: #cc1818; margin-bottom: 12px;"></span>
					<h2 style="margin: 0 0 8px 0; font-size: 15px;"><?php esc_html_e( 'Premium Account', 'relevanssi' ); ?></h2>
					<p style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.4; color: #646970;">
						<?php esc_html_e( 'Find your API authorization license key and transaction history here.', 'relevanssi' ); ?>
					</p>
					<a href="https://www.relevanssi.com/account/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-top: auto; display: inline-flex; align-items: center; gap: 4px; align-self: flex-start;">
						<?php esc_html_e( 'Manage Account', 'relevanssi' ); ?> <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span>
					</a>
				</div>

			</div>
		</div>

		<div class="relevanssi-dashboard-layout">
			<div class="relevanssi-main" style="width: 100%; max-width: 100%;">

			<!-- Section: Frequently Asked Questions Matrix -->
<div class="relevanssi-settings-row" id="relevanssi-faq" style="margin-bottom: 24px;">
	<div class="relevanssi-settings-content" style="width: 100%; max-width: 100%;">
		<div class="relevanssi-card">
			<h2><?php esc_html_e( 'Frequently Asked Questions', 'relevanssi' ); ?></h2>
			<p class="description"><?php esc_html_e( 'TOP 10 Support questions – please check them out, before submitting a new ticket.', 'relevanssi' ); ?></p>

			<div class="relevanssi-faq-grid" style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 24px;">

				<!-- Left Column: Questions 1 to 5 -->
				<div class="relevanssi-faq-column" style="flex: 1; min-width: 320px;">
					<div class="relevanssi-accordion-group" style="margin-top: 0;">

						<!-- FAQ 1 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'Relevanssi is active, but it isn\'t working', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p>
									<?php
									printf(
										/* translators: %1$s is the query_posts() code wrapper element. */
										esc_html__( 'The most common reason for this that you have a %1$s call inside your theme’s search results template script (usually search.php), which confuses Relevanssi. Try removing that function call from your template to let the plugin take control.', 'relevanssi' ),
										'<code>query_posts()</code>'
									);
									?>
								</p>
							</div>
						</details>

						<!-- FAQ 2 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'I get the error “Key xxxxxx is not valid” when indexing attachments', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p><?php esc_html_e( 'Our servers synchronize license keys once per hour. Please wait up to 60 minutes and try indexing again. In most cases the error resolves automatically within that time.', 'relevanssi' ); ?></p>
							</div>
						</details>

						<!-- FAQ 3 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'I get an activation error when trying to activate Relevanssi Premium', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p><?php esc_html_e( 'This usually happens when the free version of Relevanssi is still active. Please deactivate the free version first, then activate the Premium version. After successful activation you can safely uninstall the free version.', 'relevanssi' ); ?></p>
							</div>
						</details>

						<!-- FAQ 4 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'Do you offer a free trial version?', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p><?php esc_html_e( 'We do not offer a trial version of Relevanssi Premium. However, we provide a 30-day money-back guarantee. You can purchase the plugin, test it thoroughly for a full month, and request a refund if it doesn’t meet your expectations.', 'relevanssi' ); ?></p>
							</div>
						</details>

						<!-- FAQ 5 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( "What happens if I don't renew my annual license?", 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p><?php esc_html_e( 'Your existing installation will continue to work with the last version you downloaded. However, you will no longer receive updates, new features, or priority support after the license expires.', 'relevanssi' ); ?></p>
							</div>
						</details>

					</div>
				</div>

				<!-- Right Column: Questions 6 to 10 -->
				<div class="relevanssi-faq-column" style="flex: 1; min-width: 320px;">
					<div class="relevanssi-accordion-group" style="margin-top: 0;">

						<!-- FAQ 6 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'Do you offer refunds?', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p><?php esc_html_e( 'Yes. We offer a 30-day money-back guarantee. If you are not satisfied with Relevanssi Premium, you can request a full refund within 30 days of purchase. No questions asked.', 'relevanssi' ); ?></p>
							</div>
						</details>

						<!-- FAQ 7 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'My searches are very slow. What can I do?', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p>
								<?php
								printf(
									/* translators: %1$s opens a link to the debugging guide, %2$s closes it. */
									esc_html__( 'Slow search performance is often caused by large databases or server configuration. Please follow our detailed %1$sdebugging guide%2$s which includes index optimization, server settings, and common performance bottlenecks.', 'relevanssi' ),
									'<a href="https://www.relevanssi.com/knowledge-base/debugging-slow-searches/" target="_blank" rel="noopener noreferrer">',
									'</a>'
								);
								?>
																	</p>
						</div>
						</details>

						<!-- FAQ 8 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'Having problems with searching in general?', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p>
									<?php
									printf(
										/* translators: %1$s opens a link to the troubleshooting checklist, %2$s closes it. */
										esc_html__( 'Please go through our %1$scomprehensive troubleshooting checklist%2$s first. It covers the most common issues such as indexing problems, permission errors, theme conflicts, and caching issues.', 'relevanssi' ),
										'<a href="https://www.relevanssi.com/user-manual/checklist-for-searching-problems/" target="_blank" rel="noopener noreferrer">',
										'</a>'
									);
									?>
								</p>
							</div>
						</details>

						<!-- FAQ 9 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'Can I transfer my license to another person or website?', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<?php // Translators: %1$s is the support email address. ?>
								<p><?php printf( esc_html__( 'License transfers are possible but require our prior written approval. Please contact %1$s with your license key and the new owner’s details. We will handle the transfer manually.', 'relevanssi' ), 'support@relevanssi.com' ); ?></p>
							</div>
						</details>

						<!-- FAQ 10 -->
						<details class="relevanssi-help-accordion">
							<summary>
								<span><?php esc_html_e( 'Where can I get help if I have a problem?', 'relevanssi' ); ?></span>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</summary>
							<div class="accordion-content">
								<p><?php esc_html_e( 'Active license holders have access to our priority support form. We aim to respond within 48 business hours.', 'relevanssi' ); ?></p>
							</div>
						</details>

					</div>
				</div>

			</div>
		</div>
	</div>
</div>

				<!-- Section 2: Support Desk Flow  -->
				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
	<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">
		<?php if ( ! $is_premium ) : ?>
			<div class="relevanssi-card" id="card-support-locked" style="padding: 24px;">
				<h2><?php esc_html_e( 'Priority Developer Support', 'relevanssi' ); ?></h2>
				<p class="description" style="margin-bottom: 20px; font-size: 13.5px; line-height: 1.5; color: #646970;">
					<?php esc_html_e( 'Get direct, private assistance from the plugin developers to troubleshoot indexing bottlenecks, custom filter hooks, and complex query behaviors.', 'relevanssi' ); ?>
				</p>

				<div class="relevanssi-upsell-callout" style="padding: 16px; background: #f0f6fa; border: 1px dashed #005885; border-radius: 4px; max-width: 650px; box-sizing: border-box;">
					<p style="margin: 0 0 12px 0; font-size: 14px; line-height: 1.4; color: #1d2327; font-weight: 600;">
						<?php
						printf(
							/* translators: %1$s opens a strong tag, %2$s closes it. */
							esc_html__( 'In-dashboard support ticketing requires %1$sRelevanssi Premium%2$s.', 'relevanssi' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>

					<p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #2c3338;">
						<?php esc_html_e( 'What you unlock with Premium support:', 'relevanssi' ); ?>
					</p>
					<ul style="margin: 0 0 16px 0; padding-left: 18px; list-style-type: disc; color: #50575e; font-size: 13px; line-height: 1.6;">
						<li><strong><?php esc_html_e( 'Developer Access:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Open private help tickets right from inside your WordPress administration panel.', 'relevanssi' ); ?></li>
						<li><strong><?php esc_html_e( 'Priority Response Queue:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Receive quick help directly from core plugin maintainers within 24–48 hours.', 'relevanssi' ); ?></li>
						<li><strong><?php esc_html_e( 'Support in Several Languages:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Submit your help desk inquiries in English, German, or Finnish.', 'relevanssi' ); ?></li>
					</ul>

					<p style="margin: 0; font-size: 13px;">
						<a href="https://www.relevanssi.com/buy-premium/"
							target="_blank"
							rel="noopener noreferrer"
							style="color: #005885; text-decoration: none; font-weight: 600; border-bottom: 1px solid #005885; padding-bottom: 1px;">
							<?php esc_html_e( 'Upgrade to Premium for dedicated support →', 'relevanssi' ); ?>
						</a>
					</p>
				</div>
			</div>
		<?php else : ?>
			<?php if ( ! $support_email ) : ?>
				<div class="relevanssi-card">
					<h2><?php esc_html_e( 'License Required', 'relevanssi' ); ?></h2>
					<div class="relevanssi-notice relevanssi-notice-info">
						<p><?php echo relevanssi_get_api_key_notification(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					</div>
				</div>
			<?php else : ?>

				<?php
				// Fetch user account variables.
				$current_user     = wp_get_current_user();
				$wp_profile_email = $current_user->user_email;

				$wp_profile_name = trim( $current_user->first_name . ' ' . $current_user->last_name );
				?>
				<div class="relevanssi-card" id="card-support-form">
					<h2><?php esc_html_e( 'Open a Support Ticket', 'relevanssi' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Fill out the form configurations details below. Your technical details will be automatically sent with your support request.', 'relevanssi' ); ?>
					</p>

					<form method="post" action="" class="relevanssi-card">
						<?php wp_nonce_field( 'relevanssi_support_form', 'relevanssi_support_form' ); ?>

						<table class="form-table" role="presentation">
							<!-- Name Field -->
							<tr>
								<th scope="row">
									<label for="relevanssi_support_name">
										<?php esc_html_e( 'Your name', 'relevanssi' ); ?> <span class="rlv-required">*</span>
									</label>
								</th>
								<td>
									<div class="rlv-form-field-wrapper">
										<input type="text" name="relevanssi_support_name" id="relevanssi_support_name" class="rlv-uniform-field" value="" required />
										<?php if ( ! empty( $wp_profile_name ) ) : ?>
										<button type="button" class="button button-primary rlv-autofill-trigger" data-target="relevanssi_support_name" data-value="<?php echo esc_attr( $wp_profile_name ); ?>">
											<?php esc_html_e( 'Use account name', 'relevanssi' ); ?>
										</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>

							<!-- Email Field -->
							<tr>
								<th scope="row">
									<label for="relevanssi_support_email">
										<?php esc_html_e( 'Your email address', 'relevanssi' ); ?> <span class="rlv-required">*</span>
									</label>
								</th>
								<td>
									<div class="rlv-form-field-wrapper">
										<input type="email" name="relevanssi_support_email" id="relevanssi_support_email" class="rlv-uniform-field" value="" required />
										<button type="button" class="button button-primary rlv-autofill-trigger" data-target="relevanssi_support_email" data-value="<?php echo esc_attr( $wp_profile_email ); ?>">
											<?php esc_html_e( 'Use account email', 'relevanssi' ); ?>
										</button>
									</div>
								</td>
							</tr>

							<!-- Side-by-Side Dropdowns Row -->
							<tr>
								<th scope="row">
									<?php esc_html_e( 'Category & Type', 'relevanssi' ); ?> <span class="rlv-required">*</span>
								</th>
								<td>
									<div class="rlv-form-field-wrapper rlv-split-row">
										<select name="relevanssi_support_type" id="relevanssi_support_type" class="rlv-split-field" required>
											<option value=""><?php esc_html_e( 'Select request type...', 'relevanssi' ); ?></option>
											<option value="Technical Support"><?php esc_html_e( 'Technical Support', 'relevanssi' ); ?></option>
											<option value="Bug Report"><?php esc_html_e( 'Bug Report', 'relevanssi' ); ?></option>
											<option value="License / Account Issue"><?php esc_html_e( 'License / Account Issue', 'relevanssi' ); ?></option>
											<option value="Other"><?php esc_html_e( 'Other', 'relevanssi' ); ?></option>
										</select>

										<select name="relevanssi_support_category" id="relevanssi_support_category" class="rlv-split-field" required>
											<option value=""><?php esc_html_e( 'Select problem category...', 'relevanssi' ); ?></option>
											<option value="Indexing"><?php esc_html_e( 'Indexing', 'relevanssi' ); ?></option>
											<option value="Search Results"><?php esc_html_e( 'Search Results', 'relevanssi' ); ?></option>
											<option value="Woocommerce"><?php esc_html_e( 'WooCommerce', 'relevanssi' ); ?></option>
											<option value="PDF Indexing"><?php esc_html_e( 'PDF Indexing', 'relevanssi' ); ?></option>
											<option value="Multisite"><?php esc_html_e( 'Multisite', 'relevanssi' ); ?></option>
											<option value="Performance"><?php esc_html_e( 'Performance', 'relevanssi' ); ?></option>
											<option value="Plugin Compatibility"><?php esc_html_e( 'Plugin Compatibility', 'relevanssi' ); ?></option>
											<option value="Theme Compatibility"><?php esc_html_e( 'Theme Compatibility', 'relevanssi' ); ?></option>
											<option value="Other"><?php esc_html_e( 'Other', 'relevanssi' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<!-- Subject Field -->
							<tr>
								<th scope="row">
									<label for="relevanssi_support_subject">
										<?php esc_html_e( 'Subject', 'relevanssi' ); ?> <span class="rlv-required">*</span>
									</label>
								</th>
								<td>
									<div class="rlv-form-field-wrapper">
										<input type="text" name="relevanssi_support_subject" id="relevanssi_support_subject" class="rlv-uniform-field" value="" required />
									</div>
								</td>
							</tr>

							<!-- Message Field -->
							<tr>
								<th scope="row">
									<label for="relevanssi_support_message">
										<?php esc_html_e( 'Your Message', 'relevanssi' ); ?> <span class="rlv-required">*</span>
									</label>
								</th>
								<td>
									<div class="rlv-form-field-wrapper">
										<textarea name="relevanssi_support_message" id="relevanssi_support_message" rows="6" class="rlv-uniform-field" required></textarea>
									</div>
									<p class="description rlv-field-description">
										<?php esc_html_e( 'Please describe your issue in as much detail as possible. Include the steps that led to the problem, what you expected to happen, and what happened instead. The more details you provide, the faster we can help.', 'relevanssi' ); ?>
									</p>
								</td>
							</tr>

							<!-- Submit Button -->
							<tr>
								<td></td>
								<td><?php submit_button( __( 'Send Message', 'relevanssi' ), 'primary', 'relevanssi_support_submit', false ); ?></td>
							</tr>
						</table>
					</form>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<?php if ( $is_premium && ! empty( $support_email ) ) : ?>
	<aside class="relevanssi-settings-sidebar">
		<div class="relevanssi-info-box">
			<h3><?php esc_html_e( 'Support Information', 'relevanssi' ); ?></h3>
			<p style="font-size: 13px; line-height: 1.5; margin-bottom: 16px;">
				<?php
				printf(
				/* translators: %1$s opens a link, %2$s closes it, %3$s is the email address. */
					wp_kses_post( __( 'If you do not receive a reply within 48 hours, please reach out via %1$sthe web platform fallback form%2$s or email us directly at %3$s.', 'relevanssi' ) ),
					'<a href="https://www.relevanssi.com/support/" target="_blank" rel="noopener noreferrer">',
					'</a>',
					'<code>support@relevanssi.com</code>'
				);
				?>
			</p>

			<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">

			<h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">
				<?php esc_html_e( 'What information is sent?', 'relevanssi' ); ?>
			</h4>
			<p style="font-size: 12.5px; line-height: 1.4; color: #646970; margin: 0 0 10px 0;">
				<?php esc_html_e( 'To help us solve your problem faster, this form automatically includes a few basic technical details about your website:', 'relevanssi' ); ?>
			</p>

			<ul style="margin: 0 0 16px 0; padding-left: 16px; list-style-type: disc; color: #646970; font-size: 12px; line-height: 1.5;">
				<li><strong><?php esc_html_e( 'Software Versions:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'The version numbers for WordPress, PHP, your database server, and Relevanssi.', 'relevanssi' ); ?></li>
				<li><strong><?php esc_html_e( 'Server Settings:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Your site\'s memory limits and time thresholds (low limits could cause indexing issues).', 'relevanssi' ); ?></li>
				<li><strong><?php esc_html_e( 'Your Theme:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'The name and version number of your active WordPress design theme.', 'relevanssi' ); ?></li>
				<li><strong><?php esc_html_e( 'Active Plugins:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'A list of other plugins (and version numbers) active on your site, which helps us check for compatibility issues.', 'relevanssi' ); ?></li>
				<li><strong><?php esc_html_e( 'Search Query:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'The SQL search query Relevanssi currently uses to look up your content.', 'relevanssi' ); ?></li>
			</ul>

			<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">

			<p style="font-size: 12px; color: #646970; margin-top: 0;">
				<?php esc_html_e( 'Note: Expect slightly delayed response intervals across peak holiday operational cycles (June/July).', 'relevanssi' ); ?>
			</p>
		</div>
	</aside>
<?php endif; ?>
</div>




			</div>
		</div>
	</div>
<!-- On-Demand Form Injection Script -->
<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		const triggers = document.querySelectorAll('.rlv-autofill-trigger');

		triggers.forEach(function(button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const targetId = this.getAttribute('data-target');
				const valueToInject = this.getAttribute('data-value');
				const targetInput = document.getElementById(targetId);

				if (targetInput) {
					targetInput.value = valueToInject;
				}
			});
		});
	});
</script>

	<?php
}



/**
 * Sends out an email to Relevanssi support with extended diagnostic parameters.
 *
 * @global wpdb   $wpdb                  The WordPress database interface.
 * @global string $wp_version            The version of WordPress running.
 * @global array  $relevanssi_variables  The global Relevanssi variables array.
 *
 * @param array  $request       The request data payload directly from administrative form posts.
 * @param string $support_email The validated remote support recipient email endpoint address.
 * @return void Handles direct system email execution loops and errors displays.
 */
function rlv_help_tab_send_email( array $request, string $support_email ) {
	global $wpdb, $wp_version, $relevanssi_variables;

	if ( empty( $support_email ) || ! is_email( $support_email ) ) {
		return;
	}

	$user_email = sanitize_email( $request['relevanssi_support_email'] ?? '' );

	// Guardrail validation check: Catch malformed typos before executing mail routine.
	if ( ! is_email( $user_email ) ) {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Error: The email address provided is malformed. Please verify for typos and try again.', 'relevanssi' ); ?></p>
		</div>
		<?php
		return;
	}

	$user_name = ! empty( $request['relevanssi_support_name'] ) ? sanitize_text_field( $request['relevanssi_support_name'] ) : 'User';
	$message   = $request['relevanssi_support_message'] ?? '';
	$subject   = $request['relevanssi_support_subject'] ?? '';

	// Fetch account details to bypass user input errors.
	$current_user = wp_get_current_user();

	$headers   = array();
	$headers[] = 'Content-Type: text/plain; charset=UTF-8';
	$headers[] = "Reply-To: $user_name <$user_email>";

	// Active Theme context data parsing.
	$theme_data = wp_get_theme();
	$theme_info = $theme_data->get( 'Name' ) . ' v' . $theme_data->get( 'Version' );
	if ( is_child_theme() ) {
		$theme_info .= ' (Child Theme of: ' . $theme_data->get( 'Template' ) . ')';
	}

	// Server configurations tracking.
	$php_memory_limit = ini_get( 'memory_limit' );
	$wp_memory_limit  = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : $php_memory_limit;
	$max_exec_time    = ini_get( 'max_execution_time' );
	$mysql_version    = $wpdb->db_version();
	$server_software  = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

	// --- Fetch Advanced Debugging Metrics ---

	// 1. Fetch MySQL max_allowed_packet configuration
	$max_packet = $wpdb->get_var( 'SELECT @@max_allowed_packet' );
	if ( ! $max_packet ) {
		$packet_row = $wpdb->get_row( "SHOW VARIABLES LIKE 'max_allowed_packet'", ARRAY_A );
		$max_packet = ! empty( $packet_row['Value'] ) ? $packet_row['Value'] : 'Unknown';
	}
	if ( is_numeric( $max_packet ) && function_exists( 'size_format' ) ) {
		$max_packet = size_format( $max_packet );
	}

	// 2. Reconstruct the active Relevanssi indexing query footprint
	$indexing_query = 'Unavailable (Relevanssi indexing core functions are missing)';
	if ( function_exists( 'relevanssi_generate_indexing_query' ) ) {
		$restriction    = function_exists( 'relevanssi_post_type_restriction' ) ? relevanssi_post_type_restriction() : '';
		$valid_status   = function_exists( 'relevanssi_valid_status_array' ) ? relevanssi_valid_status_array() : '';
		$indexing_query = relevanssi_generate_indexing_query( $valid_status, false, $restriction, '' );
	}

	// Active plugins collection processing loops.
	$active_plugins = get_option( 'active_plugins', array() );
	$plugin_list    = array();

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$all_installed_plugins = get_plugins();

	foreach ( $active_plugins as $plugin_path ) {
		if ( isset( $all_installed_plugins[ $plugin_path ] ) ) {
			$plugin_list[] = $all_installed_plugins[ $plugin_path ]['Name'] . ' (v' . $all_installed_plugins[ $plugin_path ]['Version'] . ')';
		}
	}

	// Account for Network Wide Activated Plugins (Multisite Compatibility).
	$is_multisite_status = 'No';
	if ( is_multisite() ) {
		$is_multisite_status = 'Yes';
		$network_active      = get_site_option( 'active_sitewide_plugins', array() );
		foreach ( array_keys( $network_active ) as $plugin_path ) {
			if ( isset( $all_installed_plugins[ $plugin_path ] ) ) {
				$plugin_list[] = $all_installed_plugins[ $plugin_path ]['Name'] . ' (v' . $all_installed_plugins[ $plugin_path ]['Version'] . ') [Network Wide]';
			}
		}
	}

	$formatted_plugins = ! empty( $plugin_list ) ? implode( "\n   - ", $plugin_list ) : 'None detected';

	// --- Build Standardized Diagnostic Signature ---
	$message_builder .= 'Name: ' . $user_name . "\n";
	$message_builder .= 'Email: ' . $user_email . "\n";
	$message_builder .= 'Site URL: ' . home_url() . "\n\n";

	$message_builder .= 'USER MESSAGE:' . "\n" . stripslashes( $message ) . "\n\n";

	$message_builder .= "==================== Additional Data ====================\n\n";

	$message_builder .= "PLATFORM VERSIONS:\n";
	$message_builder .= 'WP Version: ' . $wp_version . "\n";
	$message_builder .= 'Is Multisite: ' . $is_multisite_status . "\n";
	$message_builder .= 'PHP Version: ' . phpversion() . "\n";
	$message_builder .= 'MySQL Version: ' . $mysql_version . "\n";
	$message_builder .= 'Server Software: ' . $server_software . "\n";
	$message_builder .= 'Relevanssi Version: ' . ( $relevanssi_variables['plugin_version'] ?? 'Premium' ) . "\n\n";

	$message_builder .= "RESOURCE BOUNDARIES:\n";
	$message_builder .= 'WP Memory Limit: ' . $wp_memory_limit . "\n";
	$message_builder .= 'PHP Memory Limit: ' . $php_memory_limit . "\n";
	$message_builder .= 'Max Execution Time: ' . $max_exec_time . " seconds\n";
	$message_builder .= 'MySQL Max Allowed Packet: ' . $max_packet . "\n\n";

	$message_builder .= "WEBSITE THEME:\n";
	$message_builder .= 'Active Theme: ' . $theme_info . "\n\n";

	$message_builder .= "ACTIVE PLUGINS:\n";
	$message_builder .= '   - ' . $formatted_plugins . "\n\n";

	$message_builder .= "DATABASE & INDEXING METRICS:\n";
	$message_builder .= "Baseline Indexing Query:\n" . $indexing_query . "\n";

	$success = wp_mail( $support_email, $subject, $message_builder, $headers );

	if ( $success ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Email sent successfully!', 'relevanssi' ); ?></p>
		</div>
		<?php
	} else {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Email dispatch failed. Please check your system mail setup parameters.', 'relevanssi' ); ?></p>
		</div>
		<?php
	}
}