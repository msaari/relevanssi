<?php
/**
 * /lib/tabs/debugging-tab.php
 *
 * Prints out the consolidated Debugging tab dashboard in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the debugging tab in Relevanssi settings.
 */
function relevanssi_debugging_tab() {
	// --- State Logic: Processing ---
	$how_relevanssi_sees = '';
	$db_post_view        = '';
	$current_post_id     = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
	$current_db_post_id  = isset( $_REQUEST['db_post_id'] ) ? intval( $_REQUEST['db_post_id'] ) : 0;
	$selected_type       = isset( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : 'post';

	if ( $current_post_id > 0 ) {
		wp_verify_nonce( '_relevanssi_nonce', 'relevanssi_how_relevanssi_sees' );
		$how_relevanssi_sees = relevanssi_generate_how_relevanssi_sees( $current_post_id, true, $selected_type );
	}

	if ( $current_db_post_id > 0 ) {
		wp_verify_nonce( '_relevanssi_nonce', 'relevanssi_how_relevanssi_sees' );
		$db_post_view = relevanssi_generate_db_post_view( $current_db_post_id );
	}

	wp_nonce_field( 'relevanssi_how_relevanssi_sees', '_relevanssi_nonce', true, true );

	// --- Configuration: Field Definitions ---

	// Debugging / Post Check.
	$sees_config = array(
		'post_id' => array(
			'type'          => 'text',
			'label'         => __( 'Item ID', 'relevanssi' ),
			'value'         => $current_post_id > 0 ? $current_post_id : '',
			'description'   => __( 'Enter the numeric ID of the item you want to test.', 'relevanssi' ),
			'hover_target'  => 'sb-inspect-id',
			'sidebar_title' => __( 'Content Inspection:', 'relevanssi' ),
			'sidebar_desc'  => __( 'This is the raw text Relevanssi has indexed from your content. Tags, shortcodes and layouts are stripped off, and all individual words are listed in alphabetical order.', 'relevanssi' ),
		),
	);

	if ( RELEVANSSI_PREMIUM ) {
		$sees_config['type'] = array(
			'type'    => 'select',
			'label'   => __( 'Content Type', 'relevanssi' ),
			'value'   => $selected_type,
			'options' => array(
				'post' => __( 'Post or Page', 'relevanssi' ),
				'term' => __( 'Category or Tag (Taxonomy)', 'relevanssi' ),
				'user' => __( 'User Profile', 'relevanssi' ),
			),
		);
	}

	$sees_config['submit_post'] = array(
		'type'         => 'submit_button',
		'label'        => '',
		'button_label' => __( 'Analyze Content', 'relevanssi' ),
		'button_type'  => 'primary',
		'button_name'  => 'submit',
	);

	// Database View.
	$db_view_config = array(
		'db_post_id' => array(
			'type'          => 'text',
			'label'         => __( 'Item ID', 'relevanssi' ),
			'value'         => $current_db_post_id > 0 ? $current_db_post_id : '',
			'description'   => __( 'Enter the numeric ID to check its saved database terms.', 'relevanssi' ),
			'hover_target'  => 'sb-db-id',
			'sidebar_title' => __( 'Database View:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Look inside the wp_posts table to see how the post content appears there. This can be useful when debugging why Relevanssi indexes a post in a particular way, and to figure out how to eliminate shortcode content from pages.', 'relevanssi' ),
		),
		'submit_db'  => array(
			'type'         => 'submit_button',
			'label'        => '',
			'button_label' => __( 'Examine Database Entry', 'relevanssi' ),
			'button_type'  => 'primary',
			'button_name'  => 'submit',
		),
	);

	// Debugging Mode.
	$debug_mode_notice = array(
		'type' => 'info',
		'text' => wp_sprintf(
			__( 'Note: Enabling this option runs diagnostics in the background. It will not display any text on your live website layout.', 'relevanssi' )
		),
	);

	$debugging_mode_config = array(
		'relevanssi_debugging_mode' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Enable background debugging logs', 'relevanssi' ),
			'description'   => __( 'Log search and indexing operations to help locate hidden issues.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_debugging_mode', 'off' ),
			'notice'        => $debug_mode_notice,
			'hover_target'  => 'sb-debug-mode',
			'sidebar_title' => __( 'Diagnostic Logs:', 'relevanssi' ),
			'sidebar_desc'  => __( "Saves behind the scenes data logs. Useful for troubleshooting issues without disrupting site visitors. Our support team might ask you to turn on this setting, and it's a good idea to enable this before asking for help with a question related to your site search or indexing.", 'relevanssi' ),
		),
	);

	?>
	<div id="admin_dev_tab_consolidated" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Debugging & Diagnostics', 'relevanssi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Tools to help you see how your content is indexed and troubleshoot ranking or visibility issues.', 'relevanssi' ); ?></p>

		<div class="relevanssi-card" id="card-debugging-guide" style="margin-top: 20px; background: #fff; border-left: 4px solid #72aee6; padding: 20px;">
			<h2 style="margin-top: 0; padding-bottom: 8px; font-size: 15px; font-weight: 600; border-bottom: 1px solid #f0f0f1;">
				<span class="dashicons dashicons-editor-help" style="vertical-align: text-bottom; margin-right: 4px; color: #72aee6;"></span>
				<?php esc_html_e( 'Quick Guide: How to Find Content IDs', 'relevanssi' ); ?>
			</h2>
			<p style="margin-bottom: 12px; font-size: 13px; color: #50575e;">
				<?php esc_html_e( 'The diagnostic tools below require a specific identification number (ID) to inspect your content. Here is how to find it natively:', 'relevanssi' ); ?>
			</p>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; font-size: 13px; line-height: 1.5;">
				<div>
					<strong><?php esc_html_e( 'For Posts, Pages, or Products:', 'relevanssi' ); ?></strong>
					<ol style="margin: 4px 0 0 20px; padding: 0; list-style-type: decimal; color: #50575e;">
						<li><?php esc_html_e( 'Go to your content list (e.g., Posts > All Posts).', 'relevanssi' ); ?></li>
						<li><?php esc_html_e( 'Hover your mouse over the title of the item you want to inspect.', 'relevanssi' ); ?></li>
						<li><?php esc_html_e( 'Look at the status bar at the bottom left of your web browser. The number following "post=" inside the URL is your ID (for example, if the URL ends in post=123, the ID is 123).', 'relevanssi' ); ?></li>
					</ol>
					<?php if ( RELEVANSSI_PREMIUM ) : ?>
						<p style="margin: 10px 0 0 0; font-size: 12px; color: #1b853d; font-weight: 500;">
							<?php esc_html_e( '💡 Tip: You do not need to look up IDs manually. This exact analysis engine is available directly inside your editor sidebar when editing any post, page, or category term. Click the "How Relevanssi sees this post" button.', 'relevanssi' ); ?>
						</p>
					<?php else : ?>
						<div style="margin-top: 12px; padding: 12px; background: #f0f6fa; border: 1px dashed #005885; border-radius: 4px;">
							<p style="margin: 0; font-size: 12.5px; line-height: 1.4; color: #1d2327; font-weight: 500;">
								<?php
								// Translators: %1$s opens a bold tag, %2$s closes it.
								printf( esc_html__( 'Skip manual ID lookups! %1$sRelevanssi Premium%2$s embeds this content analysis tool directly inside your WordPress editor sidebar for real-time debugging.', 'relevanssi' ), '<strong>', '</strong>' );
								?>
							</p>
							<p style="margin: 8px 0 0 0; font-size: 12px;">
								<?php
								// Translators: %1$s opens the purchase link, %2$s closes it.
								printf( esc_html__( '%1$sUpgrade to Relevanssi Premium →%2$s', 'relevanssi' ), '<a href="https://www.relevanssi.com/buy-premium/" target="_blank" style="color: #005885; text-decoration: none; font-weight: 600; border-bottom: 1px solid #005885; padding-bottom: 1px;">', '</a>' );
								?>
							</p>
						</div>
					<?php endif; ?>
				</div>
				<div>
					<strong><?php esc_html_e( 'For Categories, Tags, or Users:', 'relevanssi' ); ?></strong>
					<ol style="margin: 4px 0 0 20px; padding: 0; list-style-type: decimal; color: #50575e;">
						<li><?php esc_html_e( 'Navigate to Categories, Tags, or Users in your dashboard.', 'relevanssi' ); ?></li>
						<li><?php esc_html_e( 'Hover your mouse over the name of the specific row item.', 'relevanssi' ); ?></li>
						<li><?php esc_html_e( 'Look at the link layout inside your browser status bar. For categories and tags, use the number after "tag_ID=". For users, use the number after "user_id=".', 'relevanssi' ); ?></li>
					</ol>
				</div>
			</div>
		</div>

		<div class="relevanssi-dashboard-layout" style="margin-top: 24px;">
			<div class="relevanssi-main">

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-inspect-post">
							<h2><?php esc_html_e( 'Inspect how Relevanssi reads your content', 'relevanssi' ); ?></h2>
							<p><?php esc_html_e( 'If certain items are not being indexed correctly, enter their numeric ID below to view the plain text format Relevanssi generates from them.', 'relevanssi' ); ?></p>
							<?php
							if ( RELEVANSSI_PREMIUM ) {
								?>
								<p><?php esc_html_e( 'You can also inspect categories, tags, or user profiles by switching the Content Type dropdown.', 'relevanssi' ); ?></p>
								<?php
							} else {
								// Translators: %1$s starts the link, %2$s closes it.
								printf( '<p>' . esc_html__( 'In Relevanssi Premium, this diagnostic view is built right into your regular post editing pages. %1$sLearn more about Relevanssi Premium%2$s.', 'relevanssi' ) . '</p>', '<a href="https://www.relevanssi.com/buy-premium/">', '</a>' );
							}
							?>
							<?php Relevanssi_Settings_Renderer::render_table( $sees_config ); ?>
							<?php if ( ! empty( $how_relevanssi_sees ) ) : ?>
								<div class="relevanssi-debug-output" style="margin-top: 20px; background: #f6f7f7; border: 1px solid #c3c4c7; padding: 16px; border-radius: 4px; font-family: monospace;">
									<?php echo $how_relevanssi_sees; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Inspection Help', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $sees_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-db-view">
							<h2><?php esc_html_e( 'Check how the post content appears in the wp_posts database', 'relevanssi' ); ?></h2>
							<p><?php esc_html_e( 'This tool shows you exactly how the post content for a post is stored in the WordPress wp_posts database table.', 'relevanssi' ); ?></p>
							<?php Relevanssi_Settings_Renderer::render_table( $db_view_config ); ?>
							<?php if ( ! empty( $db_post_view ) ) : ?>
								<div class="relevanssi-debug-output" style="margin-top: 20px; background: #f6f7f7; border: 1px solid #c3c4c7; padding: 16px; border-radius: 4px; font-family: monospace;">
									<?php echo $db_post_view; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Database Help', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $db_view_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-debugging-mode">
							<h2><?php esc_html_e( 'Advanced Debugging Mode', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $debugging_mode_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Configuration Help', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $debugging_mode_config ); ?>
							</ul>
						</div>
					</aside>
				</div>


				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-debugging-info" style="margin-bottom: 24px;">
							<h2><?php esc_html_e( 'Database Indexing Query', 'relevanssi' ); ?></h2>
							<p><?php esc_html_e( 'This is the technical SQL database query Relevanssi executes to find posts that need indexing.', 'relevanssi' ); ?></p>
							<?php
							global $wpdb;
							$max_allowed_packet = round( $wpdb->get_var( 'SELECT @@global.max_allowed_packet' ) / 1024 / 1024, 2 );
							$indexing_query     = relevanssi_generate_indexing_query(
								relevanssi_valid_status_array(),
								false,
								relevanssi_post_type_restriction(),
								'LIMIT 0'
							);
							?>
							<p style="margin-bottom: 4px;"><strong>max_allowed_packet:</strong> <?php echo esc_html( $max_allowed_packet ); ?>M</p>
							<p class="description" style="margin-bottom: 12px; max-width: 100%;"><?php esc_html_e( 'This server configuration tracks the maximum size of data packets sent to your database. If this number is very low (e.g., under 16M), indexing exceptionally large posts or items with complex metadata could fail.', 'relevanssi' ); ?></p>
							<p style="margin-bottom: 6px;"><strong><?php esc_html_e( 'Raw Query Text:', 'relevanssi' ); ?></strong></p>
							<pre style="background: #f6f7f7; border: 1px solid #c3c4c7; padding: 12px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 12px; margin: 0; white-space: pre-wrap;"><code style="padding:0; background:transparent; border:none;"><?php echo esc_html( $indexing_query ); ?></code></pre>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
	<?php
}