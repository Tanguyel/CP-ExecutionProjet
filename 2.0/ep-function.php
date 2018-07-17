<?php

/******
Ficher avec les fonctions plugables specifique à EP CP


************/



//echo "EP function loaded";

add_filter('woocommerce_add_cart_item_data','epcp_add_cart_item_data',10,3);

/**
 * Add custom data to Cart (pour le group liée a la formule)
 * @param  [type] $cart_item_data [description]
 * @param  [type] $product_id     [description]
 * @param  [type] $variation_id   [description]
 * @return [type]                 [description]
 */
function epcp_add_cart_item_data($cart_item_data, $product_id, $variation_id)
{
    if(isset($_REQUEST['paid_name']))
    {
        $cart_item_data['paid_name'] = sanitize_text_field($_REQUEST['paid_name']);
    }
    if(isset($_REQUEST['paid_tag']))
    {
        $cart_item_data['paid_tag'] = sanitize_text_field($_REQUEST['paid_tag']);
    }
    

    return $cart_item_data;
}


add_filter('woocommerce_get_item_data','epcp_add_cart_item_meta',10,2);

/**
 * Display information as Meta on Cart page
 * @param  [type] $item_data [description]
 * @param  [type] $cart_item [description]
 * @return [type]            [description]
 */
function epcp_add_cart_item_meta($item_data, $cart_item)
{

    if(array_key_exists('paid_name', $cart_item))
    {
        $custom_details = $cart_item['paid_name'];

        $item_data[] = array(
            'key'   => 'Formule',
            'value' => $custom_details
        );
    }
    
    return $item_data;
}

add_action( 'woocommerce_checkout_create_order_line_item', 'epcp_add_custom_order_line_item_meta',10,4 );

function epcp_add_custom_order_line_item_meta($item, $cart_item_key, $values, $order)
{

    if(array_key_exists('paid_name', $values))
    {
        $item->add_meta_data( __( 'Formule', 'coursepress' ),$values['paid_name']);
    }
    
    if(array_key_exists('paid_tag', $values))
    {
        $item->add_meta_data('_paid_tag',$values['paid_tag']);
    }
}

add_filter('coursepress_localize_object', 'epcp_add_info_in_js_cp', 10 ,2);

function epcp_add_info_in_js_cp($localize_array)
{
    $course_id = CoursePress_Helper_Utility::the_course( true );
    $localize_array['current_course_has_second_formula'] = CoursePress_Data_Course::has_second_formula( $course_id )? 'yes':'no';
    return $localize_array;
}


// Nouveaux Widget

function register_ep_cp_widget() {
    register_widget( 'CoursePress_Widget_Help' );
    register_widget( 'CoursePress_Widget_Upgrade' );
}
add_action( 'widgets_init', 'register_ep_cp_widget' );




class CoursePress_Widget_Help extends WP_Widget {
/*	public static function init() {
		add_action( 'widgets_init', array( 'CoursePress_Widget_Help', 'register' ) );
	}

	public static function register() {
		register_widget( 'CoursePress_Widget_Help' );
	}
*/
	public function __construct() {
		$widget_ops = array(
			'classname' => 'cp_course_help',
			'description' => __( 'A small space giving instruction to get help', 'coursepress' ),
		);

		parent::__construct( 'CP_Widget_Help', __( 'Course Help', 'coursepress' ), $widget_ops );

	}

