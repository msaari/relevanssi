<?php
/**
 * Accessible modal search forms.
 *
 * @package Relevanssi
 */

add_action( 'init', 'relevanssi_register_modal_search_block' );
add_action( 'wp_enqueue_scripts', 'relevanssi_preload_modal_search_style' );
add_action( 'wp_footer', 'relevanssi_render_menu_modal_search' );
add_filter( 'wp_nav_menu_objects', 'relevanssi_detect_modal_search_menu_link' );
add_shortcode( 'relevanssi_modal_search', 'relevanssi_modal_search_shortcode' );

/**
 * Registers the Modal Search block and its editor script.
 */
function relevanssi_register_modal_search_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	global $relevanssi_variables;

	wp_register_script(
		'relevanssi-modal-search-block',
		plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/modal-search-block.js',
		array( 'wp-blocks', 'wp-components', 'wp-editor', 'wp-element', 'wp-i18n' ),
		$relevanssi_variables['plugin_version'],
		true
	);
	wp_set_script_translations( 'relevanssi-modal-search-block', 'relevanssi', WP_CONTENT_DIR . '/languages/plugins' );

	register_block_type(
		'relevanssi/modal-search',
		array(
			'api_version'     => 2,
			'attributes'      => array(
				'triggerLabel' => array(
					'type'    => 'string',
					'default' => __( 'Search', 'relevanssi' ),
				),
				'modalLabel'   => array(
					'type'    => 'string',
					'default' => __( 'Search this site', 'relevanssi' ),
				),
				'iconOnly'     => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'postTypes'    => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'editor_script'   => 'relevanssi-modal-search-block',
			'render_callback' => 'relevanssi_render_modal_search_block',
		)
	);
}

/**
 * Renders the Modal Search block.
 *
 * @param array $attributes Block attributes.
 *
 * @return string The block markup.
 */
function relevanssi_render_modal_search_block( $attributes ) {
	$args = array(
		'label'       => isset( $attributes['triggerLabel'] ) ? $attributes['triggerLabel'] : __( 'Search', 'relevanssi' ),
		'modal_label' => isset( $attributes['modalLabel'] ) ? $attributes['modalLabel'] : __( 'Search this site', 'relevanssi' ),
		'icon'        => ! empty( $attributes['iconOnly'] ),
		'class'       => isset( $attributes['className'] ) ? $attributes['className'] : '',
	);

	if ( ! empty( $attributes['postTypes'] ) ) {
		$args['post_types'] = $attributes['postTypes'];
	}

	return relevanssi_get_modal_search( $args );
}

/**
 * Shortcode callback for [relevanssi_modal_search].
 *
 * Unrecognized attributes are passed to the Relevanssi search form, just like
 * attributes on the [searchform] shortcode.
 *
 * @param array|string $attributes Shortcode attributes.
 *
 * @return string The modal search markup.
 */
function relevanssi_modal_search_shortcode( $attributes ) {
	if ( ! is_array( $attributes ) ) {
		$attributes = array();
	}

	return relevanssi_get_modal_search( $attributes );
}

/**
 * Prints or returns a modal search trigger and dialog.
 *
 * @param array   $args Modal search arguments. Unknown keys become search form
 *                      arguments. Supported modal keys are id, label,
 *                      modal_label, icon, class, button_class and trigger.
 * @param boolean $should_echo Whether to print the markup. Default true.
 *
 * @return string The modal search markup.
 */
function relevanssi_modal_search( $args = array(), $should_echo = true ) {
	$markup = relevanssi_get_modal_search( $args );

	if ( $should_echo ) {
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in relevanssi_get_modal_search().
	}

	return $markup;
}

/**
 * Returns a modal search trigger and dialog.
 *
 * @param array $args Modal search arguments.
 *
 * @return string The modal search markup.
 */
