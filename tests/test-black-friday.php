<?php
/**
 * Campaign scheduling and independent Overview panel preferences.
 *
 * @package Relevanssi
 */

/** Tests shared code against Premium and an extracted Free package. */
class BlackFridayTest extends WP_Ajax_UnitTestCase {
	/** Installs plugin tables in the disposable test database. */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Campaign start instant.
	 *
	 * @var int
	 */
	private $start;

	/**
	 * Campaign end instant.
	 *
	 * @var int
	 */
	private $end;

	/** Establishes a Free-capable administrator context. */
	public function setUp() {
		parent::setUp();
		$campaign    = relevanssi_black_friday_campaign();
		$timezone    = new DateTimeZone( $campaign['timezone'] );
		$this->start = ( new DateTimeImmutable( $campaign['start'], $timezone ) )->getTimestamp();
		$this->end   = ( new DateTimeImmutable( $campaign['end'], $timezone ) )->getTimestamp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'toplevel_page_relevanssi/relevanssi' );
		if ( relevanssi_is_premium() ) {
			delete_site_option( 'relevanssi_api_key' );
			update_option( 'relevanssi_api_key', 'active-campaign-test-key' );
			relevanssi_premium_record_campaign_license_result(
				'active-campaign-test-key',
				array(
					'body'     => 'customer@example.org',
					'response' => array( 'code' => 200 ),
				)
			);
		}
	}

	/** Restores request and script state. */
	public function tearDown() {
		$_POST = array();
		unset( $_REQUEST['security'] );
		wp_dequeue_script( 'relevanssi_upgrade_panels' );
		wp_dequeue_script( 'relevanssi_cyber_week_offer' );
		parent::tearDown();
	}

	/**
	 * Returns sidebar markup at the requested instant.
	 *
	 * @param int $instant Unix timestamp.
	 * @return string HTML.
	 */
	private function sidebar( int $instant ): string {
		ob_start();
		relevanssi_render_upgrade_sidebar( $instant );
		return ob_get_clean();
	}

	/**
	 * Saves a panel preference through the actual AJAX action.
	 *
	 * @param string $panel Panel identifier.
	 * @param string $state Requested state.
	 * @return array JSON response.
	 */
	private function save_panel( string $panel, string $state ): array {
		$_POST['panel']       = $panel;
		$_POST['state']       = $state;
		$_REQUEST['security'] = wp_create_nonce( 'relevanssi_upgrade_panel_state' );
		return $this->dispatch();
	}

	/**
	 * Captures an AJAX response without consuming PHPUnit's output buffer.
	 *
	 * @return array JSON response.
	 */
	private function dispatch(): array {
		$this->_last_response = '';
		ob_start();
		try {
			do_action( 'wp_ajax_relevanssi_save_upgrade_panel_state' );
		} catch ( WPAjaxDieContinueException $exception ) {
			return json_decode( $this->_last_response, true );
		}
		$this->fail( 'Expected a JSON response.' );
	}

	/** Skips edition-specific assertions when exercising Premium. */
	private function require_free() {
		if ( relevanssi_is_premium() ) {
			$this->markTestSkipped( 'Free-only behavior; run against an extracted Free package.' );
		}
	}

	/** The campaign window uses Vienna independently of the site timezone. */
	public function test_campaign_boundaries() {
		$campaign = relevanssi_black_friday_campaign();
		$this->assertSame( 'Europe/Vienna', $campaign['timezone'] );
		$this->assertSame( 'BLACKFRIDAY30', $campaign['coupon'] );
		$this->assertSame( 'https://www.relevanssi.com/buy-premium?utm_source=relevanssi_free&utm_medium=plugin_admin&utm_campaign=cyber_week_2026', $campaign['url'] );
		$boundaries = array(
			$this->start - 1 => false,
			$this->start     => true,
			$this->start + 1 => true,
			$this->end - 1   => true,
			$this->end       => false,
			$this->end + 1   => false,
		);
		foreach ( array( 'UTC', 'Pacific/Auckland', 'America/Los_Angeles' ) as $timezone ) {
			update_option( 'timezone_string', $timezone );
			foreach ( $boundaries as $instant => $expected ) {
				$this->assertSame( $expected, relevanssi_black_friday_is_active( $instant ), $timezone . ': ' . $instant );
			}
		}
	}

	/** The active campaign is initially expanded with the agreed offer. */
	public function test_campaign_offer() {
		$output = $this->sidebar( $this->start );
		if ( relevanssi_is_premium() ) {
			$this->assertSame( '', $output );
			$this->assertFalse( wp_script_is( 'relevanssi_upgrade_panels', 'enqueued' ) );
			$this->assertFalse( wp_script_is( 'relevanssi_cyber_week_offer', 'enqueued' ) );
			return;
		}
		$this->assertContains( 'data-relevanssi-upgrade-panel="black-friday-2026"', $output );
		$this->assertContains( ' open', $output );
		$this->assertContains( '<summary>Black Friday Week offer</summary>', $output );
		$this->assertContains( '30% OFF', wp_strip_all_tags( $output ) );
		$this->assertContains( '(taxes may apply at checkout)', wp_strip_all_tags( $output ) );
		$this->assertContains( 'BLACKFRIDAY30', $output );
		$this->assertContains( 'Offer ends December 1, 2026', $output );
		$this->assertContains( 'href="' . esc_url( 'https://www.relevanssi.com/buy-premium?utm_source=relevanssi_free&utm_medium=plugin_admin&utm_campaign=cyber_week_2026' ) . '"', $output );
		$this->assertNotContains( 'FREE2026', $output );
		$this->assertNotContains( 'notice-dismiss', $output );
		$this->assertTrue( wp_script_is( 'relevanssi_upgrade_panels', 'enqueued' ) );
		$this->assertContains( 'relevanssi-campaign-offer', $output );
		$this->assertContains( 'class="rv-cw-discount">30% OFF', $output );
		$this->assertContains( '<span class="rv-cw-now">€70</span>', $output );
		$this->assertContains( '<span class="rv-cw-code">BLACKFRIDAY30</span>', $output );
		$this->assertContains( 'data-rv-copy data-coupon="BLACKFRIDAY30"', $output );
		$this->assertTrue( wp_script_is( 'relevanssi_cyber_week_offer', 'enqueued' ) );
	}

	/** Saving keeps the browser's host and port when WordPress has a canonical URL. */
	public function test_panel_save_url_uses_current_origin() {
		$this->require_free();
		ob_start();
		relevanssi_upgrade_panel_attributes( 'black-friday-2026' );
		$output = ob_get_clean();
		$this->assertContains( 'data-state-url="' . esc_url( admin_url( 'admin-ajax.php', 'relative' ) ) . '"', $output );
	}

	/** Prices must survive the HTML template without mojibake or literal tab entities. */
	public function test_campaign_card_encoding() {
		ob_start();
		relevanssi_render_black_friday_offer( relevanssi_black_friday_campaign() );
		$output = ob_get_clean();
		$this->assertContains( '>€70</span>', $output );
		$this->assertContains( '>€100</span>', $output );
		$this->assertNotContains( 'â', $output );
		$this->assertNotContains( '&#x9;', $output );
	}

	/** Every settings tab shares the campaign without duplicating Overview. */
	public function test_settings_tabs_show_campaign_only_when_eligible() {
		$tabs   = array( 'overview', 'indexing', 'attachments', 'searching', 'display-ui', 'admin-dev', 'debugging', 'help', 'custom' );
		$filter = function () use ( $tabs ) {
			return array_map(
				function ( $slug ) {
					return array(
						'slug'     => $slug,
						'name'     => $slug,
						'require'  => false,
						'callback' => '__return_null',
						'save'     => false,
					);
				},
				$tabs
			);
		};
		add_filter( 'relevanssi_tabs', $filter );
		if ( relevanssi_is_premium() ) {
			delete_option( 'relevanssi_api_key' );
			delete_site_option( 'relevanssi_api_key' );
		}
		foreach ( $tabs as $tab ) {
			$_REQUEST['rlv_tab'] = $tab;
			ob_start();
			relevanssi_options_form( $this->start );
			$output = ob_get_clean();
			$this->assertSame( relevanssi_is_premium() || 'overview' === $tab ? 0 : 1, substr_count( $output, 'data-relevanssi-upgrade-panel="black-friday-2026"' ), $tab );
			if ( ! relevanssi_is_premium() && 'overview' !== $tab ) {
				$dom = new DOMDocument();
				@$dom->loadHTML( $output ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Inspecting an HTML fragment.
				$xpath = new DOMXPath( $dom );
				$this->assertSame( 1, $xpath->query( '//div[contains(@class,"relevanssi-settings-campaign")]/details[@id="relevanssi-upgrade-offer"]' )->length, $tab );
			}
		}
		$_REQUEST['rlv_tab'] = 'searching';
		ob_start();
		relevanssi_options_form( $this->end );
		$this->assertNotContains( 'data-relevanssi-upgrade-panel="', ob_get_clean() );
		if ( relevanssi_is_premium() ) {
			update_option( 'relevanssi_api_key', 'active-campaign-test-key' );
			ob_start();
			relevanssi_options_form( $this->start );
			$this->assertNotContains( 'data-relevanssi-upgrade-panel="', ob_get_clean() );
			relevanssi_premium_record_campaign_license_result(
				'active-campaign-test-key',
				array(
					'body'     => 'false',
					'response' => array( 'code' => 200 ),
				)
			);
			ob_start();
			relevanssi_options_form( $this->start );
			$this->assertNotContains( 'data-relevanssi-upgrade-panel="black-friday-2026"', ob_get_clean() );
		}
		remove_filter( 'relevanssi_tabs', $filter );
		unset( $_REQUEST['rlv_tab'] );
	}

	/** Campaign emphasis never permits coupon markup to become executable HTML. */
	public function test_campaign_copy_escapes_coupon_markup() {
		$campaign           = relevanssi_black_friday_campaign();
		$campaign['coupon'] = '<img src=x onerror=alert(1)>';
		ob_start();
		relevanssi_render_black_friday_offer( $campaign );
		$output = ob_get_clean();
		$this->assertContains( '&lt;img src=x onerror=alert(1)&gt;', $output );
		$this->assertNotContains( '<img', $output );
		$this->assertContains( 'class="rv-cw-discount">30% OFF', $output );
		$this->assertContains( 'taxes may apply at checkout', $output );
	}

	/** The regular offer can be collapsed separately from the campaign. */
	public function test_regular_offer_before_and_after_campaign() {
		$this->require_free();
		update_user_meta( get_current_user_id(), 'relevanssi_black_friday_2026_dismissed', '1' );
		foreach ( array( $this->start - 1, $this->end ) as $instant ) {
			$output = $this->sidebar( $instant );
			$this->assertContains( 'FREE2026', $output );
			$this->assertNotContains( 'BLACKFRIDAY30', $output );
			$this->assertContains( 'data-relevanssi-upgrade-panel="offer"', $output );
			$this->assertContains( ' open', $output );
		}
		$this->assertTrue( $this->save_panel( 'offer', 'collapsed' )['success'] );
		$this->assertNotContains( ' open', $this->sidebar( $this->end ) );
	}

	/** Closing the regular offer must never suppress the new campaign. */
	public function test_campaign_opens_when_regular_offer_was_collapsed() {
		$this->require_free();
		$this->assertTrue( $this->save_panel( 'offer', 'collapsed' )['success'] );
		$this->assertTrue( $this->save_panel( 'features', 'collapsed' )['success'] );
		$this->assertNotContains( ' open', $this->sidebar( $this->start - 1 ) );

		foreach ( array( $this->start, $this->start + 1 ) as $instant ) {
			$output = $this->sidebar( $instant );
			$this->assertContains( 'data-relevanssi-upgrade-panel="black-friday-2026"', $output );
			$this->assertContains( ' open', $output );
			$this->assertContains( 'BLACKFRIDAY30', $output );
			$this->assertNotContains( 'FREE2026', $output );
		}
		$this->assertTrue( relevanssi_upgrade_panel_is_collapsed( 'features' ) );

		$this->assertTrue( $this->save_panel( 'black-friday-2026', 'collapsed' )['success'] );
		$this->assertNotContains( ' open', $this->sidebar( $this->start + 2 ) );
		$output = $this->sidebar( $this->end );
		$this->assertContains( 'FREE2026', $output );
		$this->assertNotContains( ' open', $output );
	}

	/** Previous campaign dismissals remain collapsed and can be reopened. */
	public function test_previous_dismissal_is_reversible() {
		$this->require_free();
		update_user_meta( get_current_user_id(), 'relevanssi_black_friday_2026_dismissed', '1' );
		$output = $this->sidebar( $this->start );
		$this->assertContains( '<summary>Black Friday Week offer</summary>', $output );
		$this->assertNotContains( ' open', $output );
		$this->assertTrue( $this->save_panel( 'black-friday-2026', 'expanded' )['success'] );
		$this->assertContains( ' open', $this->sidebar( $this->start ) );
	}

	/** Panel preferences are independent, persistent, and per user. */
	public function test_independent_panel_preferences() {
		$this->require_free();
		$user = get_current_user_id();
		$this->assertTrue( $this->save_panel( 'features', 'collapsed' )['success'] );
		$this->assertTrue( $this->save_panel( 'features', 'collapsed' )['success'] );
		$this->assertTrue( relevanssi_upgrade_panel_is_collapsed( 'features' ) );
		$this->assertFalse( relevanssi_upgrade_panel_is_collapsed( 'black-friday-2026' ) );
		$this->assertTrue( $this->save_panel( 'black-friday-2026', 'collapsed' )['success'] );
		$this->assertTrue( $this->save_panel( 'features', 'expanded' )['success'] );
		$this->assertTrue( relevanssi_upgrade_panel_is_collapsed( 'black-friday-2026' ) );
		$this->assertFalse( relevanssi_upgrade_panel_is_collapsed( 'features' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertFalse( relevanssi_upgrade_panel_is_collapsed( 'black-friday-2026' ) );
		wp_set_current_user( $user );
		$this->assertTrue( relevanssi_upgrade_panel_is_collapsed( 'black-friday-2026' ) );
	}

	/** The red edition badge belongs to the always-visible features summary. */
	public function test_features_summary_retains_edition_badge() {
		$this->require_free();
		global $relevanssi_variables;
		require_once dirname( $relevanssi_variables['file'] ) . '/lib/tabs/overview-tab.php';
		$this->save_panel( 'features', 'collapsed' );
		ob_start();
		relevanssi_overview_tab();
		$output = ob_get_clean();
		$dom    = new DOMDocument();
		@$dom->loadHTML( $output ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Inspecting an HTML fragment with HTML5 elements.
		$xpath  = new DOMXPath( $dom );
		$panels = $xpath->query( '//details[@id="card-license-upsell"]' );
		$this->assertSame( 1, $panels->length );
		$this->assertFalse( $panels->item( 0 )->hasAttribute( 'open' ) );
		$summary = $xpath->evaluate( 'string(//details[@id="card-license-upsell"]/summary)' );
		$this->assertContains( 'Unlock Professional Search Power', $summary );
		$this->assertContains( 'Using Standard Edition', $summary );
		$this->assertSame( 1, $xpath->query( '//details[@id="card-license-upsell"]/summary/span[contains(@style,"#d63638")]' )->length );
		$this->assertSame( 1, $xpath->query( '//details[@id="relevanssi-upgrade-offer"][@open]' )->length );
	}

	/** Height matching is available only during the campaign, never for the regular offer. */
	public function test_overview_matches_heights_only_during_campaign() {
		$this->require_free();
		global $relevanssi_variables;
		require_once dirname( $relevanssi_variables['file'] ) . '/lib/tabs/overview-tab.php';
		$campaign = relevanssi_black_friday_campaign();
		$timezone = new DateTimeZone( $campaign['timezone'] );
		$start    = ( new DateTimeImmutable( $campaign['start'], $timezone ) )->getTimestamp();
		$end      = ( new DateTimeImmutable( $campaign['end'], $timezone ) )->getTimestamp();
		foreach ( array(
			$start - 1 => 0,
			$start     => 1,
			$end       => 0,
		) as $instant => $expected ) {
			ob_start();
			relevanssi_overview_tab( $instant );
			$dom = new DOMDocument();
			@$dom->loadHTML( ob_get_clean() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Inspecting an HTML fragment.
			$xpath = new DOMXPath( $dom );
			$this->assertSame( $expected, $xpath->query( '//div[contains(@class,"relevanssi-campaign-row")]' )->length );
		}
	}

	/** Free users retain the same documentation and review links in Overview. */
	public function test_free_overview_documentation_and_review() {
		$this->require_free();
		global $relevanssi_variables;
		require_once dirname( $relevanssi_variables['file'] ) . '/lib/tabs/overview-tab.php';
		ob_start();
		relevanssi_overview_tab();
		$output = ob_get_clean();
		$this->assertContains( 'Documentation Links', $output );
		$this->assertContains( 'https://www.relevanssi.com/knowledge-base/', $output );
		$this->assertContains( 'https://www.relevanssi.com/user-manual/', $output );
		$this->assertContains( 'Leave a review', $output );
		$this->assertContains( 'positive experience with Relevanssi,', $output );
		$this->assertNotContains( 'positive experience with Relevanssi Premium,', $output );
		$this->assertNotContains( 'Otherwise send us a support message', $output );
		$this->assertNotContains( 'Premium Help Desk', $output );
	}

	/** Resource cards share grid rows instead of two independently sized columns. */
	public function test_overview_resource_cards_share_grid() {
		global $relevanssi_variables;
		require_once dirname( $relevanssi_variables['file'] ) . '/lib/tabs/overview-tab.php';
		ob_start();
		relevanssi_overview_tab();
		$dom = new DOMDocument();
		@$dom->loadHTML( ob_get_clean() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Inspecting an HTML fragment.
		$xpath = new DOMXPath( $dom );
		foreach ( array( 'card-live-search-addon', 'card-documentation-links', 'card-privacy-compliance', 'card-review' ) as $id ) {
			$this->assertSame( 1, $xpath->query( '//div[@class="relevanssi-overview-resources"]/*[@id="' . $id . '"]' )->length );
		}
	}

	/** Invalid requests cannot change any preferences. */
	public function test_invalid_requests() {
		$_REQUEST['security'] = wp_create_nonce( 'relevanssi_upgrade_panel_state' );
		foreach ( array( 'invalid', 'array_panel', 'array_state', 'unknown_panel', 'invalid_state' ) as $scenario ) {
			$_REQUEST['security'] = 'invalid' === $scenario ? 'bad' : wp_create_nonce( 'relevanssi_upgrade_panel_state' );
			$_POST['panel']       = 'features';
			$_POST['state']       = 'collapsed';
			if ( 'array_panel' === $scenario ) {
				$_POST['panel'] = array( 'features' );
			} elseif ( 'array_state' === $scenario ) {
				$_POST['state'] = array( 'collapsed' );
			} elseif ( 'unknown_panel' === $scenario ) {
				$_POST['panel'] = 'unrelated_preference';
			} elseif ( 'invalid_state' === $scenario ) {
				$_POST['state'] = 'yes';
			}
			$this->assertFalse( $this->dispatch()['success'] );
		}
		$this->assertSame( '', get_user_meta( get_current_user_id(), 'relevanssi_upgrade_features_collapsed', true ) );
		$this->assertFalse( has_action( 'wp_ajax_nopriv_relevanssi_save_upgrade_panel_state' ) );
	}

	/** Edition and administrative permissions are enforced server-side. */
	public function test_ineligible_users() {
		if ( relevanssi_is_premium() ) {
			$this->assertFalse( $this->save_panel( 'features', 'collapsed' )['success'] );
		}
		foreach ( array( 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
			$this->assertSame( '', $this->sidebar( $this->start ) );
			$this->assertFalse( $this->save_panel( 'features', 'collapsed' )['success'] );
		}
		wp_set_current_user( 0 );
		$this->assertFalse( $this->save_panel( 'features', 'collapsed' )['success'] );
		set_current_screen( 'front' );
		$this->assertSame( '', $this->sidebar( $this->start ) );
	}

	/** A failed metadata update cannot report a saved preference. */
	public function test_failed_persistence() {
		$this->require_free();
		add_filter( 'update_user_metadata', '__return_false' );
		try {
			$this->assertFalse( $this->save_panel( 'features', 'collapsed' )['success'] );
			$this->assertFalse( relevanssi_upgrade_panel_is_collapsed( 'features' ) );
		} finally {
			remove_filter( 'update_user_metadata', '__return_false' );
		}
		$this->assertTrue( $this->save_panel( 'features', 'collapsed' )['success'] );
		add_filter( 'update_user_metadata', '__return_false' );
		try {
			$this->assertFalse( $this->save_panel( 'features', 'expanded' )['success'] );
			$this->assertTrue( relevanssi_upgrade_panel_is_collapsed( 'features' ) );
		} finally {
			remove_filter( 'update_user_metadata', '__return_false' );
		}
	}

	/** The client cannot choose another user's metadata. */
	public function test_client_cannot_choose_metadata_target() {
		$this->require_free();
		$other_user        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$_POST['user_id']  = $other_user;
		$_POST['meta_key'] = 'unrelated_preference';
		$this->assertTrue( $this->save_panel( 'features', 'collapsed' )['success'] );
		$this->assertSame( '', get_user_meta( $other_user, 'relevanssi_upgrade_features_collapsed', true ) );
		$this->assertSame( '', get_user_meta( get_current_user_id(), 'unrelated_preference', true ) );
	}

	/** Neither rendering nor state persistence initiates an external request. */
	public function test_no_external_requests() {
		$requests = 0;
		$record   = static function () use ( &$requests ) {
			++$requests;
			return new WP_Error( 'unexpected_http_request' );
		};
		add_filter( 'pre_http_request', $record );
		try {
			$this->sidebar( $this->start );
			$this->save_panel( 'features', 'collapsed' );
			$this->assertSame( 0, $requests );
		} finally {
			remove_filter( 'pre_http_request', $record );
		}
	}

	/** Other admin screens do not receive upgrade panels or their assets. */
	public function test_other_admin_pages_have_no_panels() {
		foreach ( array( 'dashboard', 'edit-post', 'relevanssi_page_relevanssi_admin_search' ) as $screen ) {
			set_current_screen( $screen );
			ob_start();
			do_action( 'admin_notices' );
			$output = ob_get_clean();
			$this->assertNotContains( 'data-relevanssi-upgrade-panel', $output );
			$this->assertFalse( wp_script_is( 'relevanssi_upgrade_panels', 'enqueued' ) );
		}
	}

	/** Shared user metadata keeps the preference on other network sites. */
	public function test_multisite_preference() {
		$this->require_free();
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}
		$this->assertTrue( $this->save_panel( 'black-friday-2026', 'collapsed' )['success'] );
		$blog = self::factory()->blog->create();
		add_user_to_blog( $blog, get_current_user_id(), 'administrator' );
		switch_to_blog( $blog );
		try {
			$this->assertTrue( current_user_can( 'manage_options' ) );
			$this->assertTrue( relevanssi_upgrade_panel_is_collapsed( 'black-friday-2026' ) );
			$this->assertNotContains( ' open', $this->sidebar( $this->start ) );
		} finally {
			restore_current_blog();
		}
	}
}
