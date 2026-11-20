<?php
/**
 * Conservative dashboard promotion and permanent per-user dismissal.
 *
 * @package Relevanssi
 */

/** Exercises the shared notice in Premium and generated Free. */
class CampaignNoticeTest extends WP_Ajax_UnitTestCase {
	/** Establishes an eligible administrator without making licence requests. */
	public function setUp() {
		parent::setUp();
		// The AJAX harness marks every request as AJAX, including simulated page views.
		add_filter( 'wp_doing_ajax', '__return_false' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'dashboard' );
		delete_site_option( 'relevanssi_api_key' );
		delete_option( 'relevanssi_api_key' );
	}

	/** Restores request and script state. */
	public function tearDown() {
		$_POST = array();
		unset( $_REQUEST['security'] );
		wp_dequeue_script( 'relevanssi_campaign_notice' );
		wp_dequeue_style( 'relevanssi_campaign_notice' );
		remove_filter( 'wp_doing_ajax', '__return_false' );
		parent::tearDown();
	}

	/** Positive notice rendering belongs to Free; Premium has explicit exclusion coverage. */
	private function require_free() {
		if ( relevanssi_is_premium() ) {
			$this->markTestSkipped( 'Free-only notice behavior; run against an extracted Free package.' );
		}
	}

	/**
	 * Captures notice output without changing the actual clock.
	 *
	 * @param int|null $instant Unix timestamp.
	 * @return string Notice HTML.
	 */
	private function notice( ?int $instant = null ): string {
		ob_start();
		relevanssi_render_campaign_notice( $instant ?? strtotime( '2026-11-23 12:00:00 UTC' ) );
		return ob_get_clean();
	}

	/**
	 * Dispatches the authenticated dismissal action.
	 *
	 * @param bool $valid_nonce Whether to supply a valid nonce.
	 * @return array JSON response.
	 */
	private function dismiss( bool $valid_nonce = true ): array {
		remove_filter( 'wp_doing_ajax', '__return_false' );
		$_REQUEST['security'] = $valid_nonce ? wp_create_nonce( 'relevanssi_dismiss_campaign_notice' ) : 'invalid';
		$this->_last_response = '';
		ob_start();
		try {
			do_action( 'wp_ajax_relevanssi_dismiss_campaign_notice' );
		} catch ( WPAjaxDieContinueException $exception ) {
			return json_decode( $this->_last_response, true );
		} finally {
			add_filter( 'wp_doing_ajax', '__return_false' );
		}
		$this->fail( 'Expected an AJAX response.' );
	}