function relevanssi_get_modal_search( $args = array() ) {
	if ( ! is_array( $args ) ) {
		$args = array();
	}

	$defaults = array(
		'id'           => '',
		'label'        => __( 'Search', 'relevanssi' ),
		'modal_label'  => __( 'Search this site', 'relevanssi' ),
		'icon'         => false,
		'class'        => '',
		'button_class' => '',
		'trigger'      => true,
	);

	/**
	 * Filters the modal search arguments.
	 *
	 * @param array $args     The arguments.
	 * @param array $defaults The default values.
	 */
	$args  = apply_filters( 'relevanssi_modal_search_args', $args, $defaults );
	$modal = array_merge( $defaults, array_intersect_key( $args, $defaults ) );
	$form  = array_diff_key( $args, $defaults );

	/**
	 * Filters the modal search form arguments.
	 *
	 * @param array $form  The form arguments.
	 * @param array $modal The modal arguments.
	 */
	$form = apply_filters( 'relevanssi_modal_search_form_args', $form, $modal );

	$modal['icon']    = relevanssi_modal_search_boolean( $modal['icon'] );
	$modal['trigger'] = relevanssi_modal_search_boolean( $modal['trigger'] );

	$id = sanitize_html_class( $modal['id'] );
	if ( empty( $id ) ) {
		$id = wp_unique_id( 'relevanssi-modal-search-' );
	}

	$modal['id'] = $id;
	relevanssi_enqueue_modal_search_assets();

	$trigger = '';
	if ( $modal['trigger'] ) {
		$trigger = relevanssi_get_modal_search_trigger( $modal );
	}

	$dialog = relevanssi_get_modal_search_dialog( $modal, $form );
	$markup = sprintf(
		'<div class="relevanssi-modal-search %1$s">%2$s%3$s</div>',
		esc_attr( $modal['class'] ),
		$trigger,
		$dialog
	);

	/**
	 * Filters the modal search trigger and dialog HTML code.
	 *
	 * @param string $markup The HTML code.
	 * @param array  $modal  Modal search arguments.
	 * @param array  $form   Form arguments.
	 */
	return apply_filters( 'relevanssi_modal_search_html', $markup, $modal, $form );
}

/**
 * Normalizes shortcode-friendly boolean values.
 *
 * @param mixed $value Value to normalize.
 *
 * @return boolean The normalized value.
 */
function relevanssi_modal_search_boolean( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
}

/**
 * Returns modal trigger markup.
 *
 * @param array $args Modal arguments.
 *
 * @return string The trigger markup.
 */
function relevanssi_get_modal_search_trigger( $args ) {
	$icon       = relevanssi_get_modal_search_icon( 'search' );
	$label      = esc_html( $args['label'] );
	$label_html = $args['icon'] ? '<span class="screen-reader-text">' . $label . '</span>' : '<span>' . $label . '</span>';
	$classes    = array( 'relevanssi-modal-search__trigger', 'wp-element-button' );

	if ( $args['icon'] ) {
		$classes[] = 'relevanssi-modal-search__trigger--icon';
	}
	if ( ! empty( $args['button_class'] ) ) {
		$classes = array_merge( $classes, preg_split( '/\s+/', $args['button_class'] ) );
	}

	/**
	 * Filters the CSS classes on a modal search trigger button.
	 *
	 * Themes can use this filter to add their existing button component class.
	 * The wp-element-button class is included by default for Global Styles.
	 *
	 * @param array $classes Trigger button classes.
	 * @param array $args    Modal search arguments.
	 */
	$classes = apply_filters( 'relevanssi_modal_search_trigger_classes', $classes, $args );
	if ( ! is_array( $classes ) ) {
		$classes = preg_split( '/\s+/', (string) $classes );
	}
	$classes = array_filter( array_map( 'sanitize_html_class', $classes ) );

	$trigger = sprintf(
		'<button type="button" class="%1$s" data-relevanssi-modal-search="%2$s" aria-haspopup="dialog" aria-controls="%2$s">%3$s%4$s</button>',
		esc_attr( implode( ' ', array_unique( $classes ) ) ),
		esc_attr( $args['id'] ),
		$icon,
		$label_html
	);

	/**
	 * Filters the HTML code for the modal search trigger button.
	 *
	 * @param string $trigger The HTML code.
	 * @param array  $args    Modal search arguments.
	 */
	return apply_filters( 'relevanssi_modal_search_trigger_html', $trigger, $args );
}

/**
 * Returns modal dialog markup.
 *
 * @param array $args      Modal arguments.
 * @param array $form_args Search form arguments.
 *
 * @return string The dialog markup.
 */
function relevanssi_get_modal_search_dialog( $args, $form_args ) {
	$title_id = $args['id'] . '-title';
	$form     = relevanssi_search_form( $form_args, true );
	$dialog   = sprintf(
		'<dialog id="%1$s" class="relevanssi-modal-search__dialog" aria-labelledby="%2$s"><div class="relevanssi-modal-search__panel"><div class="relevanssi-modal-search__header"><h2 id="%2$s" class="relevanssi-modal-search__title">%3$s</h2><button type="button" class="relevanssi-modal-search__close" data-relevanssi-modal-search-close aria-label="%4$s">%5$s</button></div><div class="relevanssi-modal-search__content">%6$s</div></div></dialog>',
		esc_attr( $args['id'] ),
		esc_attr( $title_id ),
		esc_html( $args['modal_label'] ),
		esc_attr__( 'Close search', 'relevanssi' ),
		relevanssi_get_modal_search_icon( 'close' ),
		$form
	);

	/**
	 * Filters the modal search dialog HTML code.
	 *
	 * @param string $dialog    The dialog HTML code.
	 * @param array  $args      Modal arguments.
	 * @param array  $form_args Search form arguments.
	 */
	return apply_filters( 'relevanssi_modal_search_dialog_html', $dialog, $args, $form_args );
}

