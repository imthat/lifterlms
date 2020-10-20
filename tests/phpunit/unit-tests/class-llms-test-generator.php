<?php
/**
 * LLMS Generator Tests
 *
 * @group generator
 *
 * @since Unknown
 * @since 3.36.3 Add tests for `is_generator_valid()` and `set_generator()` methods.
 *              Split `is_error()` method tests into multiple tests.
 * @since 3.37.4 Don't test against core metadata.
 * @since [version] Add tests for image sideloading methods.
 */
class LLMS_Test_Generator extends LLMS_UnitTestCase {

	/**
	 * Test generate method.
	 *
	 * @since Unknown.
	 * @since 3.37.4 Don't test against core metadata.
	 * @since [version] Update to accommodate changes in results data (and test to maintain backwards compat).
	 *
	 * @return void
	 */
	public function test_generate() {

		$course = $this->get_mock_course_array( 1, 3, 5, 1, 5 );

		$course['author'] = array(
			'email' => 'test@test.tld',
			'id' => 12345,
		);
		$course['categories'] = array( 'cat' );
		$course['tags'] = array( 'tag1', 'tag2' );
		$course['tracks'] = array( 'track' );
		$course['difficulty'] = 'hard';
		$course['access_plans'] = array(
			array(
				'title' => 'plan1'
			),
			array(
				'title' => 'plan2'
			),
		);

		$course['custom'] = array(
			'customdata' => array( 'yes' ),
			'customdata2' => array( 'no', 'yes', 'maybe' ),
			'customdata3' => array( serialize( array( 'no', 'yes', 'maybe' ) ) ),
		);

		$gen = new LLMS_Generator( $course );
		$gen->set_generator( 'LifterLMS/SingleCourseGenerator' );
		$gen->set_default_post_status( 'publish' );
		$gen->generate();

		$results = $gen->get_results();

		// Backwards compat keys.
		$this->assertEquals( 1, $results['authors'] );
		$this->assertEquals( 1, $results['courses'] );
		$this->assertEquals( 3, $results['sections'] );
		$this->assertEquals( 15, $results['lessons'] );
		$this->assertEquals( 3, $results['quizzes'] );
		$this->assertEquals( 15, $results['questions'] );
		$this->assertEquals( 5, $results['terms'] );
		$this->assertEquals( 2, $results['plans'] );

		// Everything else.
		$this->assertEquals( 1, $results['user'] );
		$this->assertEquals( 1, $results['course'] );
		$this->assertEquals( 3, $results['section'] );
		$this->assertEquals( 15, $results['lesson'] );
		$this->assertEquals( 3, $results['quiz'] );
		$this->assertEquals( 15, $results['question'] );
		$this->assertEquals( 5, $results['term'] );
		$this->assertEquals( 2, $results['access_plan'] );
		$this->assertEquals( 1, $results['user'] );

		// Ensure custom data is properly added
		$courses = $gen->get_generated_courses();
		$custom  = get_post_custom( $courses[0] );
		unset( $custom['_llms_instructors'] ); // remove core meta data.
		$this->assertEquals( $course['custom'], $custom );

	}

	/**
	 * Test get_results()
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_results() {

		$gen = new LLMS_Generator( array() );
		$expect = array(
		  'courses'   => 0,
		  'sections'  => 0,
		  'lessons'   => 0,
		  'plans'     => 0,
		  'quizzes'   => 0,
		  'questions' => 0,
		  'terms'     => 0,
		  'authors'   => 0,
		);
		$this->assertEquals( $expect, $gen->get_results() );

	}

	/**
	 * Test get_results() when an error is encountered
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_results_error() {

		$gen = new LLMS_Generator( array() );
		$gen->generate();
		$res = $gen->get_results();
		$this->assertIsWPError($res );
		$this->assertWPErrorCodeEquals( 'missing-generator', $res );

	}

	/**
	 * Test get_generated_content()
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_generated_content() {

		$expect = array( 'mock' => array( 1 ) );
		$gen    = new LLMS_Generator( array() );
		LLMS_Unit_Test_Util::set_private_property( $gen, 'generated', $expect );

		$this->assertEquals( $expect, $gen->get_generated_content() );

	}

	/**
	 * Test get_generated_courses()
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_get_generated_courses() {

		$gen = new LLMS_Generator( array() );

		// No courses.
		$this->assertEquals( array(), $gen->get_generated_courses() );

		LLMS_Unit_Test_Util::set_private_property( $gen, 'generated', array( 'course' => array( 123 ) ) );
		$this->assertEquals( array( 123 ), $gen->get_generated_courses() );

	}

	/**
	 * Test get_generated_posts()
	 *
	 * @since [version]
	 *
	 * @expectedDeprecated LLMS_Generator::get_generated_posts()
	 *
	 * @return void
	 */
	public function test_get_generated_posts() {

		$gen = new LLMS_Generator( array() );
		$gen->get_generated_posts();

	}

	/**
	 * Test is_error() method: no generator supplied.
	 *
	 * @since 3.36.3
	 * @since [version] Added assertion for error code.
	 *
	 * @return void
	 */
	public function test_is_error_no_generator() {

		$gen = new LLMS_Generator( array() );
		$gen->generate();
		$this->assertTrue( $gen->is_error() );
		$this->assertWPErrorCodeEquals( 'missing-generator', $gen->error );

	}

