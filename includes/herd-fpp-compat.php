<?php
/**
 * Names kept from hirasso/focal-point-picker, so themes written for it keep
 * working after switching plugins.
 *
 * Loaded only when that plugin is not active, so nothing here can collide
 * with its own definitions.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

/*
 * The original plugin's class names, for code such as
 * `new Hirasso\FocalPointPicker\Position( top: 0 )` in a
 * `hirasso/fcp/default-position` filter, or a `FocalPoint` type declaration.
 * Aliased up front rather than from an autoloader, because type declarations
 * and `instanceof` never trigger autoloading.
 */
class_alias( Herd_FPP_Position::class, 'Hirasso\FocalPointPicker\Position' );
class_alias( Herd_FPP_Focal_Point::class, 'Hirasso\FocalPointPicker\FocalPoint' );

if ( ! function_exists( 'fcp_get_focalpoint' ) ) {
	/**
	 * Get the focal point of an image. Same as herd_fpp_get_focal_point().
	 *
	 * @param WP_Post|int $post Image attachment or its ID.
	 * @throws InvalidArgumentException When $post is not an image attachment.
	 */
	function fcp_get_focalpoint( WP_Post|int $post ): Herd_FPP_Focal_Point { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function name carried over from hirasso/focal-point-picker.
		return herd_fpp_get_focal_point( $post );
	}
}
