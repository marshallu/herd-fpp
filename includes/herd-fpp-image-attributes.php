<?php
/**
 * Expose focal points on images rendered by wp_get_attachment_image().
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the `focal-point-image` class and the `--focal-point-left` and
 * `--focal-point-top` custom properties (0 to 1) to every image, so a theme can
 * crop with CSS alone:
 *
 *     .focal-point-image {
 *         object-fit: cover;
 *         object-position:
 *             calc(var(--focal-point-left, 0.5) * 100%)
 *             calc(var(--focal-point-top, 0.5) * 100%);
 *     }
 *
 * The class and property names match hirasso/focal-point-picker's, so theme
 * CSS written for it keeps working.
 *
 * @param array<string, mixed> $attr       Image attributes.
 * @param WP_Post              $attachment Image attachment.
 * @return array<string, mixed>
 */
function herd_fpp_image_attributes( $attr, $attachment ) {
	if ( ! wp_attachment_is_image( $attachment ) ) {
		return $attr;
	}

	$focal_point = herd_fpp_get_focal_point( $attachment );

	$classes = preg_split( '/\s+/', (string) ( $attr['class'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY );
	if ( ! in_array( 'focal-point-image', $classes, true ) ) {
		$classes[] = 'focal-point-image';
	}
	$attr['class'] = implode( ' ', $classes );

	$style         = rtrim( trim( (string) ( $attr['style'] ?? '' ) ), ';' );
	$attr['style'] = ltrim(
		sprintf(
			'%s; --focal-point-left: %s; --focal-point-top: %s;',
			$style,
			$focal_point->left,
			$focal_point->top
		),
		'; '
	);

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'herd_fpp_image_attributes', 10, 2 );
