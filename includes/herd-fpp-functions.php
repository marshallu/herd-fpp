<?php
/**
 * Public functions for themes and other plugins.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the focal point of an image.
 *
 * @param WP_Post|int $post Image attachment or its ID.
 * @throws InvalidArgumentException When $post is not an image attachment.
 */
function herd_fpp_get_focal_point( WP_Post|int $post ): Herd_FPP_Focal_Point {
	return new Herd_FPP_Focal_Point( $post );
}

/**
 * The focal point of images nobody has picked one for, and where Reset moves
 * the picker. The center unless a filter says otherwise.
 *
 * Themes can change it with the `herd_fpp_default_position` filter, which must
 * return a Herd_FPP_Position; anything else falls back to the center.
 */
function herd_fpp_default_position(): Herd_FPP_Position {
	$center = new Herd_FPP_Position( 0.5, 0.5 );

	$position = apply_filters( 'herd_fpp_default_position', $center );

	return $position instanceof Herd_FPP_Position ? $position : $center;
}
