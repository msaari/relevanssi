<?php
/**
 * /lib/settings/fields/class-relevanssi-upsell-field.php
 *
 * Handles rendering the unified marketing and upgrade callouts for Standard users.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Upsell_Field
 *
 * Renders a standardized upsell field
 */
class Relevanssi_Setting_Field_Upsell extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Renders the unified premium upselling callout block layout.
	 *
	 * Outputs a structured manifest of premium features if provided,
	 * along with the standardized tier routing actions.
	 *
	 * @return void
	 */
	protected function render_input() {
		$feature_name  = $this->config['feature_name'] ?? __( 'This feature', 'relevanssi' );
		$purchase_url  = $this->config['purchase_url'] ?? 'https://www.relevanssi.com/buy-premium/';
		$features_list = $this->config['features_list'] ?? array();

		?>
		<div class="relevanssi-upsell-callout" style="padding: 16px; background: #f0f6fa; border: 1px dashed #005885; border-radius: 4px; max-width: 600px; box-sizing: border-box;">
			<p style="margin: 0 0 12px 0; font-size: 13.5px; line-height: 1.4; color: #1d2327; font-weight: 600;">
				<?php
				printf(
					/* translators: %1$s is the feature suite name bolded, %2$s opens a strong tag, %3$s closes it. */
					esc_html__( 'The %1$s suite requires %2$sRelevanssi Premium%3$s.', 'relevanssi' ),
					'<strong>' . esc_html( $feature_name ) . '</strong>',
					'<strong>',
					'</strong>'
				);
				?>
			</p>

			<?php if ( ! empty( $features_list ) && is_array( $features_list ) ) : ?>
				<p style="margin: 0 0 8px 0; font-size: 12.5px; font-weight: 600; color: #2c3338;">
					<?php esc_html_e( 'What you unlock with Premium:', 'relevanssi' ); ?>
				</p>
				<ul style="margin: 0 0 16px 0; padding-left: 18px; list-style-type: disc; color: #50575e; font-size: 12.5px; line-height: 1.5;">
					<?php foreach ( $features_list as $feature_description ) : ?>
						<li style="margin-bottom: 4px;"><?php echo esc_html( $feature_description ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<p style="margin: 0; font-size: 12.5px;">
				<a href="<?php echo esc_url( $purchase_url ); ?>" 
					target="_blank" 
					rel="noopener noreferrer"
					style="color: #005885; text-decoration: none; font-weight: 600; border-bottom: 1px solid #005885; padding-bottom: 1px;">
					<?php esc_html_e( 'Upgrade to Premium and unlock these features →', 'relevanssi' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Suppresses configuration warnings for locked features.
	 */
	protected function render_notice() {
		// Do nothing.
	}

	/**
	 * Bypasses the option writing pipeline cleanly.
	 *
	 * @param array $request The raw request array payload.
	 * @return bool Always returns false since no database option entries are altered.
	 */
	public function save( array $request ): bool {
		return false;
	}
}