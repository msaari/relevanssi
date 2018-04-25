<?php
/**
 * /lib/tabs/stopwords-tab.php
 *
 * Prints out the Stopwords tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the stopwords tab in Relevanssi settings.
 */
function relevanssi_stopwords_tab() {
	?>
	<h3 id="stopwords"><?php esc_html_e( 'Stopwords', 'relevanssi' ); ?></h3>
	<?php

	relevanssi_show_stopwords();

	/**
	 * Filters whether the common words list is displayed or not.
	 *
	 * The list of 25 most common words is displayed by default, but if the index is
	 * big, displaying the list can take a long time. This filter can be used to
	 * turn the list off.
	 *
	 * @param boolean If true, show the list; if false, don't show it.
	 */
	if ( apply_filters( 'relevanssi_display_common_words', true ) ) {
		relevanssi_common_words( 25 );
	}
}

/**
 * Displays a list of stopwords.
 *
 * Displays the list of stopwords and gives the controls for adding new stopwords.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_show_stopwords() {
	global $wpdb, $relevanssi_variables;

	$plugin = 'relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$plugin = 'relevanssi-premium';
	}

	printf( '<p>%s</p>', esc_html__( 'Enter a word here to add it to the list of stopwords. The word will automatically be removed from the index, so re-indexing is not necessary. You can enter many words at the same time, separate words with commas.', 'relevanssi' ) );
?>
<table class="form-table">
<tr>
	<th scope="row">
		<label for="addstopword"><p><?php esc_html_e( 'Stopword(s) to add', 'relevanssi' ); ?>
	</th>
	<td>
		<textarea name="addstopword" id="addstopword" rows="2" cols="80"></textarea>
		<p><input type="submit" value="<?php esc_attr_e( 'Add', 'relevanssi' ); ?>" class='button' /></p>
	</td>
</tr>
</table>
<p><?php esc_html_e( "Here's a list of stopwords in the database. Click a word to remove it from stopwords. Removing stopwords won't automatically return them to index, so you need to re-index all posts after removing stopwords to get those words back to index.", 'relevanssi' ); ?></p>

<table class="form-table">
<tr>
	<th scope="row">
		<?php esc_html_e( 'Current stopwords', 'relevanssi' ); ?>
	</th>
	<td>
	<?php
	echo '<ul>';
	$results    = $wpdb->get_results( 'SELECT * FROM ' . $relevanssi_variables['stopword_table'] ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$exportlist = array();
	foreach ( $results as $stopword ) {
		$sw = stripslashes( $stopword->stopword );
		printf( '<li style="display: inline;"><input type="submit" name="removestopword" value="%s"/></li>', esc_attr( $sw ) );
		array_push( $exportlist, $sw );
	}
	echo '</ul>';

	$exportlist = htmlspecialchars( implode( ', ', $exportlist ) );
?>
	<p><input type="submit" id="removeallstopwords" name="removeallstopwords" value="<?php esc_attr_e( 'Remove all stopwords', 'relevanssi' ); ?>" class='button' /></p>
	</td>
</tr>
<tr>
	<th scope="row">
		<?php esc_html_e( 'Exportable list of stopwords', 'relevanssi' ); ?>
	</th>
	<td>
		<textarea name="stopwords" id="stopwords" rows="2" cols="80"><?php echo esc_textarea( $exportlist ); ?></textarea>
		<p class="description"><?php esc_html_e( 'You can copy the list of stopwords here if you want to back up the list, copy it to a different blog or otherwise need the list.', 'relevanssi' ); ?></p>
	</td>
</tr>
</table>

<?php
}

/**
 * Displays the list of most common words in the index.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 *
 * @param int     $limit  How many words to display, default 25.
 * @param boolean $wp_cli If true, return just a list of words. If false, print out
 * HTML code.
 *
 * @return array A list of words, if $wp_cli is true.
 */
function relevanssi_common_words( $limit = 25, $wp_cli = false ) {
	global $wpdb, $relevanssi_variables;

	$plugin = 'relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$plugin = 'relevanssi-premium';
	}

	if ( ! is_numeric( $limit ) ) {
		$limit = 25;
	}

	$words = $wpdb->get_results( 'SELECT COUNT(*) as cnt, term FROM ' . $relevanssi_variables['relevanssi_table'] . " GROUP BY term ORDER BY cnt DESC LIMIT $limit" ); // WPCS: unprepared sql ok, Relevanssi table name and $limit is numeric.

	if ( ! $wp_cli ) {
		printf( '<h2>%s</h2>', esc_html__( '25 most common words in the index', 'relevanssi' ) );
		printf( '<p>%s</p>', esc_html__( "These words are excellent stopword material. A word that appears in most of the posts in the database is quite pointless when searching. This is also an easy way to create a completely new stopword list, if one isn't available in your language. Click the icon after the word to add the word to the stopword list. The word will also be removed from the index, so rebuilding the index is not necessary.", 'relevanssi' ) );

?>
<input type="hidden" name="dowhat" value="add_stopword" />
<table class="form-table">
<tr>
	<th scope="row"><?php esc_html_e( 'Stopword Candidates', 'relevanssi' ); ?></th>
	<td>
<ul>
	<?php
	$src = plugins_url( 'delete.png', $relevanssi_variables['file'] );

	foreach ( $words as $word ) {
		$stop = __( 'Add to stopwords', 'relevanssi' );
		printf( '<li>%1$s (%2$d) <input style="padding: 0; margin: 0" type="image" src="%3$s" alt="%4$s" name="term" value="%5$s"/></li>', esc_html( $word->term ), esc_html( $word->cnt ), esc_attr( $src ), esc_attr( $stop ), esc_attr( $word->term ) );
	}
	?>
	</ul>
	</td>
</tr>
</table>
	<?php

	}

	return $words;
}
