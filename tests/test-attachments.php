<?php
/**
 * Class AttachmentTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi attachment indexing.
 *
 * @group attachments
 */
class AttachmentTest extends WP_UnitTestCase {
	/**
	 * Sets up the tests.
	 *
	 * Generates three posts and adds an attachment to each one:
	 *
	 * - PDF file that says "This is a PDF file."
	 * - DOCX file that says "This is a DOCX file."
	 * - ODT file that says "This is an ODT file."
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();

		if ( RELEVANSSI_PREMIUM ) {
			update_option( 'relevanssi_read_new_files', 'on' );
			update_option( 'relevanssi_send_pdf_files', 'on' );
			update_option( 'relevanssi_index_post_types', array( 'post', 'attachment' ) );
			update_option( 'relevanssi_implicit_operator', 'AND' );
			update_option( 'relevanssi_api_key', getenv( 'RELEVANSSI_KEY' ) );
			update_option( 'relevanssi_link_pdf_files', 'on' );

			// Truncate the index.
			relevanssi_truncate_index();

			$post_id       = self::factory()->post->create();
			$attachment_id = self::factory()->attachment->create_upload_object( 'tests/assets/pdf.pdf', $post_id );

			$post_id       = self::factory()->post->create();
			$attachment_id = self::factory()->attachment->create_upload_object( 'tests/assets/docx.docx', $post_id );

			$post_id       = self::factory()->post->create();
			$attachment_id = self::factory()->attachment->create_upload_object( 'tests/assets/odt.odt', $post_id );
		}
	}

	/**
	 * Test attachments.
	 *
	 * Tries to find all three posts created in the setup based on attachment
	 * content.
	 */
	public function test_attachments() {
		if ( RELEVANSSI_PREMIUM ) {
			// If this fails, the API key is not set in the RELEVANSSI_KEY environmental
			// variable.
			$this->assertNotEmpty( get_option( 'relevanssi_api_key ' ), 'API key not set.' );

			// PDF.
			$args = array(
				's'           => 'pdf file',
				'post_type'   => 'attachment',
				'numberposts' => -1,
				'post_status' => 'inherit',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			// There should be one post matching the search.
			$this->assertEquals( 1, count( $posts ) );

			// Check that get_permalink() returns a direct link to the .pdf file.
			$permalink = get_permalink( $posts[0]->ID );
			$suffix    = substr( $permalink, -4 );
			$this->assertEquals( '.pdf', $suffix );

			// DOCX.
			$args = array(
				's'           => 'docx file',
				'post_type'   => 'attachment',
				'numberposts' => -1,
				'post_status' => 'inherit',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			// There should be one post matching the search.
			$this->assertEquals( 1, count( $posts ) );

			// ODT.
			$args = array(
				's'           => 'odt file',
				'post_type'   => 'attachment',
				'numberposts' => -1,
				'post_status' => 'inherit',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			// There should be one post matching the search.
			$this->assertEquals( 1, count( $posts ) );
		}
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		if ( function_exists( 'relevanssi_uninstall' ) ) {
			relevanssi_uninstall();
		}
		if ( function_exists( 'relevanssi_uninstall_free' ) ) {
			relevanssi_uninstall_free();
		}
	}
}
