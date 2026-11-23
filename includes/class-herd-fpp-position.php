<?php
/**
 * A point on an image, as fractions of its width and height.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Position from the left and top edges, each from 0 to 1.
 *
 * The constructor accepts anything a theme or the stored meta might hold:
 * numbers and numeric strings from 0 to 1, percentages above 1 (`25` means
 * 0.25), and anything else as the center. Values are clamped to the image and
 * rounded to two decimals, the precision the picker works in.
 *
 * Its constructor parameters are named `left` and `top` because themes written
 * for hirasso/focal-point-picker construct it with named arguments, such as
 * `new Position( top: 0 )`, through the class alias in herd-fpp-compat.php.
 */
final class Herd_FPP_Position {

	/**
	 * Distance from the left edge, from 0 to 1.
	 *
	 * @var float
	 */
	public readonly float $left;

	/**
	 * Distance from the top edge, from 0 to 1.
	 *
	 * @var float
	 */
	public readonly float $top;

	/**
	 * Create a position.
	 *
	 * @param mixed $left Distance from the left edge.
	 * @param mixed $top  Distance from the top edge.
	 */
	public function __construct( mixed $left = null, mixed $top = null ) {
		$this->left = self::sanitize( $left );
		$this->top  = self::sanitize( $top );
	}

	/**
	 * Normalize one coordinate to a fraction from 0 to 1.
	 *
	 * @param mixed $value Raw coordinate.
	 */
	private static function sanitize( mixed $value ): float {
		if ( ! is_numeric( $value ) ) {
			return 0.5;
		}

		$value = (float) $value;

		if ( $value > 1 ) {
			$value /= 100;
		}

		return round( max( 0, min( 1, $value ) ), 2 );
	}
}
