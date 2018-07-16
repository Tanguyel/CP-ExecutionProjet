<?php

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary. In this tutorial, we are
 * going to use the WP_List_Table class directly from WordPress core.
 *
 * IMPORTANT:
 * Please note that the WP_List_Table class technically isn't an official API,
 * and it could change at some point in the distant future. Should that happen,
 * I will update this plugin with the most current techniques for your reference
 * immediately.
 *
 * If you are really worried about future compatibility, you can make a copy of
 * the WP_List_Table class (file path is shown just below) to use and distribute
 * with your plugins. If you do that, just remember to change the name of the
 * class to avoid conflicts with core.
 *
 * Since I will be keeping this tutorial up-to-date for the foreseeable future,
 * I am going to work with the copy of the class provided in WordPress core.
 */
if ( ! class_exists( 'WP_Users_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-users-list-table.php' );
}

/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 *
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 *
 * Our theme for this list table is going to be movies.
 */

class CoursePress_Helper_Table_CourseStudent extends WP_Users_List_Table {

    protected $course_id = 0;
    private $add_new = false;
    protected $students = array();
    protected $can_withdraw_students = false;
    private $filter_show = 'all';
    private $filter_options = array();

    /** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Student', 'coursepress' ),
            'plural' => __( 'Students', 'coursepress' ),
            'ajax' => false,// should this table support ajax?
        ) );

        /**
		 * add filters
		 */
        add_filter( 'user_row_actions', array( $this, 'student_row_actions' ), 10, 2 );
        add_filter( 'manage_users_custom_column', array( $this, 'columns' ), 10, 3 );
        add_filter( 'views_course', array( $this, 'views_array_filter' ) );

        /**
		 * set course ID
		 */
        if ( CoursePress_Data_Course::is_course( $this->course_id ) ) {
            $this->can_withdraw_students = CoursePress_Data_Capabilities::can_withdraw_students( $this->course_id );
        }

