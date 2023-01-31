<?php
/**
 * /lib/tabs/overview-tab.php
 *
 * Prints out the Overview tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the overview tab in Relevanssi settings.
 *
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_overview_tab() {
	global $relevanssi_variables;
	$this_page = '?page=' . plugin_basename( $relevanssi_variables['file'] );
	?>
	<h2><?php esc_html_e( 'Welcome to Relevanssi!', 'relevanssi' ); ?></h2>

	<table class="form-table" role="presentation">

	<?php
	if ( ! is_plugin_active_for_network( plugin_basename( $relevanssi_variables['file'] ) ) && function_exists( 'relevanssi_form_api_key' ) ) {
		relevanssi_form_api_key();
	}
	if ( function_exists( 'relevanssi_form_hide_post_controls' ) ) {
		relevanssi_form_hide_post_controls();
	}
	if ( function_exists( 'relevanssi_form_do_not_call_home' ) ) {
		relevanssi_form_do_not_call_home();
	}
	if ( function_exists( 'relevanssi_form_update_translations' ) ) {
		relevanssi_form_update_translations();
	}
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Getting started', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( "You've already installed Relevanssi. That's a great first step towards good search experience!", 'relevanssi' ); ?></p>
			<ol>
				<?php if ( 'done' !== get_option( 'relevanssi_indexed' ) ) : ?>
					<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
				<li><p><?php printf( esc_html__( 'Now, you need an index. Head over to the %1$s%2$s%3$s tab to set up the basic indexing options and to build the index.', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;tab=indexing'>", esc_html__( 'Indexing', 'relevanssi' ), '</a>' ); ?></p>
					<p><?php esc_html_e( 'You need to check at least the following options:', 'relevanssi' ); ?><br />
				&ndash; <?php esc_html_e( 'Make sure the post types you want to include in the index are indexed.', 'relevanssi' ); ?><br />
					<?php // Translators: %s is '_sku'. ?>
				&ndash; <?php printf( esc_html__( 'Do you use custom fields to store content you want included? If so, add those too. WooCommerce user? You probably want to include %s.', 'relevanssi' ), '<code>_sku</code>' ); ?></p>
					<p><?php esc_html_e( "Then just save the options and build the index. First time you have to do it manually, but after that, it's fully automatic: all changes are reflected in the index without reindexing. (That said, it's a good idea to rebuild the index once a year.)", 'relevanssi' ); ?></p>
				</li>
				<?php else : ?>
				<li><p><?php esc_html_e( 'Great, you already have an index!', 'relevanssi' ); ?></p></li>
				<?php endif; ?>
				<li>
					<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
					<p><?php printf( esc_html__( 'On the %1$s%2$s%3$s tab, choose whether you want the default operator to be AND (less results, but more precise) or OR (more results, less precise).', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;tab=searching'>", esc_html__( 'Searching', 'relevanssi' ), '</a>' ); ?></p>
				</li>
				<li>
				<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
					<p><?php printf( esc_html__( 'The next step is the %1$s%2$s%3$s tab, where you can enable the custom excerpts that show the relevant part of post in the search results pages.', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;tab=excerpts'>", esc_html__( 'Excerpts and highlights', 'relevanssi' ), '</a>' ); ?></p>
					<p><?php esc_html_e( 'There are couple of options related to that, so if you want highlighting in the results, you can adjust the styles for that to suit the look of your site.', 'relevanssi' ); ?></p>
				</li>
				<li>
					<p><?php esc_html_e( "That's about it! Now you should have Relevanssi up and running. The rest of the options is mostly fine-tuning.", 'relevanssi' ); ?></p>
				</li>
			</ol>
			<p><?php esc_html_e( "Relevanssi doesn't have a separate search widget. Instead, Relevanssi uses the default search widget. Any standard search form will do!", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Relevanssi Live Ajax Search', 'relevanssi' ); ?></th>
		<td>
		<?php // Translators: %1$s opens the link, %2$s closes it. ?>
			<p><?php printf( esc_html__( 'If you want a live search results, you can use the Relevanssi Live Ajax Search plugin. %1$sYou can find it in the plugin repository%2$s. It will make your search forms show instant results, powered by Relevanssi.', 'relevanssi' ), "<a href='https://wordpress.org/plugins/relevanssi-live-ajax-search/'>", '</a>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Privacy and GDPR compliance', 'relevanssi' ); ?></th>
		<td>
			<?php // Translators: %1$s and %3$s open the links, %2$s closes them. ?>
			<p><?php printf( esc_html__( '%1$sGDPR Compliance at Relevanssi knowledge base%2$s explains how using Relevanssi affects the GDPR compliance and the privacy policies of your site. Relevanssi also supports the %3$sprivacy policy tool%2$s and the WordPress user data export and erase tools.', 'relevanssi' ), "<a href='https://www.relevanssi.com/knowledge-base/gdpr-compliance/'>", '</a>', "<a href='privacy.php'>" ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'For more information', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( "Relevanssi uses the WordPress contextual help. Click 'Help' on the top right corner for more information on many Relevanssi topics.", 'relevanssi' ); ?></p>
			<?php // Translators: %1$s opens the link, %2$s closes the link. ?>
			<p><?php printf( esc_html__( '%1$sRelevanssi knowledge base%2$s has lots of information about advanced Relevanssi use, including plenty of code samples.', 'relevanssi' ), "<a href='https://www.relevanssi.com/knowledge-base/'>", '</a>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Do you like Relevanssi?', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( 'If you do, the best way to show your appreciation is to spread the word and perhaps give us a good review on WordPress.org.', 'relevanssi' ); ?></p>
			<?php // Translators: %1$s opens the link, %2$s closes the link. ?>
			<p><?php printf( esc_html__( 'If you like Relevanssi, leaving a five-star review on WordPress.org will help others discover Relevanssi. %1$sYou can add your review here%2$s.', 'relevanssi' ), "<a href='https://wordpress.org/support/plugin/relevanssi/reviews/#new-post'>", '</a>' ); ?></p>
		</td>
	</tr>
	<?php if ( ! RELEVANSSI_PREMIUM ) { ?>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Buy Relevanssi Premium', 'relevanssi' ); ?>
		</th>
		<td>
			<p><a href="https://www.relevanssi.com/buy-premium"><?php esc_html_e( 'Buy Relevanssi Premium now', 'relevanssi' ); ?></a> –
			<?php // Translators: %1$s is the coupon code, %2$s is the year it expires. ?>
			<?php printf( esc_html__( 'use coupon code %1$s for 20%% discount (valid at least until the end of %2$s)', 'relevanssi' ), '<strong>FREE2023</strong>', '2023' ); ?></p>
			<p><?php esc_html_e( 'Here are some improvements Relevanssi Premium offers:', 'relevanssi' ); ?></p>
			<ul class="relevanssi_ul">
				<li><?php esc_html_e( 'PDF content indexing', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'A Related posts feature', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Index and search user profile pages', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Index and search taxonomy term pages', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Multisite searches across many subsites', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'WP CLI commands', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Adjust weights separately for each post type and taxonomy', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Internal link anchors can be search terms for the target posts', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Index and search any columns in the wp_posts database', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Hide Relevanssi branding from the User Searches page on a client installation', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Redirect search queries to custom URLs', 'relevanssi' ); ?></li>
			</ul>
		</td>
	</tr>
	<?php } // End if ( ! RELEVANSSI_PREMIUM ). ?>
	</table>
	<?php
}
