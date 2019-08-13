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

	?>

	<h3 id="bodystopwords"><?php esc_html_e( 'Content stopwords', 'relevanssi' ); ?></h3>

	<?php
	if ( function_exists( 'relevanssi_show_body_stopwords' ) ) {
		relevanssi_show_body_stopwords();
	} else {
		printf( '<p>%s</p>', esc_html__( 'Content stopwords are a premium feature where you can set stopwords that only apply to the post content. Those stopwords will still be indexed if they appear in post titles, tags, categories, custom fields or other parts of the post. To use content stopwords, you need Relevanssi Premium.', 'relevanssi' ) );
	}

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
	$results    = $wpdb->get_results( 'SELECT * FROM ' . $relevanssi_variables['stopword_table'] );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
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
		<label for="stopwords" class="screen-reader-text"><?php esc_html_e( 'Exportable list of stopwords', 'relevanssi' ); ?></label>
		<textarea name="stopwords" id="stopwords" rows="2" cols="80"><?php echo esc_textarea( $exportlist ); ?></textarea>
		<p class="description"><?php esc_html_e( 'You can copy the list of stopwords here if you want to back up the list, copy it to a different blog or otherwise need the list.', 'relevanssi' ); ?></p>
	</td>
</tr>
</table>

	<?php
}
