<div class="step-title step-6">
	<?php printf( __( 'Step 6 &ndash; Enrollment %s', 'coursepress' ), $title2 ); ?>
	<div class="status <?php echo $setup_class; ?>"></div>
</div>

<div class="cp-box-content step-content step-6">
	<input type="hidden" name="meta_setup_step_6" value="saved" />

	<div class="wide">
		<label><?php _e( 'Enrollment Restrictions', 'coursepress' ); ?></label>
		<p class="description"><?php _e( 'Select the limitations on accessing and enrolling in this course.', 'coursepress' ); ?></p>
		<?php echo CoursePress_Helper_UI::select( 'meta_enrollment_type', $enrollment_types, $enrollment_type, 'chosen-select medium' ); ?>
	</div>

	<div class="wide enrollment-type-options prerequisite<?php echo $prerequisite_class; ?>">
		<label><?php _e( 'Prerequisite Courses', 'coursepress' ); ?></label>
		<p class="description"><?php _e( 'Select the courses a student needs to complete before enrolling in this course', 'coursepress' ); ?></p>
		<select name="meta_enrollment_prerequisite" class="medium chosen-select chosen-select-course <?php echo $class_extra; ?>" multiple="true" data-placeholder=" ">

			<?php if ( ! empty( $courses ) ) : foreach ( $courses as $course ) : ?>
				<option value="<?php echo $course->ID; ?>" <?php selected( true, in_array( $course->ID, $saved_settings ) ); ?>><?php echo $course->post_title; ?></option>
			<?php endforeach; endif; ?>

		</select>
	</div>

	<div class="wide enrollment-type-options passcode <?php echo $passcode_class; ?>">
		<label><?php _e( 'Course Passcode', 'coursepress' ); ?></label>
		<p class="description"><?php _e( 'Enter the passcode required to access this course', 'coursepress' ); ?></p>
		<input type="text" name="meta_enrollment_passcode" value="<?php echo esc_attr( $enrollment_passcode ); ?>" />
	</div>

	<?php if ( false === $disable_payment ) :
		$one = array(
				'meta_key' => 'payment_paid_course',
				'title' => __( 'Course Payment', 'coursepress' ),
				'description' => __( 'Payment options for your course. Additional plugins are required and settings vary depending on the plugin.', 'coursepress' ),
				'label' => __( 'This is a paid course', 'coursepress' ),
				'default' => false,
			);
		echo '<hr class="separator" />';
		echo CoursePress_Helper_UI::course_edit_checkbox( $one, $course_id );
	endif;
	?>

	<?php
	// Show install|payment messages when applicable
	if ( false === $payment_supported && false === $disable_payment ) :
		echo $payment_message;
	endif;
	?>
	<div class="is_paid_toggle <?php echo $payment_paid_course ? '' : 'hidden'; ?>">
		<?php
        // Ajout EP CP pour ajout formule gratuite
    $two = array(
        'meta_key' => 'second_formula_course',
        //'title' => __( 'Second Formula for the Course', 'coursepress' ),
        'description' => __( 'Create a group in order to have a second free formula for the course', 'coursepress' ),
        'label' => __( 'Create a free formula', 'coursepress' ),
        'default' => false,
    );
    echo CoursePress_Helper_UI::course_edit_checkbox( $two, $course_id );
    ?>
    <label class="normal required"><?php _e( 'Commercial name', 'coursepress' ); ?></label>
    <input type="text" name="meta_base_formula_name" value="<?php echo esc_attr( $base_formula_name ); ?>" />
    <label class="normal required"><?php _e( 'Tag of the base formula group', 'coursepress' ); ?></label>
    <input type="text" name="meta_base_formula_tag" value="<?php echo esc_attr( $base_formula_tag ); ?>" /> 
    <?php
        // Fin Ajout EP CP pour partie de cours payante et gratuite
        
        /**
		 * Add additional fields if 'This is a paid course' is selected.
		 *
		 * Field names must begin with meta_ to allow it to be automatically added to the course settings
		 *
		 * * This is the ideal filter to use for integrating payment plugins
		 */
		echo apply_filters( 'coursepress_course_setup_step_6_paid', '', $course_id );
		?>
	</div>

    <?php
    // Ajout EP CP pour partie de cours payante et gratuite
    
    ?>
    <hr class="separator" />
    <div class="has2_formula_toggle wide <?php echo $second_formula_course ? '' : 'hidden'; ?>">
        <div class="" style="overflow: hidden">
            <label class="normal"><?php _e( 'Second  Free formula', 'coursepress' ); ?></label>
            <label class="normal required" style="clear: both; display: block; float: left; font-weight: normal; width: 250px; margin: 0;"><?php _e( 'Comercial name', 'coursepress' ); ?></label>
            <input type="text" name="meta_second_formula_name" style="clear: right; display: block; float: left; margin-bottom: 10px; max-width: 280px;" value="<?php echo esc_attr( $second_formula_name ); ?>" />
            <label class="normal required" style="clear: both; display: block; float: left; font-weight: normal; width: 250px; margin: 0;"><?php _e( 'Tag of the second formula group', 'coursepress' ); ?></label>
            <input type="text" name="meta_second_formula_tag" style="clear: right; display: block; float: left; margin-bottom: 10px; max-width: 280px;" value="<?php echo esc_attr( $second_formula_tag ); ?>" /> 
        </div>  
    </div>
    
<?php

    // Fin Ajout EP CP pour partie de cours payante et gratuite
	/**
	 * Trigger to add additional fields in step 6.
	 **/
	echo apply_filters( 'coursepress_course_setup_step_6', '', $course_id );

	// Show buttons
	echo CoursePress_View_Admin_Course_Edit::get_buttons( $course_id, 6 );
	?>
</div>
