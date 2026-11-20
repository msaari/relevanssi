<?php
/**
 * Locally configured Black Friday Week offer for new and lapsed customers.
 *
 * @package Relevanssi
 */

/**
 * Returns the release's campaign configuration.
 *
 * @return array Campaign identity, schedule, coupon and destination.
 */
function relevanssi_black_friday_campaign(): array {
	return array(
		'start'                => '2026-11-23 00:00:00',
		'end'                  => '2026-12-02 00:00:00',
		'timezone'             => 'Europe/Vienna',
		'coupon'               => 'BLACKFRIDAY30',
		'url'                  => 'https://www.relevanssi.com/buy-premium?utm_source=relevanssi_free&utm_medium=plugin_admin&utm_campaign=cyber_week_2026',
		'notice_dismissed_key' => 'relevanssi_black_friday_2026_notice_dismissed',
	);
}

/**
 * Checks the campaign window against an actual instant, not the site's clock.
 *
 * @param int|null $instant Unix timestamp; defaults to the current instant.
 * @return bool Whether the campaign is active.
 */
function relevanssi_black_friday_is_active( ?int $instant = null ): bool {
	$campaign = relevanssi_black_friday_campaign();
	$timezone = new DateTimeZone( $campaign['timezone'] );
	$start    = new DateTimeImmutable( $campaign['start'], $timezone );
	$end      = new DateTimeImmutable( $campaign['end'], $timezone );
	$instant  = $instant ?? time();
	return $instant >= $start->getTimestamp() && $instant < $end->getTimestamp();
}

/**
 * Checks customer eligibility without making a remote licence request.
 *
 * @return bool Whether the current edition and licence qualify.
 */
function relevanssi_black_friday_customer_eligible(): bool {
	if ( ! relevanssi_is_premium() ) {
		return true;
	}
	return function_exists( 'relevanssi_premium_campaign_license_status' ) && in_array( relevanssi_premium_campaign_license_status(), array( 'missing', 'invalid' ), true );
}

/**
 * Checks whether Overview may render an offer for this administrator.
 *
 * @param int|null $instant Unix timestamp; defaults to the current instant.
 * @return bool Whether an offer is available.
 */
function relevanssi_upgrade_offer_is_visible( ?int $instant = null ): bool {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return ! relevanssi_is_premium() || ( relevanssi_black_friday_is_active( $instant ) && relevanssi_black_friday_customer_eligible() );
}

/**
 * Prints the independently collapsible Overview offer sidebar.
 *
 * @param int|null $instant Unix timestamp; defaults to the current instant.
 */
