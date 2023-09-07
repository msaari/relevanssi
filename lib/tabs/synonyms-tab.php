<?php
/**
 * /lib/tabs/synonyms-tab.php
 *
 * Prints out the Synonyms tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the synonyms tab in Relevanssi settings.
 */
function relevanssi_synonyms_tab() {
	$current_language = relevanssi_get_current_language();
	if ( class_exists( 'Polylang', false ) && ! pll_current_language() ) {
		relevanssi_polylang_all_languages_synonyms();
		return;
	}
	$synonyms_array = get_option( 'relevanssi_synonyms', array() );
	$synonyms       = isset( $synonyms_array[ $current_language ] ) ? $synonyms_array[ $current_language ] : '';

	if ( isset( $synonyms ) ) {
		$synonyms = str_replace( ';', "\n", $synonyms );
	} else {
		$synonyms = '';
	}

	$synonyms_disabled = false;
	$operator          = get_option( 'relevanssi_implicit_operator' );
	if ( 'AND' === $operator ) {
		$index_synonyms = get_option( 'relevanssi_index_synonyms', false );
		if ( 'on' !== $index_synonyms ) {
			$synonyms_disabled = true;
		}
	}

	?>
	<h3 id="synonyms"><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></h3>

<table class="form-table" role="presentation">
<tr
	<?php
	if ( $synonyms_disabled ) {
		echo "class='relevanssi_disabled'";
	}
	?>
>
	<?php if ( $synonyms_disabled ) : ?>
	<tr>
		<th scope="row">
			<p class="important"><?php esc_html_e( 'No synonyms!', 'relevanssi' ); ?></p>
		</th>
		<td>
			<p class="important"><?php esc_html_e( 'Synonyms are disabled because the searching operator is set to AND. Enable OR searching to use the synonyms.', 'relevanssi' ); ?></p>
			<?php if ( RELEVANSSI_PREMIUM ) : ?>
				<p class="description"><?php esc_html_e( "If you want to use synonyms in AND searches, enable synonym indexing on the Indexing tab. Also, any changes to the synonyms won't take effect until you rebuild the index.", 'relevanssi' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Relevanssi Premium has a feature that allows you to include the synonyms in the indexing. This makes it possible to use synonyms in AND searches.', 'relevanssi' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php endif; ?>
	<th scope="row">
		<label for="relevanssi_synonyms"><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></label>
	</th>
	<td>
		<p class="description"><?php esc_html_e( 'Add synonyms here to make the searches find better results. If you notice your users frequently misspelling a product name, or for other reasons use many names for one thing, adding synonyms will make the results better.', 'relevanssi' ); ?></p>

		<p class="description"><?php esc_html_e( "Do not go overboard, though, as too many synonyms can make the search confusing: users understand if a search query doesn't match everything, but they get confused if the searches match to unexpected things.", 'relevanssi' ); ?></p>
		<br />
		<textarea name='relevanssi_synonyms' id='relevanssi_synonyms' rows='9' cols='60'
		<?php
		if ( $synonyms_disabled ) {
			echo 'disabled';
		}
		?>
		><?php echo esc_textarea( $synonyms ); ?></textarea>

		<p class="description"><?php _e( 'The format here is <code>key = value</code>. If you add <code>dog = hound</code> to the list of synonyms, searches for <code>dog</code> automatically become a search for <code>dog hound</code> and will thus match to posts that include either <code>dog</code> or <code>hound</code>. This only works in OR searches: in AND searches the synonyms only restrict the search, as now the search only finds posts that contain <strong>both</strong> <code>dog</code> and <code>hound</code>.', 'relevanssi' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>

		<p class="description"><?php _e( 'The synonyms are one direction only. If you want both directions, add the synonym again, reversed: <code>hound = dog</code>.', 'relevanssi' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>

		<p class="description"><?php _e( "It's possible to use phrases for the value, but not for the key. <code>dog = \"great dane\"</code> works, but <code>\"great dane\" = dog</code> doesn't.", 'relevanssi' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
	</td>
</tr>
</table>
	<?php
}

/**
 * Displays an error message when Polylang is in all languages mode.
 */
function relevanssi_polylang_all_languages_synonyms() {
	?>
	<h3 id="synonyms"><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></h3>

	<p class="description"><?php esc_html_e( 'You are using Polylang and are in "Show all languages" mode. Please select a language before adjusting the synonym settings.', 'relevanssi' ); ?></p>
	<?php
}
