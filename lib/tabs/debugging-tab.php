<?php
/**
 * /lib/tabs/debugging-tab.php
 *
 * Prints out the Debugging tab in Relevanssi settings.
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
	$how_relevanssi_sees = '';
	$db_post_view        = '';
	$current_post_id     = 0;
	$current_db_post_id  = 0;
	$selected            = 'post';
	if ( isset( $_REQUEST['post_id'] ) ) {
		wp_verify_nonce( '_relevanssi_nonce', 'relevanssi_how_relevanssi_sees' );
		$type = 'post';
		if ( isset( $_REQUEST['type'] ) ) {
			if ( 'term' === $_REQUEST['type'] ) {
				$type     = 'term';
				$selected = 'term';
			}
			if ( 'user' === $_REQUEST['type'] ) {
				$type     = 'user';
				$selected = 'user';
			}
		}
		if ( intval( $_REQUEST['post_id'] ) > 0 ) {
			$current_post_id     = intval( $_REQUEST['post_id'] );
			$how_relevanssi_sees = relevanssi_generate_how_relevanssi_sees(
				intval( $current_post_id ),
				true,
				$type
			);
		}
	}

	if ( isset( $_REQUEST['db_post_id'] ) ) {
		wp_verify_nonce( '_relevanssi_nonce', 'relevanssi_how_relevanssi_sees' );
		if ( intval( $_REQUEST['db_post_id'] ) > 0 ) {
			$current_db_post_id = intval( $_REQUEST['db_post_id'] );
			$db_post_view       = relevanssi_generate_db_post_view( $current_db_post_id );
		}
	}
	wp_nonce_field( 'relevanssi_how_relevanssi_sees', '_relevanssi_nonce', true, true );
	?>
	<h2><?php esc_html_e( 'Debugging', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'In order to figure out problems with indexing posts, you can test how Relevanssi sees the post by entering the post ID number in the field below.', 'relevanssi' ); ?></p>
	<?php
	if ( RELEVANSSI_PREMIUM ) {
		?>
		<p><?php esc_html_e( 'You can also check user profiles and taxonomy terms by choosing the type from the dropdown.', 'relevanssi' ); ?></p>
		<?php
	}
	if ( ! RELEVANSSI_PREMIUM ) {
		// Translators: %1$s starts the link, %2$s closes it.
		printf( '<p>' . esc_html__( 'In Relevanssi Premium, you can find this feature for each post on the post edit page. %1$sBuy Relevanssi Premium here%2$s.', 'relevanssi' ) . '</p>', '<a href="https://www.relevanssi.com/buy-premium/">', '</a>' );
	}
	?>
	<p><label for="post_id"><?php esc_html_e( 'The ID', 'relevanssi' ); ?></label>:
	<input type="text" name="post_id" id="post_id"
	<?php
	if ( $current_post_id > 0 ) {
		echo 'value="' . esc_attr( $current_post_id ) . '"';
	}
	?>
	/>
	<?php
	if ( RELEVANSSI_PREMIUM ) {
		?>
	<select name="type">
		<option value="post"
			<?php if ( 'post' === $selected ) { ?>
				selected="selected"
			<?php } ?>><?php esc_html_e( 'Post', 'relevanssi' ); ?></option>
		<option value="term"
			<?php if ( 'term' === $selected ) { ?>
				selected="selected"
			<?php } ?>><?php esc_html_e( 'Taxonomy term', 'relevanssi' ); ?></option>
		<option value="user"
			<?php if ( 'user' === $selected ) { ?>
				selected="selected"
			<?php } ?>><?php esc_html_e( 'User', 'relevanssi' ); ?></option>
	</select>
		<?php
	}
	?>
	</p>
	<p>
		<input
			type='submit' name='submit'
			value='<?php esc_attr_e( 'Check the post', 'relevanssi' ); ?>'
			class='button button-primary' />
	</p>

	<?php echo $how_relevanssi_sees; // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<h2><?php esc_html_e( 'What does the post look like in the database?', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( "This feature will show you how the post looks like in the database. It can sometimes be very helpful for debugging why a post isn't indexed the way you expect it to be.", 'relevanssi' ); ?></p>

	<p><label for="db_post_id"><?php esc_html_e( 'The ID', 'relevanssi' ); ?></label>:
	<input type="text" name="db_post_id" id="db_post_id"
	<?php
	if ( $current_db_post_id > 0 ) {
		echo 'value="' . esc_attr( $current_db_post_id ) . '"';
	}
	?>
	/>
	</p>
	<p>
		<input
			type='submit' name='submit'
			value='<?php esc_attr_e( 'Check the post', 'relevanssi' ); ?>'
			class='button button-primary' />
	</p>

	<?php echo $db_post_view; // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<h2><?php esc_html_e( 'Debugging information', 'relevanssi' ); ?></h2>

	<?php
	global $wpdb;
	$max_allowed_packet = $wpdb->get_var( 'SELECT @@global.max_allowed_packet' );
	$max_allowed_packet = round( $max_allowed_packet / 1024 / 1024, 2 );
	echo '<p>max_allowed_packet: ' . $max_allowed_packet . 'M</p>'; // phpcs:ignore WordPress.Security.EscapeOutput

	$indexing_query = relevanssi_generate_indexing_query(
		relevanssi_valid_status_array(),
		false,
		relevanssi_post_type_restriction(),
		'LIMIT 0'
	);
	?>
	<p><?php esc_html_e( 'Indexing query', 'relevanssi' ); ?>:</p>
	<?php
	echo '<code>' . $indexing_query . '</code>'; // phpcs:ignore WordPress.Security.EscapeOutput
	?>

	<?php do_action( 'relevanssi_debugging_tab' ); ?>

	<h2><?php esc_html_e( 'Debugging mode', 'relevanssi' ); ?></h2>

	<?php
	$enable_debugging_mode = relevanssi_check( get_option( 'relevanssi_debugging_mode' ) );
	?>

	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Enable the debugging mode.', 'relevanssi' ); ?></legend>
		<label for='relevanssi_debugging_mode'>
			<input type='checkbox' name='relevanssi_debugging_mode' id='relevanssi_debugging_mode' <?php echo esc_html( $enable_debugging_mode ); ?> />
			<?php esc_html_e( 'Enable the debugging mode.', 'relevanssi' ); ?>
		</label>
		<p class="description"><?php esc_html_e( "Relevanssi support may ask you to enable the debugging mode. When you check this box, it's possible to see debugging information from the front-end.", 'relevanssi' ); ?></p>
	</fieldset>

	<?php
}
