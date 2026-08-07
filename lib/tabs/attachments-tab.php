<?php
/**
 * /lib/tabs/attachments-tab.php
 *
 * Prints out the Attachments tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Premium attachments tab in Relevanssi settings.
 *
 * @global wpdb $wpdb The WordPress database interface instance.
 *
 * @return void Outputs the attachment settings tab HTML.
 */
function relevanssi_attachments_tab() {
	global $wpdb;

	$is_premium = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;

	if ( ! $is_premium ) {
		// --- Free Tier: Premium Upgrade Overview ---
		?>
		<div class="wrap" id="attachments_tab_upsell">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Attachment Settings', 'relevanssi' ); ?></h1>

			<div class="relevanssi-dashboard-layout" style="margin-top: 20px;">
				<div class="relevanssi-main" style="width: 100%; max-width: 100%;">
					<div class="relevanssi-settings-row">
						<div class="relevanssi-settings-content">
							<div class="relevanssi-card" id="card-attachments-locked" style="padding: 24px;">
								<h2><?php esc_html_e( 'Search Inside Files', 'relevanssi' ); ?></h2>
								<p class="description" style="margin-bottom: 20px; font-size: 13.5px; line-height: 1.5; color: #646970;">
									<?php esc_html_e( 'Allow your visitors to search for text inside files (like PDFs and text documents) uploaded to your Media Library, instead of just matching post titles.', 'relevanssi' ); ?>
								</p>

								<div class="relevanssi-upsell-callout" style="padding: 16px; background: #f0f6fa; border: 1px dashed #005885; border-radius: 4px; max-width: 650px; box-sizing: border-box;">
									<p style="margin: 0 0 12px 0; font-size: 14px; line-height: 1.4; color: #1d2327; font-weight: 600;">
										<?php
										printf(
											/* translators: %1$s opens a strong tag, %2$s closes it. */
											esc_html__( 'Searching inside file attachments requires %1$sRelevanssi Premium%2$s.', 'relevanssi' ),
											'<strong>',
											'</strong>'
										);
										?>
									</p>

									<p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #2c3338;">
										<?php esc_html_e( 'What you unlock with Premium:', 'relevanssi' ); ?>
									</p>
									<ul style="margin: 0 0 16px 0; padding-left: 18px; list-style-type: disc; color: #50575e; font-size: 13px; line-height: 1.6;">
										<li><strong><?php esc_html_e( 'Read Text Inside Files:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Automatically scans and indexes content inside PDF, Word, Excel, and text documents.', 'relevanssi' ); ?></li>
										<li><strong><?php esc_html_e( 'Automatic Scanning:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Instantly reads new file uploads the moment they are added to your media library.', 'relevanssi' ); ?></li>
										<li><strong><?php esc_html_e( 'Link Directly to Files:', 'relevanssi' ); ?></strong>
										<?php
										/* translators: %s is the name of the attachment post type wrapped in code tags. */
										printf( esc_html__( 'Skips empty WordPress attachment landing pages and opens the actual document file when clicked in search results.', 'relevanssi' ), '<code>attachment</code>' );
										?>
										</li>
										<li><strong><?php esc_html_e( 'Works on Private Sites:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Safely reads documents even on local development environments, private intranets, or password-protected sites.', 'relevanssi' ); ?></li>
									</ul>

									<p style="margin: 0; font-size: 13px;">
										<a href="https://www.relevanssi.com/buy-premium/"
											target="_blank"
											rel="noopener noreferrer"
											style="color: #005885; text-decoration: none; font-weight: 600; border-bottom: 1px solid #005885; padding-bottom: 1px;">
											<?php esc_html_e( 'Upgrade to Premium and index your media library documents →', 'relevanssi' ); ?>
										</a>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return;
	}

	// --- Premium Tier: Settings & Configuration ---

	// --- Card 1: Attachment Actions ---
	$pdf_count = wp_cache_get( 'relevanssi_pdf_count' );
	if ( false === $pdf_count ) {
		$pdf_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_content' AND meta_value != ''" );
		wp_cache_set( 'relevanssi_pdf_count', $pdf_count );
	}

	$pdf_error_count = wp_cache_get( 'relevanssi_pdf_error_count' );
	if ( false === $pdf_error_count ) {
		$pdf_error_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_error' AND meta_value != ''" );
		wp_cache_set( 'relevanssi_pdf_error_count', $pdf_error_count );
	}

	try {
		$actions_handler = Relevanssi_Setting_Field_Factory::create(
			'attachment_actions',
			array(
				'type'            => 'attachment_manager',
				'pdf_count'       => $pdf_count,
				'pdf_error_count' => $pdf_error_count,
			)
		);
	} catch ( Exception $e ) {
		$actions_handler = null;
	}

	// --- Card 2: Configuration Settings ---
	$index_post_types     = get_option( 'relevanssi_index_post_types', array() );
	$index_pdf_parent     = get_option( 'relevanssi_index_pdf_parent' );
	$indexing_attachments = in_array( 'attachment', $index_post_types, true );

	$frontend_links_notice = false;
	if ( ! $indexing_attachments ) {
		$frontend_links_notice = array(
			'type' => 'warning',
			/* translators: %s is the code-styled name of the attachment post type. */
			'text' => sprintf( esc_html__( 'You are not indexing the %s content type under your Core Content settings, so these layout adjustments currently have no effect.', 'relevanssi' ), '<code>attachment</code>' ),
		);
	}
	if ( ! $indexing_attachments && ! $index_pdf_parent ) {
		$frontend_links_notice = array(
			'type' => 'warning',
			/* translators: %s is the code-styled name of the attachment post type. */
			'text' => sprintf( esc_html__( 'You are not indexing the %s content type and have not connected files to parent posts. Your files will not appear in search results.', 'relevanssi' ), '<code>attachment</code>' ),
		);
	}

	$attachments_config = array(
		'relevanssi_server_location' => array(
			'type'          => 'select',
			'label'         => __( 'Processing server location', 'relevanssi' ),
			'hover_target'  => 'sb-server-location',
			'value'         => get_option( 'relevanssi_server_location', 'us' ),
			'options'       => array(
				'us' => __( 'United States', 'relevanssi' ),
				'eu' => __( 'European Union', 'relevanssi' ),
			),
			'tooltip'       => __( 'Privacy: Choose the European Union if your website must comply with local privacy laws (GDPR).', 'relevanssi' ),
			'sidebar_title' => __( 'Processing Server:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Selects the server used to extract text from your files. Both options are completely secure and delete your files immediately after processing.', 'relevanssi' ),
		),
		'relevanssi_read_new_files'  => array(
			'type'           => 'checkbox',
			'label'          => __( 'Automation', 'relevanssi' ),
			'checkbox_label' => __( 'Read new files automatically', 'relevanssi' ),
			'description'    => __( 'Scans and extracts text from files automatically as soon as they are uploaded to your Media Library.', 'relevanssi' ),
			'hover_target'   => 'sb-automation',
			'value'          => get_option( 'relevanssi_read_new_files' ),
			'tooltip'        => __( 'Tip: Uncheck this temporarily if you are bulk-importing a large number of files to avoid server performance drops.', 'relevanssi' ),
			'sidebar_title'  => __( 'Automatic Scanning:', 'relevanssi' ),
			'sidebar_desc'   => __( 'Keeps your search index up to date automatically. Uploading very large documents might cause a brief delay during the upload process.', 'relevanssi' ),
		),
		'relevanssi_send_pdf_files'  => array(
			'type'           => 'checkbox',
			'label'          => __( 'File Access Method', 'relevanssi' ),
			'checkbox_label' => __( 'Upload files for reading', 'relevanssi' ),
			'description'    => __( 'Enable this if external servers cannot access your files (e.g., your site is on a private intranet, password-protected, or hosted on a local computer).', 'relevanssi' ),
			'hover_target'   => 'sb-file-access',
			'value'          => get_option( 'relevanssi_send_pdf_files' ),
			'tooltip'        => __( 'Note: This sends the file data directly to our extraction tool instead of passing a public download URL.', 'relevanssi' ),
			'sidebar_title'  => __( 'File Access:', 'relevanssi' ),
			'sidebar_desc'   => __( 'By default, Relevanssi sends a public file link to the text extractor. Turn this on if your site blocks outside web traffic to upload files directly instead.', 'relevanssi' ),
		),
		'relevanssi_link_pdf_files'  => array(
			'type'           => 'checkbox',
			'label'          => __( 'Frontend Link Destination', 'relevanssi' ),
			'checkbox_label' => __( 'Link search results directly to files', 'relevanssi' ),
			'description'    => __( 'Make search results open the actual document file instead of landing on an empty default WordPress attachment page.', 'relevanssi' ),
			'hover_target'   => 'sb-frontend-links',
			'value'          => get_option( 'relevanssi_link_pdf_files' ),
			'notice'         => $frontend_links_notice,
			'tooltip'        => __( 'SEO Tip: Direct linking prevents search engines like Google from indexing empty placeholder layout pages for your files.', 'relevanssi' ),
			'sidebar_title'  => __( 'Frontend Links:', 'relevanssi' ),
			'sidebar_desc'   => __( 'Determines where visitors are sent when they click a document or file in the search results.', 'relevanssi' ),
		),
	);
	?>
	<div class="wrap" id="attachments_tab_consolidated">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Attachment Settings', 'relevanssi' ); ?></h1>

		<div class="relevanssi-dashboard-layout" style="margin-top: 20px;">
			<div class="relevanssi-main">

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-attachment-actions">
							<?php
							if ( $actions_handler ) {
								$actions_handler->render();
							}
							?>
						</div>
					</div>

					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'How it works', 'relevanssi' ); ?></h3>
							<?php /* translators: %s is the name of the custom field hidden storage key */ ?>
							<p><?php printf( esc_html__( 'When a file is scanned, text is extracted and saved into a hidden custom field named %s.', 'relevanssi' ), '<code>_relevanssi_pdf_content</code>' ); ?></p>
							<?php /* translators: %s is the name of the code styled attachment post type */ ?>
							<p><?php printf( esc_html__( 'To ensure your media files are searchable, you must also make sure the %s content type is checked on your indexing settings tab.', 'relevanssi' ), '<code>attachment</code>' ); ?></p>

							<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">

							<h4 style="margin-top: 0; margin-bottom: 8px; font-weight: 600; color: #1d2327; font-size: 14px;"><?php esc_html_e( 'Processing Limitations:', 'relevanssi' ); ?></h4>
							<ul style="margin-top: 0; margin-bottom: 16px; padding-left: 20px; list-style-type: disc; font-size: 13px; color: #3c434a; line-height: 1.5;">
								<li>
									<strong><?php esc_html_e( 'File Size Limits:', 'relevanssi' ); ?></strong>
									<?php esc_html_e( 'Maximum file size is 256 MB. Files larger than this limit cannot be processed.', 'relevanssi' ); ?>
								</li>
								<li style="margin-top: 6px;">
									<strong><?php esc_html_e( 'Supported Formats:', 'relevanssi' ); ?></strong>
									<?php esc_html_e( 'PDFs with selectable text layers, Word documents (DOC/DOCX), OpenDocument Format (ODF), Rich Text Format (RTF), and Electronic Publication format (EPUB) files.', 'relevanssi' ); ?>
								</li>
								<li style="margin-top: 6px;">
									<strong><?php esc_html_e( 'Incompatible Content:', 'relevanssi' ); ?></strong>
									<?php esc_html_e( 'Scanned images/PDFs (unless they already contain selectable text layers) and compressed archives (like ZIP files).', 'relevanssi' ); ?>
								</li>
							</ul>

							<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">

							<div class="relevanssi-notice relevanssi-notice-warning" style="margin-top:0;">
								<p><strong><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Privacy Notice:', 'relevanssi' ); ?></strong><br>
								<?php esc_html_e( 'Do not index files containing highly confidential or sensitive personal data. You can exclude individual media files directly from their media edit screens.', 'relevanssi' ); ?></p>
							</div>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-attachment-settings">
							<h2><?php esc_html_e( 'Configuration Options', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $attachments_config ); ?>
						</div>
					</div>

					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Settings Overview', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $attachments_config ); ?>
							</ul>

							<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">

							<p><strong><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Troubleshooting License Keys', 'relevanssi' ); ?></strong></p>
							<ul style="margin-top: 8px; margin-bottom: 0; padding-left: 20px; list-style-type: disc; font-size: 13px; color: #3c434a;">
								<li><?php esc_html_e( "Seeing 'Key is not valid' after buying Premium? New keys can take up to an hour to completely synchronize across servers. Please check back shortly.", 'relevanssi' ); ?></li>
								<li><?php esc_html_e( "Seeing 'Key 0 is not valid'? If you are running a WordPress multisite network, you must register your primary license key inside your Network Settings page instead.", 'relevanssi' ); ?></li>
							</ul>
						</div>
					</aside>
				</div>

			</div>
		</div>
	</div>
	<?php
}