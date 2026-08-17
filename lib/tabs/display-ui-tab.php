<?php
/**
 * /lib/tabs/display-ui-tab.php
 *
 * Prints out the consolidated Display & UI tab in Relevanssi premium admin settings panel view.
 *
 * @package Relevanssi
 * @author   Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Display & UI tab in Relevanssi settings.
 *
 * @return void Writes dashboard configuration structures and visual layout containers.
 */
function relevanssi_display_ui_tab() {
	global $relevanssi_variables;
	$is_premium = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;

	// --- Card 1: Voice Search ---

	// Voice Search Notice.
	$voice_search_privacy = array(
		'type' => 'warning',
		'text' => __(
			'The voice search uses the WebSpeech API and prioritizes local speech detection. However, the WebSpeech API may use external speech recognition services (such as Google or Apple). Your privacy policy may need to reflect this.',
			'relevanssi'
		),
	);

	$voice_search = array(
		'relevanssi_voice_search'            => array(
			'type'          => $is_premium ? 'checkbox' : 'upsell',
			'label'         => __( 'Voice search', 'relevanssi' ),
			'description'   => __( 'Enable voice search', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_voice_search', 'off' ),
			// Translators: %1$s is the URL.
			'tooltip'       => sprintf( __( 'The WebSpeech API only works on supported browsers. On unsupported browsers like Firefox, this feature will not appear. <a href="%1$s" target="_blank" rel="noopener noreferrer">Read Documentation</a>.', 'relevanssi' ), 'https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API#browser_compatibility' ),
			'feature_name'  => __( 'Voice Search Recognition', 'relevanssi' ),
			'hover_target'  => 'sb-voice-search-toggle',
			'sidebar_title' => __( 'Voice Search:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Enable this option to allow users to search your content using voice input.', 'relevanssi' ),
			'notice'        => $voice_search_privacy,
		),

		'relevanssi_voice_search_autosubmit' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Voice search autosubmit', 'relevanssi' ),
			'description'   => __( 'Automatically submit search when user stops talking', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_voice_search_autosubmit', 'on' ),
			'hover_target'  => 'sb-voice-search-autosubmit',
			'sidebar_title' => __( 'Voice Search Autosubmit:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Automatically triggers the search query as soon as the user finishes speaking.', 'relevanssi' ),
			'visible'       => $is_premium,
		),

		'relevanssi_voice_search_css'        => array(
			'type'          => 'checkbox',
			'label'         => __( 'Voice search CSS', 'relevanssi' ),
			'description'   => __( 'Include default voice search styles', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_voice_search_css', 'on' ),
			'tooltip'       => __( 'If your theme styles conflict with this setting, disable it and add custom styles to your stylesheet.', 'relevanssi' ),
			'hover_target'  => 'sb-voice-search-css',
			'sidebar_title' => __( 'Voice Search CSS:', 'relevanssi' ),
			'sidebar_desc'  => sprintf(
				/* translators: %1$s opens the link to the voice search article, %2$s closes it. */
				__( 'When enabled, Relevanssi loads default styles for the Voice Search interface. If it conflicts with your theme, disable this option and provide your own styles. See the %1$sVoice Search Documentation%2$s for guidance.', 'relevanssi' ),
				'<a href="https://www.relevanssi.com/knowledge-base/voice-search/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			'visible'       => $is_premium,
		),
	);

	// --- Card 2: Snippet Generation Mechanics ---

	$index_fields = get_option( 'relevanssi_index_fields', '' );

	// Theme checking dependencies.
	$theme_notice = false;
	$theme        = wp_get_theme();
	$template     = $theme->get( 'Template' );
	if ( 'divi' === strtolower( $template ) ) {
		$theme_notice = array(
			'type' => 'warning',
			'text' => wp_sprintf(
				// Translators: %1$s opens the link, %2$s closes it.
				__( 'You are using the Divi theme. To display custom search excerpts with Divi, template modifications are required. %1$sSee instructions here%2$s.', 'relevanssi' ),
				'<a href="https://www.relevanssi.com/knowledge-base/divi-page-builder-and-cleaner-excerpts/" target="_blank">',
				'</a>'
			),
		);
	}

	$custom_field_excerpts = array(
		'type' => 'info',
		'text' => __( 'If you need PDF excerpts you must enable this setting!', 'relevanssi' ),
	);

	$length_unit = get_option( 'relevanssi_excerpt_type' ) === 'words'
		? __( 'words', 'relevanssi' )
		: __( 'characters', 'relevanssi' );

	$snippet_config = array(
		'relevanssi_excerpts'                => array(
			'type'          => 'checkbox',
			'label'         => __( 'Custom excerpts/snippets', 'relevanssi' ),
			'description'   => __( 'Generate context-rich search result snippets', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_excerpts', 'off' ),
			'tooltip'       => __( 'Enable this to overwrite your theme\'s default post excerpts on search results pages.', 'relevanssi' ),
			'hover_target'  => 'sb-excerpts-toggle',
			'sidebar_title' => __( 'Custom Excerpts:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Generates context-rich snippets that display search keywords in the context they were found. Requires your theme template files to display results using the_excerpt().', 'relevanssi' ),
			'notice'        => $theme_notice,
		),
		'relevanssi_max_excerpts'            => array(
			'type'          => 'number',
			'min'           => 0,
			'label'         => __( 'Number of excerpt snippets', 'relevanssi' ),
			'description'   => __( 'The maximum number of excerpt snippets Relevanssi will create for each post.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_max_excerpts', 1 ),
			'hover_target'  => 'sb-excerpt-max-segments',
			'sidebar_title' => __( 'Snippet Limits:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Controls the upper limit of distinct matching text segments extracted and concatenated into a single search result snippet.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_excerpt_length'          => array(
			'type'          => 'number',
			'label'         => __( 'Snippet length', 'relevanssi' ),
			'description'   => __( 'Maximum length of the generated snippet', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_excerpt_length', 10 ),
			'min'           => 1,
			'max'           => 999,
			'step'          => 1,
			'unit'          => $length_unit,
			'hover_target'  => 'sb-excerpts-len',
			'sidebar_title' => __( 'Snippet Length:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Controls snippet length using the measurement unit selected below.', 'relevanssi' ),
		),
		'relevanssi_excerpt_type'            => array(
			'type'          => 'select',
			'label'         => __( 'Length calculation metric', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_excerpt_type', 'words' ),
			'options'       => array(
				'words' => __( 'Words', 'relevanssi' ),
				'chars' => __( 'Characters', 'relevanssi' ),
			),
			'hover_target'  => 'sb-excerpts-metric',
			'sidebar_title' => __( 'Length Metric:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose whether snippet length counts total words or individual characters. Word counts generally offer better performance.', 'relevanssi' ),
		),
		'relevanssi_excerpt_allowable_tags'  => array(
			'type'          => 'text',
			'label'         => __( 'Allowable tags in excerpts', 'relevanssi' ),
			'placeholder'   => '<p><a><strong>',
			'value'         => get_option( 'relevanssi_excerpt_allowable_tags', '' ),
			'hover_target'  => 'sb-excerpts-tags',
			'sidebar_title' => __( 'Preserved HTML Tags:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Specify HTML elements to preserve inside search snippets. Example format: <code>&lt;p&gt;&lt;a&gt;&lt;strong&gt;</code>.', 'relevanssi' ),
		),
		'relevanssi_excerpt_custom_fields'   => array(
			'type'          => 'checkbox',
			'label'         => __( 'Use custom field content for building excerpts', 'relevanssi' ),
			'description'   => __( 'Include custom field data in search result snippets', 'relevanssi' ),
			'tooltip'       => __( 'PDF content is stored in custom fields', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_excerpt_custom_fields', 'off' ),
			'notice'        => $custom_field_excerpts,
			'visible'       => true,
			'hover_target'  => 'sb-excerpts-meta',
			'sidebar_title' => __( 'Custom Field Snippets:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Allows the snippet engine to extract context matches out of custom fields and attachment document texts (like PDFs).', 'relevanssi' ),
		),
		'relevanssi_excerpt_specific_fields' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Specific custom field excerpts', 'relevanssi' ),
			'description'   => __( 'Link excerpt segments explicitly to their custom field meta keys', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_excerpt_specific_fields', 'off' ),
			'visible'       => ! empty( $index_fields ),
			'hover_target'  => 'sb-excerpts-specific',
			'sidebar_title' => __( 'Specific Snippets:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Requires more processing time, but allows your theme templates to identify and output exactly which custom fields triggered the match.', 'relevanssi' ),
		),
	);

	if ( in_array( $index_fields, array( 'all', 'visible' ), true ) ) {
		$snippet_config['list_custom_fields'] = array(
			'type'         => 'custom_fields_list',
			'label'        => __( 'Indexed fields index logs', 'relevanssi' ),
			'hover_target' => 'sb-excerpts-fields-list',
		);
	}

	// --- Card 3: Highlight Aesthetics & Styling ---

	$txt_col = get_option( 'relevanssi_txt_col', '#ff0000' );
	if ( '#' !== substr( $txt_col, 0, 1 ) ) {
		$txt_col = '#' . $txt_col;
	}
	$txt_col = function_exists( 'relevanssi_sanitize_hex_color' ) ? relevanssi_sanitize_hex_color( $txt_col ) : $txt_col;

	$bg_col = get_option( 'relevanssi_bg_col', '#ffaf75' );
	if ( '#' !== substr( $bg_col, 0, 1 ) ) {
		$bg_col = '#' . $bg_col;
	}
	$bg_col = function_exists( 'relevanssi_sanitize_hex_color' ) ? relevanssi_sanitize_hex_color( $bg_col ) : $bg_col;

	$highlight_config = array(
		'relevanssi_highlight' => array(
			'type'          => 'select',
			'label'         => __( 'Highlight style', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_highlight', 'no' ),
			'options'       => array(
				'no'     => __( 'No highlighting', 'relevanssi' ),
				'mark'   => __( 'HTML mark tag (<mark>)', 'relevanssi' ),
				'em'     => __( 'Emphasis tag (<em>)', 'relevanssi' ),
				'strong' => __( 'Strong bold tag (<strong>)', 'relevanssi' ),
				'col'    => __( 'Text color', 'relevanssi' ),
				'bgcol'  => __( 'Background color', 'relevanssi' ),
				'css'    => __( 'Custom CSS Style', 'relevanssi' ),
				'class'  => __( 'Custom CSS Class', 'relevanssi' ),
			),
			'hover_target'  => 'sb-style-type',
			'sidebar_title' => __( 'Highlighting Method:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose how user search terms are highlighted within the search results snippets.', 'relevanssi' ),
		),
		'relevanssi_txt_col'   => array(
			'type'          => 'color',
			'label'         => __( 'Highlight text color', 'relevanssi' ),
			'value'         => $txt_col,
			'default'       => '#ff0000',
			'sample_text'   => __( 'Example Highlighting Text', 'relevanssi' ),
			'hover_target'  => 'sb-style-txt',
			'sidebar_title' => __( 'Text Color:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Sets the text color for highlighted search terms. Only works if "Text color" is chosen above.', 'relevanssi' ),
		),
		'relevanssi_bg_col'    => array(
			'type'          => 'color',
			'label'         => __( 'Highlight background color', 'relevanssi' ),
			'value'         => $bg_col,
			'default'       => '#ffaf75',
			'sample_text'   => __( 'Example Highlighting Background', 'relevanssi' ),
			'hover_target'  => 'sb-style-bg',
			'sidebar_title' => __( 'Background Color:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Sets the background color for highlighted search terms. Only works if "Background color" is selected.', 'relevanssi' ),
		),
		'relevanssi_css'       => array(
			'type'          => 'text',
			'label'         => __( 'Custom CSS styling', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_css', '' ),
			'hover_target'  => 'sb-style-custom-css',
			'sidebar_title' => __( 'Custom CSS:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Enter inline CSS rules to style the highlighted terms (for example: font-weight: bold;).', 'relevanssi' ),
		),
		'relevanssi_class'     => array(
			'type'          => 'text',
			'label'         => __( 'Custom CSS class name', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_class', '' ),
			'hover_target'  => 'sb-style-custom-class',
			'sidebar_title' => __( 'Custom Class:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Adds a custom class name to the highlighted terms so you can style them in your theme\'s stylesheet.', 'relevanssi' ),
		),
	);

	// --- Card 4: Highlight Locations & Search Scores ---

	$show_matches_text = stripslashes( get_option( 'relevanssi_show_matches_text', '' ) );

	$target_config = array(
		'relevanssi_hilite_title'       => array(
			'type'          => 'checkbox',
			'label'         => __( 'Highlight terms in titles', 'relevanssi' ),
			'description'   => __( 'Highlight matching keywords directly inside post and page titles.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_hilite_title', 'off' ),
			'hover_target'  => 'sb-scope-titles',
			'sidebar_title' => __( 'Title Highlighting:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Note: This requires theme code changes. You must replace standard the_title() calls with relevanssi_the_title() in your search templates.', 'relevanssi' ),
		),
		'relevanssi_highlight_docs'     => array(
			'type'          => 'checkbox',
			'label'         => __( 'Keep highlights active on the full page', 'relevanssi' ),
			'description'   => __( 'Keep search terms highlighted even after a user clicks through to read the full post.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_highlight_docs', 'off' ),
			'hover_target'  => 'sb-scope-docs',
			'sidebar_title' => __( 'Single Post Highlighting:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Adds a temporary parameter to search result links so terms stay highlighted when visitors view the full article.', 'relevanssi' ),
		),
		'relevanssi_highlight_comments' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Highlight terms in comments', 'relevanssi' ),
			'description'   => __( 'Highlight matching keywords within the user comments section.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_highlight_comments', 'off' ),
			'hover_target'  => 'sb-scope-comments',
			'sidebar_title' => __( 'Comment Highlighting:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Extends highlighting to the comments section, using the same style rules selected for post content.', 'relevanssi' ),
		),
		'relevanssi_expand_highlights'  => array(
			'type'          => 'checkbox',
			'label'         => __( 'Highlight whole words', 'relevanssi' ),
			'description'   => __( 'Highlight the entire word even if the search query only matches a part of it.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_expand_highlights', 'off' ),
			'hover_target'  => 'sb-scope-expansion',
			'sidebar_title' => __( 'Whole Word Highlighting:', 'relevanssi' ),
			'sidebar_desc'  => __( 'For example, if someone searches for "cat" and matches "catastrophe", this highlights the entire word "catastrophe" instead of just the "cat" part.', 'relevanssi' ),
		),
		'relevanssi_show_matches'       => array(
			'type'          => 'checkbox',
			'label'         => __( 'Show search score breakdown', 'relevanssi' ),
			'description'   => __( 'Display a breakdown of how many times terms matched next to each search result.', 'relevanssi' ),
			'value'         => get_option( 'relevanssi_show_matches', 'off' ),
			'hover_target'  => 'sb-breakdown-toggle',
			'sidebar_title' => __( 'Search Score Breakdown:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Displays a text summary showing exactly how many keyword matches occurred in the title, body, tags, or categories.', 'relevanssi' ),
		),
		'relevanssi_show_matches_text'  => array(
			'type'          => 'textarea',
			'label'         => __( 'Score breakdown template', 'relevanssi' ),
			'description'   => __( 'Customize the breakdown text format. Only works if "Show search score breakdown" is enabled above.', 'relevanssi' ),
			'value'         => $show_matches_text,
			'hover_target'  => 'sb-breakdown-template',
			'sidebar_title' => __( 'Breakdown Template Tags:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Use these tags to customize the output: %body%, %title%, %categories%, %tags%, %total%, %score%, %terms%.', 'relevanssi' ), /* phpcs:ignore WordPress.WP.I18n */
		),
	);

	// --- Card 5: Premium Related Posts - Extraction Criteria ---

	$related_settings = get_option( 'relevanssi_related_settings', array() );
	if ( empty( $related_settings ) && function_exists( 'relevanssi_related_default_settings' ) ) {
		$related_settings = relevanssi_related_default_settings();
	}

	$related_enabled        = $related_settings['enabled'] ?? 'off';
	$related_append         = $related_settings['append'] ?? '';
	$related_number         = $related_settings['number'] ?? 6;
	$related_months         = $related_settings['months'] ?? 0;
	$related_keyword        = $related_settings['keyword'] ?? 'title';
	$related_restrict       = $related_settings['restrict'] ?? '';
	$related_nothing        = $related_settings['nothing'] ?? 'nothing';
	$related_notenough      = $related_settings['notenough'] ?? 'random';
	$related_post_types_val = $related_settings['post_types'] ?? 'post';

	$append_array    = ! empty( $related_append ) ? explode( ',', $related_append ) : array();
	$keyword_sources = ! empty( $related_keyword ) ? explode( ',', $related_keyword ) : array();
	$restrict_taxos  = ! empty( $related_restrict ) ? explode( ',', $related_restrict ) : array();
	$post_type_array = ! empty( $related_post_types_val ) ? explode( ',', $related_post_types_val ) : array();

	$append_options = array();
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
		if ( ! in_array( $pt->name, relevanssi_get_forbidden_post_types(), true ) ) {
			$append_options[ $pt->name ] = $pt->labels->singular_name;
		}
	}

	$related_general_config = array(
		'relevanssi_related_enabled'      => array(
			'type'          => $is_premium ? 'checkbox' : 'upsell',
			'label'         => __( 'Enable related posts', 'relevanssi' ),
			'description'   => __( 'Turn on the related posts feature across your site.', 'relevanssi' ),
			'value'         => $related_enabled,
			'hover_target'  => 'sb-related-toggle',
			'sidebar_title' => __( 'Related Posts:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Uses Relevanssi\'s search logic to automatically find and recommend relevant articles to your readers.', 'relevanssi' ),
			'feature_name'  => __( 'Related Posts', 'relevanssi' ),
			'features_list' => array(
				__( 'Keep readers on your site longer with smart, automated content suggestions.', 'relevanssi' ),
				__( 'Display them automatically at the end of posts, or manually place them anywhere using a shortcode.', 'relevanssi' ),
			),
		),
		'relevanssi_related_append'       => array(
			'type'          => 'multicheckbox',
			'label'         => __( 'Automatic injection', 'relevanssi' ),
			'description'   => __( 'Select where related posts should automatically appear.', 'relevanssi' ),
			'value'         => $append_array,
			'options'       => $append_options,
			'hover_target'  => 'sb-related-append',
			'sidebar_title' => __( 'Automatic Display:', 'relevanssi' ),
			'sidebar_desc'  => sprintf(
				// Translators: %1$s is the_content, %2$s is relevanssi_related_priority, %3$s is function, %4$s is shortcode.
				__( 'Automatically appends related items to the end of %1$s. To place them manually instead, uncheck these boxes and use the shortcode %4$s or template function %3$s.', 'relevanssi' ),
				'<code>the_content</code>',
				'<code>relevanssi_related_priority</code>',
				'<code>relevanssi_related_posts()</code>',
				'<code>[relevanssi_related_posts]</code>'
			),
			'visible'       => $is_premium,
		),
		'relevanssi_related_keyword[]'    => array(
			'type'          => 'related_keywords',
			'label'         => __( 'Matching criteria', 'relevanssi' ),
			'meta'          => array(
				'keyword_sources' => $keyword_sources,
				'restrict_taxos'  => $restrict_taxos,
			),
			'hover_target'  => 'sb-related-keywords',
			'sidebar_title' => __( 'How Posts Match:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose where Relevanssi looks to find related terms (like titles or tags). Enforcing a taxonomy restriction means recommended posts must share that same category or tag.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_related_number'       => array(
			'type'          => 'number',
			'label'         => __( 'Number of posts', 'relevanssi' ),
			'description'   => __( 'Maximum number of related items to display.', 'relevanssi' ),
			'value'         => $related_number,
			'min'           => 1,
			'max'           => 100,
			'hover_target'  => 'sb-related-count',
			'sidebar_title' => __( 'Display Limit:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Set the total number of recommended posts you want to show in the related posts section.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_related_months'       => array(
			'type'          => 'number',
			'label'         => __( 'Age limit', 'relevanssi' ),
			'description'   => __( 'Restrict results to posts published within this timeframe.', 'relevanssi' ),
			'value'         => $related_months,
			'min'           => 0,
			'max'           => 240,
			'unit'          => __( 'months', 'relevanssi' ),
			'hover_target'  => 'sb-related-date-gate',
			'sidebar_title' => __( 'Age Filter:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Prevents older content from appearing. Set this to 0 to allow posts of any age to be recommended.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_related_post_types[]' => array(
			'type'          => 'related_post_types',
			'label'         => __( 'Allowed post types', 'relevanssi' ),
			'meta'          => array(
				'value_array'      => $post_type_array,
				'post_types_value' => $related_post_types_val,
			),
			'hover_target'  => 'sb-related-pt-matrix',
			'sidebar_title' => __( 'Content Pool:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose which content types can be recommended. Selecting "Matching post type" ensures a post only suggests items of its same type (e.g., articles only suggest other articles).', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_related_nothing'      => array(
			'type'          => 'select',
			'label'         => __( 'Fallback behavior', 'relevanssi' ),
			'description'   => __( 'What to do if no matching related posts are found.', 'relevanssi' ),
			'value'         => $related_nothing,
			'options'       => array(
				'nothing'    => __( 'Show nothing (hide the section)', 'relevanssi' ),
				'random'     => __( 'Display random posts', 'relevanssi' ),
				'random_cat' => __( 'Display random posts from the same category', 'relevanssi' ),
			),
			'hover_target'  => 'sb-related-fallback-empty',
			'sidebar_title' => __( 'No Matches Found:', 'relevanssi' ),
			'sidebar_desc'  => __( 'If Relevanssi can\'t find any contextual matches for a post, use this to decide whether to hide the section or fill it with generic content.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
		'relevanssi_related_notenough'    => array(
			'type'          => 'select',
			'label'         => __( 'Backfill behavior', 'relevanssi' ),
			'description'   => __( 'What to do if the number of matches is lower than your requested limit.', 'relevanssi' ),
			'value'         => $related_notenough,
			'options'       => array(
				'nothing'    => __( 'Show fewer posts (do not backfill)', 'relevanssi' ),
				'random'     => __( 'Fill remaining slots with random posts', 'relevanssi' ),
				'random_cat' => __( 'Fill remaining slots with category matches', 'relevanssi' ),
			),
			'hover_target'  => 'sb-related-backfill',
			'sidebar_title' => __( 'Filling Empty Slots:', 'relevanssi' ),
			'sidebar_desc'  => __( 'If you requested 5 posts but Relevanssi only finds 2 relevant matches, this setting fills the remaining 3 slots so your site layout stays uniform.', 'relevanssi' ),
			'visible'       => $is_premium,
		),
	);

		// --- Card 6: Premium Related Posts - Structural Layout & Styling ---

		wp_enqueue_media();

		$related_style = get_option( 'relevanssi_related_style', array() );
	if ( function_exists( 'relevanssi_related_default_styles' ) ) {
		$related_style = array_merge( relevanssi_related_default_styles(), $related_style );
	}
		$related_width      = $related_style['width'] ?? '';
		$related_titles     = $related_style['titles'] ?? 'off';
		$related_excerpts   = $related_style['excerpts'] ?? 'off';
		$related_thumbnails = $related_style['thumbnails'] ?? 'off';
		$thumbnail_id       = $related_style['default_thumbnail'] ?? 0;

		$related_style_config = array(
			'relevanssi_related_titles'     => array(
				'type'          => 'checkbox',
				'label'         => __( 'Display titles', 'relevanssi' ),
				'description'   => __( 'Show post titles in the related posts section.', 'relevanssi' ),
				'value'         => $related_titles,
				'hover_target'  => 'sb-style-titles',
				'sidebar_title' => __( 'Post Titles:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Displays the title of each recommended post in the list.', 'relevanssi' ),
			),
			'relevanssi_related_thumbnails' => array(
				'type'          => 'checkbox',
				'label'         => __( 'Display thumbnails', 'relevanssi' ),
				'description'   => __( 'Show thumbnails for recommended posts.', 'relevanssi' ),
				'value'         => $related_thumbnails,
				'hover_target'  => 'sb-style-thumbs',
				'sidebar_title' => __( 'Thumbnails:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Shows the thumbnail for each related item. If turned off, fallback images are skipped too.', 'relevanssi' ),
			),
			'relevanssi_default_thumbnail'  => array(
				'type'          => 'media_upload',
				'label'         => __( 'Fallback image', 'relevanssi' ),
				'meta'          => array(
					'thumbnail_id' => $thumbnail_id,
					'thumbnails'   => $related_thumbnails,
				),
				'hover_target'  => 'sb-style-media-picker',
				'sidebar_title' => __( 'Fallback Image:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Choose a backup image to use if a recommended post doesn\'t have its own featured image.', 'relevanssi' ),
			),
			'relevanssi_related_excerpts'   => array(
				'type'          => 'checkbox',
				'label'         => __( 'Display excerpts', 'relevanssi' ),
				'description'   => __( 'Show a short text summary under each related post.', 'relevanssi' ),
				'value'         => $related_excerpts,
				'hover_target'  => 'sb-related-excerpts-toggle',
				'sidebar_title' => __( 'Post Excerpts:', 'relevanssi' ),
				'sidebar_desc'  => sprintf(
					// Translators: name of the filter hook.
					__( 'Displays the post excerpt, falling back automatically to the main post text. The default length is 50 characters, which you can adjust using the %s filter.', 'relevanssi' ),
					'<code>relevanssi_related_excerpt_length</code>'
				),
			),
			'relevanssi_related_width'      => array(
				'type'          => 'number',
				'label'         => __( 'Minimum item width', 'relevanssi' ),
				'description'   => __( 'Set the minimum width for each related post card.', 'relevanssi' ),
				'value'         => intval( $related_width ),
				'min'           => 1,
				'max'           => 2000,
				'unit'          => __( 'px', 'relevanssi' ),
				'hover_target'  => 'sb-related-width',
				'sidebar_title' => __( 'Layout Width:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Controls how many columns fit on a single row. Related posts will drop cleanly onto a new line if the screen size gets too small.', 'relevanssi' ),
			),
		);

		// --- Card 7: Premium Related Posts - Caching Parameters ---

		$related_cache_for_admins = $related_settings['cache_for_admins'] ?? 'off';

		$related_cache_config = array(
			'relevanssi_related_cache_for_admins' => array(
				'type'          => 'checkbox',
				'label'         => __( 'Cache for administrators', 'relevanssi' ),
				'description'   => __( 'Keep related posts cached even when you are logged in as an administrator.', 'relevanssi' ),
				'value'         => $related_cache_for_admins,
				'hover_target'  => 'sb-cache-admin-toggle',
				'sidebar_title' => __( 'Admin Caching:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Turn this off while you are configuring your related posts so you can see your layout and style changes instantly.', 'relevanssi' ),
			),
			'relevanssi_flush_related_cache'      => array(
				'type'          => 'checkbox',
				'label'         => __( 'Clear related posts cache', 'relevanssi' ),
				'description'   => __( 'Instantly wipe all stored related posts and regenerate them.', 'relevanssi' ),
				'value'         => 'off',
				'hover_target'  => 'sb-cache-flush-trigger',
				'sidebar_title' => __( 'Clear Cache:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Check this box and click save to instantly clear the cached related posts across your entire site.', 'relevanssi' ),
			),
		);

		// =========================================================================
		// 3. LAYOUT VIEW & CLIENT-SIDE VISIBILITY CONTROLLER
		// =========================================================================
		?>
	<div id="display_ui_tab_consolidated" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Display & UI Settings', 'relevanssi' ); ?></h1>

		<?php wp_nonce_field( 'relevanssi_update_options', 'relevanssi_nonce' ); ?>
		<input type="hidden" name="rlv_tab" value="display-ui" />

		<div class="relevanssi-dashboard-layout">
			<div class="relevanssi-main">
				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-voice-search">
							<h2><?php esc_html_e( 'Voice Search', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $voice_search ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Voice Search', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $voice_search ); ?>
							</ul>
						</div>
					</aside>
					<?php endif; ?>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-snippet-mechanics">
							<h2><?php esc_html_e( 'Snippets & Excerpts Generation', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php esc_html_e( 'Configure how Relevanssi creates dynamic contextual results highlights.', 'relevanssi' ); ?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $snippet_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Excerpts Controls Guide', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $snippet_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-highlight-aesthetics">
							<h2><?php esc_html_e( 'Search Term Highlighting', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php esc_html_e( 'Customize styling mechanics targeting keywords matched inside excerpts.', 'relevanssi' ); ?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $highlight_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Styling Context Guide', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $highlight_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-target-scopes-breakdowns">
							<h2><?php esc_html_e( 'Highlight Locations & Score Breakdown', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php esc_html_e( 'Configure location scopes and search score statistics templates.', 'relevanssi' ); ?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $target_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Highlight Locations Guide', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $target_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-related-general-queries">
							<h2><?php esc_html_e( 'Related Posts - Selection Criteria', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">

							<?php if ( $is_premium ) : ?>
								<?php esc_html_e( "Relevanssi Related Posts feature shows related posts on posts pages, based on keywords like post title, tags and categories. This feature uses the Relevanssi index to find the best-matching related posts. All results are cached, so your site performance won't suffer.", 'relevanssi' ); ?>
									<br>
								<?php // Translators: %s is the WP CLI command. ?>
								<?php printf( esc_html__( 'A pro tip: you can regenerate related posts for all posts with the WP CLI command %s.', 'relevanssi' ), '<code>wp relevanssi regenerate_related</code>' ); ?>
							<?php endif; ?>

							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $related_general_config ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Related Matching Logic Guide', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $related_general_config ); ?>
							</ul>
						</div>
					</aside>
					<?php endif; ?>
				</div>

				<?php if ( $is_premium ) : ?>
				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-related-visual-styles">
							<h2><?php esc_html_e( 'Related Posts - Layout & Styling', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php esc_html_e( 'When you add the related posts to your site, Relevanssi will use a template to print out the results. These settings control how that template displays the posts. If you need to modify the related posts in a way these settings do not allow, you can always create your own template.', 'relevanssi' ); ?>
								<br>
								<?php
								// Translators: %1$s is the default template filename, %2$s is the theme template directory.
								printf( esc_html__( "To create your own template, it's best if you begin with the default Relevanssi template, which can be found in the file %1\$s. Copy the template in the %2\$s folder in your theme and make the necessary changes. Relevanssi will then use your template file to display the related posts.", 'relevanssi' ), '<code>' . esc_html( $relevanssi_variables['plugin_dir'] ?? '' ) . 'premium/templates/relevanssi-related.php</code>', '<code>' . esc_html( get_stylesheet_directory() ) . '/templates/</code>' );
								?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $related_style_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Layout & Styles Help', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $related_style_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-related-transients-cache">
							<h2><?php esc_html_e( 'Related Posts - Caching', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php esc_html_e( 'The related posts are cached using WordPress transients. The related posts for each post are stored in a transient that is stored for two weeks. The cache for each post is flushed whenever the post is saved. When a post is made non-public (returned to draft, trashed), Relevanssi automatically flushes all related post caches where that post appears.', 'relevanssi' ); ?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $related_cache_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Transients Lifecycle Help', 'relevanssi' ); ?></h3>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $related_cache_config ); ?>
							</ul>
						</div>
					</aside>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			const excerptsMaster = document.getElementById('relevanssi_excerpts');
			const highlightSelect = document.getElementById('relevanssi_highlight');
			const showMatchesMaster = document.getElementById('relevanssi_show_matches');
			const relatedMaster = document.getElementById('relevanssi_related_enabled');
			const postTypesTableContainer = document.getElementById('row_relevanssi_related_post_types[]');
			const relatedThumbnailsToggle = document.getElementById('relevanssi_related_thumbnails');

			if (!excerptsMaster) return;

			function evaluateFormDependencies() {
				const snippetsEnabled = excerptsMaster.checked;

				const secondaryRowIDs = [
					'row_relevanssi_excerpt_length',
					'row_relevanssi_excerpt_type',
					'row_relevanssi_excerpt_allowable_tags',
					'row_relevanssi_excerpt_custom_fields',
					'row_relevanssi_excerpt_specific_fields',
					'row_relevanssi_highlight',
					'row_relevanssi_hilite_title',
					'row_relevanssi_highlight_docs',
					'row_relevanssi_highlight_comments',
					'row_relevanssi_expand_highlights',
					'row_relevanssi_show_matches'
				];

				secondaryRowIDs.forEach(id => {
					const el = document.getElementById(id);
					if (!el) return;

					if (snippetsEnabled) {
						el.classList.remove('rlv-js-blended');
						el.querySelectorAll('input, select, textarea').forEach(input => input.removeAttribute('disabled'));
					} else {
						el.classList.add('rlv-js-blended');
						el.querySelectorAll('input, select, textarea').forEach(input => input.setAttribute('disabled', 'disabled'));
					}
				});

				const highlightValue = highlightSelect ? highlightSelect.value : 'no';
				const styleMapping = {
					'col':   'row_relevanssi_txt_col',
					'bgcol': 'row_relevanssi_bg_col',
					'css':   'row_relevanssi_css',
					'class': 'row_relevanssi_class'
				};

				Object.keys(styleMapping).forEach(key => {
					const rowId = styleMapping[key];
					const rowEl = document.getElementById(rowId);
					if (!rowEl) return;

					if (snippetsEnabled && highlightValue === key) {
						rowEl.classList.remove('rlv-js-hidden');
					} else {
						rowEl.classList.add('rlv-js-hidden');
					}
				});

				const breakdownRow = document.getElementById('row_relevanssi_show_matches_text');
				const breakdownTextarea = document.getElementById('relevanssi_show_matches_text');
				const breakdownSidebar = document.getElementById('sb-breakdown-template');

				if (breakdownRow) {
					const isBreakdownEnabled = snippetsEnabled && showMatchesMaster && showMatchesMaster.checked;

					if (isBreakdownEnabled) {
						breakdownRow.classList.remove('rlv-js-blended');
						if (breakdownTextarea) breakdownTextarea.removeAttribute('disabled');
						if (breakdownSidebar) breakdownSidebar.classList.remove('rlv-js-blended');
					} else {
						breakdownRow.classList.add('rlv-js-blended');
						if (breakdownTextarea) breakdownTextarea.setAttribute('disabled', 'disabled');
						if (breakdownSidebar) breakdownSidebar.classList.add('rlv-js-blended');
					}
				}

				if (relatedMaster) {
					const relatedEnabled = relatedMaster.checked;
					const relatedRowIDs = [
						'row_relevanssi_related_append',
						'row_relevanssi_related_keyword[]',
						'row_relevanssi_related_number',
						'row_relevanssi_related_months',
						'row_relevanssi_related_post_types[]',
						'row_relevanssi_related_nothing',
						'row_relevanssi_related_notenough',
						'row_relevanssi_related_titles',
						'row_relevanssi_related_thumbnails',
						'row_relevanssi_default_thumbnail',
						'row_relevanssi_related_excerpts',
						'row_relevanssi_related_width',
						'row_relevanssi_related_cache_for_admins',
						'row_relevanssi_flush_related_cache'
					];

					relatedRowIDs.forEach(id => {
						const el = document.getElementById(id);
						if (!el) return;

						if (relatedEnabled) {
							el.classList.remove('rlv-js-blended');
							el.querySelectorAll('input, select, textarea, button').forEach(input => {
								if (!input.classList.contains('rlv-internally-locked')) {
									input.removeAttribute('disabled');
								}
							});
						} else {
							el.classList.add('rlv-js-blended');
							el.querySelectorAll('input, select, textarea, button').forEach(input => input.setAttribute('disabled', 'disabled'));
						}
					});

					const thumbnailRow = document.getElementById('row_relevanssi_default_thumbnail');
					if (thumbnailRow) {
						if (relatedEnabled && relatedThumbnailsToggle && relatedThumbnailsToggle.checked) {
							thumbnailRow.classList.remove('rlv-js-hidden');
						} else {
							thumbnailRow.classList.add('rlv-js-hidden');
						}
					}
				}
			}

			excerptsMaster.addEventListener('change', evaluateFormDependencies);
			if (highlightSelect) highlightSelect.addEventListener('change', evaluateFormDependencies);
			if (showMatchesMaster) showMatchesMaster.addEventListener('change', evaluateFormDependencies);
			if (relatedMaster) relatedMaster.addEventListener('change', evaluateFormDependencies);
			if (relatedThumbnailsToggle) relatedThumbnailsToggle.addEventListener('change', evaluateFormDependencies);

			if (postTypesTableContainer) {
				postTypesTableContainer.addEventListener('change', function(e) {
					if (e.target && e.target.classList.contains('rlv-matching-toggle')) {
						const nonMatchingCheckboxes = postTypesTableContainer.querySelectorAll('.rlv-nonmatching-item');
						if (e.target.checked) {
							nonMatchingCheckboxes.forEach(cb => {
								cb.setAttribute('disabled', 'disabled');
								cb.classList.add('rlv-internally-locked');
							});
						} else {
							nonMatchingCheckboxes.forEach(cb => {
								cb.removeAttribute('disabled');
								cb.classList.remove('rlv-internally-locked');
							});
						}
					}
				});
			}

			evaluateFormDependencies();

			if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
				jQuery('.color-field').each(function() {
					const $input = jQuery(this);
					const inputId = $input.attr('id');
					const $previewBox = jQuery('#preview_' + inputId);

					$input.wpColorPicker({
						change: function(event, ui) {
							const selectedColor = ui.color.toString();
							if (inputId === 'relevanssi_txt_col') {
								$previewBox.css('color', selectedColor);
							} else if (inputId === 'relevanssi_bg_col') {
								$previewBox.css('background-color', selectedColor);
							}
						},
						clear: function() {
							if (inputId === 'relevanssi_txt_col') $previewBox.css('color', '#000000');
							if (inputId === 'relevanssi_bg_col') $previewBox.css('background-color', '#ffffff');
						}
					});

					if (inputId === 'relevanssi_txt_col') $previewBox.css('color', $input.val());
					if (inputId === 'relevanssi_bg_col') $previewBox.css('background-color', $input.val());
				});
			}

			if (typeof jQuery !== 'undefined' && wp && wp.media) {
				jQuery(document).ready(function($) {
					var file_frame;
					var wp_media_post_id = wp.media.model.settings.post.id;
					var set_to_post_id = parseInt($('#relevanssi_default_thumbnail').val()) || 0;

					$('#upload_image_button').on('click', function(event) {
						event.preventDefault();

						if (file_frame) {
							file_frame.uploader.uploader.param('post_id', set_to_post_id);
							file_frame.open();
							return;
						} else {
							wp.media.model.settings.post.id = set_to_post_id;
						}

						file_frame = wp.media.frames.file_frame = wp.media({
							title: '<?php esc_html_e( 'Select an image to upload', 'relevanssi' ); ?>',
							button: { text: '<?php esc_html_e( 'Use this image', 'relevanssi' ); ?>' },
							multiple: false
						});

						file_frame.on('select', function() {
							var attachment = file_frame.state().get('selection').first().toJSON();
							$('#image-preview').attr('src', attachment.url).css('width', '100px');
							$('#relevanssi_default_thumbnail').val(attachment.id);
							wp.media.model.settings.post.id = wp_media_post_id;
							$('.image-preview-wrapper').show();
						});

						file_frame.open();
					});

					$('a.add_media').on('click', function() {
						wp.media.model.settings.post.id = wp_media_post_id;
						$(".image-preview-wrapper").show();
					});
				});
			}
		});
	</script>
	<?php
}