	public function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = esc_attr( $instance['title'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'coursepress' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Course Help', 'coursepress' ) : $instance['title'], $instance, $this->id_base );
        $course_id = CoursePress_Helper_Utility::the_course( true );
        $course_id = (int) $course_id;
        $course_base_url = CoursePress_Data_Course::get_course_url( $course_id );

        $course_is_paid = CoursePress_Data_Course::is_paid_course( $course_id );
        $has_second_formula = CoursePress_Data_Course::has_second_formula( $course_id );
        $free_group_tag = CoursePress_Data_Course::group_tag( $course_id, 'free' );
        if ( true==$course_is_paid && true == $has_second_formula) {
            $student_id = get_current_user_id();
            if (empty($student_id)) {
                $group = '';
            } else {
               $group = get_user_option( 'enrolled_course_group_' . $course_id, $student_id ); 
            }
        } else {
            $group = '';
        }
        
		echo $before_widget;

		if ( $title && ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		?>
		<ul>
            <li>N'hésitez pas à poser une question sur le <a href="<?php echo esc_url_raw( $course_base_url . CoursePress_Core::get_slug( 'discussion' ) ); ?>"><?php echo esc_html__( 'Discussions', 'coursepress' ); ?></a></li>
<?php       if ($group != $free_group_tag && $group != 'free' && $group != '') { ?>
            <li>Vous pouvez aussi <a href="http://www.executionprojet.fr/contact/">contacter le formateur</a></li>
<?php       } ?>
		</ul>
		<?php
		echo $after_widget;
	}
}

class CoursePress_Widget_Upgrade extends WP_Widget {
/*	public static function init() {
		add_action( 'widgets_init', array( 'CoursePress_Widget_Help', 'register' ) );
	}

	public static function register() {
		register_widget( 'CoursePress_Widget_Help' );
	}
*/
	public function __construct() {
		$widget_ops = array(
			'classname' => 'cp_course_upgrade',
			'description' => __( 'An invitation to upgrade for the paid version of the course if the couse has 2 formula and the student is in the free formula', 'coursepress' ),
		);

		parent::__construct( 'CP_Widget_Upgrade', __( 'Course Upgrade', 'coursepress' ), $widget_ops );

	}

	public function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = esc_attr( $instance['title'] );
        $formule_pres = esc_attr( $instance['formule-pres'] );
        $formule_link_text = esc_attr( $instance['formule-link-text'] );
        $contact_pres = esc_attr( $instance['contact-pres'] );
        $contact_link_text = esc_attr( $instance['contact-link-text'] );
        
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'coursepress' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>
        <p>
			<label for="<?php echo $this->get_field_id( 'formule-pres' ); ?>"><?php _e( 'Presentation texte for Paid Formula:', 'coursepress' ); ?></label>
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'formule-pres' ); ?>" name="<?php echo $this->get_field_name( 'formule-pres' ); ?>" rows="12"><?php echo $formule_pres; ?></textarea>
		</p>
        <p>
			<label for="<?php echo $this->get_field_id( 'formule-link-text' ); ?>"><?php _e( 'Texte for the link adding the course to the cart (upgrade)', 'coursepress' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'formule-link-text' ); ?>" name="<?php echo $this->get_field_name( 'formule-link-text' ); ?>" type="text" value="<?php echo $formule_link_text; ?>"/>
            <legend><?php _e( 'Use [FORMULE] to insert the (paid) Formule Name', 'coursepress' ); ?></legend>
		</p>
        <p>
			<label for="<?php echo $this->get_field_id( 'contact-pres' ); ?>"><?php _e( 'Presentation texte for the contact invite :', 'coursepress' ); ?></label>
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'contact-pres' ); ?>" name="<?php echo $this->get_field_name( 'contact-pres' ); ?>" rows="5"><?php echo $contact_pres; ?></textarea>
		</p>
        <p>
			<label for="<?php echo $this->get_field_id( 'contact-link-text' ); ?>"><?php _e( 'Texte for the contact link:', 'coursepress' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'contact-link-text' ); ?>" name="<?php echo $this->get_field_name( 'contact-link-text' ); ?>" type="text" value="<?php echo $contact_link_text; ?>"/>
		</p>

		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
        $instance['formule-pres'] = $new_instance['formule-pres'];
        $instance['formule-link-text'] = strip_tags( $new_instance['formule-link-text'] );
        $instance['contact-pres'] = $new_instance['contact-pres'];
        $instance['contact-link-text'] = strip_tags( $new_instance['contact-link-text'] );

		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );

        $course_id = CoursePress_Helper_Utility::the_course( true );
        $course_id = (int) $course_id;
        
        $course_is_paid = CoursePress_Data_Course::is_paid_course( $course_id );
        $has_second_formula = CoursePress_Data_Course::has_second_formula( $course_id );
        
        if ( true==$course_is_paid && true == $has_second_formula) {
            $student_id = get_current_user_id();
            if (empty($student_id)) {
                return;
            }
            $group = get_user_option( 'enrolled_course_group_' . $course_id, $student_id );
            $free_group_tag = CoursePress_Data_Course::group_tag( $course_id, 'free' );
            
            
            
            if ($group == $free_group_tag || $group == 'free' || $group == '') {
                $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Course Upgrade', 'coursepress' ) : $instance['title'], $instance, $this->id_base );
                $formule_pres = html_entity_decode(esc_attr( $instance['formule-pres'] ));
                $formule_link_text = esc_attr( $instance['formule-link-text'] );
                $formule_link_text = str_replace('[FORMULE]', CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name'), $formule_link_text );
                $contact_pres = html_entity_decode(esc_attr( $instance['contact-pres'] ));
                $contact_link_text = esc_attr( $instance['contact-link-text'] );
                                
                echo $before_widget;

                if ( $title && ! empty( $title ) ) {
                    echo $before_title . $title . $after_title;
                }
                echo '<div>';
                if ($formule_pres != '') {
                    echo $formule_pres;
                    
                    $upgrade_link = add_to_cart_button_by_course_id( $course_id, $formule_link_text, $formule_link_text );
                    
                    if ($upgrade_link == '') {
                        $upgrade_link = '<a class="apply-button" href="' . site_url('/contact/' ) . '">' . $contact_link_text . '</a>';
                    }
                    
                    echo $upgrade_link;
                }
                
                if ($contact_pres != '') {
                    echo '<p>' . $contact_pres . '</p>';
                    echo '<a class="apply-button" href="' . site_url('/contact/' ) . '">' . $contact_link_text . '</a>';
                }
                echo '</div>';
                echo $after_widget;
            }
        }
	}
}