        /**
		 * filter options
		 */
        $this->filter_options = array(
            'all' => __( 'All', 'coursepress' ),
            'yes' => __( 'Certified', 'coursepress' ),
            'no' => __( 'Not certified', 'coursepress' ),
        );
        if ( isset( $_REQUEST['certified'] ) && array_key_exists( $_REQUEST['certified'], $this->filter_options ) ) {
            $this->filter_show = $_REQUEST['certified'];
        }
    }

    /**
	 * Show quick filter.
	 *
	 * @since 2.0.8
	 */
    public function views_array_filter( $views ) {
        global $post;
        $views = array();
        $pattern = '<a href="%s" class="%s">%s</a>';
        $url = add_query_arg(
            array(
                'post_type' => $post->post_type,
                'post' => $post->ID,
                'action' => 'edit',
                'tab' => 'students',
            ),
            admin_url( 'post.php' )
        );
        foreach ( $this->filter_options as $key => $label ) {
            $action_url = add_query_arg( 'certified', $key, $url );
            $class = $key == $this->filter_show? 'current':'';
            $views[ $key ] = sprintf(
                $pattern,
                esc_url( $action_url ),
                $class,
                esc_html( $label )
            );
        }
        return $views;
    }

    /**
	 * Get student object by student id.
	 *
	 * @since 2.0.8
	 *
	 * @param integer $ID Student ID.
	 * @return null|WP User Student object.
	 */
    protected function get_student( $ID ) {
        foreach ( $this->items as $item ) {
            if ( $ID == $item->ID ) {
                return $item;
            }
        }
        return null;
    }

    public function columns( $content, $column_name, $item_id ) {
        switch ( $column_name ) {
            case 'display_name':
            case 'first_name':
            case 'last_name':
                return sprintf(
                    '%s', get_user_option( $column_name, $item_id )
                );
            case 'certificates':
                return $this->column_certificates( $item_id );
                // Ajout EP CP 2 formule
            case 'formule':

                $group = get_user_option( 'enrolled_course_group_' . $this->course_id, $item_id ); 
                $course_id = $this->course_id;

                if ( strpos( $group, CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag' ))!==false) {
                    $group_class = 'paid';
                    $group_name = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name');
                    $group = str_replace(CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag') . '-', '', $group);
                    $group = str_replace(CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag'), '', $group);
                    if ($group == 'hold'){
                        $group_name = __(' Hold','coursepress');
                        $group_class .= ' hold';
                    } else if ($group == 'complete'){
                        $group_name .= __(' Paid','coursepress');
                        $group_class .= ' complete';
                    } else {
                        $group_name .= ' ' . $group;
                        $group_class .= ' ' . $group;
                    }
                    
                } else if ( $group == 'paid') {
                    $group_class = 'paid';
                    $group_name = $group;
                    
                } else if ( $group == CoursePress_Data_Course::get_setting( $course_id, 'second_formula_tag' )) {
                    $group_class = 'free';
                    $group_name = CoursePress_Data_Course::get_setting( $course_id, 'second_formula_name');
                    
                } else if ( $group == 'free') {
                    $group_class = 'free';
                    $group_name = $group;
                    
                } elseif ( $group == '' ) {
                    $group_class = '';
                    $group_name = $group;
                    
                } else {
                    $group_class = 'other';
                    $group_name = $group;
                }


                return sprintf(	'<span class="formule %s">%s</span>', $group_class, $group_name );
                //Fin Ajout EP CP 2 Fprmule
        }
        return $content;
    }

    public function set_course( $id ) {
        $this->course_id = (int) $id;
    }

    /**
	 * get course_id
	 *
	 * @since 2.0.0
	 *
	 * return integer course id
	 */
    public function get_course_id() {
        return $this->course_id;
    }

    public function set_add_new( $bool ) {
        $this->add_new = $bool;
    }

    /** ************************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 *
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************/
    public function get_columns() {
        $course_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : null;
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'username' => __( 'Username', 'coursepress' ),
            'display_name' => __( 'Display Name', 'coursepress' ),
            'first_name' => __( 'First Name', 'coursepress' ),
            'last_name' => __( 'Last Name', 'coursepress' ),
            'formule' => __( 'Formule', 'coursepress' ), //Ajout EP CP 2 Formule
            'certificates' => __( 'Certified', 'coursepress' ),
        );

        if ( ! CoursePress_Data_Capabilities::can_withdraw_students( $course_id ) ) {
            unset( $columns['actions'] );
        }

        return $columns;
    }

    public function get_hidden_columns() {
        return array();
    }

    /** ************************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
    public function get_sortable_columns() {
        $c = array(
            'display_name' => array( 'display_name', false ),
            'first_name' => array( 'first_name', false ),
            'last_name' => array( 'last_name', false ),
            'username' => array( 'login', false ),
        );
        return $c;
    }

    /** ************************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-actions[]" value="%s" />', $item->ID
        );
    }

    public function student_row_actions( $actions, $item ) {
        $this->students[] = $item->ID;
        $profile_link = CoursePress_Data_Student::get_admin_profile_url( $item->ID );
        $workbook_link = add_query_arg(
            array(
                'post_type' => CoursePress_Data_Course::get_post_type_name(),
                'page' => 'coursepress_assessments',
                'view' => 'profile',
                'student_id' => $item->ID,
                'course_id' => $this->course_id,
            ),
            remove_query_arg(
                array(
                    'tab',
                    'post',
                    'action',
                ),
                admin_url( 'edit.php' )
            )
        );

        $actions = array(
            'id' => sprintf( '<span>%s</span>', esc_html( sprintf( __( 'ID: %d', 'coursepress' ), $item->ID ) ) ),
            'profile' => sprintf( '<a href="%s">%s</a>', $profile_link, esc_html__( 'Student Profile', 'coursepress' ) ),
            'workbook' => sprintf( '<a href="%s">%s</a>', $workbook_link, esc_html__( 'Workbook', 'coursepress' ) ),
        );

        if ( current_user_can( 'edit_users' ) ) {
            $actions['edit_user_profile'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        array(
                            'courses' => 'show',
                        ),
                        get_edit_user_link( $item->ID )
                    )
                ),
                __( 'Edit User Profile', 'coursepress' )
            );
        }

        if ( $this->can_withdraw_students ) {
            $actions['trash'] = sprintf(
                '<a href="#" class="withdraw-student" data-id="%s" data-nonce="%s">%s</a>',
                esc_attr( $item->ID ),
                esc_attr( wp_create_nonce( 'withdraw-single-student-'.$item->ID ) ),
                esc_html__( 'Withdraw', 'coursepress' )
            );
        }
        return $actions;
    }

    /** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
    public function prepare_items() {

        global $wpdb;

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        
        $this->process_bulk_action(); //Ajout EP CP 2 Formule

        $per_page = 20;
        $current_page = $this->get_pagenum();

        $offset = ( $current_page - 1 ) * $per_page;

        $this->_column_headers = array( $columns, $hidden, $sortable );

        if ( is_multisite() ) {
            $course_meta_key = $wpdb->prefix . 'enrolled_course_date_' . $this->course_id;
        } else {
            $course_meta_key = 'enrolled_course_date_' . $this->course_id;
        }

        // Could use the Course Model methods here, but lets try stick to one query
        $query_args = array(
            'meta_query' => array(
                array(
                    'key' => $course_meta_key,
                    'compare' => 'EXISTS',
                ),
            ),
            'number' => $per_page,
            'offset' => $offset,
        );
        $usersearch = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        if ( ! empty( $usersearch ) ) {
            $query_args['search'] = '*' . $usersearch . '*';
        }

        if ( isset( $_REQUEST['orderby'] ) ) {
            $query_args['orderby'] = $_REQUEST['orderby'];
            switch ( $_REQUEST['orderby'] ) {
                case 'first_name':
                case 'last_name':
                    $query_args['meta_query'] = array(
                        'relation' => 'AND',
                        array(
                            'key' => $_REQUEST['orderby'],
                            'compare' => 'EXISTS',
                        ),
                        array(
                            'key' => $course_meta_key,
                            'compare' => 'EXISTS',
                        ),
                    );
                    $query_args['orderby'] = 'meta_value';
                    break;
            }
        }
        if ( isset( $_REQUEST['order'] ) ) {
            $query_args['order'] = $_REQUEST['order'];
        }

        /**
		 * fil certificates
		 */
        $certificates = CoursePress_Data_Certificate::get_certificated_students_by_course_id( $this->course_id );

        /**
		 * Certificates
		 */
        if ( ! empty( $certificates ) ) {
            switch ( $this->filter_show ) {
                case 'no':
                    $query_args['exclude'] = $certificates;
                    break;
                case 'yes':
                    $query_args['include'] = $certificates;
                    break;
            }
        }

        $users = new WP_User_Query( $query_args );

        foreach ( $users->get_results() as $one ) {
            $one->data->certified = in_array( $one->ID, $certificates )? 'yes' : 'no';
            $this->items[] = $one;
        }

        $total_items = $users->get_total();
        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page' => $per_page,
            )
        );

    }

    public function extra_tablenav( $which ) {
        $course_id = (int) $_GET['id'];
        
        //Ajout EP CP 2 Formule
        
        if ('top' === $which) { 
?>
<script type="text/javascript">
    $(document).ready(function () {
        var post_input = $('<input>');
        post_input.attr('type', 'hidden');
        post_input.attr('name', 'post');
        post_input.attr('value', $('[name="post_ID"]').val());
        post_input.insertAfter('#bulk-action-selector-top'); 
        
        var tab_input = $('<input>');
        tab_input.attr('type', 'hidden');
        tab_input.attr('name', 'tab');
        tab_input.attr('value', 'students');
        tab_input.insertAfter('#bulk-action-selector-top'); 
        
        var action_input = $('<input>');
        action_input.attr('type', 'hidden');
        action_input.attr('name', 'action');
        action_input.attr('value', 'edit');
        action_input.insertAfter('#bulk-action-selector-top'); 
        
        $('#bulk-action-selector-top').on('change', function () {
            if ( $(this).val()==='custom_formule') {
                var formule_input = $('<input>');
                formule_input.attr('type', 'text');
                formule_input.attr('id', 'group_formule_input_top');
                formule_input.attr('name', 'group_formule');
                formule_input.insertAfter('#bulk-action-selector-top');
            } else {
                $('#group_formule_input_top').remove();
            }
        });
        
        $('#bulk-action-selector-bottom').on('change', function () {
            if ( $(this).val()==='custom_formule') {
                var formule_input = $('<input>');
                formule_input.attr('type', 'text');
                formule_input.attr('id', 'group_formule_input_bottom');
                formule_input.attr('name', 'group_formule');
                formule_input.insertAfter('#bulk-action-selector-bottom');
            } else {
                $('#group_formule_input_bottom').remove();
            }
        });
});
</script>
<?php
        }
        
        //Fin Ajout EP CP 
        
        if ( 'bottom' === $which && $this->add_new ) {

?>
<div class="coursepress_course_add_student_wrapper">
    <?php
            $nonce = wp_create_nonce( 'add_student' );
            $withdraw_nonce = wp_create_nonce( 'withdraw_all_students' );

            if ( CoursePress_Data_Capabilities::can_assign_course_student( $course_id ) ) {
                $class_limited = CoursePress_Data_Course::get_setting( $course_id, 'class_limited' );
                $class_limited = cp_is_true( $class_limited );
                $add_form_to_add_student = false;
                if ( $class_limited ) {
                    $class_size = (int) CoursePress_Data_Course::get_setting( $course_id, 'class_size' );
                    $total_items = count( $this->items );
                    if ( 0 === $class_size || $class_size > $total_items ) {
                        $add_form_to_add_student = true;
                    } else {
                        $add_form_to_add_student = false;
                        printf(
                            '<span>%s</span>',
                            __( 'You can not add a student, the class limit is reached.', 'coursepress' )
                        );
                    }
                } else {
                    $add_form_to_add_student = true;
                }
                if ( $add_form_to_add_student ) {
                    $name = 'student-add';
                    $id = 'student-add';
                    if ( apply_filters( 'coursepress_use_default_student_selector', false ) ) {
                        $user_selector = CoursePress_Helper_UI::get_user_dropdown(
                            $id,
                            $name,
                            array(
                                'placeholder' => __( 'Choose student...', 'coursepress' ),
                                'class' => 'chosen-select narrow',
                                'exclude' => $this->students,
                                'context' => 'students',
                            )
                        );
                    } else if ( apply_filters( 'coursepress_use_select2_student_selector', true ) ) {
                        $nonce_search = CoursePress_Admin_Students::get_search_nonce_name( $course_id );
                        $nonce_search = wp_create_nonce( $nonce_search );
                        $user_selector = sprintf(
                            '<select name="%s" id="%s" data-nonce="%s" data-nonce-search="%s"></select>',
                            $name,
                            $id,
                            esc_attr( $nonce ),
                            esc_attr( $nonce_search )
                        );
                    } else {
                        $user_selector = '<input type="text" id="' . $id .'" name="' . $name . '" placeholder="' . esc_attr__( 'Enter user ID', 'coursepress' ) . '" />';
                    }
                    $user_selector = apply_filters( 'coursepress_student_selector', $user_selector, $id, $name );
                    echo $user_selector;
                    printf(
                        ' <input type="button" class="add-new-student-button button" data-nonce="%s" value="%s" >',
                        esc_attr( $nonce ),
                        esc_attr__( 'Add Student', 'coursepress' )
                    );
                }
            }

            if ( CoursePress_Data_Capabilities::can_withdraw_students( $course_id ) ) {
    ?>
    <a class="withdraw-all-students" data-nonce="<?php echo $withdraw_nonce; ?>" href="#"><?php esc_html_e( 'Withdraw all students', 'coursepress' ); ?></a>
    <?php
            }
    ?>
    <br />
</div>
<?php

        }

    }

    public function no_items() {
        $course_id = (int) $_GET['id'];

        if ( CoursePress_Data_Capabilities::can_assign_course_student( $course_id ) || CoursePress_Data_Capabilities::can_invite_students( $course_id ) ) {
            esc_html_e( 'There are no students enrolled in this course. Add them below.', 'coursepress' );
        } else {
            esc_html_e( 'There are no students enrolled in this course.', 'coursepress' );
        }
    }

    /**
	 * Column contain number of certified students.
	 *
	 * @since 2.0.0
	 */
    public function column_certificates( $item_id ) {
        $item = $this->get_student( $item_id );
        if ( 'yes' == $item->data->certified ) {
            return sprintf( '<span class="cp-certified">%s</span>', esc_html__( 'Certified', 'coursepress' ) );
        }
        return '<span class="dashicons dashicons-no"></span>';
    }

    //Ajout EP CP 2 Formule

    protected function get_bulk_actions() {
        
        $actions = array();
        
        if ( CoursePress_Data_Capabilities::can_assign_course_student( $course_id ) || CoursePress_Data_Capabilities::can_invite_students( $course_id ) ) {
            $course_id = $this->course_id;
            $free_formule_name = CoursePress_Data_Course::get_setting( $course_id, 'second_formula_name');
            $paid_formule_name = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name'); 

            $actions = array(
                //'withdraw'    => __( 'Withdraw','coursepress' ),
                'free-formule' => sprintf(__('Swich Formula to: %s','coursepress'), $free_formule_name),
                'paid-formule' => sprintf(__('Swich Formula to: %s','coursepress'), $paid_formule_name),
                'custom_formule' => __('Swich Formula group','coursepress'),
            );
        }
        return $actions;
    }

    public function process_bulk_action() {  
        global $wpdb;
        
        $course_id = $this->course_id;

        switch ($this->current_action()) {
            case 'free-formule':
            case 'paid-formule':
            case 'custom_formule':
                if ($this->current_action() == 'free-formule') {
                    $formule = CoursePress_Data_Course::get_setting( $course_id, 'second_formula_tag');
                } else if ($this->current_action() == 'paid-formule') {
                    $formule = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag');
                } else {
                    $formule = $_REQUEST['group_formule']; 
                }
                if (isset($_REQUEST['users'])) {
                    if (is_array($_REQUEST['users'])){
                        foreach ($_REQUEST['users'] as $id) {
                            CoursePress_Data_Course::change_group($formule, $course_id, $id);
                        }
                    } else if (!empty($_REQUEST['users'])) {
                        $id = $_REQUEST['users'];
                        CoursePress_Data_Course::change_group($formule, $course_id, $id);         
                    }
                }
                break; 
            case 'withdraw':
                
                break;
            
        
                
        }
    }
  
    //Reprise de la fonction  bulk_action pour pouvoir changer action et action 2 en bulk_action et bulk_action2 car on utilise deja action pour obtenir la page. 
    //Si cela pose probleme dans les classes enfants de celle-ci il faudra modifier cela en JS
    
   protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();
			/**
			 * Filters the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
    
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) )
			return;

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="bulk_action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply', 'coursepress' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}
    
    
    //Reprise de la fonction  current_action pour pouvoir changer action et action 2 en bulk_action et bulk_action2 car on utilise deja action pour obtenir la page. 
    //Si cela pose probleme dans les classes enfants de celle-ci il faudra modifier process_bulk_action pour recuperer directement bulk_action et bulk_action2 au lieu d'utiliser current_action
    
    public function current_action() {
                if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
	                        return false;
	
                if ( isset( $_REQUEST['bulk_action'] ) && -1 != $_REQUEST['bulk_action'] )
                        return $_REQUEST['bulk_action'];

               if ( isset( $_REQUEST['bulk_action2'] ) && -1 != $_REQUEST['bulk_action2'] )
                        return $_REQUEST['bulk_action2'];

                return false;
        }
    
    
    
    // Fin Ajout EP CP 2 Formule


}
