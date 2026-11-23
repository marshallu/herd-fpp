<?php
/**
 * Legacy names existing themes use, kept so they keep working unchanged.
 *
 * Loaded only when no other plugin defining these names is active, so nothing
 * here can collide.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'fcp_get_focalpoint' ) ) {
	/**
	 * Get the focal point of an image. Same as herd_fpp_get_focal_point().
	 *
	 * @param WP_Post|int $post Image attachment or its ID.
	 * @throws InvalidArgumentException When $post is not an image attachment.
	 */
	function fcp_get_focalpoint( WP_Post|int $post ): Herd_FPP_Focal_Point { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy function name kept for existing themes.
		return herd_fpp_get_focal_point( $post );
	}
}