	/**
	 * Test is_error() method: valid generator but no data to generate.
	 *
	 * @since 3.36.3
	 * @since [version] Added assertion for error code.
	 *
	 * @return void
	 */
	public function test_is_error_no_data() {

		$gen = new LLMS_Generator( array() );
		$gen->set_generator( 'LifterLMS/BulkCourseGenerator' );
		$gen->generate();
		$this->assertTrue( $gen->is_error() );
		$this->assertWPErrorCodeEquals( 'ERROR_GEN_MISSING_REQUIRED', $gen->error );

	}

	/**
	 * Test is_error() method: valid generator but data formatted improperly.
	 *
	 * @since 3.36.3
	 * @since [version] Added assertion for error code.
	 *
	 * @return void
	 */
	public function test_is_error_invalid_data_format() {

		$gen = new LLMS_Generator( array( 'title' => 'course title' ) );
		$gen->set_generator( 'LifterLMS/BulkCourseGenerator' );
		$gen->generate();
		$this->assertTrue( $gen->is_error() );
		$this->assertWPErrorCodeEquals( 'ERROR_GEN_MISSING_REQUIRED', $gen->error );

	}

	/**
	 * Test is_error() method: not an error
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_is_error_not_an_error() {

		$gen = new LLMS_Generator( array( 'title' => 'course title' ) );
		$gen->set_generator( 'LifterLMS/SingleCourseExporter' );
		$gen->generate();
		$this->assertFalse( $gen->is_error() );

	}

	/**
	 * Test is_generator_valid() method: valid generators.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_is_generator_valid_valid_generators() {

		$gen = new LLMS_Generator( array() );
		$list = array_keys( LLMS_Unit_Test_Util::call_method( $gen, 'get_generators' ) );
		foreach ( $list as $name ) {
			$this->assertTrue( LLMS_Unit_Test_Util::call_method( $gen, 'is_generator_valid', array( $name ) ) );
		}

	}

	/**
	 * Test is_generator_valid() method: invalid generators.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_is_generator_valid_invalid() {

		$gen = new LLMS_Generator( array() );
		$this->assertFalse( LLMS_Unit_Test_Util::call_method( $gen, 'is_generator_valid', array( 'fake' ) ) );
		$this->assertFalse( LLMS_Unit_Test_Util::call_method( $gen, 'is_generator_valid', array( 'LifterLMS/SingleFakeExporter' ) ) );

	}

	/**
	 * Test parse_raw() when passing in an array
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_parse_raw_array() {

		$gen = new LLMS_Generator( array() );
		$this->assertEquals( array( 'test' ), LLMS_Unit_Test_Util::call_method( $gen, 'parse_raw', array( array( 'test' ) ) ) );

	}

	/**
	 * Test parse_raw() when passing in a JSON string
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_parse_raw_json() {

		$gen = new LLMS_Generator( array() );
		$this->assertEquals( array( 'test' ), LLMS_Unit_Test_Util::call_method( $gen, 'parse_raw', array( wp_json_encode( array( 'test' ) ) ) ) );

	}

	/**
	 * Test parse_raw() when passing in an object
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_parse_raw_object() {

		$gen = new LLMS_Generator( array() );
		$obj = new stdClass();
		$obj->test = 1;
		$this->assertEquals( array( 'test' => 1 ), LLMS_Unit_Test_Util::call_method( $gen, 'parse_raw', array( wp_json_encode( $obj ) ) ) );

	}

	/**
	 * Test parse_raw() when passing in invalid data
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_parse_raw_invalid() {

		$gen = new LLMS_Generator( array() );
		$this->assertEquals( array(), LLMS_Unit_Test_Util::call_method( $gen, 'parse_raw', array( 'not json string' ) ) );

	}

	/**
	 * Test set_generator(): interpret from raw missing generator.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_set_generator_interpret_from_raw_missing() {

		$gen = new LLMS_Generator( array() );
		$err = $gen->set_generator();
		$this->assertIsWPError( $err );
		$this->assertWPErrorCodeEquals( 'missing-generator', $err );

	}

	/**
	 * Test set_generator(): interpret from raw invalid generator.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_set_generator_interpret_from_raw_invalid() {

		$gen = new LLMS_Generator( array(
			'_generator' => 'Fake/Generator',
		) );
		$err = $gen->set_generator();
		$this->assertIsWPError( $err );
		$this->assertWPErrorCodeEquals( 'invalid-generator', $err );

	}

	/**
	 * Test set_generator(): interpret from raw success.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_set_generator_interpret_from_raw_success() {

		$gen = new LLMS_Generator( array(
			'_generator' => 'LifterLMS/SingleCourseExporter',
		) );
		$this->assertEquals( 'LifterLMS/SingleCourseExporter', $gen->set_generator() );

	}

	/**
	 * Test set_generator(): explicitly supplied invalid.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_set_generator_explicit_invalid() {

		$gen = new LLMS_Generator( array() );
		$err = $gen->set_generator( 'Fake/Generator' );
		$this->assertIsWPError( $err );
		$this->assertWPErrorCodeEquals( 'invalid-generator', $err );

	}

	/**
	 * Test set_generator(): explicitly supplied success.
	 *
	 * @since 3.36.3
	 *
	 * @return void
	 */
	public function test_set_generator_explicit_success() {

		$gen = new LLMS_Generator( array() );
		$this->assertEquals( 'LifterLMS/SingleCourseExporter', $gen->set_generator( 'LifterLMS/SingleCourseExporter' ) );

	}

}
