<?php
/**
 * The Focal Point field in the Media Library and on the Edit Media screen.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the picker in the admin, where any screen can open the media modal.
 */
function herd_fpp_enqueue_assets() {
	wp_enqueue_style( 'herd-fpp', HERD_FPP_URL . 'assets/herd-fpp.css', array(), HERD_FPP_VERSION );
	wp_enqueue_script( 'herd-fpp', HERD_FPP_URL . 'assets/herd-fpp.js', array(), HERD_FPP_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'herd_fpp_enqueue_assets' );

/**
 * Add the Focal Point field to images.
 *
 * The field is a read-only `left top` input inside a <herd-fpp-picker>
 * element; assets/herd-fpp.js finds the image preview next to it and draws the
 * draggable handle over it. WordPress saves the input with the rest of the
 * attachment fields: on change in the media modal, on Update on the Edit Media
 * screen.
 *
 * The field key `focalpoint-input` and input name `focalpoint` are legacy names
 * kept so existing admin CSS or scripts aimed at them still apply.
 *
 * @param array<string, array<string, mixed>> $fields Attachment fields.
 * @param WP_Post                             $post   Attachment.
 * @return array<string, array<string, mixed>>
 */
function herd_fpp_attachment_fields_to_edit( $fields, $post ) {
	if ( ! wp_attachment_is_image( $post ) ) {
		return $fields;
	}

	$focal_point = herd_fpp_get_focal_point( $post );
	$default     = herd_fpp_default_position();

	ob_start();
	?>
	<herd-fpp-picker data-default-left="<?php echo esc_attr( (string) $default->left ); ?>" data-default-top="<?php echo esc_attr( (string) $default->top ); ?>" data-handle-label="<?php esc_attr_e( 'Focal point. Drag, or use the arrow keys. Double-click to reset.', 'herd-fpp' ); ?>">
		<span class="herd-fpp-field">
			<input type="text" readonly
				class="herd-fpp-input"
				id="attachments-<?php echo esc_attr( (string) $post->ID ); ?>-focalpoint"
				name="attachments[<?php echo esc_attr( (string) $post->ID ); ?>][focalpoint]"
				value="<?php echo esc_attr( $focal_point->left . ' ' . $focal_point->top ); ?>"
			>
			<button type="button" class="button herd-fpp-reset" <?php disabled( $focal_point->isDefaultPosition() ); ?>><?php esc_html_e( 'Reset', 'herd-fpp' ); ?></button>
		</span>
		<span class="herd-fpp-help"><?php esc_html_e( 'Click or drag on the image to set the point to keep in view when it is cropped.', 'herd-fpp' ); ?></span>
	</herd-fpp-picker>
	<?php

	$fields['focalpoint-input'] = array(
		'label' => __( 'Focal Point', 'herd-fpp' ),
		'input' => 'html',
		'html'  => ob_get_clean(),
	);

	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'herd_fpp_attachment_fields_to_edit', 10, 2 );

/**
 * Save the Focal Point field.
 *
 * WordPress runs this filter from both places the field appears, after it has
 * checked the `update-post_{ID}` nonce and the user's edit_post capability, so
 * this only validates the value. Anything other than two numbers is ignored.
 *
 * @param array<string, mixed> $post            Attachment data about to be saved.
 * @param array<string, mixed> $attachment_data The submitted attachment fields.
 * @return array<string, mixed>
 */
function herd_fpp_attachment_fields_to_save( $post, $attachment_data ) {
	$id = (int) ( $post['ID'] ?? 0 );

	if ( ! isset( $attachment_data['focalpoint'] ) || ! wp_attachment_is_image( $id ) ) {
		return $post;
	}

	$values = preg_split( '/\s+/', trim( (string) $attachment_data['focalpoint'] ) );

	if ( count( $values ) !== 2 || ! is_numeric( $values[0] ) || ! is_numeric( $values[1] ) ) {
		return $post;
	}

	$position = new Herd_FPP_Position( $values[0], $values[1] );

	update_post_meta(
		$id,
		HERD_FPP_META_KEY,
		array(
			'left' => $position->left,
			'top'  => $position->top,
		)
	);

	return $post;
}
add_filter( 'attachment_fields_to_save', 'herd_fpp_attachment_fields_to_save', 10, 2 );
