<?php
/**
 * Expose focal points on rendered images.
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

	$attr['style'] = herd_fpp_style_with_focal_point( (string) ( $attr['style'] ?? '' ), $focal_point );

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'herd_fpp_image_attributes', 10, 2 );

/**
 * Add the `--focal-point-left` and `--focal-point-top` custom properties to
 * images in post content, such as Image blocks and Classic editor images,
 * which are saved as HTML and so never pass through wp_get_attachment_image().
 *
 * Only the custom properties are added, not the `focal-point-image` class:
 * theme CSS aimed at that class, such as `object-fit: cover` with an aspect
 * ratio, would otherwise start cropping inline images on existing pages.
 * Target content images in theme CSS instead, for example
 * `.wp-block-image img { object-position: calc(var(--focal-point-left, 0.5) * 100%) … }`.
 *
 * WordPress identifies the attachment from the image's `wp-image-{ID}` class
 * and primes the meta cache for every image in the content first, so this adds
 * no queries per image.
 *
 * @param string $image         The <img> tag.
 * @param string $context       Where the content is being filtered.
 * @param int    $attachment_id Attachment ID, or 0 when the image is not one.
 * @return string
 */
function herd_fpp_content_image( $image, $context, $attachment_id ) {
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return $image;
	}

	$tag = new WP_HTML_Tag_Processor( $image );
	if ( ! $tag->next_tag( array( 'tag_name' => 'img' ) ) ) {
		return $image;
	}

	$style = (string) $tag->get_attribute( 'style' );
	if ( str_contains( $style, '--focal-point-left' ) ) {
		return $image;
	}

	$tag->set_attribute( 'style', herd_fpp_style_with_focal_point( $style, herd_fpp_get_focal_point( $attachment_id ) ) );

	return $tag->get_updated_html();
}
add_filter( 'wp_content_img_tag', 'herd_fpp_content_image', 10, 3 );

/**
 * Append an image's focal point custom properties to an inline style,
 * keeping the declarations already there intact.
 *
 * @param string               $style       Existing inline style.
 * @param Herd_FPP_Focal_Point $focal_point The image's focal point.
 */
function herd_fpp_style_with_focal_point( string $style, Herd_FPP_Focal_Point $focal_point ): string {
	$style = rtrim( trim( $style ), ';' );

	return ltrim(
		sprintf( '%s; --focal-point-left: %s; --focal-point-top: %s;', $style, $focal_point->left, $focal_point->top ),
		'; '
	);
}
