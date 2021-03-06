<?php
/**
 * Tests for LifterLMS Course Model
 *
 * @group    LLMS_Course
 * @group    LLMS_Post_Model
 *
 * @since 3.4.0
 * @since 3.24.0 Add tests for the `get_available_points()` method.
 * @since 4.7.0 Add tests for `to_array_extra_blocks()` and `to_array_extra_images()`.
 */
class LLMS_Test_LLMS_Course extends LLMS_PostModelUnitTestCase {

	/**
	 * class name for the model being tested by the class
	 * @var  string
	 */
	protected $class_name = 'LLMS_Course';

	/**
	 * db post type of the model being tested
	 * @var  string
	 */
	protected $post_type = 'course';

	/**
	 * Get properties, used by test_getters_setters
	 * This should match, exactly, the object's $properties array
	 * @return   array
	 * @since    3.4.0
	 * @version  3.20.0
	 */
	protected function get_properties() {
		return array(
			'audio_embed' => 'text',
			'capacity' => 'absint',
			'capacity_message' => 'text',
			'course_closed_message' => 'text',
			'course_opens_message' => 'text',
			'content_restricted_message' => 'text',
			'enable_capacity' => 'yesno',
			'end_date' => 'text',
			'enrollment_closed_message' => 'text',
			'enrollment_end_date' => 'text',
			'enrollment_opens_message' => 'text',
			'enrollment_period' => 'yesno',
			'enrollment_start_date' => 'text',
			'has_prerequisite' => 'yesno',
			'length' => 'text',
			'prerequisite' => 'absint',
			'prerequisite_track' => 'absint',
			'sales_page_content_page_id' => 'absint',
			'sales_page_content_type' => 'string',
			'sales_page_content_url' => 'string',
			'tile_featured_video' => 'yesno',
			'time_period' => 'yesno',
			'start_date' => 'text',
			'video_embed' => 'text',
		);
	}

	/**
	 * Get data to fill a create post with
	 * This is used by test_getters_setters
	 * @return   array
	 * @since    3.4.0
	 * @version  3.20.0
	 */
	protected function get_data() {
		return array(
			'audio_embed' => 'http://example.tld/audio_embed',
			'capacity' => 25,
			'capacity_message' => 'Capacity Reached',
			'course_closed_message' => 'Course has closed',
			'course_opens_message' => 'Course is not yet open',
			'content_restricted_message' => 'You cannot access this content',
			'enable_capacity' => 'yes',
			'end_date' => '2017-05-05',
			'enrollment_closed_message' => 'Enrollment is closed',
			'enrollment_end_date' => '2017-05-05',
			'enrollment_opens_message' => 'Enrollment opens later',
			'enrollment_period' => 'yes',
			'enrollment_start_date' => '2017-05-01',
			'has_prerequisite' => 'no',
			'length' => '1 year',
			'prerequisite' => 0,
			'prerequisite_track' => 0,
			'tile_featured_video' => 'yes',
			'time_period' => 'yes',
			'sales_page_content_page_id' => 0,
			'sales_page_content_type' => 'none',
			'sales_page_content_url' => 'https://lifterlms.com',
			'start_date' => '2017-05-01',
			'video_embed' => 'http://example.tld/video_embed',
		);
	}

	/**
	 * Test the get_available_points() method
	 * @return   [type]
	 * @since    3.24.0
	 * @version  3.24.0
	 */
	public function test_get_available_points() {

		$course = llms_get_post( $this->generate_mock_courses( 1, 2, 5, 0, 0 )[0] );

		// default setup is 1 point per lesson
		$this->assertEquals( 10, $course->get_available_points() );

		// change them all up
		$points = 0;
		foreach ( $course->get_lessons() as $lesson ) {
			$lesson_points = rand( 0, 3 );
			$lesson->set( 'points', $lesson_points );
			$points += $lesson_points;
		}
		$this->assertEquals( $points, $course->get_available_points() );

	}