/**
 * Returns an inline modal icon.
 *
 * @param string $icon Icon name: search or close.
 *
 * @return string SVG markup.
 */
function relevanssi_get_modal_search_icon( $icon ) {
	if ( 'close' === $icon ) {
		return '<svg class="relevanssi-modal-search__icon" aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" focusable="false"><path d="M6.4 5 12 10.6 17.6 5 19 6.4 13.4 12l5.6 5.6-1.4 1.4-5.6-5.6L6.4 19 5 17.6l5.6-5.6L5 6.4z"/></svg>';
	}

	return '<svg class="relevanssi-modal-search__icon" aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" focusable="false"><path d="M10 4a6 6 0 1 0 3.9 10.6l4.7 4.7 1.4-1.4-4.7-4.7A6 6 0 0 0 10 4zm0 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"/></svg>';
}

/**
 * Registers the modal search frontend assets.
 */
function relevanssi_register_modal_search_assets() {
	global $relevanssi_variables;

	$base_url = plugin_dir_url( $relevanssi_variables['file'] );
	$version  = $relevanssi_variables['plugin_version'];

	wp_register_style( 'relevanssi-modal-search', $base_url . 'lib/modal-search.css', array(), $version );
	wp_register_script( 'relevanssi-modal-search', $base_url . 'lib/modal-search.js', array(), $version, true );
}

/**
 * Loads modal styles in the page head when post content contains a modal.
 *
 * Late-rendered modals also have a JavaScript fallback that loads the styles
 * immediately. The filter can preload styles for modals rendered by a theme,
 * widget or another integration that cannot be detected from post content.
 */
function relevanssi_preload_modal_search_style() {
	$post           = get_queried_object();
	$should_preload = false;

	if ( $post instanceof WP_Post ) {
		$should_preload = has_shortcode( $post->post_content, 'relevanssi_modal_search' );

		if ( function_exists( 'has_block' ) && has_block( 'relevanssi/modal-search', $post ) ) {
			$should_preload = true;
		}
	}

	/**
	 * Filters whether modal search styles are loaded in the page head.
	 *
	 * @param boolean      $should_preload Whether to preload the stylesheet.
	 * @param WP_Post|null $post           The current queried post, if available.
	 */
	$should_preload = apply_filters( 'relevanssi_modal_search_preload_style', $should_preload, $post );

	if ( $should_preload ) {
		relevanssi_register_modal_search_assets();
		wp_enqueue_style( 'relevanssi-modal-search' );
	}
}

/**
 * Enqueues the modal controller and makes the stylesheet URL available to it.
 *
 * The script loads the stylesheet if the modal is rendered after wp_head.
 */
function relevanssi_enqueue_modal_search_assets() {
	global $relevanssi_variables;

	$base_url = plugin_dir_url( $relevanssi_variables['file'] );
	$version  = $relevanssi_variables['plugin_version'];

	relevanssi_register_modal_search_assets();

	if ( ! did_action( 'wp_head' ) ) {
		wp_enqueue_style( 'relevanssi-modal-search' );
	}

	wp_localize_script(
		'relevanssi-modal-search',
		'relevanssiModalSearchSettings',
		array(
			'stylesheet' => $base_url . 'lib/modal-search.css?ver=' . rawurlencode( $version ),
		)
	);
	wp_enqueue_script( 'relevanssi-modal-search' );
}

/**
 * Detects the documented modal-search custom menu link.
 *
 * Add a Custom Link with the URL #relevanssi-modal-search to any navigation
 * menu. A shared modal is then rendered in the footer.
 *
 * @param array $items Navigation menu items.
 *
 * @return array Unmodified menu items.
 */
function relevanssi_detect_modal_search_menu_link( $items ) {
	foreach ( $items as $item ) {
		if ( isset( $item->url ) && '#relevanssi-modal-search' === $item->url ) {
			$GLOBALS['relevanssi_modal_search_menu_dialog'] = true;
			relevanssi_enqueue_modal_search_assets();
			break;
		}
	}

	return $items;
}

/**
 * Renders the shared dialog used by modal-search menu links.
 */
function relevanssi_render_menu_modal_search() {
	if ( empty( $GLOBALS['relevanssi_modal_search_menu_dialog'] ) ) {
		return;
	}

	$markup = relevanssi_get_modal_search(
		array(
			'id'      => 'relevanssi-modal-search',
			'trigger' => false,
		)
	);

	echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in relevanssi_get_modal_search().
}
