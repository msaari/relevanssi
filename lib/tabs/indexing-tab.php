<?php
/**
 * /lib/tabs/indexing-tab.php
 *
 * Prints out the Indexing tab in Relevanssi settings.
 * Handles content settings, index rebuilding, and stopword exclusions.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the indexing tab in Relevanssi settings.
 *
 * @global wpdb   $wpdb                 The WordPress database interface instance.
 * @global array  $relevanssi_variables Global Relevanssi variables.
 *
 * @return void Outputs the settings tab HTML rows directly.
 */
function relevanssi_indexing_tab() {
	global $wpdb, $relevanssi_variables;

	// --- Index Statistics & Counts ---
	$is_premium  = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;
	$docs_count  = get_option( 'relevanssi_doc_count', 0 );
	$terms_count = get_option( 'relevanssi_terms_count', 0 );

	$lowest_doc = $wpdb->get_var( 'SELECT doc FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc > 0 ORDER BY doc ASC LIMIT 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	if ( null === $lowest_doc ) {
		$lowest_doc = 0;
		relevanssi_create_database_tables( 0 );
	}

	$this_page  = '?page=' . plugin_basename( $relevanssi_variables['file'] );
	$update_url = wp_nonce_url( $this_page . '&rlv_tab=indexing&update_counts=1', 'update_counts' );

	$user_count    = get_option( 'relevanssi_user_count', 0 );
	$taxterm_count = get_option( 'relevanssi_taxterm_count', 0 );

	// --- Core Content Settings ---
	$index_post_types = get_option( 'relevanssi_index_post_types', array() );
	if ( empty( $index_post_types ) ) {
		$index_post_types = array();
	}

	$index_taxonomies_list = get_option( 'relevanssi_index_taxonomies_list', array() );
	if ( empty( $index_taxonomies_list ) ) {
		$index_taxonomies_list = array();
	}

	$core_content_config = array(
		'relevanssi_index_post_types'  => array(
			'type'          => 'post_types_table',
			'label'         => __( 'Post types', 'relevanssi' ),
			'hover_target'  => 'sb-post-types',
			'value'         => $index_post_types,
			'visible'       => true,
			'tooltip'       => __( 'Private or hidden post types can be indexed safely; they simply will not appear in standard public search results.', 'relevanssi' ),
			'sidebar_title' => __( 'Post Types:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Select the content types you want to include in search results. Choosing only the necessary post types keeps your database compact and fast.', 'relevanssi' ),
		),
		'relevanssi_index_image_files' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Index image files', 'relevanssi' ),
			'description'   => __( 'Include image attachments in search results.', 'relevanssi' ),
			'hover_target'  => 'sb-image-files',
			'value'         => get_option( 'relevanssi_index_image_files', 'off' ),
			'visible'       => in_array( 'attachment', $index_post_types, true ),
			'sidebar_title' => __( 'Image Files:', 'relevanssi' ),
			'sidebar_desc'  => __( 'When enabled, Relevanssi indexes image metadata (like titles and alt text). If turned off, only text documents are searched.', 'relevanssi' ),
		),
		'relevanssi_index_taxonomies'  => array(
			'type'          => 'taxonomies_table',
			'label'         => __( 'Taxonomy terms (Index inside posts)', 'relevanssi' ),
			'hover_target'  => 'sb-taxonomies',
			'value'         => $index_taxonomies_list,
			'tooltip'       => $is_premium
				? __( 'Note: This lets visitors find posts by searching for their category or tag names. If you want the actual category landing pages to show up as separate search results, use the Specialized Indexing section below.', 'relevanssi' )
				: __( 'This lets visitors find posts by searching for their category or tag names. If you want the actual category landing pages to show up as separate search results, you will need Relevanssi Premium.', 'relevanssi' ),
			'sidebar_title' => __( 'Taxonomy Matching:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Checking boxes here adds category and tag terms directly to your posts\' search data. For example, searching for "Recipes" will display posts filed under Recipes, but it won\'t list the actual Recipes category page.', 'relevanssi' ),
		),
		'relevanssi_index_comments'    => array(
			'type'          => 'select',
			'label'         => __( 'Comments', 'relevanssi' ),
			'hover_target'  => 'sb-comments',
			'value'         => get_option( 'relevanssi_index_comments', 'none' ),
			'options'       => array(
				'none'   => __( 'Do not index comments', 'relevanssi' ),
				'normal' => __( 'Index regular comments', 'relevanssi' ),
				'all'    => __( 'Index comments, trackbacks, and pingbacks', 'relevanssi' ),
			),
			'sidebar_title' => __( 'User Comments:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose whether text inside user comments should help visitors discover matching posts.', 'relevanssi' ),
		),
		'relevanssi_index_author'      => array(
			'type'          => 'checkbox',
			'label'         => __( 'Author display names', 'relevanssi' ),
			'description'   => __( 'Allow visitors to find posts by searching for the author\'s name.', 'relevanssi' ),
			'hover_target'  => 'sb-authors',
			'value'         => get_option( 'relevanssi_index_author', 'off' ),
			'sidebar_title' => __( 'Author Search:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Matches search queries against author display names so visitors can find posts by specific writers.', 'relevanssi' ),
		),
		'relevanssi_index_excerpt'     => array(
			'type'          => 'checkbox',
			'label'         => __( 'Excerpts', 'relevanssi' ),
			'description'   => __( 'Index text from manual excerpts.', 'relevanssi' ),
			'hover_target'  => 'sb-excerpts',
			'value'         => get_option( 'relevanssi_index_excerpt', 'off' ),
			'sidebar_title' => __( 'Custom Excerpts:', 'relevanssi' ),
			'sidebar_desc'  => __( 'When enabled, Relevanssi searches manual post excerpts in addition to the main post content.', 'relevanssi' ),
		),
	);

	// --- Custom Fields Settings ---
	$raw_index_fields = get_option( 'relevanssi_index_fields', '' );
	$resolved_select  = 'some';
	$display_fields   = $raw_index_fields;

	if ( empty( $raw_index_fields ) ) {
		$resolved_select = 'none';
		$display_fields  = '';
	} elseif ( 'all' === $raw_index_fields ) {
		$resolved_select = 'all';
		$display_fields  = '';
	} elseif ( 'visible' === $raw_index_fields ) {
		$resolved_select = 'visible';
		$display_fields  = '';
	}

	$custom_field_options = array(
		'none'    => __( 'Do not index custom fields', 'relevanssi' ),
		'all'     => __( 'Index all custom fields', 'relevanssi' ),
		'visible' => __( 'Index visible custom fields only', 'relevanssi' ),
		'some'    => __( 'Index specific custom fields below', 'relevanssi' ),
	);

	$woo_notice = false;
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$fields_array = array_map( 'trim', explode( ',', $raw_index_fields ) );
		$has_sku      = in_array( '_sku', $fields_array, true );

		if ( 'some' === $resolved_select && $has_sku ) {
			$woo_notice = array(
				'type' => 'info',
				'text' => __( '✓ WooCommerce SKUs are successfully registered in your custom fields list.', 'relevanssi' ),
			);
		} else {
			$woo_notice = array(
				'type' => 'info',
				'text' => sprintf(
					/* translators: %1$s is the dropdown option label, %2$s is the '_sku' key. */
					__( 'To make WooCommerce SKUs searchable, set the dropdown below to "%1$s" and add %2$s to the text list.', 'relevanssi' ),
					esc_html( $custom_field_options['some'] ),
					'<strong>_sku</strong>'
				),
			);
		}
	}

	$custom_fields_config = array(
		'relevanssi_index_fields_select' => array(
			'type'          => 'select',
			'label'         => __( 'Custom fields', 'relevanssi' ),
			'hover_target'  => 'sb-custom-fields',
			'value'         => $resolved_select,
			'options'       => $custom_field_options,
			'tooltip'       => __( 'Warning: Choosing "Index all" when using Advanced Custom Fields (ACF) can accidentally include internal technical data in your search result snippets.', 'relevanssi' ),
			'sidebar_title' => __( 'Custom Fields:', 'relevanssi' ),
			'sidebar_desc'  => sprintf(
				/* translators: %1$s, %2$s, and %3$s are the option names. */
				__( '"%1$s" searches everything. "%2$s" skips hidden system data. "%3$s" lets you manually type keys.', 'relevanssi' ),
				esc_html( $custom_field_options['all'] ),
				esc_html( $custom_field_options['visible'] ),
				esc_html( $custom_field_options['some'] )
			),
		),
		'relevanssi_index_fields'        => array(
			'type'        => 'text',
			'label'       => __( 'Custom fields to index', 'relevanssi' ),
			'placeholder' => '_sku, book_author, custom_field_key',
			'value'       => $display_fields,
			'tooltip'     => __( 'Separate multiple keys with commas. For nested fields like ACF repeaters, use the fieldname_%_subfieldname format.', 'relevanssi' ),
			'notice'      => $woo_notice,
		),
	);

	if ( 'none' !== $resolved_select ) {
		$custom_fields_config['list_custom_fields'] = array(
			'type'          => 'custom_fields_list',
			'label'         => __( 'Detected custom fields', 'relevanssi' ),
			'hover_target'  => 'sb-list-custom-fields',
			'sidebar_title' => __( 'Custom Fields List:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Displays a list of custom field keys found in your database content.', 'relevanssi' ),
		);
	}

	$rlv_validation_l10n = array(
		'errorText' => '<strong>' . __( 'Configuration Error:', 'relevanssi' ) . '</strong> ' . __( 'You selected "Index specific custom fields below" but left the custom fields text box empty. Please enter at least one field key before saving.', 'relevanssi' ),
	);

	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
	const selectField = document.getElementById('relevanssi_index_fields_select');
	const textField = document.getElementById('relevanssi_index_fields');

	if (!selectField || !textField) {
		return;
	}

	const form = selectField.closest('form');
	const errorHtml = <?php echo wp_json_encode( $rlv_validation_l10n['errorText'] ); ?>;

	function validateCustomFields() {
		let existingNotice = textField.parentNode.querySelector('.relevanssi-validation-error');

		if (selectField.value === 'some' && textField.value.trim() === '') {
			if (!existingNotice) {
				existingNotice = document.createElement('div');
				existingNotice.className = 'relevanssi-notice relevanssi-notice-error relevanssi-validation-error';

				existingNotice.setAttribute('role', 'alert');
				existingNotice.setAttribute('id', 'relevanssi-field-error-msg');
				textField.setAttribute('aria-invalid', 'true');
				textField.setAttribute('aria-describedby', 'relevanssi-field-error-msg');

				existingNotice.innerHTML = '<p>' + errorHtml + '</p>';
				textField.parentNode.appendChild(existingNotice);
			}
			return false;
		} else {
			if (existingNotice) {
				existingNotice.remove();
				textField.removeAttribute('aria-invalid');
				textField.removeAttribute('aria-describedby');
			}
			return true;
		}
	}

	selectField.addEventListener('change', validateCustomFields);
	textField.addEventListener('input', validateCustomFields);

	if (form) {
		form.addEventListener('submit', function(event) {
			if (!validateCustomFields()) {
				event.preventDefault();
				textField.focus();
			}
		});
	}
});
	</script>
	<?php

	// --- Premium Content Options (Upsells) ---
	if ( $is_premium ) {
		$premium_indexing_config = array(
			'premium_indexing_header' => array(
				'type'        => 'subheader',
				'title'       => __( 'Additional Profiles & Objects', 'relevanssi' ),
				'description' => __( 'Configure indexing rules for users, synonyms, and specialized file attachments.', 'relevanssi' ),
				'visible'     => true,
			),
		);
		$premium_indexing_config = apply_filters( 'relevanssi_premium_indexing_config', $premium_indexing_config );
	} else {
		$premium_indexing_config = array(
			'premium_indexing_upsell' => array(
				'type'          => 'upsell',
				'label'         => __( 'Specialized Indexing', 'relevanssi' ),
				'feature_name'  => __( 'Advanced User, Category, and File Search', 'relevanssi' ),
				'features_list' => array(
					__( 'PDF & Document Text Scanning: Extract and search text content directly inside attached PDFs, Word documents, and text uploads.', 'relevanssi' ),
					__( 'Taxonomy terms (Index inside posts): Let visitors find the actual category, tag, or portfolio landing pages in their search results, rather than just the individual posts inside them.', 'relevanssi' ),
					__( 'User Profile Search: Include biographical descriptions, custom metadata, and user details in your search index.', 'relevanssi' ),
					__( 'Multisite Network Support: Search across multiple sub-sites within a WordPress multisite network at the same time.', 'relevanssi' ),
				),
				'hover_target'  => 'sb-premium-indexing',
				'sidebar_title' => __( 'Premium Features:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Unlock file scanning and user searches to let visitors find content beyond standard posts and pages.', 'relevanssi' ),
			),
		);
	}

	// --- Stopwords Settings ---
	$stopwords_config = array(
		'relevanssi_manage_stopwords' => array(
			'type'          => 'stopwords_manager',
			'label'         => __( 'Core Stopwords List', 'relevanssi' ),
			'hover_target'  => 'sb-stopwords-ctrl',
			'sidebar_title' => __( 'Stopword Exclusions:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Stopwords are extremely common words (like "the", "a", "of") that add no unique value to searches. Filtering them out saves database space and increases search speed.', 'relevanssi' ),
		),
	);

	if ( $is_premium ) {
		$stopwords_config['relevanssi_body_stopwords'] = array(
			'type'          => 'body_stopwords_manager',
			'label'         => __( 'Content-Only Stopwords', 'relevanssi' ),
			'hover_target'  => 'sb-body-stopwords-ctrl',
			'sidebar_title' => __( 'Content-Only Rules:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Allows you to exclude words from the post body while still indexing them if they appear in post titles, tags, or categories.', 'relevanssi' ),
		);
		$stopwords_config                              = apply_filters( 'relevanssi_premium_stopwords_config', $stopwords_config );
	} else {
		$stopwords_config['relevanssi_body_stopwords_upsell'] = array(
			'type'          => 'upsell',
			'label'         => __( 'Content-Only Stopwords', 'relevanssi' ),
			'feature_name'  => __( 'Advanced Post Body Stopword Filtering', 'relevanssi' ),
			'features_list' => array(
				__( 'Targeted Exclusions: Ignore common words in the post body but keep them searchable if they appear in titles, tags, or custom fields.', 'relevanssi' ),
			),
			'hover_target'  => 'sb-body-stopwords-ctrl',
			'sidebar_title' => __( 'Content-Only Rules:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Unlock content filters to remove common words from the post body while keeping them searchable in titles and tags.', 'relevanssi' ),
		);
	}

	// --- Advanced Index Settings ---
	$cat_restriction       = get_option( 'relevanssi_cat_restriction', '' );
	$cat_exclusion         = get_option( 'relevanssi_cat_exclusion', '' );
	$post_exclusion        = get_option( 'relevanssi_post_exclusion', '' );
	$has_legacy_exclusions = ( ! empty( $cat_restriction ) || ! empty( $cat_exclusion ) || ! empty( $post_exclusion ) );

	$supported_locales = array( 'cs_CZ', 'de_DE', 'es_ES', 'fi', 'fr_CA', 'fr_FR', 'it_IT', 'ja', 'nl_NL', 'pl_PL', 'pt_BR', 'ru_RU', 'sv_SE' );
	$current_lang      = determine_locale();
	$lang_base         = substr( $current_lang, 0, 2 );
	$show_translations = ( in_array( $current_lang, $supported_locales, true ) || in_array( $lang_base, $supported_locales, true ) );

	$punctuation_options = get_option( 'relevanssi_punctuation', array() );

	$advanced_config = array(
		'relevanssi_expand_shortcodes'          => array(
			'type'          => 'checkbox',
			'label'         => __( 'Expand shortcodes', 'relevanssi' ),
			'description'   => __( 'Execute shortcodes during indexing to capture their text outputs.', 'relevanssi' ),
			'hover_target'  => 'sb-adv-shortcodes',
			'value'         => get_option( 'relevanssi_expand_shortcodes', 'off' ),
			'tooltip'       => __( 'Turn this off immediately if the index build process freezes, times out, or runs out of server memory.', 'relevanssi' ),
			'sidebar_title' => __( 'Shortcode Execution:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose whether Relevanssi runs shortcodes and page builders to index the text content they generate inside your pages.', 'relevanssi' ),
		),
		'relevanssi_disable_shortcodes'         => array(
			'type'          => 'text',
			'label'         => __( 'Disable these shortcodes', 'relevanssi' ),
			'description'   => __( 'Enter a comma-separated list of shortcode tags (without brackets). These will be ignored during indexing.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_disable_shortcodes', '' ),
			'hover_target'  => 'sb-disable-shortcodes',
			'sidebar_title' => __( 'Shortcode Exclusion:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Enter a comma-separated list of shortcodes to skip. This is useful if a specific shortcode breaks your indexing process.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_min_word_length'            => array(
			'type'          => 'number',
			'label'         => __( 'Minimum word length', 'relevanssi' ),
			'hover_target'  => 'sb-adv-wordlen',
			'value'         => get_option( 'relevanssi_min_word_length', 3 ),
			'min'           => 1,
			'max'           => 9,
			'step'          => 1,
			'tooltip'       => __( 'Caution: Setting this higher than 3 will completely drop short, important terms from your search index.', 'relevanssi' ),
			'sidebar_title' => __( 'Minimum Length:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Words shorter than this are ignored to keep the search tables compact and fast.', 'relevanssi' ),
		),
		'legacy_exclusion_header'               => array(
			'type'        => 'subheader',
			'title'       => __( 'Legacy Exclusions (Deprecated)', 'relevanssi' ),
			'description' => __( 'These options are outdated and will be removed entirely in a future version.', 'relevanssi' ),
			'visible'     => $has_legacy_exclusions,
		),
		'relevanssi_cat_restriction'            => array(
			'type'    => 'text',
			'label'   => __( 'Category restriction', 'relevanssi' ),
			'value'   => $cat_restriction,
			'visible' => ! empty( $cat_restriction ),
			'notice'  => array(
				'type' => 'warning',
				'text' => __( 'Deprecated: Please use standard theme query parameters or tax_query filters instead.', 'relevanssi' ),
			),
		),
		'relevanssi_cat_exclusion'              => array(
			'type'    => 'text',
			'label'   => __( 'Category exclusion', 'relevanssi' ),
			'value'   => $cat_exclusion,
			'visible' => ! empty( $cat_exclusion ),
			'notice'  => array(
				'type' => 'warning',
				'text' => __( 'Deprecated: Use theme search parameters to exclude specific category IDs dynamically.', 'relevanssi' ),
			),
		),
		'relevanssi_post_exclusion'             => array(
			'type'    => 'text',
			'label'   => __( 'Post exclusion', 'relevanssi' ),
			'value'   => $post_exclusion,
			'visible' => ! empty( $post_exclusion ),
			'notice'  => array(
				'type' => 'warning',
				'text' => __( 'Deprecated: Target individual item exclusion rules during your search parsing loops.', 'relevanssi' ),
			),
		),
		'translation_updates_header'            => array(
			'type'        => 'subheader',
			'title'       => __( 'Translation updates', 'relevanssi' ),
			'description' => __( 'Configure translation updates and language options.', 'relevanssi' ),
			'visible'     => $show_translations,
		),
		'relevanssi_update_translations_toggle' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Automatic dictionary sync', 'relevanssi' ),
			'description' => __( 'Download language translation dictionaries automatically.', 'relevanssi' ),
			'value'       => get_option( 'relevanssi_update_translations_toggle', 'off' ),
			'visible'     => $show_translations,
		),
		'punct_header'                          => array(
			'type'        => 'subheader',
			'title'       => __( 'Punctuation control', 'relevanssi' ),
			'description' => __( 'Define how punctuation marks are parsed. Any changes here require rebuilding the index before results show up accurately.', 'relevanssi' ),
		),
		'relevanssi_punct_hyphens'              => array(
			'type'          => 'select',
			'label'         => __( 'Hyphens and dashes', 'relevanssi' ),
			'hover_target'  => 'sb-punct-hyphens',
			'value'         => $punctuation_options['hyphens'] ?? 'replace',
			'options'       => array(
				'keep'    => __( 'Keep hyphens intact', 'relevanssi' ),
				'replace' => __( 'Replace hyphens with spaces', 'relevanssi' ),
				'remove'  => __( 'Remove hyphens entirely', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Hyphen Rules:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Replacing hyphens with spaces splits compound words (like "e-commerce" into "e" and "commerce"), which generally reflects user search styles best.', 'relevanssi' ),
		),
		'relevanssi_punct_quotes'               => array(
			'type'          => 'select',
			'label'         => __( 'Apostrophes and quotes', 'relevanssi' ),
			'hover_target'  => 'sb-punct-quotes',
			'value'         => $punctuation_options['quotes'] ?? 'replace',
			'options'       => array(
				'replace' => __( 'Replace quotes with spaces', 'relevanssi' ),
				'remove'  => __( 'Remove quotes entirely', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Quote Settings:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Controls whether quotes break words into separate terms or are removed entirely.', 'relevanssi' ),
		),
		'relevanssi_punct_ampersands'           => array(
			'type'          => 'select',
			'label'         => __( 'Ampersands', 'relevanssi' ),
			'hover_target'  => 'sb-punct-ampersands',
			'value'         => $punctuation_options['ampersands'] ?? 'replace',
			'options'       => array(
				'keep'    => __( 'Keep symbols intact', 'relevanssi' ),
				'replace' => __( 'Replace with spaces', 'relevanssi' ),
				'remove'  => __( 'Remove symbols entirely', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Ampersand Symbols:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Keep the symbol if your content contains names that rely on them, like "AT&T" or "R&B".', 'relevanssi' ),
		),
		'relevanssi_punct_decimals'             => array(
			'type'          => 'select',
			'label'         => __( 'Decimal separators', 'relevanssi' ),
			'hover_target'  => 'sb-punct-decimals',
			'value'         => $punctuation_options['decimals'] ?? 'remove',
			'options'       => array(
				'keep'    => __( 'Keep decimal separators intact', 'relevanssi' ),
				'replace' => __( 'Replace with spaces', 'relevanssi' ),
				'remove'  => __( 'Remove decimal separators entirely', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Decimal Points:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Keep decimals if your visitors search for specific numbers, like prices ($9.99) or version tags (v2.4).', 'relevanssi' ),
		),
	);

	$advanced_config = apply_filters( 'relevanssi_advanced_indexing_config', $advanced_config );
	?>
	<div id="indexing_tab_consolidated" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Indexing Settings', 'relevanssi' ); ?></h1>

		<?php
		if ( count( $index_post_types ) < 2 ) {
			$index_users      = get_option( 'relevanssi_index_users', 'off' );
			$index_taxonomies = get_option( 'relevanssi_index_taxonomies', 'off' );
			if ( 'off' === $index_users && 'off' === $index_taxonomies ) {
				printf( '<div class="notice notice-warning"><p><strong>%s</strong></p></div>', esc_html__( 'Warning: No content types have been chosen for indexing. Please select at least one post type below.', 'relevanssi' ) );
			}
		}
		?>

		<div class="relevanssi-dashboard-layout">
			<div class="relevanssi-main">

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-index-grid">

							<div class="relevanssi-card" id="card-index-control">
								<h2><?php esc_html_e( 'Index Actions', 'relevanssi' ); ?></h2>
								<div class="relevanssi-action-group">
									<input type="button" id="build_index" name="index" value="<?php esc_attr_e( 'Build the index', 'relevanssi' ); ?>" class='button-primary' data-hover-target="sb-build-index" />
									<input type="button" id="continue_indexing" name="continue" value="<?php esc_attr_e( 'Index unindexed posts', 'relevanssi' ); ?>" class='button-primary' data-hover-target="sb-continue-index" />
								</div>

								<div id='relevanssi-note' style='display: none'></div>
								<div id='relevanssi-progress' class='rpi-progress'><div class="rpi-indicator"></div></div>
								<div id='relevanssi-timer'><?php esc_html_e( 'Time elapsed', 'relevanssi' ); ?>: <span id="relevanssi_elapsed">0:00:00</span> | <?php esc_html_e( 'Time remaining', 'relevanssi' ); ?>: <span id="relevanssi_estimated"><?php esc_html_e( 'calculating...', 'relevanssi' ); ?></span></div>
								<label for="results" class="screen-reader-text"><?php esc_html_e( 'Results', 'relevanssi' ); ?></label><textarea id='results' rows='10' cols='80' style="display:none;"></textarea>
								<div id='relevanssi-indexing-instructions' style='display: none'><?php esc_html_e( 'Indexing usually responds immediately. If progress stalls for more than a couple of minutes, it may be stuck. Check your advanced server options or decrease word limits.', 'relevanssi' ); ?></div>
							</div>

							<div class="relevanssi-card relevanssi-metric-card" id="card-index-state">
								<h2><?php esc_html_e( 'Index Status', 'relevanssi' ); ?></h2>
								<div id="stateoftheindex" class="relevanssi-metrics">
									<div class="metric">
										<span class="metric-number"><?php echo esc_html( $docs_count ); ?></span>
										<span class="metric-label"><?php echo esc_html( _n( 'Document', 'Documents', $docs_count, 'relevanssi' ) ); ?></span>
									</div>

									<div class="metric <?php echo ! $is_premium ? 'metric-locked-placeholder' : ''; ?>" style="<?php echo ! $is_premium ? 'opacity: 0.55; position: relative;' : ''; ?>">
										<span class="metric-number"><?php echo esc_html( $user_count ); ?></span>
										<span class="metric-label">
											<?php echo esc_html( _n( 'User Profile', 'User Profiles', $user_count, 'relevanssi' ) ); ?>
											<?php
											if ( ! $is_premium ) :
												?>
												<span class="dashicons dashicons-lock" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-top;"></span><?php endif; ?>
										</span>
									</div>

									<div class="metric <?php echo ! $is_premium ? 'metric-locked-placeholder' : ''; ?>" style="<?php echo ! $is_premium ? 'opacity: 0.55; position: relative;' : ''; ?>">
										<span class="metric-number"><?php echo esc_html( $taxterm_count ); ?></span>
										<span class="metric-label">
											<?php echo esc_html( _n( 'Taxonomy Archive', 'Taxonomy Archives', $taxterm_count, 'relevanssi' ) ); ?>
											<?php
											if ( ! $is_premium ) :
												?>
												<span class="dashicons dashicons-lock" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-top;"></span><?php endif; ?>
										</span>
									</div>

									<div class="metric">
										<span class="metric-number"><?php echo esc_html( $terms_count ); ?></span>
										<span class="metric-label">
											<?php echo esc_html( _n( 'Indexed Word', 'Indexed Words', $terms_count, 'relevanssi' ) ); ?>
										</span>
									</div>

									<p class="lowest-doc"><?php echo esc_html( $lowest_doc ); ?> <?php esc_html_e( 'is the lowest post ID indexed.', 'relevanssi' ); ?></p>

									<?php // Translators: %1$s opens an anchor link navigation tag context, %2$s terminates it. ?>
									<p class="description update-counts">(<?php printf( esc_html__( 'Need updated database statistics? %1$sUpdate counts%2$s', 'relevanssi' ), '<a href="' . esc_url( $update_url ) . '">', '</a>' ); ?>)</p>
								</div>
							</div>

						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Index Management', 'relevanssi' ); ?></h3>
							<?php // Translators: %s is a bold tag string container for the "Build the index" button text label. ?>
							<p id="sb-build-index"><?php printf( esc_html__( '%s safely wipes out your existing index and runs a clean search database build based on your saved rules below.', 'relevanssi' ), '<strong>' . esc_html__( 'Build the index', 'relevanssi' ) . '</strong>' ); ?></p>
							<?php // Translators: %s is a bold tag string wrapper targeting the "Index unindexed posts" action label. ?>
							<p id="sb-continue-index"><?php printf( esc_html__( 'If an indexing task gets interrupted or drops out, use %s to resume indexing content missing from the index.', 'relevanssi' ), '<strong>' . esc_html__( 'Index unindexed posts', 'relevanssi' ) . '</strong>' ); ?>
							<?php
							if ( $is_premium ) {
								echo '<br/>';
								esc_html_e( 'Note: Continuing an index build does not scan taxonomy archive terms or user entries.', 'relevanssi' );
							}
							?>
							</p>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-core-content">
							<h2 id="indexing"><?php esc_html_e( 'Core Content', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Any structural changes made here will apply only after you rebuild the index.', 'relevanssi' ); ?></p>
							<?php Relevanssi_Settings_Renderer::render_table( $core_content_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Core Fields Guide', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Specify exactly which basic fields Relevanssi should read when scanning your WordPress data.', 'relevanssi' ); ?></p>
							<ul style="list-style: disc; margin-left: 20px; margin-bottom: 12px; font-size: 13px;">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $core_content_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-custom-fields">
							<h2><?php esc_html_e( 'Custom Fields', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $custom_fields_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Custom Fields Support', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Custom fields store additional item metadata. If you use page builders, Advanced Custom Fields (ACF), or WooCommerce store data setups, register your specific custom field keys here to make them searchable.', 'relevanssi' ); ?></p>
							<ul style="list-style: disc; margin-left: 20px; margin-bottom: 12px; font-size: 13px;">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $custom_fields_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">
						<div class="relevanssi-card" id="card-premium-indexing">
							<h2><?php esc_html_e( 'Special Indexing', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $premium_indexing_config ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box">
								<h3><?php esc_html_e( 'Special Indexing', 'relevanssi' ); ?></h3>
								<p><?php esc_html_e( 'Expand your search beyond typical posts and pages.', 'relevanssi' ); ?></p>
								<ul style="list-style: disc; margin-left: 20px; margin-bottom: 12px; font-size: 13px;">
									<?php Relevanssi_Settings_Renderer::render_sidebar_list( $premium_indexing_config ); ?>
								</ul>
							</div>
						</aside>
					<?php endif; ?>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<details class="relevanssi-card" id="card-stopwords-indexing">
							<summary style="cursor: pointer; outline: none;"><h2 style="display: inline-block; margin: 0;"><?php esc_html_e( 'Stopwords Exclusions', 'relevanssi' ); ?></h2></summary>
							<div style="margin-top: 16px;">
								<?php
								if ( class_exists( 'Polylang', false ) && ! relevanssi_get_current_language() ) {
									?>

									<h3 id="stopwords"><?php esc_html_e( 'Stopwords', 'relevanssi' ); ?></h3>
									<p class="description"><?php esc_html_e( 'You are using Polylang and are in "Show all languages" mode. Please select a language before adjusting the stopword settings.', 'relevanssi' ); ?></p>

									<?php
								} else {
									Relevanssi_Settings_Renderer::render_table( $stopwords_config );

									if ( apply_filters( 'relevanssi_display_common_words', true ) ) {
										relevanssi_common_words( 25 );

									}
								}
								?>
							</div>
						</details>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Stopwords Guide', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Manage phrases and words that should be ignored by the engine to keep your searches focused and quick.', 'relevanssi' ); ?></p>
							<ul style="list-style: disc; margin-left: 20px; margin-bottom: 12px; font-size: 13px;">
								<li id="sb-stopwords-ctrl"><strong><?php esc_html_e( 'Core Stopwords:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Wipes general terms entirely across all parts of your indexed post entries.', 'relevanssi' ); ?></li>
								<li id="sb-body-stopwords-ctrl"><strong><?php esc_html_e( 'Content-Only:', 'relevanssi' ); ?></strong> <?php esc_html_e( 'Only ignores words if they occur strictly within the post content.', 'relevanssi' ); ?></li>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<details class="relevanssi-card" id="card-advanced-indexing">
							<summary style="cursor: pointer; outline: none;"><h2 style="display: inline-block; margin: 0;"><?php esc_html_e( 'Advanced Indexing Options', 'relevanssi' ); ?></h2></summary>
							<div style="margin-top: 16px;">
								<?php
								Relevanssi_Settings_Renderer::render_table( $advanced_config );
								do_action( 'relevanssi_indexing_tab_advanced' );
								?>
							</div>
						</details>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Advanced Tuning', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Configure complex character set configurations, punctuation filters, and custom string rules.', 'relevanssi' ); ?></p>
							<ul style="list-style: disc; margin-left: 20px; margin-bottom: 12px; font-size: 13px;">
								<?php
								Relevanssi_Settings_Renderer::render_sidebar_list( $advanced_config );
								do_action( 'relevanssi_advanced_indexing_sidebar_list' );
								?>

							</ul>
						</div>
					</aside>
				</div>

			</div>
		</div>
	</div>
	<?php
}