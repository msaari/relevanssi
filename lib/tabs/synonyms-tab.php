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
	$synonyms = get_option( 'relevanssi_synonyms' );

	if ( isset( $synonyms ) ) {
		$synonyms = str_replace( ';', "\n", $synonyms );
	} else {
		$synonyms = '';
	}
?>
	<h3 id="synonyms"><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></h3>

<table class="form-table">
<tr>
	<th scope="row">
		<?php esc_html_e( 'Synonyms', 'relevanssi' ); ?>
	</th>
	<td>
		<p class="description"><?php esc_html_e( 'Add synonyms here to make the searches find better results. If you notice your users frequently misspelling a product name, or for other reasons use many names for one thing, adding synonyms will make the results better.', 'relevanssi' ); ?></p>

		<p class="description"><?php esc_html_e( "Do not go overboard, though, as too many synonyms can make the search confusing: users understand if a search query doesn't match everything, but they get confused if the searches match to unexpected things.", 'relevanssi' ); ?></p>
		<br />
		<textarea name='relevanssi_synonyms' id='relevanssi_synonyms' rows='9' cols='60'><?php echo esc_textarea( $synonyms ); ?></textarea>

		<p class="description"><?php _e( 'The format here is <code>key = value</code>. If you add <code>dog = hound</code> to the list of synonyms, searches for <code>dog</code> automatically become a search for <code>dog hound</code> and will thus match to posts that include either <code>dog</code> or <code>hound</code>. This only works in OR searches: in AND searches the synonyms only restrict the search, as now the search only finds posts that contain <strong>both</strong> <code>dog</code> and <code>hound</code>.', 'relevanssi' ); // WPCS: XSS ok. ?></p>

		<p class="description"><?php _e( 'The synonyms are one direction only. If you want both directions, add the synonym again, reversed: <code>hound = dog</code>.', 'relevanssi' ); // WPCS: XSS ok. ?></p>

		<p class="description"><?php _e( "It's possible to use phrases for the value, but not for the key. <code>dog = \"great dane\"</code> works, but <code>\"great dane\" = dog</code> doesn't.", 'relevanssi' ); // WPCS: XSS ok. ?></p>

		<?php if ( RELEVANSSI_PREMIUM ) : ?>
			<p class="description"><?php esc_html_e( 'If you want to use synonyms in AND searches, enable synonym indexing on the Indexing tab.', 'relevanssi' ); ?></p>
		<?php endif; ?>
	</td>
</tr>
</table>
<?php
}
