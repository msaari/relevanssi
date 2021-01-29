<?php
/**
 * /lib/tabs/indexing-tab.php
 *
 * Prints out the Indexing tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the indexing tab in Relevanssi settings.
 *
 * @global $wpdb                 The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_indexing_tab() {
	global $wpdb, $relevanssi_variables;

	$index_post_types      = get_option( 'relevanssi_index_post_types' );
	$index_taxonomies_list = get_option( 'relevanssi_index_taxonomies_list' );
	$index_comments        = get_option( 'relevanssi_index_comments' );
	$index_fields          = get_option( 'relevanssi_index_fields' );
	$index_author          = get_option( 'relevanssi_index_author' );
	$index_excerpt         = get_option( 'relevanssi_index_excerpt' );
	$index_image_files     = get_option( 'relevanssi_index_image_files' );
	$expand_shortcodes     = get_option( 'relevanssi_expand_shortcodes' );
	$punctuation           = get_option( 'relevanssi_punctuation' );
	$min_word_length       = get_option( 'relevanssi_min_word_length' );

	if ( empty( $index_post_types ) ) {
		$index_post_types = array();
	}
	if ( empty( $index_taxonomies_list ) ) {
		$index_taxonomies_list = array();
	}

	$expand_shortcodes     = relevanssi_check( $expand_shortcodes );
	$index_author          = relevanssi_check( $index_author );
	$index_excerpt         = relevanssi_check( $index_excerpt );
	$index_image_files     = relevanssi_check( $index_image_files );
	$index_comments_all    = relevanssi_select( $index_comments, 'all' );
	$index_comments_normal = relevanssi_select( $index_comments, 'normal' );
	$index_comments_none   = relevanssi_select( $index_comments, 'none' );

	$fields_select_all     = '';
	$fields_select_none    = '';
	$fields_select_some    = 'selected';
	$fields_select_visible = '';

	if ( empty( $index_fields ) ) {
		$fields_select_none = 'selected';
		$fields_select_some = '';
	}
	if ( 'all' === $index_fields ) {
		$fields_select_all  = 'selected';
		$fields_select_some = '';
		$index_fields       = '';
	}
	if ( 'visible' === $index_fields ) {
		$fields_select_visible = 'selected';
		$fields_select_some    = '';
		$index_fields          = '';
	}

	if ( ! isset( $punctuation['quotes'] ) ) {
		$punctuation['quotes'] = 'replace';
	}
	if ( ! isset( $punctuation['decimals'] ) ) {
		$punctuation['decimals'] = 'remove';
	}
	if ( ! isset( $punctuation['ampersands'] ) ) {
		$punctuation['ampersands'] = 'replace';
	}
	if ( ! isset( $punctuation['hyphens'] ) ) {
		$punctuation['hyphens'] = 'replace';
	}
	$punct_quotes_replace     = relevanssi_select( $punctuation['quotes'], 'replace' );
	$punct_quotes_remove      = relevanssi_select( $punctuation['quotes'], 'remove' );
	$punct_decimals_replace   = relevanssi_select( $punctuation['decimals'], 'replace' );
	$punct_decimals_remove    = relevanssi_select( $punctuation['decimals'], 'remove' );
	$punct_decimals_keep      = relevanssi_select( $punctuation['decimals'], 'keep' );
	$punct_ampersands_replace = relevanssi_select( $punctuation['ampersands'], 'replace' );
	$punct_ampersands_remove  = relevanssi_select( $punctuation['ampersands'], 'remove' );
	$punct_ampersands_keep    = relevanssi_select( $punctuation['ampersands'], 'keep' );
	$punct_hyphens_replace    = relevanssi_select( $punctuation['hyphens'], 'replace' );
	$punct_hyphens_remove     = relevanssi_select( $punctuation['hyphens'], 'remove' );
	$punct_hyphens_keep       = relevanssi_select( $punctuation['hyphens'], 'keep' );

	$docs_count  = get_option( 'relevanssi_doc_count', 0 );
	$terms_count = get_option( 'relevanssi_terms_count', 0 );
	$lowest_doc  = $wpdb->get_var( 'SELECT doc FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc > 0 ORDER BY doc ASC LIMIT 1' );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	if ( RELEVANSSI_PREMIUM ) {
		$user_count    = get_option( 'relevanssi_user_count', 0 );
		$taxterm_count = get_option( 'relevanssi_taxterm_count', 0 );
	}

	$this_page  = '?page=' . plugin_basename( $relevanssi_variables['file'] );
	$update_url = wp_nonce_url( $this_page . '&tab=indexing&update_counts=1', 'update_counts' );

	?>
	<div id="indexing_tab">

	<table class="form-table" role="presentation">
	<tr>
		<td scope="row">
			<input type='submit' name='submit' value='<?php esc_attr_e( 'Save the options', 'relevanssi' ); ?>' class='button button-primary' /><br /><br />
			<input type="button" id="build_index" name="index" value="<?php esc_attr_e( 'Build the index', 'relevanssi' ); ?>" class='button-primary' /><br /><br />
			<input type="button" id="continue_indexing" name="continue" value="<?php esc_attr_e( 'Index unindexed posts', 'relevanssi' ); ?>" class='button-primary' />
		</td>
		<td>
			<div id='indexing_button_instructions'>
				<?php // Translators: %s is "Build the index". ?>
				<p class="description"><?php printf( esc_html__( '%s empties the existing index and rebuilds it from scratch.', 'relevanssi' ), '<strong>' . esc_html__( 'Build the index', 'relevanssi' ) . '</strong>' ); ?></p>
				<?php // Translators: %s is "Build the index". ?>
				<p class="description"><?php printf( esc_html__( "%s doesn't empty the index and only indexes those posts that are not indexed. You can use it if you have to interrupt building the index.", 'relevanssi' ), '<strong>' . esc_html__( 'Index unindexed posts', 'relevanssi' ) . '</strong>' ); ?>
				<?php
				if ( RELEVANSSI_PREMIUM ) {
					esc_html_e( "This doesn't index any taxonomy terms or users.", 'relevanssi' );
				}
				?>
				</p>
			</div>
			<div id='relevanssi-note' style='display: none'></div>
			<div id='relevanssi-progress' class='rpi-progress'><div class="rpi-indicator"></div></div>
			<div id='relevanssi-timer'><?php esc_html_e( 'Time elapsed', 'relevanssi' ); ?>: <span id="relevanssi_elapsed">0:00:00</span> | <?php esc_html_e( 'Time remaining', 'relevanssi' ); ?>: <span id="relevanssi_estimated"><?php esc_html_e( 'some time', 'relevanssi' ); ?></span></div>
			<label for="results" class="screen-reader-text"><?php esc_html_e( 'Results', 'relevanssi' ); ?></label><textarea id='results' rows='10' cols='80'></textarea>
			<div id='relevanssi-indexing-instructions' style='display: none'><?php esc_html_e( "Indexing should respond quickly. If nothing happens in couple of minutes, it's probably stuck. The most common reasons for indexing issues are incompatible shortcodes, so try disabling the shortcode expansion setting and try again. Also, if you've just updated Relevanssi, doing a hard refresh in your browser will make sure your browser is not trying to use an outdated version of the Relevanssi scripts.", 'relevanssi' ); ?></div>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'State of the index', 'relevanssi' ); ?></td>
		<td id="stateoftheindex"><p><?php echo esc_html( $docs_count ); ?> <?php echo esc_html( _n( 'document in the index.', 'documents in the index.', $docs_count, 'relevanssi' ) ); ?>
	<?php if ( RELEVANSSI_PREMIUM ) : ?>
		<br /><?php echo esc_html( $user_count ); ?> <?php echo esc_html( _n( 'user in the index.', 'users in the index.', $user_count, 'relevanssi' ) ); ?><br />
		<?php echo esc_html( $taxterm_count ); ?> <?php echo esc_html( _n( 'taxonomy term in the index.', 'taxonomy terms in the index.', $taxterm_count, 'relevanssi' ) ); ?>
	<?php endif; ?>
		</p>
		<p><?php echo esc_html( $terms_count ); ?> <?php echo esc_html( _n( 'term in the index.', 'terms in the index.', $terms_count, 'relevanssi' ) ); ?><br />
		<?php echo esc_html( $lowest_doc ); ?> <?php esc_html_e( 'is the lowest post ID indexed.', 'relevanssi' ); ?></p>
		<?php /* Translators: %1$s opens the a tag, %2$s closes it. */ ?>
		<p class="description">(<?php printf( esc_html__( 'These values may be inaccurate. If you need exact values, %1$supdate the counts%2$s', 'relevanssi' ), '<a href="' . esc_attr( $update_url ) . '">', '</a>' ); ?>.)</p>
		</td>
	</tr>
	</table>

	<?php
	if ( count( $index_post_types ) < 2 ) {
		$index_users      = get_option( 'relevanssi_index_users', 'off' );
		$index_taxonomies = get_option( 'relevanssi_index_taxonomies', 'off' );
		if ( 'off' === $index_users && 'off' === $index_taxonomies ) {
			printf( '<p><strong>%s</strong></p>', esc_html__( "WARNING: You've chosen no post types to index. Nothing will be indexed. Choose some post types to index.", 'relevanssi' ) );
		}
	}
	?>

	<h2 id="indexing"><?php esc_html_e( 'Indexing options', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'Any changes to the settings on this page require reindexing before they take effect.', 'relevanssi' ); ?></p>

	<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'Post types', 'relevanssi' ); ?></th>
		<td>

		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Post types to index', 'relevanssi' ); ?></legend>
			<table class="widefat" id="index_post_types_table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'relevanssi' ); ?></th>
						<th><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
						<th><?php esc_html_e( 'Excluded from search?', 'relevanssi' ); ?></th>
					</tr>
				</thead>
	<?php
	$pt_1         = get_post_types( array( 'exclude_from_search' => '0' ) );
	$pt_2         = get_post_types( array( 'exclude_from_search' => false ) );
	$public_types = array_merge( $pt_1, $pt_2 );
	$post_types   = get_post_types();
	foreach ( $post_types as $type ) {
		if ( in_array( $type, relevanssi_get_forbidden_post_types(), true ) ) {
			continue;
		}
		$checked = '';
		if ( in_array( $type, $index_post_types, true ) ) {
			$checked = 'checked="checked"';
		}
		// Translators: %s is the post type name.
		$screen_reader_label  = sprintf( __( 'Index post type %s', 'relevanssi' ), $type );
		$label                = $type;
		$excluded_from_search = __( 'yes', 'relevanssi' );
		// Translators: %s is the post type name.
		$screen_reader_exclude = sprintf( __( 'Post type %s is excluded from search', 'relevanssi' ), $type );
		if ( in_array( $type, $public_types, true ) ) {
			$excluded_from_search = __( 'no', 'relevanssi' );
			// Translators: %s is the post type name.
			$screen_reader_exclude = sprintf( __( 'Post type %s can be searched', 'relevanssi' ), $type );
		}
		$name_id = 'relevanssi_index_type_' . $type;
		printf(
			'<tr>
				<th scope="row"><label class="screen-reader-text" for="%3$s">%1$s</label> %2$s</th>
				<td><input type="checkbox" name="%3$s" id="%3$s" %4$s /></td>
				<td><span aria-hidden="true">%5$s</span><span class="screen-reader-text">%6$s</span></td>
			</tr>',
			esc_html( $screen_reader_label ),
			esc_html( $label ),
			esc_attr( $name_id ),
			esc_html( $checked ),
			esc_html( $excluded_from_search ),
			esc_html( $screen_reader_exclude )
		);
	}
	?>
			<tr style="display:none">
				<td>
					<label for="relevanssi_index_type_bogus">Helper control field to make sure settings are saved if no post types are selected.</label>
				</td>
				<td>
					<input type='checkbox' name='relevanssi_index_type_bogus' id='relevanssi_index_type_bogus' checked="checked" />
				</td>
				<td>
					This is our little secret, just for you and me
				</td>
			</tr>
			</table>
		</fieldset>
		<p class="description"><?php esc_html_e( "If you want to index a post type that's marked 'Excluded from search', you can do that without worrying about it – but you need to uncheck the 'Respect exclude_from_search' setting from the Searching tab.", 'relevanssi' ); ?></p>
	</td>
	</tr>

	<tr id="row_index_image_files"
	<?php
	if ( ! in_array( 'attachment', $index_post_types, true ) ) {
		echo 'style="display: none"';
	}
	?>
	>
		<th scope="row">
			<?php esc_html_e( 'Index image files', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_index_image_files'>
				<input type='checkbox' name='relevanssi_index_image_files' id='relevanssi_index_image_files' <?php echo esc_attr( $index_image_files ); ?> />
				<?php esc_html_e( 'Index image attachments', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'If this option is enabled, Relevanssi will include image attachments in the index. If the option is disabled, only other attachment types are included.', 'relevanssi' ); ?></p>
			<?php // Translators: %1$s opens the link, %2$s closes it. ?>
			<p class="description"><?php printf( esc_html__( 'For more detailed control over the attachment type indexing, see %1$sControlling attachment types in the Knowledge base%2$s.', 'relevanssi' ), '<a href="https://www.relevanssi.com/knowledge-base/controlling-attachment-types-index/">', '</a>' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Taxonomies', 'relevanssi' ); ?>
		</th>
		<td>

			<table class="widefat" id="custom_taxonomies_table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Taxonomy', 'relevanssi' ); ?></th>
					<th><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
					<th><?php esc_html_e( 'Public?', 'relevanssi' ); ?></th>
				</tr>
			</thead>

	<?php
	$taxos = get_taxonomies( '', 'objects' );
	foreach ( $taxos as $taxonomy ) {
		if ( in_array( $taxonomy->name, relevanssi_get_forbidden_taxonomies(), true ) ) {
			continue;
		}
		$checked = '';
		if ( in_array( $taxonomy->name, $index_taxonomies_list, true ) ) {
			$checked = 'checked="checked"';
		}

		// Translators: %s is the taxonomy name.
		$screen_reader_label = sprintf( __( 'Index taxonomy %s', 'relevanssi' ), $taxonomy->name );
		$public              = __( 'no', 'relevanssi' );
		// Translators: %s is the taxonomy name.
		$screen_reader_public = sprintf( __( 'Taxonomy %s is not public', 'relevanssi' ), $taxonomy->name );
		if ( $taxonomy->public ) {
			$public = __( 'yes', 'relevanssi' );
			// Translators: %s is the taxonomy name.
			$screen_reader_public = sprintf( __( 'Taxonomy %s is public', 'relevanssi' ), $taxonomy->name );
		}

		$name_id = 'relevanssi_index_taxonomy_' . $taxonomy->name;
		printf(
			'<tr>
				<th scope="row"><label class="screen-reader-text" for="%3$s">%1$s</label> %2$s</th>
				<td><input type="checkbox" name="%3$s" id="%3$s" %4$s /></td>
				<td><span aria-hidden="true">%5$s</span><span class="screen-reader-text">%6$s</span></td>
			</tr>',
			esc_html( $screen_reader_label ),
			esc_html( $taxonomy->name ),
			esc_attr( $name_id ),
			esc_html( $checked ),
			esc_html( $public ),
			esc_html( $screen_reader_public )
		);

	}
	?>
			</table>

			<p class="description"><?php esc_html_e( 'If you check a taxonomy here, the terms for that taxonomy are indexed with the posts. If you for example choose "post_tag", searching for a tag will find all posts that have the tag.', 'relevanssi' ); ?>

		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_comments'><?php esc_html_e( 'Comments', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_index_comments' id='relevanssi_index_comments'>
				<option value='none' <?php echo esc_html( $index_comments_none ); ?>><?php esc_html_e( 'none', 'relevanssi' ); ?></option>
				<option value='normal' <?php echo esc_html( $index_comments_normal ); ?>><?php esc_html_e( 'comments', 'relevanssi' ); ?></option>
				<option value='all' <?php echo esc_html( $index_comments_all ); ?>><?php esc_html_e( 'comments and pingbacks', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'If you choose to index comments, you can choose if you want to index just comments, or everything including comments and track- and pingbacks.', 'relevanssi' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_fields_select'><?php esc_html_e( 'Custom fields', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_index_fields_select' id='relevanssi_index_fields_select'>
				<option value='none' <?php echo esc_html( $fields_select_none ); ?>><?php esc_html_e( 'none', 'relevanssi' ); ?></option>
				<option value='all' <?php echo esc_html( $fields_select_all ); ?>><?php esc_html_e( 'all', 'relevanssi' ); ?></option>
				<option value='visible' <?php echo esc_html( $fields_select_visible ); ?>><?php esc_html_e( 'visible', 'relevanssi' ); ?></option>
				<option value='some' <?php echo esc_html( $fields_select_some ); ?>><?php esc_html_e( 'some', 'relevanssi' ); ?></option>
			</select>
			<p class="description">
			<?php
			esc_html_e( "'All' indexes all custom fields for posts.", 'relevanssi' );
			echo '<br/>';
			esc_html_e( "'Visible' only includes the custom fields that are visible in the user interface (with names that don't start with an underscore).", 'relevanssi' );
			echo '<br/>';
			esc_html_e( "'Some' lets you choose individual custom fields to index.", 'relevanssi' );
			?>
			</p>
			<?php
			if ( class_exists( 'acf' ) && $fields_select_all ) {
				echo "<p class='description important'>";
				esc_html_e( 'Advanced Custom Fields has lots of invisible custom fields with meta data. Selecting "all" will include lots of garbage in the index and excerpts. "Visible" is usually a better option with ACF.' );
				echo '</p>';
			}
			?>
			<div id="index_field_input"
			<?php
			if ( empty( $fields_select_some ) ) {
				echo 'style="display: none"';
			}
			?>
			>
				<label for="relevanssi_index_fields" class="screen-reader-text"><?php esc_html_e( 'Custom fields to index', 'relevanssi' ); ?></label>
				<input type='text' name='relevanssi_index_fields' id='relevanssi_index_fields' size='60' value='<?php echo esc_attr( $index_fields ); ?>' />
				<p class="description"><?php esc_html_e( "Enter a comma-separated list of custom fields to include in the index. With Relevanssi Premium, you can also use 'fieldname_%_subfieldname' notation for ACF repeater fields.", 'relevanssi' ); ?></p>
				<p class="description"><?php esc_html_e( "You can use 'relevanssi_index_custom_fields' filter hook to adjust which custom fields are indexed.", 'relevanssi' ); ?></p>
			</div>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
				<?php // Translators: %1$s is the 'some' option and %2$s is '_sku'. ?>
			<p class="description"><?php printf( esc_html__( 'If you want the SKU included, choose %1$s and enter %2$s. Also see the contextual help for more details.', 'relevanssi' ), esc_html( "'" . __( 'some', 'relevanssi' ) . "'" ), '<code>_sku</code>' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Author display names', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_index_author'>
				<input type='checkbox' name='relevanssi_index_author' id='relevanssi_index_author' <?php echo esc_html( $index_author ); ?> />
				<?php esc_html_e( 'Index the post author display name', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Searching for the post author display name will return posts by that author.', 'relevanssi' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Excerpts', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_index_excerpt'>
				<input type='checkbox' name='relevanssi_index_excerpt' id='relevanssi_index_excerpt' <?php echo esc_html( $index_excerpt ); ?> />
				<?php esc_html_e( 'Index the post excerpt', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Relevanssi will find posts by the content in the excerpt.', 'relevanssi' ); ?></p>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description"><?php esc_html_e( "WooCommerce stores the product short description in the excerpt, so it's a good idea to index excerpts.", 'relevanssi' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>

	</table>

	<h2><?php esc_html_e( 'Shortcodes', 'relevanssi' ); ?></h2>

	<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Expand shortcodes', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_expand_shortcodes'>
				<input type='checkbox' name='relevanssi_expand_shortcodes' id='relevanssi_expand_shortcodes' <?php echo esc_html( $expand_shortcodes ); ?> />
				<?php esc_html_e( 'Expand shortcodes when indexing', 'relevanssi' ); ?>
			</label>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description important"><?php esc_html_e( "WooCommerce has shortcodes that don't work well with Relevanssi. With WooCommerce, make sure the option is disabled.", 'relevanssi' ); ?></p>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'If checked, Relevanssi will expand shortcodes in post content before indexing. Otherwise shortcodes will be stripped.', 'relevanssi' ); ?></p>
			<p class="description"><?php esc_html_e( 'If you use shortcodes to include dynamic content, Relevanssi will not keep the index updated, the index will reflect the status of the shortcode content at the moment of indexing.', 'relevanssi' ); ?></p>
		</td>
	</tr>

	<?php
		do_action( 'relevanssi_indexing_tab_shortcodes' );
	?>

	</table>

	<?php
		do_action( 'relevanssi_indexing_tab' );
	?>

	<h2><?php esc_html_e( 'Advanced indexing settings', 'relevanssi' ); ?></h2>

	<p><button type="button" id="show_advanced_indexing"><?php esc_html_e( 'Show advanced settings', 'relevanssi' ); ?></button></p>

	<table class="form-table screen-reader-text" id="advanced_indexing" role="presentation">
	<tr>
		<th scope="row">
			<label for='relevanssi_min_word_length'><?php esc_html_e( 'Minimum word length', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_min_word_length' id='relevanssi_min_word_length' value='<?php echo esc_attr( $min_word_length ); ?>' />
			<p class="description"><?php esc_html_e( 'Words shorter than this many letters will not be indexed.', 'relevanssi' ); ?></p>
			<?php // Translators: %1$s is 'relevanssi_block_one_letter_searches' and %2$s is 'false'. ?>
			<p class="description"><?php printf( esc_html__( 'To enable one-letter searches, you need to add a filter function on the filter hook %1$s that returns %2$s.', 'relevanssi' ), '<code>relevanssi_block_one_letter_searches</code>', '<code>false</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Punctuation control', 'relevanssi' ); ?></th>
		<td><p class="description"><?php esc_html_e( 'Here you can adjust how the punctuation is controlled. For more information, see help. Remember that any changes here require reindexing, otherwise searches will fail to find posts they should.', 'relevanssi' ); ?></p></td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_hyphens'><?php esc_html_e( 'Hyphens and dashes', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_hyphens' id='relevanssi_punct_hyphens'>
				<option value='keep' <?php echo esc_html( $punct_hyphens_keep ); ?>><?php esc_html_e( 'Keep', 'relevanssi' ); ?></option>
				<option value='replace' <?php echo esc_html( $punct_hyphens_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_hyphens_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'How Relevanssi should handle hyphens and dashes (en and em dashes)? Replacing with spaces is generally the best option, but in some cases removing completely is the best option. Keeping them is rarely the best option.', 'relevanssi' ); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_quotes'><?php esc_html_e( 'Apostrophes and quotes', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_quotes' id='relevanssi_punct_quotes'>
				<option value='replace' <?php echo esc_html( $punct_quotes_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_quotes_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( "How Relevanssi should handle apostrophes and quotes? It's not possible to keep them; that would lead to problems. Default behaviour is to replace with spaces, but sometimes removing makes sense.", 'relevanssi' ); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_ampersands'><?php esc_html_e( 'Ampersands', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_ampersands' id='relevanssi_punct_ampersands'>
				<option value='keep' <?php echo esc_html( $punct_ampersands_keep ); ?>><?php esc_html_e( 'Keep', 'relevanssi' ); ?></option>
				<option value='replace' <?php echo esc_html( $punct_ampersands_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_ampersands_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'How Relevanssi should handle ampersands? Replacing with spaces is generally the best option, but if you talk a lot about D&amp;D, for example, keeping the ampersands is useful.', 'relevanssi' ); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_decimals'><?php esc_html_e( 'Decimal separators', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_decimals' id='relevanssi_punct_decimals'>
				<option value='keep' <?php echo esc_html( $punct_decimals_keep ); ?>><?php esc_html_e( 'Keep', 'relevanssi' ); ?></option>
				<option value='replace' <?php echo esc_html( $punct_decimals_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_decimals_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'How Relevanssi should handle periods between decimals? Replacing with spaces is the default option, but that often leads to the numbers being removed completely. If you need to search decimal numbers a lot, keep the periods.', 'relevanssi' ); ?></p>

		</td>
	</tr>
	<?php
	do_action( 'relevanssi_indexing_tab_advanced' );
	?>

	</table>

	<p><button type="button" style="display: none" id="hide_advanced_indexing"><?php esc_html_e( 'Hide advanced settings', 'relevanssi' ); ?></button></p>

	</div>
	<?php
}