/**
	 * Fonction pour afficher le bouton permetant d'ajouter au panier (ou de l'afficher) le cours
	 *
	 * @since 
	 *
	 * @param integer $course_id course to check
     * @param string $show_cart_text text to show when the course is already in the cart
     * @param string $add_to_cart_text text to show to add the course in the cart
	 *
	 * @return string html with "add to cart" button
	 */

function add_to_cart_button_by_course_id( $course_id, $show_cart_text = '', $add_to_cart_text = '', $type = 'form', $class='apply-button' ) {
    $product_id = CoursePress_Data_Course::get_setting( $course_id, 'woo/product_id', false );
    if ( empty( $product_id ) ) {
        return '';
    }

    if ( $show_cart_text == '' ) {
        $show_cart_text = esc_html__( 'Show cart', 'coursepress' );
    }
    
    $cart_data = WC()->cart->get_cart();
    foreach ( $cart_data as $cart_item_key => $values ) {
        $_product = $values['data'];
        if ( $product_id == $_product->get_id() ) {
            //$content = __( 'This course is already in the cart.', 'coursepress' );
            global $woocommerce;
            $content .= sprintf(
                ' <a href="%s" class="single_show_cart_button %s">%s</a>',
                esc_url( wc_get_cart_url() ),
                $class,
                $show_cart_text
            );
            return $content;
        }
    }
    $product = new WC_Product( $product_id );
    /**
     * no or invalid product? any doubts?
     */
    if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
        return '';
    }

    if ( $add_to_cart_text == '' ) {
        $add_to_cart_text = esc_html( $product->single_add_to_cart_text() );
    }
    
    
    if ($type == 'form')  {
        ob_start();
        do_action( 'woocommerce_before_add_to_cart_form' ); ?>
        <form class="cart" method="post" enctype='multipart/form-data' action="<?php echo esc_url( wc_get_cart_url() ); ?>">
        <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->id ); ?>" />
        <?php   if (cp_is_true(CoursePress_Data_Course::get_setting( $course_id, 'second_formula_course',false))) {
            $paid_name = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name');
            $paid_tag = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag'); ?>
            <input type="hidden" name="paid_name" value="<?php echo $paid_name; ?>"/> 
            <input type="hidden" name="paid_tag" value="<?php echo $paid_tag; ?>"/>
        <?php   } else { ?>
            <input type="hidden" name="paid_tag" value="paid"/> 
        <?php   } ?>
        <button type="submit" class="single_add_to_cart_button button alt <?php echo $class  ?>">
            <?php echo $add_to_cart_text  ?>
        </button>  
        <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
        </form>
        <?php
        do_action( 'woocommerce_after_add_to_cart_form' );
        $content = ob_get_contents();
        ob_end_clean();
        //$content = $type;
    } else {
        
        if (cp_is_true(CoursePress_Data_Course::get_setting( $course_id, 'second_formula_course',false))) {
            $paid_name = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name');
            $paid_tag = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag');
            $paid_name_part = '&paid_name=' . $paid_name;
        } else { 
            $paid_tag = 'paid';
            $paid_name_part ='';
        } 
        $paid_tag_part = '&paid_tag=' . $paid_tag;
        
        global $woocommerce;
        $url = esc_url( wc_get_cart_url()) . '?add-to-cart=' . esc_attr( $product->id ) . $paid_name_part . $paid_tag_part;
        
        $content = sprintf(
                    ' <a href="%s" class="single_add_to_cart_button %s">%s</a>',
                    $url,
                    $class,
                    $add_to_cart_text
        );
        
    }
        
    return $content;
}