function relevanssi_render_upgrade_sidebar( ?int $instant = null ) {
	if ( ! relevanssi_upgrade_offer_is_visible( $instant ) ) {
		return;
	}
	if ( ! relevanssi_is_premium() && relevanssi_black_friday_is_active( $instant ) ) {
		global $relevanssi_variables;
		wp_enqueue_script( 'relevanssi_campaign_layout', plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/campaign-layout.js', array(), $relevanssi_variables['plugin_version'] . '-' . substr( hash_file( 'sha256', __DIR__ . '/campaign-layout.js' ), 0, 12 ), true );
	}
	?>
	<aside class="relevanssi-settings-sidebar">
		<?php relevanssi_render_upgrade_offer_panel( $instant ); ?>
	</aside>
	<?php
}

/**
 * Prints offer content within an existing Overview layout.
 *
 * @param int|null $instant Unix timestamp; defaults to the current instant.
 */
function relevanssi_render_upgrade_offer_panel( ?int $instant = null ) {
	if ( ! relevanssi_upgrade_offer_is_visible( $instant ) ) {
		return;
	}
	$campaign = relevanssi_black_friday_campaign();
	$active   = relevanssi_black_friday_is_active( $instant );
	$panel    = $active ? 'black-friday-2026' : 'offer';
	$premium  = relevanssi_is_premium();
	$heading  = $premium ? __( 'Forgot to renew your license? Your BLACK FRIDAY WEEK offer', 'relevanssi' ) : __( 'Black Friday Week offer', 'relevanssi' );
	$classes  = $active ? 'relevanssi-campaign-offer' : ( $premium ? 'relevanssi-license-offer' : 'relevanssi-info-box' );
	?>
		<details id="relevanssi-upgrade-offer" class="relevanssi-upgrade-panel <?php echo esc_attr( $classes ); ?>"
		<?php
		if ( ! $premium ) {
			relevanssi_upgrade_panel_attributes( $panel ); }
		?>
		>
			<summary><?php echo $active ? esc_html( $heading ) : esc_html__( 'Premium offer', 'relevanssi' ); ?></summary>
		<?php if ( $active ) : ?>
			<?php relevanssi_render_black_friday_offer( $campaign ); ?>
		<?php else : ?>
			<div>
				<h3><?php esc_html_e( 'Get 20% Off Today', 'relevanssi' ); ?></h3>
				<p style="font-size: 13px; line-height: 1.5; margin-bottom: 16px; color: #50575e;">
					<?php
					/* translators: %1$s is the coupon code bolded, %2$s is the year. */
					printf( esc_html__( 'Use coupon code %1$s at checkout for a 20%% initialization discount. Valid through the end of %2$s.', 'relevanssi' ), '<strong>FREE2026</strong>', '2026' );
					?>
				</p>
				<a href="https://www.relevanssi.com/buy-premium/" target="_blank" rel="noopener noreferrer" class="button button-primary" style="text-align: center; display: block; width: 100%; box-sizing: border-box;">
					<?php esc_html_e( 'Upgrade License Now', 'relevanssi' ); ?>
				</a>
			</div>
		<?php endif; ?>
		</details>
	<?php
}

/**
 * Prints the campaign's offer content.
 *
 * @param array $campaign Campaign configuration.
 */
function relevanssi_render_black_friday_offer( array $campaign ) {
	global $relevanssi_variables;

	wp_enqueue_script( 'relevanssi_cyber_week_offer', plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/cyber-week-offer.js', array(), $relevanssi_variables['plugin_version'], true );
	?>
	<section id="relevanssi-black-friday" class="rv-cw-card" aria-label="<?php esc_attr_e( 'Relevanssi Premium Black Friday Week offer', 'relevanssi' ); ?>">
		<svg class="rv-cw-brandmark" viewBox="0 0 128.67 174.57" aria-hidden="true" focusable="false">
			<path d="M16.18 95.93c-16.5,14.06 -23.03,75.12 3.62,70 -0.36,-9.97 -0.09,-21.12 3.65,-25.13 2.02,8.06 2.77,22.09 7.31,30.43 11.8,7.3 64.86,2.31 67.78,-11.5 -5.74,-5.59 -13.41,-36.55 -8.19,-42.77 14.98,-17.86 31.4,-38.3 15.57,-50.7 -2.77,-2.17 -13.29,-8 -14.28,-1.78 -5.89,37 -40.06,47.55 -75.45,31.46z"/>
			<path d="M49.82 29.82c6.83,1.27 12.33,6.85 13.17,14.12 1.02,8.91 -5.37,16.96 -14.28,17.98 -8.91,1.02 -16.96,-5.37 -17.98,-14.28 -0.31,-2.73 0.07,-5.39 1.01,-7.78 7.53,3.53 17.76,-2.27 18.08,-10.04z"/>
			<path d="M14.86 12.22c9.35,-8.63 21.35,-12.66 33.18,-12.19 11.82,0.47 23.45,5.45 32.11,14.83 10.91,11.82 15.23,31.44 7.93,47.69l35.29 19.72c10.98,5.91 3.36,20.65 -8.82,14.04l-34.35 -18.85c-0.85,0.92 -1.75,1.82 -2.69,2.69l-0.87 0.8 -0.06 0.03c-9.21,8.06 -20.81,11.81 -32.25,11.35 -11.82,-0.47 -23.45,-5.45 -32.11,-14.83l-0.8 -0.87 -0.03 -0.06c-8.06,-9.21 -11.81,-20.81 -11.35,-32.26 0.47,-11.82 5.45,-23.45 14.83,-32.11zm53.65 8.59c-6.41,-5.95 -15.04,-9.45 -24.38,-9.09 -9.28,0.36 -17.48,4.51 -23.23,10.94 -5.61,6.29 -8.88,14.79 -8.51,24.07 -1.89,12.48 -1.2,14.01 8.13,21.15 0.96,1.15 2,2.24 3.09,3.26 6.41,5.95 15.04,9.45 24.38,9.1l0.02 0c9.28,-0.36 17.47,-4.52 23.21,-10.94 5.73,-6.41 9.01,-15.13 8.49,-24.63l-0.01 -0.18c1.45,-12.02 0.13,-14.89 -8.65,-21.07 -0.8,-0.91 -1.64,-1.78 -2.53,-2.6z"/>
		</svg>
		<p class="rv-cw-eyebrow"><?php esc_html_e( 'Relevanssi Premium', 'relevanssi' ); ?></p>
		<p class="rv-cw-kicker"><?php esc_html_e( 'YOUR BLACK FRIDAY WEEK OFFER', 'relevanssi' ); ?></p>
		<h3 class="rv-cw-discount"><?php esc_html_e( '30% OFF', 'relevanssi' ); ?></h3>
		<p class="rv-cw-product"><?php esc_html_e( 'Premium Annual', 'relevanssi' ); ?></p>
		<div class="rv-cw-price"><span class="rv-cw-now">€70</span><span class="rv-cw-before">€100</span><span class="rv-cw-tax"><?php esc_html_e( '(taxes may apply at checkout)', 'relevanssi' ); ?></span></div>
		<p class="rv-cw-first">
		<?php
		if ( relevanssi_is_premium() ) {
			esc_html_e( 'Enjoy your exclusive Black Friday Week price for your renewal for the first year on our annual plan.', 'relevanssi' );
		} else {
			esc_html_e( 'Enjoy the Black Friday Week price for your first year as a Relevanssi Premium member.', 'relevanssi' );
		}
		?>
		</p>
		<div class="rv-cw-coupon-area">
			<div><p class="rv-cw-label"><?php esc_html_e( 'USE COUPON CODE', 'relevanssi' ); ?></p><span class="rv-cw-code"><?php echo esc_html( $campaign['coupon'] ); ?></span></div>
			<button class="rv-cw-copy" type="button" data-rv-copy data-coupon="<?php echo esc_attr( $campaign['coupon'] ); ?>"
				data-copied="<?php esc_attr_e( 'Copied!', 'relevanssi' ); ?>"
				data-copy-error="<?php esc_attr_e( 'Select code', 'relevanssi' ); ?>"
				aria-label="<?php esc_attr_e( 'Copy coupon code', 'relevanssi' ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/>
					<path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>
				</svg>
				<span data-rv-copy-label role="status" aria-live="polite"><?php esc_html_e( 'Copy', 'relevanssi' ); ?></span>
			</button>
		</div>
		<a class="rv-cw-cta" href="<?php echo esc_url( $campaign['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Relevanssi Premium', 'relevanssi' ); ?>
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M5 12h14m-6-6 6 6-6 6"/>
			</svg>
		</a>
		<p class="rv-cw-footer"><?php esc_html_e( 'Offer ends December 1, 2026', 'relevanssi' ); ?></p>
	</section>
	<?php
}

/**
 * Returns the shared campaign heading for both placements.
 *
 * @return string Escaped translated heading with discount emphasis.
 */
function relevanssi_black_friday_heading(): string {
	/* translators: %s is the bolded discount percentage. */
	return sprintf( esc_html__( 'Black Friday Week: Save %s on Annual', 'relevanssi' ), '<strong>30%</strong>' );
}

/**
 * Returns the shared Annual-only offer description.
 *
 * @param array $campaign Campaign configuration.
 * @return string Escaped translated description with price and coupon emphasis.
 */
function relevanssi_black_friday_description( array $campaign ): string {
	return sprintf(
		/* translators: %1$s is the bolded first-year price, %2$s is the bolded coupon code. */
		esc_html__( 'New or returning customer? Get your first year of Relevanssi Premium Annual for %1$s instead of €100, excluding tax. Use coupon code %2$s at checkout. Offer ends December 1, 2026.', 'relevanssi' ),
		'<strong class="relevanssi-campaign-price">€70</strong>',
		'<strong class="relevanssi-campaign-coupon">' . esc_html( $campaign['coupon'] ) . '</strong>'
	);
}

