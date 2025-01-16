<?php

if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Learndash_Hooks')) {

    class LearnDash_Refresher_Learndash_Hooks {

        public function __construct() {
            //add_action('learndash_course_completed', array($this, 'add_course_completion_time_callback'), 11, 1);
            add_action('learndash_before_course_completed', array($this, 'add_course_completion_time_callback'), 11, 1);
            add_filter('learndash_show_user_course_complete_options', array($this, 'allow_user_course_completion_callback'), 20, 2);
            add_filter('learndash_courseinfo', array($this, 'certificate_time_callback'), 20, 2);
            
            // override template redirect for certificate functionality
           add_action('template_redirect', array($this, 'certificate_redirect_override'), 5);
        }

        function add_course_completion_time_callback($data) {
            $user_id = $data['user']->ID;
            $course_id = $data['course']->ID;
            
            $time = time();
            
            $last = null;
            $array = null;
            $courseInfo = get_user_meta($user_id, 'ldm_course_info_' . $course_id, true);

            if ($courseInfo && is_array($courseInfo)) {
                foreach ($courseInfo as $key => $value) {
                    if (next($courseInfo) === false) {
                        $last = $key;
                        $array = $value;
                    }
                }
            } else {
                $unique = uniqid();
                $courseInfo = array();
                $courseInfo[$unique] = array('completion_date' => $time, 'is_email_sent' => false);
                update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
            }
            if ($last) {
                if ($array['completion_date'] == '') {
                    $array['completion_date'] = $time;
                    $courseInfo[$last] = $array;
                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
                }
            }
            
            $meta_key = 'ldm_course_completed_' . $course_id;
            
            $course_completed = get_user_meta($user_id, $meta_key, true);
            if ($course_completed && is_array($course_completed)) {
                if($last){
                    $course_completed[$last] = $time;
                }
            } else {
                $course_completed = array();
                $course_completed[$unique] = $time;
            }

            update_user_meta($user_id, $meta_key, $course_completed);
        }

        function allow_user_course_completion_callback($show_options, $user_id) {
            global $pagenow;

            if ($show_options == false) {
                if ($pagenow == 'admin.php') {
                    if (( isset($_GET['page']) ) && ( $_GET['page'] == 'junior_edit_user' )) {
                        if (( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() )) {
                            $show_options = true;
                        }
                    }
                }
            }

            return $show_options;
        }

        function certificate_time_callback($time_formated, $shortcode_atts) {
            if (isset($shortcode_atts['show']) && $shortcode_atts['show'] == 'completed_on') {
                $user_history = get_user_meta($shortcode_atts['user_id'], 'ldm_course_completed_'.$shortcode_atts['course_id'], true);
                if(isset($_GET['cert_index'])){
                    $index = $_GET['cert_index'];
                    if(isset($user_history[$index])){
                        $time_formated = date_i18n($shortcode_atts['format'], $user_history[$index]);
                    }
                }
            }

            return $time_formated;
        }
        
        public function certificate_redirect_override() {
            global $post;
            
            if ( empty( $post ) ) {
                return;
            }

            if ( ! ( $post instanceof WP_Post ) ) {
		        return;
            }

            if ( get_query_var( 'post_type' ) ) {
		        $post_type = get_query_var( 'post_type' );
            } else {
		        if ( ! empty( $post ) ) {
                    $post_type = $post->post_type;
		        }
            }

            if ( empty( $post_type ) ) {
		        return;
            }
            
            if ('sfwd-certificates' === $post_type) {
		        if ( is_user_logged_in() ) {
                    if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			            $course_id = intval( $_GET['course_id'] );

			            if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
                            $cert_user_id = intval( $_GET['user'] );
			            } else {
                            $cert_user_id = get_current_user_id();
			            }

			            $view_user_id = get_current_user_id();

			            if ( ( isset( $_GET['cert-nonce'] ) ) && ( ! empty( $_GET['cert-nonce'] ) ) ) {
                            if ( wp_verify_nonce( esc_attr( $_GET['cert-nonce'] ), $course_id . $cert_user_id . $view_user_id ) ) {
                                if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( intval( $cert_user_id ) !== intval( $view_user_id ) ) ) {
                                    wp_set_current_user( $cert_user_id );
                                }

                                $certificate_id = learndash_get_setting( $course_id, 'certificate' );
                                
                                $cert_args = array(
                                    'cert_id' => $certificate_id,   // The certificate Post ID.
			                        'post_id' => $course_id,     // The Course/Quiz Post ID.
			                        'user_id' => $cert_user_id,	    // The User ID for the Certificate
		                        );

				                /**
				                * Include library to generate PDF
				                */
                                require_once LEARNDASH_LMS_PLUGIN_DIR.'includes/ld-convert-post-pdf.php';
                                learndash_certificate_post_shortcode($cert_args);
								
				                die();
                            }
			            }
                    }
		        }
            }
        }

    }

    $LearnDash_Refresher_Learndash_Hooks = new LearnDash_Refresher_Learndash_Hooks();
}