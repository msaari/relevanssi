<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-media-upload.php
 *
 * Abstracted Reusable Native WP Media Picker frame attachment selector fields handler wrapper.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Media_Upload
 */
class Relevanssi_Setting_Field_Media_Upload extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Generates visual placeholder thumbnail canvases and modal system activation buttons triggers nodes layers.
	 *
	 * @return void Writes layout buffers directly out down onto standard form processing streams.
	 */
	protected function render_input() {
		$meta           = $this->config['meta'] ?? array();
		$thumbnail_id   = $meta['thumbnail_id'] ?? 0;
		$related_opts   = get_option( 'relevanssi_related_settings', array() );
		$disabled_state = ( 'off' === ( $related_opts['enabled'] ?? 'off' ) ) ? 'disabled="disabled"' : '';
		$preview_style  = empty( $thumbnail_id ) ? 'style="display: none;"' : '';

		printf(
			'<div class="image-preview-wrapper" %1$s style="margin-bottom: 12px; padding: 4px; border: 1px solid #c3c4c7; width: 102px; height: 102px; background: #fff;">
                <img id="image-preview" alt="Thumbnail image preview" src="%2$s" width="100" height="100" style="max-height: 100px; width: 100px; object-fit: cover;">
            </div>',
			$preview_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_attr( wp_get_attachment_url( $thumbnail_id ) )
		);

		printf(
			'<div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                <input id="upload_image_button" type="button" class="button" value="%1$s" %2$s />
                <input type="hidden" name="relevanssi_default_thumbnail" id="relevanssi_default_thumbnail" value="%3$s">
                <p class="description">%4$s</p>
                <label style="margin-top: 4px;">
                    <input class="relevanssi-toggle" type="checkbox" name="relevanssi_remove_default_thumbnail" %2$s />
                    %5$s
                </label>
            </div>',
			esc_attr( __( 'Select image', 'relevanssi' ) ),
			$disabled_state, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_attr( $thumbnail_id ),
			esc_html__( "If a post doesn't have a featured image, this image will be used instead.", 'relevanssi' ),
			esc_html__( 'Check this post to remove the default thumbnail.', 'relevanssi' )
		);
	}

	/**
	 * Saves changes targeting styles maps configurations vectors keys sequences arrays safely.
	 *
	 * @param array $request Incoming payload array values mapping parameters logs boundaries.
	 * @return bool True if modifications successfully commit down into option tables fields properties layers.
	 */
	public function save( array $request ): bool {
		$style_settings = get_option( 'relevanssi_related_style', array() );

		if ( isset( $request['relevanssi_remove_default_thumbnail'] ) ) {
			$style_settings['default_thumbnail'] = 0;
		} elseif ( isset( $request['relevanssi_default_thumbnail'] ) ) {
			$style_settings['default_thumbnail'] = intval( $request['relevanssi_default_thumbnail'] );
		}

		return update_option( 'relevanssi_related_style', $style_settings );
	}
}
