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
 * Themes can change it with the `herd_fpp_default_position` filter, or with
 * `hirasso/fcp/default-position`, which hirasso/focal-point-picker used. Either
 * filter must return a Herd_FPP_Position (Hirasso\FocalPointPicker\Position is
 * the same class); anything else falls back to the center.
 */
function herd_fpp_default_position(): Herd_FPP_Position {
	$center = new Herd_FPP_Position( 0.5, 0.5 );

	// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook name carried over from hirasso/focal-point-picker.
	$position = apply_filters( 'hirasso/fcp/default-position', $center );
	$position = apply_filters( 'herd_fpp_default_position', $position );

	return $position instanceof Herd_FPP_Position ? $position : $center;
}
