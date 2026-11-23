<?php
/**
 * Reading focal points, and the API kept from hirasso/focal-point-picker.
 *
 * Expected values come from that plugin's documented behavior, so these tests
 * fail if a change breaks themes written for it.
 *
 * @package herd-fpp
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

class Focal_Point_Test extends WP_UnitTestCase {

	private int $image_id;

	public function set_up(): void {
		parent::set_up();
		$this->image_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
	}

	public function test_reads_focal_points_saved_by_the_original_plugin(): void {
		update_post_meta( $this->image_id, 'focalpoint', array( 'left' => 0.75, 'top' => 0.25 ) );

		$focal_point = fcp_get_focalpoint( $this->image_id );

		$this->assertInstanceOf( Hirasso\FocalPointPicker\FocalPoint::class, $focal_point );
		$this->assertSame(
			array(
				'left'        => 0.75,
				'top'         => 0.25,
				'leftPercent' => 75.0,
				'topPercent'  => 25.0,
				'x'           => 0.75,
				'y'           => 0.25,
				'xPercent'    => 75.0,
				'yPercent'    => 25.0,
			),
			(array) $focal_point
		);
		$this->assertFalse( $focal_point->isDefaultPosition() );
	}

	public function test_images_without_a_focal_point_get_the_center(): void {
		$focal_point = herd_fpp_get_focal_point( get_post( $this->image_id ) );

		$this->assertSame( array( 0.5, 0.5 ), array( $focal_point->left, $focal_point->top ) );
		$this->assertTrue( $focal_point->isDefaultPosition() );
	}

	public function test_the_original_default_position_filter_still_applies(): void {
		add_filter( 'hirasso/fcp/default-position', fn() => new Hirasso\FocalPointPicker\Position( top: 0 ) );

		$focal_point = fcp_get_focalpoint( $this->image_id );

		$this->assertSame( array( 0.5, 0.0 ), array( $focal_point->left, $focal_point->top ) );
		$this->assertTrue( $focal_point->isDefaultPosition() );
	}

	public function test_the_herd_filter_overrides_the_original_one(): void {
		add_filter( 'hirasso/fcp/default-position', fn() => new Herd_FPP_Position( top: 0 ) );
		add_filter( 'herd_fpp_default_position', fn() => new Herd_FPP_Position( 0.2, 0.8 ) );

		$this->assertEquals( new Herd_FPP_Position( 0.2, 0.8 ), herd_fpp_default_position() );
	}

	public function test_a_filter_returning_something_else_falls_back_to_the_center(): void {
		add_filter( 'herd_fpp_default_position', fn() => array( 'left' => 0, 'top' => 0 ) );

		$this->assertEquals( new Herd_FPP_Position( 0.5, 0.5 ), herd_fpp_default_position() );
	}

	public function test_is_default_position_compares_left_with_left_and_top_with_top(): void {
		// The original compared x with the default top and y with the default
		// left, so a point mirroring the default counted as the default.
		add_filter( 'herd_fpp_default_position', fn() => new Herd_FPP_Position( 0.5, 0 ) );
		update_post_meta( $this->image_id, 'focalpoint', array( 'left' => 0, 'top' => 0.5 ) );

		$this->assertFalse( fcp_get_focalpoint( $this->image_id )->isDefaultPosition() );
	}

	public function test_positions_accept_percentages_and_clamp_to_the_image(): void {
		$this->assertEquals( array( 'left' => 0.25, 'top' => 0.75 ), (array) new Herd_FPP_Position( 25, '75' ) );
		$this->assertEquals( array( 'left' => 0.0, 'top' => 0.5 ), (array) new Herd_FPP_Position( -1, 'abc' ) );
		$this->assertEquals( array( 'left' => 0.33, 'top' => 1.0 ), (array) new Herd_FPP_Position( '0.333', 250 ) );
	}

	public function test_non_images_are_rejected(): void {
		$post_id = self::factory()->post->create();

		$this->expectException( InvalidArgumentException::class );
		fcp_get_focalpoint( $post_id );
	}
}