	/** Only Dashboard and Plugins receive the standard, locally rendered notice. */
	public function test_limited_placement_and_offer_content() {
		$this->require_free();
		$this->assertSame( 10, has_action( 'admin_notices', 'relevanssi_render_campaign_notice' ) );
		foreach ( array( 'dashboard', 'plugins' ) as $screen ) {
			set_current_screen( $screen );
			$output = $this->notice();
			$this->assertContains( 'class="notice notice-info"', $output );
			$this->assertContains( 'class="notice-dismiss"', $output );
			$this->assertContains( 'Black Friday Week: Save 30% on Annual', wp_strip_all_tags( $output ) );
			$this->assertContains( '€70 instead of €100, excluding tax', wp_strip_all_tags( $output ) );
			$this->assertContains( 'BLACKFRIDAY30', $output );
			$this->assertContains( 'Offer ends December 1, 2026.', $output );
			$this->assertContains( 'href="' . esc_url( relevanssi_black_friday_campaign()['url'] ) . '"', $output );
			$this->assertNotContains( 'is-dismissible', $output );
			$this->assertNotContains( 'FREE2026', $output );
			$this->assertNotContains( 'Permanent', $output );
			$this->assertTrue( wp_script_is( 'relevanssi_campaign_notice', 'enqueued' ) );
			$this->assertTrue( wp_style_is( 'relevanssi_campaign_notice', 'enqueued' ) );
			$dom = new DOMDocument();
			@$dom->loadHTML( '<?xml encoding="UTF-8">' . $output ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Inspecting an HTML fragment with HTML5 elements.
			$xpath = new DOMXPath( $dom );
			$this->assertSame( 1, $xpath->query( '//strong[text()="€70"]' )->length );
			$this->assertSame( 1, $xpath->query( '//strong[text()="BLACKFRIDAY30"]' )->length );
			$this->assertSame( 1, $xpath->query( '//strong[text()="30%"]' )->length );
			$logos = $xpath->query( '//img[@alt=""][@width="36"][@height="49"]' );
			$this->assertSame( 1, $logos->length );
			$this->assertSame( plugin_dir_url( $GLOBALS['relevanssi_variables']['file'] ) . 'images/relevanssi-campaign-logo.png', $logos->item( 0 )->getAttribute( 'src' ) );
			$this->assertFileExists( dirname( $GLOBALS['relevanssi_variables']['file'] ) . '/images/relevanssi-campaign-logo.png' );
			$this->assertFileExists( dirname( $GLOBALS['relevanssi_variables']['file'] ) . '/lib/campaign-notice.css' );
		}
		wp_dequeue_script( 'relevanssi_campaign_notice' );
		wp_dequeue_style( 'relevanssi_campaign_notice' );
		foreach ( array( 'edit-post', 'post', 'update', 'update-core', 'dashboard-network', 'plugins-network', 'dashboard-user', 'toplevel_page_relevanssi/relevanssi', 'front' ) as $screen ) {
			set_current_screen( $screen );
			$this->assertSame( '', $this->notice(), $screen );
			$this->assertFalse( wp_script_is( 'relevanssi_campaign_notice', 'enqueued' ) );
			$this->assertFalse( wp_style_is( 'relevanssi_campaign_notice', 'enqueued' ) );
		}
		set_current_screen( 'dashboard' );
		add_filter( 'wp_doing_ajax', '__return_true', 20 );
		try {
			$this->assertSame( '', $this->notice() );
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true', 20 );
		}
	}

	/** All six date boundaries remain independent of the site's timezone. */
	public function test_campaign_boundaries_and_conditional_assets() {
		$campaign = relevanssi_black_friday_campaign();
		$zone     = new DateTimeZone( $campaign['timezone'] );
		$start    = ( new DateTimeImmutable( $campaign['start'], $zone ) )->getTimestamp();
		$end      = ( new DateTimeImmutable( $campaign['end'], $zone ) )->getTimestamp();
		foreach ( array( 'UTC', 'Pacific/Auckland', 'America/Los_Angeles' ) as $timezone ) {
			update_option( 'timezone_string', $timezone );
			foreach ( array(
				$start - 1 => false,
				$start     => true,
				$start + 1 => true,
				$end - 1   => true,
				$end       => false,
				$end + 1   => false,
			) as $instant => $visible ) {
				$visible = $visible && ! relevanssi_is_premium();
				wp_dequeue_script( 'relevanssi_campaign_notice' );
				wp_dequeue_style( 'relevanssi_campaign_notice' );
				$this->assertSame( $visible, '' !== $this->notice( $instant ) );
				$this->assertSame( $visible, wp_script_is( 'relevanssi_campaign_notice', 'enqueued' ) );
				$this->assertSame( $visible, wp_style_is( 'relevanssi_campaign_notice', 'enqueued' ) );
			}
		}
	}

	/** Neither regular collapses nor reopening the sidebar resets notice dismissal. */
	public function test_dismissal_is_permanent_independent_and_per_user() {
		$this->require_free();
		$user = get_current_user_id();
		update_user_meta( $user, 'relevanssi_upgrade_offer_collapsed', '1' );
		update_user_meta( $user, 'relevanssi_black_friday_2026_dismissed', '1' );
		$this->assertNotSame( '', $this->notice() );
		$this->assertTrue( $this->dismiss()['success'] );
		$this->assertTrue( $this->dismiss()['success'] );
		$this->assertSame( '1', get_user_meta( $user, 'relevanssi_black_friday_2026_notice_dismissed', true ) );
		foreach ( array( 'dashboard', 'plugins' ) as $screen ) {
			set_current_screen( $screen );
			$this->assertSame( '', $this->notice() );
		}
		update_user_meta( $user, 'relevanssi_black_friday_2026_dismissed', '0' );
		$this->assertSame( '', $this->notice() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertNotSame( '', $this->notice() );
		wp_set_current_user( $user );
		$this->assertSame( '', $this->notice() );
	}

	/** The server owns the campaign key and current user; callers cannot reopen it. */
	public function test_client_cannot_select_metadata_or_user() {
		$other_user        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$_POST['user_id']  = $other_user;
		$_POST['meta_key'] = 'unrelated_preference';
		$_POST['state']    = 'expanded';
		$this->assertTrue( $this->dismiss()['success'] );
		$this->assertSame( '', get_user_meta( $other_user, 'relevanssi_black_friday_2026_notice_dismissed', true ) );
		$this->assertSame( '', get_user_meta( get_current_user_id(), 'unrelated_preference', true ) );
		$this->assertSame( '', $this->notice() );
	}

	/** Invalid nonces and non-administrators cannot persist a dismissal. */
	public function test_unauthorized_requests_and_visibility() {
		$this->assertFalse( $this->dismiss( false )['success'] );
		$this->assertSame( '', get_user_meta( get_current_user_id(), 'relevanssi_black_friday_2026_notice_dismissed', true ) );
		foreach ( array( 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
			$this->assertSame( '', $this->notice() );
			$this->assertFalse( $this->dismiss()['success'] );
			$this->assertSame( '', get_user_meta( get_current_user_id(), 'relevanssi_black_friday_2026_notice_dismissed', true ) );
		}
		wp_set_current_user( 0 );
		$this->assertSame( '', $this->notice() );
		$this->assertFalse( $this->dismiss()['success'] );
		$this->assertFalse( has_action( 'wp_ajax_nopriv_relevanssi_dismiss_campaign_notice' ) );
	}

	/** Failed persistence keeps the notice available for retry. */
	public function test_failed_persistence_and_no_external_requests() {
		$this->require_free();
		$requests = 0;
		$record   = static function () use ( &$requests ) {
			++$requests;
			return new WP_Error( 'unexpected_http_request' );
		};
		add_filter( 'pre_http_request', $record );
		add_filter( 'update_user_metadata', '__return_false' );
		try {
			$this->assertFalse( $this->dismiss()['success'] );
			$this->assertNotSame( '', $this->notice() );
		} finally {
			remove_filter( 'update_user_metadata', '__return_false' );
		}
		try {
			$this->assertTrue( $this->dismiss()['success'] );
			$this->assertSame( '', $this->notice() );
			$this->assertSame( 0, $requests );
		} finally {
			remove_filter( 'pre_http_request', $record );
		}
	}

	/** Premium never displays a global notice, even with an eligible missing or invalid key. */
	public function test_premium_never_displays_global_sale_notices() {
		if ( ! relevanssi_is_premium() ) {
			$this->markTestSkipped( 'Premium-only notice exclusion.' );
		}
		foreach ( array( 'missing', 'unknown', 'valid', 'invalid' ) as $status ) {
			delete_option( 'relevanssi_api_key' );
			if ( 'missing' !== $status ) {
				$key = 'notice-' . $status;
				update_option( 'relevanssi_api_key', $key );
				if ( in_array( $status, array( 'valid', 'invalid' ), true ) ) {
					relevanssi_premium_record_campaign_license_result(
						$key,
						array(
							'body'     => 'valid' === $status ? 'customer@example.org' : 'false',
							'response' => array( 'code' => 200 ),
						)
					);
				}
			}
			$this->assertSame( $status, relevanssi_premium_campaign_license_status() );
			foreach ( array( 'dashboard', 'plugins' ) as $screen ) {
				set_current_screen( $screen );
				$this->assertSame( '', $this->notice(), $status . ': ' . $screen );
				$this->assertFalse( wp_script_is( 'relevanssi_campaign_notice', 'enqueued' ) );
				$this->assertFalse( wp_style_is( 'relevanssi_campaign_notice', 'enqueued' ) );
			}
		}
	}

	/** Multisite shares dismissal across sites without a network-wide banner. */
	public function test_shared_user_dismissal_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}
		$this->assertTrue( $this->dismiss()['success'] );
		$blog = self::factory()->blog->create();
		add_user_to_blog( $blog, get_current_user_id(), 'administrator' );
		switch_to_blog( $blog );
		try {
			$this->assertSame( '', $this->notice() );
		} finally {
			restore_current_blog();
		}
	}
}