	/**
	 * Test Audio and Video Embeds
	 * @return   void
	 * @since    3.4.0
	 * @version  3.4.0
	 */
	public function test_get_embeds() {

		$audio_url = 'https://open.spotify.com/track/1rNUOtuCWv1qswqsMFvzvz';
		$video_url = 'https://www.youtube.com/watch?v=MhQlNwxn5oo';

		$course = new LLMS_Course( 'new', 'Course With Embeds' );

		// empty string when none set
		$this->assertEmpty( $course->get_audio() );
		$this->assertEmpty( $course->get_video() );

		$course->set( 'audio_embed', $audio_url );
		$course->set( 'video_embed', $video_url );

		$audio_embed = $course->get_audio();
		$video_embed = $course->get_video();

		// string
		$this->assertTrue( is_string( $audio_embed ) );
		$this->assertTrue( is_string( $video_embed ) );

		// should be an iframe for valid embeds
		$this->assertEquals( 0, strpos( $audio_embed, '<iframe' ) );
		$this->assertEquals( 0, strpos( $video_embed, '<iframe' ) );

		// fallbacks should be a link to the URL
		$course->set( 'audio_embed', 'http://lifterlms.com/not/embeddable' );
		$course->set( 'video_embed', 'http://lifterlms.com/not/embeddable' );
		$this->assertEquals( 0, strpos( $audio_embed, '<a' ) );
		$this->assertEquals( 0, strpos( $video_embed, '<a' ) );

	}

	/**
	 * Test get percent complete from course
	 * @return   void
	 * @since    3.17.2
	 * @version  3.17.2
	 */
	public function test_get_percent_complete() {

		$course = llms_get_post( $this->generate_mock_courses( 1, 4, 4, 0, 0 )[0] );
		$student = $this->get_mock_student();

		$student->enroll( $course->get( 'id' ) );

		// get student by ID
		$this->assertEquals( 0, $course->get_percent_complete( $student->get( 'id' ) ) );

		// get from current user
		$this->assertEquals( 0, $course->get_percent_complete() );

		// complete some courses
		$this->complete_courses_for_student( $student->get_id(), $course->get( 'id' ), 75 );

		// get by id
		$this->assertEquals( 75, $course->get_percent_complete( $student->get( 'id' ) ) );

		// get from current user
		$this->assertEquals( 0, $course->get_percent_complete() );

		// log the user in
		wp_set_current_user( $student->get_id() );

		// get from current user
		$this->assertEquals( 75, $course->get_percent_complete() );


	}

	/**
	 * Test prerequisite functions related to courses
	 * @return   void
	 * @since    3.4.0
	 * @version  3.7.3
	 */
	public function test_get_prerequisites() {

		$course = new LLMS_Course( 'new', 'Course Name' );
		$prereq_course = new LLMS_Course( 'new', 'Course Prereq' );
		$prereq_track = wp_create_term( 'test track', 'course_track' );

		// no prereqs
		$this->assertFalse( $course->has_prerequisite( 'any' ) );
		$this->assertFalse( $course->has_prerequisite( 'course' ) );
		$this->assertFalse( $course->has_prerequisite( 'course_track' ) );
		$this->assertFalse( $course->get_prerequisite_id( 'course' ) );
		$this->assertFalse( $course->get_prerequisite_id( 'course_track' ) );

		$course->set( 'prerequisite', $prereq_course->get( 'id' ) );
		$course->set( 'prerequisite_track', $prereq_track['term_id'] );

		// still no prereqs
		$this->assertFalse( $course->has_prerequisite( 'any' ) );
		$this->assertFalse( $course->has_prerequisite( 'course' ) );
		$this->assertFalse( $course->has_prerequisite( 'course_track' ) );
		$this->assertFalse( $course->get_prerequisite_id( 'course' ) );
		$this->assertFalse( $course->get_prerequisite_id( 'course_track' ) );

		$course->set( 'has_prerequisite', 'yes' );

		// have prereqs
		$this->assertTrue( $course->has_prerequisite( 'any' ) );
		$this->assertTrue( $course->has_prerequisite( 'course' ) );
		$this->assertTrue( $course->has_prerequisite( 'course_track' ) );
		$this->assertEquals( $prereq_course->get( 'id' ), $course->get_prerequisite_id( 'course' ) );
		$this->assertEquals( $prereq_track['term_id'], $course->get_prerequisite_id( 'course_track' ) );

		$course->set( 'prerequisite', 0 );

		$this->assertTrue( $course->has_prerequisite( 'any' ) );
		$this->assertFalse( $course->has_prerequisite( 'course' ) );
		$this->assertTrue( $course->has_prerequisite( 'course_track' ) );
		$this->assertEquals( 0, $course->get_prerequisite_id( 'course' ) );

		$course->set( 'prerequisite', 'string' );
		$this->assertFalse( $course->has_prerequisite( 'course' ) );
		$this->assertEquals( 0, $course->get_prerequisite_id( 'course' ) );

	}

