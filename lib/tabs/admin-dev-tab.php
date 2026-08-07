<?php
/**
 * /lib/tabs/admin-dev-tab.php
 *
 * Prints out the consolidated Admin & Dev tab in Relevanssi admin settings panel view.
 * Groups high-risk, diagnostic, and maintenance-heavy utilities into a sandboxed zone.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Admin & Dev tab in Relevanssi settings.
 *
 * @global wpdb $wpdb The WordPress database interface.
 *
 * @return void Writes dashboard control layouts structural markup rows directly.
 */
function relevanssi_admin_dev_tab() {
	global $wpdb;

	// --- State Logic: Environment Assessment ---
	$is_premium       = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;
	$has_hide_control = function_exists( 'relevanssi_get_hide_post_controls_config' );
	$has_call_home    = function_exists( 'relevanssi_get_do_not_call_home_config' );
	$has_translations = function_exists( 'relevanssi_get_update_translations_config' );

	// --- Configuration: Global Fields Configuration ---
	$global_controls_config = array();
	if ( $has_hide_control ) {
		$global_controls_config = array_merge( $global_controls_config, relevanssi_get_hide_post_controls_config() );
	}
	if ( $has_call_home ) {
		$global_controls_config = array_merge( $global_controls_config, relevanssi_get_do_not_call_home_config() );
	}
	if ( $has_translations ) {
		$global_controls_config = array_merge( $global_controls_config, relevanssi_get_update_translations_config() );
	}

	// --- Configuration: Spam Blocking Settings ---
	$spamblock_data = get_option( 'relevanssi_spamblock', array() );
	$bot_list_array = function_exists( 'relevanssi_bot_block_list' )
		? array_keys( apply_filters( 'relevanssi_bots_to_block', relevanssi_bot_block_list() ) )
		: array();

	$bots_string = ! empty( $bot_list_array )
		? implode( ', ', $bot_list_array ) . '. '
		: '';

	$spamblock_config = array(
		'relevanssi_spamblock_keywords' => array(
			'type'          => $is_premium ? 'textarea' : 'upsell',
			'label'         => __( 'Block keywords', 'relevanssi' ),
			'description'   => __( 'Enter keywords, one per line. If a search query contains any of these keywords, it will be blocked automatically.', 'relevanssi' ),
			'value'         => $spamblock_data['keywords'] ?? '',
			'feature_name'  => __( 'Advanced Spam Blocking', 'relevanssi' ),
			'features_list' => array(
				__( 'Keyword blocking: Prevent specific spam terms from running queries.', 'relevanssi' ),
				__( 'Regular expressions: Filter out complex patterns or malicious search scripts.', 'relevanssi' ),
				__( 'Character limits: Drop exceptionally long searches that can slow down your server.', 'relevanssi' ),
				__( 'Character set blocking: Block spam containing foreign alphabets (like Chinese or Cyrillic) or emojis.', 'relevanssi' ),
				__( 'Bot blocking: Stop automated crawlers from inflating search traffic numbers.', 'relevanssi' ),
			),
			'hover_target'  => 'sb-spam-keywords',
			'sidebar_title' => __( 'Spam Blocking:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Adding common spam domain extensions (e.g., <code>.shop</code>, <code>.online</code>, <code>.cn</code>) is an easy way to stop automated bots. <strong>Note:</strong> Blocked searches are stopped instantly and will not appear in your search logs.', 'relevanssi' ),
		),
		'relevanssi_spamblock_regex'    => array(
			'type'          => 'textarea',
			'label'         => __( 'Block by regular expression', 'relevanssi' ),
			/* translators: %1$s is /.../iu. */
			'description'   => sprintf( __( 'Enter regular expressions for advanced text matching. Your patterns will automatically use the %1$s delimiters.', 'relevanssi' ), "'/.../iu'" ),
			'value'         => $spamblock_data['regex'] ?? '',
			'hover_target'  => 'sb-spam-regex',
			'sidebar_title' => __( 'Regex Matching:', 'relevanssi' ),
			'sidebar_desc'  => sprintf(
				/* translators: %1$s opens the code tag, %2$s closes it. */
				__( 'Allows you to use regular expressions to block complex spam patterns. Expressions use case-insensitive Unicode matching (%1$s/.../iu%2$s).', 'relevanssi' ),
				'<code>',
				'</code>'
			),
			'visible'       => $is_premium,
		),
		'relevanssi_spamblock_limit'    => array(
			'type'        => 'number',
			'label'       => __( 'Maximum character limit', 'relevanssi' ),
			'description' => __( 'Searches longer than this limit will be blocked automatically. Set to 0 to disable this limit.', 'relevanssi' ),
			'value'       => $spamblock_data['limit'] ?? 0,
			'unit'        => __( 'characters', 'relevanssi' ),
			'min'         => 0,
			'visible'     => $is_premium,
		),
		'relevanssi_spamblock_chinese'  => array(
			'type'        => 'checkbox',
			'label'       => __( 'Block Chinese queries', 'relevanssi' ),
			'description' => __( 'Block search queries containing Chinese characters.', 'relevanssi' ),
			'value'       => $spamblock_data['chinese'] ?? 'off',
			'visible'     => $is_premium,
		),
		'relevanssi_spamblock_cyrillic' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Block Cyrillic queries', 'relevanssi' ),
			'description' => __( 'Block search queries containing Cyrillic characters.', 'relevanssi' ),
			'value'       => $spamblock_data['cyrillic'] ?? 'off',
			'visible'     => $is_premium,
		),
		'relevanssi_spamblock_emoji'    => array(
			'type'        => 'checkbox',
			'label'       => __( 'Block emoji queries', 'relevanssi' ),
			'description' => __( 'Block search queries containing emojis.', 'relevanssi' ),
			'value'       => $spamblock_data['emoji'] ?? 'off',
			'visible'     => $is_premium,
		),
		'relevanssi_spamblock_bots'     => array(
			'type'          => 'checkbox',
			'label'         => __( 'Block bot queries', 'relevanssi' ),
			'description'   => __( 'Block search requests coming from automated crawlers and bots.', 'relevanssi' ),
			'value'         => $spamblock_data['bots'] ?? 'off',
			'hover_target'  => 'sb-spam-bots',
			'sidebar_title' => __( 'Bot Block List:', 'relevanssi' ),
			'sidebar_desc'  => esc_html( $bots_string ) . sprintf(
				/* translators: %1$s is the filter hook name wrapped in code tags. */
				__( 'You can add custom bots to this list using the %1$s filter.', 'relevanssi' ),
				'<code>relevanssi_bots_to_block</code>'
			),
			'visible'       => $is_premium,
		),
	);

	// --- Security Check & Nonce Initialization ---
	wp_nonce_field( 'relevanssi_export_logs', '_relevanssi_export_nonce', true, true );

	// --- Configuration: Query Logging Options ---
	$trim_logs_value = get_option( 'relevanssi_trim_logs', '0' );
	$trim_notice     = ( '0' === (string) $trim_logs_value )
		? array(
			'type' => 'info',
			'text' => __( 'Over time, huge log tables can slow down your search performance. We recommend setting up automatic log trimming below.', 'relevanssi' ),
		)
		: false;

	$query_logging_config = array(
		'relevanssi_log_queries'         => array(
			'type'          => 'checkbox',
			'label'         => __( 'Enable query logging', 'relevanssi' ),
			'description'   => __( 'Record the search terms entered by your site visitors.', 'relevanssi' ),
			'hover_target'  => 'sb-log-queries',
			'value'         => get_option( 'relevanssi_log_queries', 'off' ),
			'sidebar_title' => __( 'Search Logging:', 'relevanssi' ),
			'sidebar_desc'  => sprintf(
				/* translators: %s: SQL database table name string wrapper */
				__( "Saves user search terms so you can view popularity reports under 'User searches' in your WordPress menu. This data is stored in the %s table.", 'relevanssi' ),
				'<code>' . esc_html( $wpdb->prefix . 'relevanssi_log' ) . '</code>'
			),
		),
		'relevanssi_log_queries_with_ip' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Log user IP addresses', 'relevanssi' ),
			'description'   => __( "Save the user's IP address alongside their search queries.", 'relevanssi' ),
			'hover_target'  => 'sb-log-ip',
			'value'         => get_option( 'relevanssi_log_queries_with_ip', 'off' ),
			'sidebar_title' => __( 'IP Address Logging:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Saves IP data with search logs. Note: Logging IP addresses may violate local privacy regulations or require GDPR compliance steps in the EU.', 'relevanssi' ),
		),
		'relevanssi_omit_from_logs'      => array(
			'type'          => 'text',
			'label'         => __( 'Exclude users from logs', 'relevanssi' ),
			'hover_target'  => 'sb-exclude-users',
			'value'         => get_option( 'relevanssi_omit_from_logs', '' ),
			'sidebar_title' => __( 'Exclude Users:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Enter a comma-separated list of numeric user IDs or usernames to stop their searches from being logged.', 'relevanssi' ),
		),
		'relevanssi_trim_logs'           => array(
			'type'          => 'number',
			'min'           => 0,
			'label'         => __( 'Automatically delete old logs', 'relevanssi' ),
			'hover_target'  => 'sb-trim-logs',
			'value'         => $trim_logs_value,
			'unit'          => __( 'days', 'relevanssi' ),
			'notice'        => $trim_notice,
			/* translators: %d is the value for disabling trimming (usually 0). */
			'tooltip'       => sprintf( __( 'Set to %d to keep search logs forever.', 'relevanssi' ), 0 ),
			'sidebar_title' => __( 'Log Trimming:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Keeps your database fast and lightweight by automatically deleting search records older than the specified number of days.', 'relevanssi' ),
		),
		'relevanssi_hide_branding'       => array(
			'type'          => 'checkbox',
			'label'         => __( 'Hide Relevanssi branding', 'relevanssi' ),
			'description'   => sprintf(
				/* translators: %s is the name of the 'User Searches' interface screen */
				__( "Hide the Relevanssi logo and welcome headers on the '%s' dashboard page.", 'relevanssi' ),
				__( 'User Searches', 'relevanssi' )
			),
			'hover_target'  => 'sb-hide-branding',
			'value'         => get_option( 'relevanssi_hide_branding', 'off' ),
			'sidebar_title' => __( 'Hide Branding:', 'relevanssi' ),
			/* translators: title of the User Searches page */
			'sidebar_desc'  => __( 'Removes plugin logos and promotional branding from the search history screen for a cleaner interface.', 'relevanssi' ),
		),
	);

	// --- Configuration: Maintenance & Portability Utilities ---
	$maintenance_config = array(
		'relevanssi_export_logs' => array(
			'type'          => 'submit_button',
			'label'         => __( 'Export logs', 'relevanssi' ),
			'hover_target'  => 'sb-export-logs',
			'button_label'  => __( 'Export logs to CSV', 'relevanssi' ),
			'button_type'   => 'secondary',
			'button_name'   => 'relevanssi_export',
			'description'   => __( 'Click to download your entire search history as a standard CSV file.', 'relevanssi' ),
			'sidebar_title' => __( 'Log Export:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Downloads your logged search queries into a CSV file that you can easily view and sort in Excel or Google Sheets.', 'relevanssi' ),
		),
	);

	// --- Configuration: Click Tracking Settings ---
	$trim_clicks_value     = get_option( 'relevanssi_trim_click_logs', '0' );
	$click_tracking_config = array(
		'relevanssi_click_tracking'  => array(
			'type'          => $is_premium ? 'checkbox' : 'upsell',
			'label'         => __( 'Enable click tracking', 'relevanssi' ),
			'description'   => __( 'Track which links visitors click on your search results pages.', 'relevanssi' ),
			'feature_name'  => __( 'Search Result Click Analytics', 'relevanssi' ),
			'features_list' => array(
				__( 'Click tracking: Monitor which results are clicked most often to find your most popular content.', 'relevanssi' ),
				__( 'Log trimming: Automatically clear out old click records to save database space.', 'relevanssi' ),
				__( 'Log exporting: Export click interaction statistics directly to a CSV file.', 'relevanssi' ),
			),
			'hover_target'  => 'sb-click-tracking',
			'value'         => get_option( 'relevanssi_click_tracking', 'off' ),
			'sidebar_title' => __( 'Click Tracking:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Measures user engagement by tracking where visitors click. This helps you discover if your search results are relevant and helpful.', 'relevanssi' ),
		),
		'relevanssi_trim_click_logs' => array(
			'type'          => 'number',
			'min'           => 0,
			'label'         => __( 'Automatically delete click logs', 'relevanssi' ),
			'hover_target'  => 'sb-trim-click-logs',
			'value'         => $trim_clicks_value,
			'unit'          => __( 'days', 'relevanssi' ),
			'notice'        => array(
				'type' => 'info',
				/* translators: %d is the value for disabling trimming (usually 0). */
				'text' => sprintf( __( 'Set to %d to keep click logs forever. Click records are small and can usually be kept longer than search text queries.', 'relevanssi' ), 0 ),
			),
			'sidebar_title' => __( 'Click Log Trimming:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Sets how many days of search click data to keep before automatically deleting old entries.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_export_clicks'   => array(
			'type'          => 'submit_button',
			'label'         => __( 'Export click logs', 'relevanssi' ),
			'hover_target'  => 'sb-export-clicks',
			'button_label'  => __( 'Export click tracking logs to CSV', 'relevanssi' ),
			'button_type'   => 'secondary',
			'button_name'   => 'relevanssi_export_clicks',
			'description'   => __( 'Click to download your visitor click history as a CSV file.', 'relevanssi' ),
			'sidebar_title' => __( 'Click Log Export:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Downloads your user interaction history into a spreadsheet-compatible file.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
	);

	// --- Configuration: Import / Export Options ---
	$serialized_options   = function_exists( 'relevanssi_serialize_options' ) ? relevanssi_serialize_options() : '';
	$import_export_config = array(
		'relevanssi_settings' => array(
			'type'          => $is_premium ? 'textarea' : 'upsell',
			'label'         => __( 'Export settings', 'relevanssi' ),
			'rows'          => 6,
			'cols'          => 80,
			'value'         => $serialized_options,
			'feature_name'  => __( 'Import/Export', 'relevanssi' ),
			'features_list' => array(
				__( 'Configuration export: Package your entire setup into a simple text code for backups.', 'relevanssi' ),
				__( 'Configuration import: Paste a saved settings code to instantly copy your setup to another site.', 'relevanssi' ),
			),
			'description'   => __( 'Copy the text from this field to back up your setup. Paste a configuration code here to restore or import settings.', 'relevanssi' ),
			'hover_target'  => 'sb-current-settings',
			'sidebar_title' => __( 'Export/Import Settings:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Easily duplicate your configuration settings when moving between local development, staging environments, or live production sites.', 'relevanssi' ),
		),
		'import_options'      => array(
			'type'          => 'submit_button',
			'button_label'  => __( 'Import settings', 'relevanssi' ),
			'button_type'   => 'secondary',
			'button_name'   => 'import_options',
			'hover_target'  => 'sb-current-settings',
			'sidebar_title' => __( 'Import Warning:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Make sure this settings code comes from a compatible version of Relevanssi to avoid unexpected layout errors.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
	);
	?>

	<div id="admin_dev_tab_consolidated" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Admin & Dev Settings', 'relevanssi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Maintenance utilities, query logs, spam protection, and migration tools.', 'relevanssi' ); ?></p>

		<div class="relevanssi-dashboard-layout">
			<div class="relevanssi-main">

				<?php if ( ! empty( $global_controls_config ) ) : ?>
					<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
						<div class="relevanssi-settings-content">
							<div class="relevanssi-card" id="card-global-controls">
								<h2><?php esc_html_e( 'Global Settings', 'relevanssi' ); ?></h2>
								<p class="description" style="margin-bottom: 20px;">
									<?php esc_html_e( 'Manage general plugin visibility, updates, and global configuration values.', 'relevanssi' ); ?>
								</p>
								<?php Relevanssi_Settings_Renderer::render_table( $global_controls_config ); ?>
							</div>
						</div>
						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box">
								<h3><?php esc_html_e( 'Global Controls Guide', 'relevanssi' ); ?></h3>
								<ul class="relevanssi-sidebar-list">
									<?php Relevanssi_Settings_Renderer::render_sidebar_list( $global_controls_config ); ?>
								</ul>
							</div>
						</aside>
					</div>
				<?php endif; ?>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">
						<div class="relevanssi-card" id="card-spamblock">
							<h2><?php esc_html_e( 'Spam Blocking', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php
								if ( $is_premium ) {
									printf(
										/* translators: %1$s opens the knowledge base link, %2$s closes it. */
										esc_html__( 'Block search spam and malicious bot queries before they impact server performance. %1$sKnowledge Base article →%2$s', 'relevanssi' ),
										'<a href="https://www.relevanssi.com/knowledge-base/features/spam-search-blocking/" target="_blank" rel="noopener noreferrer" style="text-decoration: none; margin-left: 4px;">',
										'</a>'
									);
								} else {
									esc_html_e( 'Protect database performance by keeping automated bot queries out of your search logs.', 'relevanssi' );
								}
								?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $spamblock_config ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box">
								<h3><?php esc_html_e( 'Spam Protection', 'relevanssi' ); ?></h3>
								<p><?php esc_html_e( 'Block malicious search patterns and bot tracking from executing database queries.', 'relevanssi' ); ?></p>
								<ul class="relevanssi-sidebar-list">
									<?php Relevanssi_Settings_Renderer::render_sidebar_list( $spamblock_config ); ?>
								</ul>
							</div>
						</aside>
					<?php endif; ?>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-query-logging">
							<h2><?php esc_html_e( 'User Query Logging', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php esc_html_e( 'Record and analyze search terms entered by your site visitors.', 'relevanssi' ); ?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $query_logging_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Logging Controls', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Manage how user searches are saved to the database.', 'relevanssi' ); ?></p>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $query_logging_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-log-maintenance">
							<h2><?php esc_html_e( 'Log Maintenance', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $maintenance_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Maintenance Options', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $maintenance_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">
						<div class="relevanssi-card" id="card-click-tracking">
							<h2><?php esc_html_e( 'Click Tracking', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php
								if ( $is_premium ) {
									esc_html_e( 'Track which specific posts are clicked from search results. Click data is also displayed on individual post editing screens.', 'relevanssi' );
								} else {
									esc_html_e( 'See exactly what content your users find interesting by tracking search result clicks.', 'relevanssi' );
								}
								?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $click_tracking_config ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box">
								<h3><?php esc_html_e( 'Click Analytics', 'relevanssi' ); ?></h3>
								<p><?php esc_html_e( 'Analyze search result link engagement statistics over time.', 'relevanssi' ); ?></p>
								<ul class="relevanssi-sidebar-list">
									<?php Relevanssi_Settings_Renderer::render_sidebar_list( $click_tracking_config ); ?>
								</ul>
							</div>
						</aside>
					<?php endif; ?>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">
						<div class="relevanssi-card" id="card-import-export">
							<h2><?php esc_html_e( 'Import / Export Settings', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php
								if ( $is_premium ) {
									esc_html_e( 'Your current configuration is shown below as text. Copy it to create a backup or migrate settings to another site.', 'relevanssi' );
								} else {
									esc_html_e( 'Copy this text block to back up your setup or deploy it across other environments.', 'relevanssi' );
								}
								?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $import_export_config ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box">
								<h3><?php esc_html_e( 'Configuration Engine', 'relevanssi' ); ?></h3>
								<ul class="relevanssi-sidebar-list">
									<?php Relevanssi_Settings_Renderer::render_sidebar_list( $import_export_config ); ?>
								</ul>
							</div>
						</aside>
					<?php endif; ?>
				</div>

				<?php do_action( 'relevanssi_debugging_tab_content' ); ?>

			</div>
		</div>
	</div>
	<?php
}