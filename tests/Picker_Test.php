<?php
/**
 * The Focal Point attachment field.
 *
 * @package herd-fpp
 */

class Picker_Test extends WP_UnitTestCase {

	private int $image_id;

	public function set_up(): void {
		parent::set_up();
		$this->image_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
	}

	public function test_the_field_shows_the_stored_point_under_the_legacy_name(): void {
		update_post_meta( $this->image_id, 'focalpoint', array( 'left' => 0.75, 'top' => 0.25 ) );

		$fields = apply_filters( 'attachment_fields_to_edit', array(), get_post( $this->image_id ) );

		$this->assertArrayHasKey( 'focalpoint-input', $fields );
		$this->assertStringContainsString( 'name="attachments[' . $this->image_id . '][focalpoint]"', $fields['focalpoint-input']['html'] );
		$this->assertStringContainsString( 'value="0.75 0.25"', $fields['focalpoint-input']['html'] );
	}

	public function test_saving_stores_the_point_in_the_legacy_meta_shape(): void {
		$this->save( '0.3 0.7' );

		$this->assertSame( array( 'left' => 0.3, 'top' => 0.7 ), get_post_meta( $this->image_id, 'focalpoint', true ) );
	}

	public function test_saving_clamps_points_outside_the_image(): void {
		$this->save( '-0.2 1' );

		$this->assertSame( array( 'left' => 0.0, 'top' => 1.0 ), get_post_meta( $this->image_id, 'focalpoint', true ) );
	}

	public function test_malformed_values_leave_the_stored_point_alone(): void {
		update_post_meta( $this->image_id, 'focalpoint', array( 'left' => 0.75, 'top' => 0.25 ) );

		foreach ( array( '', '0.3', '0.1 0.2 0.3', 'abc 0.5' ) as $value ) {
			$this->save( $value );
		}

		$this->assertSame( array( 'left' => 0.75, 'top' => 0.25 ), get_post_meta( $this->image_id, 'focalpoint', true ) );
	}

	private function save( string $value ): void {
		apply_filters( 'attachment_fields_to_save', get_post( $this->image_id, ARRAY_A ), array( 'focalpoint' => $value ) );
	}
}
