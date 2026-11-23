<?php
/**
 * An image's focal point, ready to use in templates.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

// The camelCase property names are the public API themes already use with
// hirasso/focal-point-picker, so they stay as they are.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * The focal point of one image attachment.
 *
 * `left` and `top` (also available as `x` and `y`) run from 0 to 1; the
 * `*Percent` variants run from 0 to 100 for CSS such as
 * `object-position: {leftPercent}% {topPercent}%`. Images without a stored
 * focal point get the default position.
 */
final class Herd_FPP_Focal_Point {

	/**
	 * Distance from the left edge, from 0 to 1.
	 *
	 * @var float
	 */
	public float $left;

	/**
	 * Distance from the top edge, from 0 to 1.
	 *
	 * @var float
	 */
	public float $top;

	/**
	 * Distance from the left edge, from 0 to 100.
	 *
	 * @var float
	 */
	public float $leftPercent;

	/**
	 * Distance from the top edge, from 0 to 100.
	 *
	 * @var float
	 */
	public float $topPercent;

	/**
	 * Same as $left.
	 *
	 * @var float
	 */
	public float $x;

	/**
	 * Same as $top.
	 *
	 * @var float
	 */
	public float $y;

	/**
	 * Same as $leftPercent.
	 *
	 * @var float
	 */
	public float $xPercent;

	/**
	 * Same as $topPercent.
	 *
	 * @var float
	 */
	public float $yPercent;

	/**
	 * Read the focal point of an image.
	 *
	 * @param WP_Post|int $post Image attachment or its ID.
	 * @throws InvalidArgumentException When $post is not an image attachment.
	 */
	public function __construct( WP_Post|int $post ) {
		$post = get_post( $post );

		if ( ! $post || ! wp_attachment_is_image( $post ) ) {
			throw new InvalidArgumentException( '$post is not an image' );
		}

		$stored   = get_post_meta( $post->ID, HERD_FPP_META_KEY, true );
		$stored   = is_array( $stored ) ? $stored : array();
		$default  = herd_fpp_default_position();
		$position = new Herd_FPP_Position(
			$stored['left'] ?? $default->left,
			$stored['top'] ?? $default->top
		);

		$this->left        = $position->left;
		$this->top         = $position->top;
		$this->leftPercent = round( $position->left * 100, 2 );
		$this->topPercent  = round( $position->top * 100, 2 );
		$this->x           = $this->left;
		$this->y           = $this->top;
		$this->xPercent    = $this->leftPercent;
		$this->yPercent    = $this->topPercent;
	}

	/**
	 * Whether this focal point is the default position.
	 */
	public function isDefaultPosition(): bool { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Public API carried over from hirasso/focal-point-picker.
		$default = herd_fpp_default_position();

		return $this->left === $default->left && $this->top === $default->top;
	}
}
