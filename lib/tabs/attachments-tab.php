<?php
/**
 * /lib/tabs/attachments-tab.php
 *
 * Prints out the Attachments tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the attachments tab in Relevanssi settings.
 */
function relevanssi_attachments_tab() {
	?>
	<h2><?php esc_html_e( 'Indexing attachment content', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'With Relevanssi Premium, you can index the text contents of attachments (PDFs, Word documents, Open Office documents and many other types). The contents of the attachments are processed on an external service, which makes the feature reliable and light on your own server performance.', 'relevanssi' ); ?></p>
	<?php // Translators: %1$s starts the link, %2$s closes it. ?>
	<p><?php printf( esc_html__( 'In order to access this and many other delightful Premium features, %1$sbuy Relevanssi Premium here%2$s.', 'relevanssi' ), '<a href="https://www.relevanssi.com/buy-premium/">', '</a>' ); ?></p>
	<?php
}