	/**
	 * Test the get lessons function
	 * @return   void
	 * @since    3.12.0
	 * @version  3.12.0
	 */
	public function test_get_lessons() {

		$course = llms_get_post( $this->generate_mock_courses( 1, 2, 2, 0, 0 )[0] );

		// get just ids
		$lessons = $course->get_lessons( 'ids' );
		$this->assertEquals( 4, count( $lessons ) );
		array_map( function( $id ) {
			$this->assertTrue( is_numeric( $id ) );
		}, $lessons );

		// wp post objects
		$lessons = $course->get_lessons( 'posts' );
		$this->assertEquals( 4, count( $lessons ) );
		array_map( function( $post ) {
			$this->assertTrue( is_a( $post, 'WP_Post' ) );
		}, $lessons );

		// lesson objects
		$lessons = $course->get_lessons( 'lessons' );
		$this->assertEquals( 4, count( $lessons ) );
		array_map( function( $lesson ) {
			$this->assertTrue( is_a( $lesson, 'LLMS_Lesson' ) );
		}, $lessons );

	}

	/**
	 * Test the get quizzes function
	 * @return   void
	 * @since    3.12.0
	 * @version  3.12.0
	 */
	public function test_get_quizzes() {

		$course = llms_get_post( $this->generate_mock_courses( 1, 1, 5, 3, 1 )[0] );

		$quizzes = $course->get_quizzes();
		$this->assertEquals( 3, count( $quizzes ) );
		array_map( function( $id ) {
			$this->assertTrue( is_numeric( $id ) );
		}, $quizzes );

	}

	/**
	 * Test get_sales_page_url method
	 * @return   void
	 * @since    3.20.0
	 * @version  3.20.0
	 */
	public function test_get_sales_page_url() {

		$course = new LLMS_Course( 'new', 'Course Name' );

		$this->assertEquals( get_permalink( $course->get( 'id' ) ), $course->get_sales_page_url() );

		$course->set( 'sales_page_content_type', 'none' );
		$this->assertEquals( get_permalink( $course->get( 'id' ) ), $course->get_sales_page_url() );

		$course->set( 'sales_page_content_type', 'content' );
		$this->assertEquals( get_permalink( $course->get( 'id' ) ), $course->get_sales_page_url() );

		$course->set( 'sales_page_content_type', 'url' );
		$course->set( 'sales_page_content_url', 'https://lifterlms.com' );
		$this->assertEquals( 'https://lifterlms.com', $course->get_sales_page_url() );

		$course->set( 'sales_page_content_type', 'page' );
		$page = $this->factory->post->create();
		$course->set( 'sales_page_content_page_id', $page );
		$this->assertEquals( get_permalink( $page ), $course->get_sales_page_url() );

	}

	/**
	 * Test the get sections function
	 * @return   void
	 * @since    3.12.0
	 * @version  3.12.0
	 */
	public function test_get_sections() {

		$course = llms_get_post( $this->generate_mock_courses( 1, 4, 0, 0, 0 )[0] );

		// get just ids
		$sections = $course->get_sections( 'ids' );
		$this->assertEquals( 4, count( $sections ) );
		array_map( function( $id ) {
			$this->assertTrue( is_numeric( $id ) );
		}, $sections );

		// wp post objects
		$sections = $course->get_sections( 'posts' );
		$this->assertEquals( 4, count( $sections ) );
		array_map( function( $post ) {
			$this->assertTrue( is_a( $post, 'WP_Post' ) );
		}, $sections );

		// section objects
		$sections = $course->get_sections( 'sections' );
		$this->assertEquals( 4, count( $sections ) );
		array_map( function( $section ) {
			$this->assertTrue( is_a( $section, 'LLMS_Section' ) );
		}, $sections );

	}

	/**
	 * Test the get students function
	 * @return   void
	 * @since    3.12.0
	 * @version  3.12.0
	 */
	public function test_get_students() {

		$this->create();

		$students = $this->factory->user->create_many( 10, array( 'role' => 'student' ) );
		foreach ( $students as $sid ) {
			llms_enroll_student( $sid, $this->obj->get( 'id' ), 'testing' );
		}

		$this->assertEquals( 5, count( $this->obj->get_students( array( 'enrolled' ), 5 ) ) );
		$this->assertEquals( 10, count( $this->obj->get_students() ) );

	}

	/**
	 * Test the has_capacity function
	 * @return   void
	 * @since    3.12.0
	 * @version  3.12.0
	 */
	public function test_has_capacity() {

		$this->create();
		// has capacity when nothing set
		$this->assertTrue( $this->obj->has_capacity() );

		$students = $this->factory->user->create_many( 10, array( 'role' => 'student' ) );
		foreach ( $students as $sid ) {
			llms_enroll_student( $sid, $this->obj->get( 'id' ), 'testing' );
		}

		// has capacity when students enrolled and nothing set
		$this->assertTrue( $this->obj->has_capacity() );

		// enabled capacity
		$this->obj->set( 'enable_capacity', 'yes' );
		$this->obj->set( 'capacity', 25 );

		// still open
		$this->assertTrue( $this->obj->has_capacity() );

		// over capacity
		$this->obj->set( 'capacity', 5 );
		$this->assertFalse( $this->obj->has_capacity() );

		// disable capacity
		$this->obj->set( 'enable_capacity', 'no' );
		$this->assertTrue( $this->obj->has_capacity() );

	}

