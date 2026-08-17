<?php
/**
 * /lib/tabs/overview-tab.php
 *
 * Prints out the modern Overview tab dashboard in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the overview tab in Relevanssi settings.
 *
 * @global array     $relevanssi_variables The global Relevanssi variables array.
 * @global wpdb      $wpdb                  The WordPress database management object.
 * @return void Writes raw markup buffers straight onto the administration viewport.
 */
function relevanssi_overview_tab() {
	global $relevanssi_variables, $wpdb;

	// --- State Logic: Environment Assessment ---
	$is_premium  = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;
	$docs_count  = get_option( 'relevanssi_doc_count', 0 );
	$terms_count = get_option( 'relevanssi_terms_count', 0 );

	$table_name  = $relevanssi_variables['relevanssi_table'] ?? '';
	$lowest_doc  = 0;
	$table_ready = false;

	if ( ! empty( $table_name ) ) {
		// Verify database table integrity.
		$table_ready = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

		if ( $table_ready ) {
			$lowest_doc = $wpdb->get_var( 'SELECT doc FROM ' . $table_name . ' WHERE doc > 0 ORDER BY doc ASC LIMIT 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	if ( null === $lowest_doc ) {
		$lowest_doc = 0;
		if ( function_exists( 'relevanssi_create_database_tables' ) ) {
			relevanssi_create_database_tables( 0 );
			$table_ready = true;
		}
	}

	$this_page  = '?page=' . plugin_basename( $relevanssi_variables['file'] );
	$update_url = wp_nonce_url( $this_page . '&rlv_tab=overview&update_counts=1', 'update_counts' );

	$user_count    = 0;
	$taxterm_count = 0;

	if ( $is_premium ) {
		$user_count    = get_option( 'relevanssi_user_count', 0 );
		$taxterm_count = get_option( 'relevanssi_taxterm_count', 0 );
	}

	$is_logging_active     = 'on' === get_option( 'relevanssi_log_queries' );
	$is_didyoumean_active  = 'on' === get_option( 'relevanssi_enable_didyoumean' );
	$is_voicesearch_active = 'on' === get_option( 'relevanssi_voice_search' );
	$is_related_active     = $related_settings['enabled'] ?? 'off';

	?>
	<div id="overview_tab_dashboard" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Welcome to Relevanssi!', 'relevanssi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Get an overview of your search index and quick links.', 'relevanssi' ); ?></p>

		<input type="hidden" name="rlv_tab" value="overview" />

		<div class="relevanssi-dashboard-layout" style="margin-top: 20px;">
			<?php if ( $is_premium ) : ?>
				<?php
				$has_api_key            = ! is_plugin_active_for_network( plugin_basename( $relevanssi_variables['file'] ) ) && function_exists( 'relevanssi_get_api_key_config' );
				$global_controls_config = array();

				if ( $has_api_key ) {
					$global_controls_config = array_merge( $global_controls_config, relevanssi_get_api_key_config() );
				}

				if ( ! is_plugin_active_for_network( plugin_basename( $relevanssi_variables['file'] ) ) ) :
					$api_key_constant = defined( 'RELEVANSSI_API_KEY' );
					$api_key          = $api_key_constant ? RELEVANSSI_API_KEY : get_option( 'relevanssi_api_key' );
					$is_saved         = ! empty( $api_key );
					?>
					<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
						<div class="relevanssi-settings-content">
							<div class="relevanssi-card" id="card-license-management" style="padding: 24px;">

								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f0f0f1; padding-bottom: 16px;">
									<h2 style="margin: 0; padding: 0; border: none; font-size: 1.25rem; font-weight: 600; color: #1d2327;">
										<?php esc_html_e( 'License Authentication', 'relevanssi' ); ?>
									</h2>
									<?php if ( $api_key_constant ) : ?>
										<span class="badge" style="background: #2271b1; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'wp-config defined', 'relevanssi' ); ?></span>
									<?php elseif ( $is_saved ) : ?>
										<span class="badge" style="background: #2271b1; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Saved', 'relevanssi' ); ?></span>
									<?php else : ?>
										<span class="badge" style="background: #47494e; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Not Configured', 'relevanssi' ); ?></span>
									<?php endif; ?>
								</div>

								<?php if ( $api_key_constant ) : ?>
									<p class="description" style="margin-bottom: 16px; font-size: 13.5px; color: #2c3338;">
										<?php esc_html_e( 'Your license key is managed externally via your site configuration file constant definition.', 'relevanssi' ); ?>
									</p>
									<input id="relevanssi_api_key_field" type="text" class="regular-text" value="••••••••" disabled style="background: #f0f0f1; color: #8c8f94; font-family: monospace; letter-spacing: 2px;" />

								<?php elseif ( $is_saved ) : ?>
									<p class="description" style="margin-bottom: 16px; font-size: 13.5px; color: #2c3338;">
										<?php esc_html_e( 'Your API key is saved locally. We check for authentication during background update routines and PDF extraction tasks.', 'relevanssi' ); ?>
									</p>

									<div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
										<label for="relevanssi_api_key_field" class="screen-reader-text"><?php esc_html_e( 'Relevanssi API key', 'relevanssi' ); ?></label>
										<input id="relevanssi_api_key_field" type="text" class="regular-text" value="<?php echo esc_attr( str_repeat( '•', max( 0, strlen( $api_key ) - 3 ) ) . substr( $api_key, -3 ) ); ?>" disabled style="background: #f8f9fa; font-family: monospace; color: #2c3338; font-weight: 500;" />

										<label for="relevanssi_remove_api_key" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #b32d2e; font-weight: 500; font-size: 13px;">
											<input type="checkbox" name="relevanssi_remove_api_key" id="relevanssi_remove_api_key" value="on" style="border-color: #b32d2e; margin: 0;" />
											<?php esc_html_e( 'Remove License Key', 'relevanssi' ); ?>
										</label>
									</div>
									<p class="description" style="margin-top: 10px; color: #47494e; font-size: 12px;">
										<?php esc_html_e( 'To remove the key, check the box above and save changes.', 'relevanssi' ); ?>
									</p>

								<?php else : ?>
									<p class="description" style="margin-bottom: 16px; font-size: 13.5px; color: #2c3338;">
										<?php esc_html_e( 'Enter your API key below to enable automated updates, PDF indexing, and premium support.', 'relevanssi' ); ?>
									</p>

									<div style="display: flex; gap: 8px; max-width: 480px;">
										<input type="text" name="relevanssi_api_key" id="relevanssi_api_key" class="regular-text" placeholder="<?php esc_html_e( 'Enter your api key...', 'relevanssi' ); ?>" style="flex-grow: 1; font-family: monospace;" />
									</div>
									<p class="description" style="margin-top: 10px; font-size: 12px; color: #47494e;">
										<?php esc_html_e( 'Enter your token and save changes to update the local instance configuration.', 'relevanssi' ); ?>
									</p>
								<?php endif; ?>
							</div>
						</div>

						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box" style="height: 100%; box-sizing: border-box; margin-bottom: 0;">
								<h3 style="margin-top: 0; font-size: 14px;"><?php esc_html_e( 'License Verification', 'relevanssi' ); ?></h3>
								<p style="font-size: 13px; line-height: 1.5; margin-bottom: 16px; color: #50575e;">
									<?php esc_html_e( 'An active API key lets you use PDF indexing, automatic update services, and premium support.', 'relevanssi' ); ?>
								</p>
								<a href="https://www.relevanssi.com/account/" target="_blank" rel="noopener noreferrer" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
									<?php esc_html_e( 'Access Account Profile', 'relevanssi' ); ?>
									<span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; margin: 0; position: relative; top: 1px;"></span>
								</a>
							</div>
						</aside>
					</div>
				<?php endif; ?>

			<?php else : ?>
				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-license-upsell" style="padding: 24px; background: #f0f6fa; border: 1px dashed #005885;">
							<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #d2e3ed; padding-bottom: 14px;">
								<h2 style="margin: 0; padding: 0; border: none; font-size: 1.25rem; font-weight: 600; color: #1d2327;">
									<?php esc_html_e( 'Unlock Professional Search Power', 'relevanssi' ); ?>
								</h2>
								<span class="badge" style="background: #d63638; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Using Standard Edition', 'relevanssi' ); ?></span>
							</div>

							<p style="font-size: 14px; line-height: 1.5; color: #2c3338; margin-bottom: 16px;">
								<?php esc_html_e( 'You are currently running Relevanssi Standard. Upgrading to a premium license unlocks a deep-scanning engine that lets visitors search inside files, user profiles, and complex custom plugin settings seamlessly.', 'relevanssi' ); ?>
							</p>

							<h4 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 600; color: #1d2327;"><?php esc_html_e( 'What is included in the Premium Edition Upgrade:', 'relevanssi' ); ?></h4>
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px 24px; font-size: 13px; color: #50575e; margin-bottom: 20px;">
								<div><strong>&bull; <?php esc_html_e( 'PDF &amp; Document Reading:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Extract and index raw text hidden inside your attached media assets including PDFs, Word docs, and text files.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Multi-site Network Queries:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Execute cross-site queries concurrently using unified background indexing data tables across your network.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Algorithmic Relevance Tuning:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Fine-tune scoring weights separately by individual post types, custom taxonomies, and post publication dates.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Advanced Query Operators:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Unlock true Boolean "NOT" exclusion logic operations and automatic "AND-to-OR" fallback operator shifting.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Terminal Engine Management:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Full WP-CLI execution support to build indexes, flush query logs, and run diagnostics without dashboard timeout bottlenecks.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Profile & Taxonomy Discovery:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Extend search paths to read and index user biographical profile nodes and stand-alone taxonomy archive landing pages.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Keyword English Stemming:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Automatically break down words to their base linguistic roots so searches for variations return exact core contextual matches.', 'relevanssi' ); ?></div>
								<div><strong>&bull; <?php esc_html_e( 'Priority Developer Support:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Gain direct access to our premium ticketing infrastructure to resolve system conflicts and custom implementation code blocks quickly.', 'relevanssi' ); ?></div>
							</div>

							<p style="margin: 0; font-size: 13px;">
								<a href="https://www.relevanssi.com/buy-premium/" target="_blank" rel="noopener noreferrer" style="color: #005885; text-decoration: none; font-weight: 600; border-bottom: 1px solid #005885; padding-bottom: 1px;">
									<?php esc_html_e( 'Upgrade to Premium and unlock these advanced options →', 'relevanssi' ); ?>
								</a>
							</p>
						</div>
					</div>

					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box" >
							<h3><?php esc_html_e( 'Get 20% Off Today', 'relevanssi' ); ?></h3>
							<p style="font-size: 13px; line-height: 1.5; margin-bottom: 16px; color: #50575e;">
								<?php
								/* translators: %1$s is the coupon code bolded, %2$s is the year. */
								printf( esc_html__( 'Use coupon code %1$s at checkout for a 20%% initialization discount. Valid through the end of %2$s.', 'relevanssi' ), '<strong>FREE2026</strong>', '2026' );
								?>
							</p>
							<a href="https://www.relevanssi.com/buy-premium/" target="_blank" rel="noopener noreferrer" class="button button-primary" style="text-align: center; display: block; width: 100%; box-sizing: border-box;">
								<?php esc_html_e( 'Upgrade License Now', 'relevanssi' ); ?>
							</a>
						</div>
					</aside>
				</div>
			<?php endif; ?>

			<div class="relevanssi-index-grid" style="margin-bottom: 24px;">

				<div class="relevanssi-card" id="card-search-index-status" style="display: flex; flex-direction: column; justify-content: space-between;">
					<div>
						<h2><?php esc_html_e( 'Search Index Status', 'relevanssi' ); ?></h2>

						<?php if ( 'done' !== get_option( 'relevanssi_indexed' ) ) : ?>
							<div class="relevanssi-notice relevanssi-notice-warning" style="margin-top: 0; margin-bottom: 20px;">
								<p><strong><?php esc_html_e( 'Index Pending:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Your search index has not been built yet. Relevanssi cannot route search queries accurately until initialization is complete.', 'relevanssi' ); ?></p>
							</div>
						<?php else : ?>
							<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
								<div style="background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px; padding: 16px; text-align: center;">
									<span style="display: block; font-size: 12px; font-weight: 500; color: #47494e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;"><?php esc_html_e( 'Indexed Documents', 'relevanssi' ); ?></span>
									<span class="metric-number" style="font-size: 1.8rem; line-height: 1.2; display: block; font-weight: 600; color: #1d2327;"><?php echo esc_html( number_format_i18n( $docs_count ) ); ?></span>
								</div>
								<div style="background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px; padding: 16px; text-align: center;">
									<span style="display: block; font-size: 12px; font-weight: 500; color: #47494e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;"><?php esc_html_e( 'Unique Search Terms', 'relevanssi' ); ?></span>
									<span class="metric-number" style="font-size: 1.8rem; line-height: 1.2; display: block; font-weight: 600; color: #1d2327;"><?php echo esc_html( number_format_i18n( $terms_count ) ); ?></span>
								</div>
								<?php if ( $is_premium ) : ?>
										<div style="background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px; padding: 16px; text-align: center;">
											<span style="display: block; font-size: 12px; font-weight: 500; color: #47494e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;"><?php esc_html_e( 'User Profiles', 'relevanssi' ); ?></span>
											<span class="metric-number" style="font-size: 1.8rem; line-height: 1.2; display: block; font-weight: 600; color: #1d2327;"><?php echo esc_html( number_format_i18n( $user_count ) ); ?></span>
										</div>

										<div style="background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px; padding: 16px; text-align: center;">
											<span style="display: block; font-size: 12px; font-weight: 500; color: #47494e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;"><?php esc_html_e( 'Taxonomy Terms', 'relevanssi' ); ?></span>
											<span class="metric-number" style="font-size: 1.8rem; line-height: 1.2; display: block; font-weight: 600; color: #1d2327;"><?php echo esc_html( number_format_i18n( $taxterm_count ) ); ?></span>
										</div>
								<?php endif; ?>
							</div>

							<h3 style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #1d2327; margin: 0 0 12px 0; padding-bottom: 6px; border-bottom: 1px solid #f0f0f1;"><?php esc_html_e( 'Engine Parameters Status', 'relevanssi' ); ?></h3>
							<ul style="margin: 0 0 24px 0; padding: 0; list-style: none; font-size: 13px;">
								<li style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
									<span style="color: #47494e;"><?php esc_html_e( 'Database Tables', 'relevanssi' ); ?></span>
									<?php if ( $table_ready ) : ?>
										<span style="font-weight: 500; color: #176E34; display: inline-flex; align-items: center; gap: 4px;">
											<span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span><?php esc_html_e( 'Operational', 'relevanssi' ); ?>
										</span>
									<?php else : ?>
										<span style="font-weight: 500; color: #d63638; display: inline-flex; align-items: center; gap: 4px;">
											<span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span><?php esc_html_e( 'Tables Missing', 'relevanssi' ); ?>
										</span>
									<?php endif; ?>
								</li>
								<li style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
									<span style="color: #47494e;"><?php esc_html_e( 'Search Query Logging', 'relevanssi' ); ?></span>
									<?php if ( $is_logging_active ) : ?>
										<span style="font-weight: 500; color: #176E34; display: inline-flex; align-items: center; gap: 4px;">
											<span class="dashicons dashicons-chart-bar" style="font-size: 14px; width: 14px; height: 14px; margin: 0;"></span><?php esc_html_e( 'Recording', 'relevanssi' ); ?>
										</span>
									<?php else : ?>
										<span style="font-weight: 500; color: #47494e; display: inline-flex; align-items: center; gap: 4px;">
											<span class="dashicons dashicons-reformat" style="font-size: 14px; width: 14px; height: 14px; margin: 0;"></span><?php esc_html_e( 'Inactive', 'relevanssi' ); ?>
										</span>
									<?php endif; ?>
								</li>
								<li style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
									<span style="color: #47494e;"><?php esc_html_e( '"Did you mean?" Search', 'relevanssi' ); ?></span>
									<?php if ( $is_didyoumean_active && $is_premium ) : ?>
										<span style="font-weight: 500; color: #176E34; display: inline-flex; align-items: center; gap: 4px;">
											<?php esc_html_e( 'Enabled', 'relevanssi' ); ?>
										</span>
									<?php else : ?>
										<span style="font-weight: 500; color: #47494e; display: inline-flex; align-items: center; gap: 4px;">
											<?php esc_html_e( 'Disabled', 'relevanssi' ); ?>
										</span>
									<?php endif; ?>
								</li>

								<?php if ( $is_premium ) : ?>
									<li style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
										<span style="color: #47494e;"><?php esc_html_e( 'Voice Search', 'relevanssi' ); ?></span>
										<?php if ( $is_voicesearch_active ) : ?>
											<span style="font-weight: 500; color: #176E34; display: inline-flex; align-items: center; gap: 4px;">
												<?php esc_html_e( 'Enabled', 'relevanssi' ); ?>
											</span>
										<?php else : ?>
											<span style="font-weight: 500; color: #47494e; display: inline-flex; align-items: center; gap: 4px;">
												<?php esc_html_e( 'Disabled', 'relevanssi' ); ?>
											</span>
										<?php endif; ?>
									</li>
									<li style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f1;">
										<span style="color: #47494e;"><?php esc_html_e( 'Related Posts', 'relevanssi' ); ?></span>
										<?php if ( $is_related_active ) : ?>
											<span style="font-weight: 500; color: #176E34; display: inline-flex; align-items: center; gap: 4px;">
												<?php esc_html_e( 'Enabled', 'relevanssi' ); ?>
											</span>
										<?php else : ?>
											<span style="font-weight: 500; color: #47494e; display: inline-flex; align-items: center; gap: 4px;">
												<?php esc_html_e( 'Disabled', 'relevanssi' ); ?>
											</span>
										<?php endif; ?>
									</li>
								<?php endif; ?>

								<li style="display: flex; justify-content: space-between; padding: 6px 0;">
									<span style="color: #47494e;"><?php esc_html_e( 'Relevanssi Tier', 'relevanssi' ); ?></span>
									<span style="font-weight: 600; color: #176E34;">
										<?php echo $is_premium ? esc_html__( 'Premium', 'relevanssi' ) : esc_html__( 'Standard', 'relevanssi' ); ?>
									</span>
								</li>
							</ul>
						<?php endif; ?>
					</div>

					<div>
						<div class="relevanssi-action-group" style="margin-bottom: 0; padding-top: 16px; border-top: 1px solid #f0f0f1;">
							<a href="<?php echo esc_attr( $this_page ); ?>&amp;rlv_tab=indexing" class="button button-primary">
								<?php 'done' !== get_option( 'relevanssi_indexed' ) ? esc_html_e( 'Build Search Index', 'relevanssi' ) : esc_html_e( 'Manage Indexing', 'relevanssi' ); ?>
							</a>
							<?php if ( 'done' === get_option( 'relevanssi_indexed' ) ) : ?>
								<a href="<?php echo esc_url( $update_url ); ?>" class="button button-outline"><?php esc_html_e( 'Update Counts', 'relevanssi' ); ?></a>
							<?php endif; ?>
						</div>

							<?php if ( $lowest_doc > 0 ) : ?>
							<div class="lowest-doc" style="background: #f0f2f5; border: none; margin-top: 16px; padding: 8px 12px; border-radius: 4px;">
								<?php // translators: %d is the internal database ID of the lowest document entry. ?>
								<p style="margin: 0; color: #47494e; font-size: 12px; font-family: monospace;"><?php printf( esc_html__( 'Lowest post ID indexed: %d', 'relevanssi' ), intval( $lowest_doc ) ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="relevanssi-card" id="card-setup-navigation-map">
					<h2><?php esc_html_e( 'Quick Links', 'relevanssi' ); ?></h2>
					<p class="description" style="margin-top: -8px; margin-bottom: 20px;">
							<?php esc_html_e( 'Navigate through core functional areas to tune your search configurations.', 'relevanssi' ); ?>
					</p>

					<ul style="margin: 0; padding-left: 0; list-style: none; display: flex; flex-direction: column; gap: 16px;">
						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-database" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'The "What": %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=indexing'>", esc_html__( 'Indexing Options', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'Control your data footprint. Define precisely which post types, taxonomies, terms, and custom fields are included in the search index.', 'relevanssi' ); ?></span>
							</div>
						</li>

						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-admin-settings" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'The "How": %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=searching'>", esc_html__( 'Search Behavior', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'Adjust search logic. Fine-tune keyword weights allocation rules, fallback operators, keyword matching methods, and synonyms.', 'relevanssi' ); ?></span>
							</div>
						</li>

						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-paperclip" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'Media Content: %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=attachments'>", esc_html__( 'Attachments Parsing', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'Read text content from attachments including PDFs, Word documents and other Media Library documents.', 'relevanssi' ); ?></span>
							</div>
						</li>

						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-desktop" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'User Experience: %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=display-ui'>", esc_html__( 'Display & UI Layouts', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'Everything your visitor interacts with. Enable Voice Search, customize excerpts, related posts, and snippet match highlights.', 'relevanssi' ); ?></span>
							</div>
						</li>

						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-code-standards" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'Maintenance: %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=admin-dev'>", esc_html__( 'Admin & Developer Utilities', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'System configurations space. Track user search metrics logs, protect your site with spam blocking, and import or export options.', 'relevanssi' ); ?></span>
							</div>
						</li>

						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-visibility" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'Diagnostics: %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=debugging'>", esc_html__( 'Debugging', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'Inspect background operations. Run search parameter check routines, review database structure anomalies directly, and flush caches.', 'relevanssi' ); ?></span>
							</div>
						</li>

						<li style="display: flex; align-items: flex-start; gap: 12px;">
							<span class="dashicons dashicons-sos" style="color: #1b853d; margin-top: 2px; font-size: 18px; width: 18px; height: 18px;"></span>
							<div>
								<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
								<strong style="display: block; font-size: 14px;"><?php printf( esc_html__( 'Get Help: %1$s%2$s%3$s', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;rlv_tab=help'>", esc_html__( 'Premium Help Desk', 'relevanssi' ), '</a>' ); ?></strong>
								<span class="description" style="margin-top: 2px; display: block;"><?php esc_html_e( 'Direct support form for active premium license holders.', 'relevanssi' ); ?></span>
							</div>
						</li>
					</ul>
				</div>
			</div>

			<div class="relevanssi-settings-row">

				<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">

					<div class="relevanssi-card" id="card-live-search-addon" style="margin-bottom: 24px;">
						<h2 style="border-bottom: none; margin-bottom: 8px; padding-bottom: 0;"><?php esc_html_e( 'Live Ajax Search Integration', 'relevanssi' ); ?></h2>
						<p class="description" style="margin-top: 0; margin-bottom: 16px; font-size: 14px;">
							<?php
							// Translators: %1$s opens the link, %2$s closes it.
							printf( esc_html__( 'Provide interactive instant results using the official companion plugin. %1$sGet Relevanssi Live Ajax Search from the plugin repository%2$s.', 'relevanssi' ), "<a href='https://wordpress.org/plugins/relevanssi-live-ajax-search/' target='_blank'>", '</a>' );
							?>
						</p>
						<a href="https://wordpress.org/plugins/relevanssi-live-ajax-search/" target="_blank" class="button button-outline"><?php esc_html_e( 'Get Live Search Plugin', 'relevanssi' ); ?></a>
					</div>

					<div class="relevanssi-card" id="card-privacy-compliance">
						<h2><?php esc_html_e( 'Privacy, Security & GDPR Governance', 'relevanssi' ); ?></h2>
						<p style="margin: 0; line-height: 1.6; font-size: 13.5px; color: #50575e;">
							<?php
							// Translators: %1$s and %3$s open links, %2$s closes them.
							printf( esc_html__( 'Review our %1$sGDPR Compliance guide%2$s to learn how using Relevanssi affects your site. Relevanssi interfaces with native WordPress %3$sPrivacy Policy Tools%2$s to honor personal profile export and erase actions.', 'relevanssi' ), "<a href='https://www.relevanssi.com/knowledge-base/gdpr-compliance/' target='_blank'>", '</a>', "<a href='privacy.php'>" );
							?>
						</p>
					</div>


				</div>

				<?php if ( $is_premium ) : ?>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box" style="margin-bottom: 20px;">
							<h3><?php esc_html_e( 'Documentation Links', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'If you are having issues with Relevanssi, please consult the knowledge base to see if your problem has been solved before. Otherwise send us a support message in the Help tab!', 'relevanssi' ); ?></p>
							<div style="margin-top: 12px; font-weight: 500; display: flex; flex-direction: column; gap: 8px;">
								<div>
									<?php
									// Translators: %1$s opens the link, %2$s closes the link.
									printf( esc_html__( '&rarr; Read our deep %1$sKnowledge Base manual%2$s.', 'relevanssi' ), "<a href='https://www.relevanssi.com/knowledge-base/' target='_blank'>", '</a>' );
									?>
								</div>
								<div>
									<?php
									// Translators: %1$s opens the link, %2$s closes the link.
									printf( esc_html__( '&rarr; Read our %1$sUser Manual%2$s.', 'relevanssi' ), "<a href='https://www.relevanssi.com/user-manual/' target='_blank'>", '</a>' );
									?>
								</div>
							</div>
						</div>

						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Leave a review', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'If you had a positive experience with Relevanssi Premium, don\'t forget to leave a 5 star review!', 'relevanssi' ); ?></p>
							<p style="margin-top: 12px; font-weight: 600;">
								<?php
								// Translators: %1$s opens the link, %2$s closes the link.
								printf( esc_html__( '%1$sSubmit a five-star review on WordPress.org &rarr;%2$s', 'relevanssi' ), "<a href='https://wordpress.org/support/plugin/relevanssi/reviews/#new-post' target='_blank' style='text-decoration: none;'>", '</a>' );
								?>
							</p>
						</div>
					</aside>
				<?php endif; ?>
			</div>

		</div>
	</div>
	<?php
}