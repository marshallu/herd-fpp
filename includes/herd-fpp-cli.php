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
	$value   = array(
		'left' => $default->left,
		'top'  => $default->top,
	);

	if ( ! $all ) {
		$ids     = array_map( 'intval', $args );
		$invalid = array_filter( $ids, fn( $id ) => ! wp_attachment_is_image( $id ) );

		if ( $invalid ) {
			WP_CLI::error( 'Not image attachments: ' . implode( ', ', $invalid ) );
		}

		foreach ( $ids as $id ) {
			update_post_meta( $id, HERD_FPP_META_KEY, $value );
		}

		WP_CLI::success( sprintf( 'Set the focal point of %d image(s) to left %s, top %s.', count( $ids ), $default->left, $default->top ) );
		return;
	}

	$total = herd_fpp_cli_image_batch( 1 )->found_posts;

	if ( ! $total ) {
		WP_CLI::warning( 'No images found.' );
		return;
	}

	WP_CLI::confirm(
		sprintf( 'Set the focal point of all %d images to left %s, top %s?', $total, $default->left, $default->top ),
		$assoc_args
	);

	// Work through the library a page at a time, clearing the in-memory caches
	// between pages, so a large media library cannot exhaust memory. Updating
	// meta does not change which images match, so paging stays stable.
	$progress = WP_CLI\Utils\make_progress_bar( 'Updating images', $total );
	$updated  = 0;

	for ( $page = 1; ; $page++ ) {
		$ids = herd_fpp_cli_image_batch( $page )->posts;
		if ( ! $ids ) {
			break;
		}

		foreach ( $ids as $id ) {
			update_post_meta( (int) $id, HERD_FPP_META_KEY, $value );
			++$updated;
			$progress->tick();
		}

		if ( function_exists( 'vip_inmemory_cleanup' ) ) {
			vip_inmemory_cleanup();
		} else {
			wp_cache_flush_runtime();
		}
	}

	$progress->finish();

	WP_CLI::success( sprintf( 'Set the focal point of %d image(s) to left %s, top %s.', $updated, $default->left, $default->top ) );
}

/**
 * One page of image attachment IDs, oldest first.
 *
 * @param int $page Page number, from 1.
 */
function herd_fpp_cli_image_batch( int $page ): WP_Query {
	return new WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_mime_type'         => 'image',
			'post_status'            => 'inherit',
			'posts_per_page'         => 100,
			'paged'                  => $page,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'no_found_rows'          => $page > 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
}

WP_CLI::add_command( 'herd-fpp apply-default-position', 'herd_fpp_cli_apply_default_position' );

// The command name hirasso/focal-point-picker used, for existing scripts.
WP_CLI::add_command( 'fcp apply-default-position', 'herd_fpp_cli_apply_default_position' );
