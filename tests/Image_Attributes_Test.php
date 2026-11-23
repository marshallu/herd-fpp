<?php
/**
 * Focal points on rendered images.
 *
 * @package herd-fpp
 */

class Image_Attributes_Test extends WP_UnitTestCase {

	private int $image_id;

	public function set_up(): void {
		parent::set_up();
		$this->image_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		update_post_meta( $this->image_id, 'focalpoint', array( 'left' => 0.75, 'top' => 0.25 ) );
	}

	public function test_attachment_images_get_the_class_and_custom_properties(): void {
		$html = wp_get_attachment_image( $this->image_id, 'medium', false, array( 'class' => 'hero focal-point-image' ) );

		$tag = new WP_HTML_Tag_Processor( $html );
		$tag->next_tag( 'img' );

		$this->assertSame( 'hero focal-point-image', $tag->get_attribute( 'class' ) );
		$this->assertSame( '--focal-point-left: 0.75; --focal-point-top: 0.25;', $tag->get_attribute( 'style' ) );
	}

	public function test_an_existing_inline_style_is_kept_intact(): void {
		$html = wp_get_attachment_image( $this->image_id, 'medium', false, array( 'style' => 'color:red' ) );

		$this->assertStringContainsString( 'style="color:red; --focal-point-left: 0.75; --focal-point-top: 0.25;"', $html );
	}

	public function test_content_images_get_the_custom_properties_but_not_the_class(): void {
		$content = sprintf(
			'<figure class="wp-block-image"><img src="%s" class="wp-image-%d" style="border:0;" alt=""></figure>',
			wp_get_attachment_url( $this->image_id ),
			$this->image_id
		);

		// Filtering twice, as when content passes through the_content twice,
		// must not repeat the properties.
		$html = wp_filter_content_tags( wp_filter_content_tags( $content ) );

		$tag = new WP_HTML_Tag_Processor( $html );
		$tag->next_tag( 'img' );

		$this->assertSame( 'border:0; --focal-point-left: 0.75; --focal-point-top: 0.25;', $tag->get_attribute( 'style' ) );
		$this->assertSame( 'wp-image-' . $this->image_id, $tag->get_attribute( 'class' ) );
	}

	public function test_content_images_that_are_not_attachments_are_left_alone(): void {
		$html = wp_filter_content_tags( '<img src="https://example.com/remote.jpg" width="10" height="10" alt="">' );

		$this->assertStringNotContainsString( '--focal-point', $html );
	}
}