/**********************************************************************

                        Nouveaux Shortcode
                        
***********************************************************************/

add_shortcode('ep-addtocart', 'ep_addtocart_button' );
add_shortcode('ep-joinfree', 'ep_joinfree_button' );
add_shortcode('ep-showfree', 'ep_showfree' );    
add_shortcode('ep-showpaid', 'ep_showpaid' );
add_shortcode('ep-showformule', 'ep_showformule' );
add_shortcode('ep-modulerep', 'ep_module_rep' );

function ep_addtocart_button ($atts) {
    
    extract( shortcode_atts( array(
        'course_id' => CoursePress_Helper_Utility::the_course( true ),
        'show_cart_text' => __( 'Passez à la formule certifié', 'coursepress' ),
        'add_to_cart_text' => __( 'Passez à la formule certifié', 'coursepress' ),
        'class' => '',
    ), $atts, 'ep_addtocart_button' ) );
    
    $course_id = (int) $course_id;
		if ( empty( $course_id ) ) {
			return '';
		}
    
    /**
     * check course
     */
    $is_course = CoursePress_Data_Course::is_course( $course_id );
    if ( ! $is_course ) {
        return '';
    }
    
    
    
    $show_cart_text = str_replace('[FORMULE]', CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name'), $show_cart_text );
    $add_to_cart_text = str_replace('[FORMULE]', CoursePress_Data_Course::get_setting( $course_id, 'base_formula_name'), $add_to_cart_text );

    return add_to_cart_button_by_course_id( $course_id, $show_cart_text, $add_to_cart_text, 'link', $class );
    
}

function ep_joinfree_button ($atts) {
    
    extract( shortcode_atts( array(
        'course_id' => CoursePress_Helper_Utility::the_course( true ),
        'join_text' => __( 'S inscrire', 'coursepress' ),
        'class' => '',
    ), $atts, 'ep_addtocart_button' ) );
    
    $course_id = (int) $course_id;
		if ( empty( $course_id ) ) {
			return '';
		}
    
    /**
     * check course
     */
    $is_course = CoursePress_Data_Course::is_course( $course_id );
    if ( ! $is_course ) {
        return '';
    }
    
    $course_url = CoursePress_Data_Course::get_course_url( $course_id );
    
    $general_settings = CoursePress_Core::get_setting( 'general' );
    $is_custom_login = cp_is_true( $general_settings['use_custom_login'] );
    
    
    if ( is_user_logged_in() ) {
			$student_id = get_current_user_id();
			$student_enrolled = CoursePress_Data_Course::student_enrolled( $student_id, $course_id );
			if(true == $student_enrolled ) {
                $button = sprintf( '<a href="%s" class="%s">%s</a>', $course_url, $class, $join_text );
            } else {
                $course_url = add_query_arg(
                    array(
                        'action' => 'enroll_student',
                        '_wpnonce' => wp_create_nonce( 'enroll_student' ),
                    ),
                    $course_url
                );
                $button = sprintf( '<a href="%s" class="%s">%s</a>', $course_url, $class, $join_text );
            }
		} else {
			$course_url = add_query_arg(
				array(
					'action' => 'enroll_student',
					'_wpnonce' => wp_create_nonce( 'enroll_student' ),
				),
				$course_url
			);
			if ( false === $is_custom_login ) {
				$signup_url = wp_login_url( $course_url );
			} else {
				$signup_url = CoursePress_Core::get_slug( 'signup/', true );
				$signup_url = add_query_arg(
					array(
						'redirect_url' => urlencode( $course_url ),
						'_wpnonce' => wp_create_nonce( 'redirect_to' ),
					),
					$signup_url
				);
			}
            
            $url = esc_url( $signup_url . '?course_id=' . $course_id );
            $format = '<a href="%s" class="%s">%s</a>';
            $button = sprintf( $format, $url, $class, $join_text );
		}


    return $button;
    
}


function ep_showfree ($atts, $content = '') {
    extract( shortcode_atts( array(
        'course_id' => CoursePress_Helper_Utility::the_course( true ),
    ), $atts, 'ep_showfree' ) );
    
    $content = do_shortcode($content);
    
    $course_id = (int) $course_id;

    $course_is_paid = CoursePress_Data_Course::is_paid_course( $course_id );
    $has_second_formula = CoursePress_Data_Course::has_second_formula( $course_id );

    if ( true==$course_is_paid ) {
        if ( true == $has_second_formula) {
            $student_id = get_current_user_id();
            if (empty($student_id)) {
                return;
            }
            $user_group = get_user_option( 'enrolled_course_group_' . $course_id, $student_id );
            $free_group_tag = CoursePress_Data_Course::group_tag( $course_id, 'free' );



            if ($user_group == $free_group_tag || $user_group == 'free' || $user_group == '') {
                return $content;
            } else {
                return ;
            }
        } else {
            return ; 
        }
    } else {
        return $content;
    }
}

function ep_showpaid ($atts, $content = '') {
    extract( shortcode_atts( array(
        'course_id' => CoursePress_Helper_Utility::the_course( true ),
    ), $atts, 'ep_showpaid' ) );
    
    $content = do_shortcode($content);

    $course_id = (int) $course_id;

    $course_is_paid = CoursePress_Data_Course::is_paid_course( $course_id );
    $has_second_formula = CoursePress_Data_Course::has_second_formula( $course_id );

    if ( true==$course_is_paid ) {
        if ( true == $has_second_formula) {
            $student_id = get_current_user_id();
            if (empty($student_id)) {
                return;
            }
            $user_group = get_user_option( 'enrolled_course_group_' . $course_id, $student_id );
            $paid_group_tag = CoursePress_Data_Course::get_setting( $course_id, 'base_formula_tag' );

            if (strpos( $user_group, $paid_group_tag )!==false || strpos( $user_group, 'paid' )!==false) {
                return $content;
            } else {
                return ;
            }
        } else {
            return $content; 
        }
    } else {
        return ;
    }
}

function ep_showformule ($atts, $content = '') {
    extract( shortcode_atts( array(
        'formule_tag'=> 'test',
        'course_id' => CoursePress_Helper_Utility::the_course( true ),
    ), $atts, 'ep_showformule' ) );
    
    $content = do_shortcode($content);
    
    $course_id = (int) $course_id;

    $user_group = get_user_option( 'enrolled_course_group_' . $course_id, $student_id );
        
    if (strpos( $user_group, $formule_tag )!==false ) {
        return $content;
    } else {
        return ;
    }
}

function ep_module_rep ($atts) {
    extract( shortcode_atts( array(
        'module_id' => '',
        'student_id' => get_current_user_id(),
    ), $atts, 'ep_module_rep' ) );
    
    $unit_id = get_post_field( 'post_parent', $module_id );
    $course_id = get_post_field( 'post_parent', $unit_id );

    $reponse = CoursePress_Data_Student::get_response( $student_id, $course_id, $unit_id, $module_id );
    $reponse = $reponse['response'];
    return $reponse;
}