	/**
	 * Test the has_sales_page_redirect method
	 * @return   void
	 * @since    3.20.0
	 * @version  3.20.0
	 */
	public function test_has_sales_page_redirect() {

		$course = new LLMS_Course( 'new', 'Course Name' );

		$this->assertEquals( false, $course->has_sales_page_redirect() );

		$course->set( 'sales_page_content_type', 'none' );
		$this->assertEquals( false, $course->has_sales_page_redirect() );

		$course->set( 'sales_page_content_type', 'content' );
		$this->assertEquals( false, $course->has_sales_page_redirect() );

		$course->set( 'sales_page_content_type', 'url' );
		$this->assertEquals( true, $course->has_sales_page_redirect() );

		$course->set( 'sales_page_content_type', 'page' );
		$this->assertEquals( true, $course->has_sales_page_redirect() );

	}

	/**
	 * Test to_array_extra_blocks()
	 *
	 * @since 4.7.0
	 *
	 * @return void
	 */
	public function test_to_array_extra_blocks() {

		// Mock reusable block.
		$block_title   = 'Reusable block title';
		$block_content = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$block         = $this->factory->post->create( array(
			'post_content' => $block_content,
			'post_title'   => $block_title,
			'post_type'    => 'wp_block',
		) );

		// Get the HTML of the reusable block to use in our mock course content..
		$html  = serialize_block( array(
			'blockName' => 'core/block',
			'innerContent' => array( '' ),
			'attrs' => array(
				'ref' => $block,
			)
		) );
		$html .= serialize_block( array(
			'blockName'    => 'core/paragraph',
			'innerContent' => array( 'Lorem ipsum dolor sit.' ),
			'attrs'        => array(),
		) );

		// Mock course.
		$post   = $this->factory->post->create_and_get( array(
			'post_type'    => 'course',
			'post_content' => $html,
		) );
		$course = llms_get_post( $post );

		$expect = array(
			$block => array(
				'title'   => $block_title,
				'content' => $block_content,
			),
		);

		$this->assertEquals( $expect, LLMS_Unit_Test_Util::call_method( $course, 'to_array_extra_blocks', array( $post->post_content ) ) );

	}

	/**
	 * Test to_array_extra_images()
	 *
	 * @since 4.7.0
	 *
	 * @return void
	 */
	public function test_to_array_extra_images() {

		$post = $this->factory->post->create_and_get( array(
			'post_type'    => 'course',
			'post_content' => '<!-- wp:image {"id":552,"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="http://example.org/wp-content/uploads/2020/09/image1.png" alt="" class="wp-image-1" /></figure>
<!-- /wp:image -->
<!-- wp:gallery {"ids":[1,2]} -->
<figure class="wp-block-gallery columns-2 is-cropped"><ul class="blocks-gallery-grid">
<li class="blocks-gallery-item"><figure><img src="http://example.org/wp-content/uploads/2020/09/image1.png" alt="" data-id="1" data-full-url="http://example.org/wp-content/uploads/2020/09/image1.png" data-link="http://example.org/wp-content/uploads/2020/09/image1.png" class="wp-image-1" /></figure></li>
<li class="blocks-gallery-item"><figure><img src="http://example.org/wp-content/uploads/2020/09/image2.jpg" alt="" data-id="2" data-full-url="http://example.org/wp-content/uploads/2020/09/image2.jpg" data-link="http://example.org/wp-content/uploads/2020/09/image2.jpg" class="wp-image-2" /></figure></li></ul></figure>
<!-- /wp:gallery -->
<img src="http://example.org/wp-content/uploads/2020/09/image1.png" alt="" class="wp-image-1"  />
<img src="http://cdn.tld/image3.png"  />'
		) );

		$expect = array(
			'http://example.org/wp-content/uploads/2020/09/image1.png',
			'http://example.org/wp-content/uploads/2020/09/image2.jpg',
		);
		$this->assertEquals( $expect, LLMS_Unit_Test_Util::call_method( llms_get_post( $post ), 'to_array_extra_images', array( $post->post_content ) ) );

	}

}
