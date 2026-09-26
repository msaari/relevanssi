<?php
/**
 * Registers and renders the Did You Mean block.
 *
 * @package Relevanssi
 */

add_action( 'init', 'relevanssi_register_did_you_mean_block' );

/**
 * Registers the Did You Mean block and its editor script.
 *
 * @return void
 */
function relevanssi_register_did_you_mean_block() {
	if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
		return;
	}

	global $relevanssi_variables;
	$asset      = array(
		'dependencies' => array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
		'version'      => $relevanssi_variables['plugin_version'],
	);
	$asset_file = __DIR__ . '/editor.asset.php';
	if ( is_file( $asset_file ) ) {
		$generated_asset = require $asset_file;
		if ( is_array( $generated_asset ) ) {
			$asset = array_merge( $asset, $generated_asset );
		}
	}

	wp_register_script(
		'relevanssi-did-you-mean-editor',
		plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/blocks/did-you-mean/editor.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations(
			'relevanssi-did-you-mean-editor',
			'relevanssi',
			WP_CONTENT_DIR . '/languages/plugins'
		);
	}

	register_block_type_from_metadata(
		__DIR__,
		array(
			'title'           => _x( 'Did You Mean?', 'block title', 'relevanssi' ),
			'description'     => _x( 'Displays spelling suggestions for search queries.', 'block description', 'relevanssi' ),
			'attributes'      => relevanssi_get_did_you_mean_block_attributes(),
			'render_callback' => 'relevanssi_render_did_you_mean_block',
		)
	);
}

/**
 * Returns the block attributes with localized defaults.
 *
 * Attribute defaults in block.json are not translated by WordPress. Defining
 * the registered attributes here keeps the default prefix translatable on the
 * server while preserving block.json as the public metadata contract.
 *
 * @return array The block attribute schema.
 */
function relevanssi_get_did_you_mean_block_attributes() {
	return array(
		'prefix'     => array(
			'type'    => 'string',
			'default' => __( 'Did you mean:', 'relevanssi' ),
		),
		'suffix'     => array(
			'type'    => 'string',
			'default' => '?',
		),
		'maxResults' => array(
			'type'    => 'integer',
			'default' => 5,
		),
	);
}

/**
 * Normalizes the result threshold to the range supported by the editor.
 *
 * Blocks can be edited as markup, so the server must enforce the same bounds
 * as the editor control instead of trusting serialized attributes.
 *
 * @param mixed $value Attribute value to normalize.
 *
 * @return int An integer between 0 and 50.
 */
function relevanssi_normalize_did_you_mean_max_results( $value ) {
	return min( 50, max( 0, (int) $value ) );
}

/**
 * Renders the Did You Mean block.
 *
 * @param array         $attributes Block attributes.
 * @param string        $content    Saved block content. Not used for this dynamic block.
 * @param WP_Block|null $block Block instance. Not used for this dynamic block.
 *
 * @return string The block markup, or an empty string when there is no suggestion.
 */
function relevanssi_render_did_you_mean_block( array $attributes, string $content = '', ?WP_Block $block = null ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Dynamic block callback signature.
	global $wp_query;

	$search_query = get_search_query( false );
	if ( empty( $search_query ) ) {
		return '';
	}

	$max_results = relevanssi_normalize_did_you_mean_max_results( $attributes['maxResults'] ?? 5 );
	$found_posts = 0;
	if ( is_object( $wp_query ) && isset( $wp_query->found_posts ) ) {
		$found_posts = (int) $wp_query->found_posts;
	}

	if ( $found_posts > $max_results ) {
		return '';
	}

	$prefix = isset( $attributes['prefix'] ) ? (string) $attributes['prefix'] : __( 'Did you mean:', 'relevanssi' );
	$suffix = isset( $attributes['suffix'] ) ? (string) $attributes['suffix'] : '?';
	$prefix = '' !== $prefix ? esc_html( $prefix ) . ' ' : '';
	$suffix = esc_html( $suffix );

	$suggestion = relevanssi_didyoumean(
		$search_query,
		$prefix,
		$suffix,
		$max_results,
		false
	);

	if ( empty( $suggestion ) ) {
		return '';
	}

	$wrapper_attributes = '';
	if ( function_exists( 'get_block_wrapper_attributes' ) ) {
		$wrapper_attributes = get_block_wrapper_attributes();
	}

	$markup = sprintf(
		'<p%1$s>%2$s</p>',
		$wrapper_attributes ? ' ' . $wrapper_attributes : '',
		$suggestion
	);

	return wp_kses_post( $markup );
}
