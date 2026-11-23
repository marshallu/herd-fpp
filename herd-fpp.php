<?php
/**
 * Plugin Name: Herd Focal Point Picker
 * Plugin URI: https://github.com/marshallu/herd-fpp
 * Description: Pick a focal point for each image in the Media Library. A drop-in replacement for hirasso/focal-point-picker that reads and writes the same data.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Marshall University
 * License: MIT
 * Text Domain: herd-fpp
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

define( 'HERD_FPP_VERSION', '1.0.0' );
define( 'HERD_FPP_URL', plugin_dir_url( __FILE__ ) );
define( 'HERD_FPP_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Post meta key holding an image's focal point as `[ 'left' => float, 'top' => float ]`.
 *
 * The key and shape are the ones hirasso/focal-point-picker uses, so images
 * picked with that plugin keep their focal points here and the other way round.
 */
define( 'HERD_FPP_META_KEY', 'focalpoint' );

require_once HERD_FPP_DIR . 'includes/class-herd-fpp-position.php';
require_once HERD_FPP_DIR . 'includes/class-herd-fpp-focal-point.php';
require_once HERD_FPP_DIR . 'includes/herd-fpp-functions.php';

/**
 * Boot once every plugin has loaded, so we can tell whether
 * hirasso/focal-point-picker is active too, whatever the load order.
 *
 * Both plugins edit the same field, so running them together would show two
 * pickers on every image. While the original is active this plugin stays out of
 * the way and asks for it to be deactivated.
 */
function herd_fpp_boot() {
	if ( defined( 'WPFP_PLUGIN_DIR' ) ) {
		add_action( 'admin_notices', 'herd_fpp_conflict_notice' );
		return;
	}

	require_once HERD_FPP_DIR . 'includes/herd-fpp-compat.php';
	require_once HERD_FPP_DIR . 'includes/herd-fpp-picker.php';
	require_once HERD_FPP_DIR . 'includes/herd-fpp-image-attributes.php';

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once HERD_FPP_DIR . 'includes/herd-fpp-cli.php';
	}
}
add_action( 'plugins_loaded', 'herd_fpp_boot' );

/**
 * Tell administrators why the picker is not running.
 */
function herd_fpp_conflict_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	wp_admin_notice(
		esc_html__( 'Herd Focal Point Picker is paused because the Focal Point Picker plugin is also active. Deactivate Focal Point Picker; your existing focal points carry over unchanged.', 'herd-fpp' ),
		array( 'type' => 'warning' )
	);
}
