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
	$current_post_id     = 0;
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

	<?php do_action( 'relevanssi_debugging_tab' ); ?>

	<?php
}
