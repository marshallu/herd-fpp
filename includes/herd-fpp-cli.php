<?php
/**
 * WP-CLI commands.
 *
 * @package herd-fpp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set image focal points to the default position.
 *
 * Useful after changing the default with the `herd_fpp_default_position`
 * filter, since images that were already saved keep the point stored for them.
 *
 * ## OPTIONS
 *
 * [<attachment-id>...]
 * : One or more image attachment IDs.
 *
 * [--all]
 * : Apply the default position to every image.
 *
 * [--yes]
 * : Skip the confirmation for --all.
 *
 * ## EXAMPLES
 *
 *     wp herd-fpp apply-default-position 123 456
 *     wp herd-fpp apply-default-position --all --yes
 *
 * @param array<int, string>   $args       Attachment IDs.
 * @param array<string, mixed> $assoc_args Flags.
 */
function herd_fpp_cli_apply_default_position( $args, $assoc_args ) {
	$all = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );

	if ( $all === ! empty( $args ) ) {
		WP_CLI::error( 'Pass either attachment IDs or --all.' );
	}

	$default = herd_fpp_default_position();

	if ( $all ) {
		$ids = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_mime_type'   => 'image',
				'post_status'      => 'inherit',
				'posts_per_page'   => -1, // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- A CLI run over every image is the point.
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! $ids ) {
			WP_CLI::warning( 'No images found.' );
			return;
		}

		WP_CLI::confirm(
			sprintf( 'Set the focal point of all %d images to left %s, top %s?', count( $ids ), $default->left, $default->top ),
			$assoc_args
		);
	} else {
		$ids     = array_map( 'intval', $args );
		$invalid = array_filter( $ids, fn( $id ) => ! wp_attachment_is_image( $id ) );

		if ( $invalid ) {
			WP_CLI::error( 'Not image attachments: ' . implode( ', ', $invalid ) );
		}
	}

	foreach ( $ids as $id ) {
		update_post_meta(
			$id,
			HERD_FPP_META_KEY,
			array(
				'left' => $default->left,
				'top'  => $default->top,
			)
		);
	}

	WP_CLI::success( sprintf( 'Set the focal point of %d image(s) to left %s, top %s.', count( $ids ), $default->left, $default->top ) );
}

WP_CLI::add_command( 'herd-fpp apply-default-position', 'herd_fpp_cli_apply_default_position' );

// The command name hirasso/focal-point-picker used, for existing scripts.
WP_CLI::add_command( 'fcp apply-default-position', 'herd_fpp_cli_apply_default_position' );
