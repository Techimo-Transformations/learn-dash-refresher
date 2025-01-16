<?php

if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Cronjob')) {

    class LearnDash_Refresher_Cronjob {

        public function __construct() {
            /**
             * Cronjob to check if user reached to o
             */
            add_action('ldr_check_refresher_event', array($this, 'ld_refresher_logic_callback'));
        }

        public function ld_refresher_logic_callback() {
            set_time_limit(0);

            global $wpdb;

            //get all courses in system
            $courses = new WP_Query(array('post_type' => 'sfwd-courses', 'posts_per_page' => -1));

            //get all users in system
            $users = $wpdb->get_results("SELECT * FROM $wpdb->users");

            //get admin email
            $admin_email = get_option('admin_email');

            foreach ($users as $u) {//loop over users and check courses refresher
                foreach ($courses->get_posts() as $course) {
                    $courseMetasArr = get_post_meta($course->ID);
                    $expPeriod = isset($courseMetasArr['expiration_period'][0]) ? $courseMetasArr['expiration_period'][0] : "";
                    $refPeriod = isset($courseMetasArr['refresher_period'][0]) ? $courseMetasArr['refresher_period'][0] : "";
                    $courseFullReset = isset($courseMetasArr['course_full_reset'][0]) ? $courseMetasArr['course_full_reset'][0] : "";
                    $isCourseRefreshed = isset($courseMetasArr['is_course_refreshed'][0]) ? $courseMetasArr['is_course_refreshed'][0] : "";

                    if ($isCourseRefreshed == "on") {//check that course support refresh
                        //check that user has access for course (assigned to him)
                        if ($expPeriod && $refPeriod && sfwd_lms_has_access($course->ID, $u->ID)) {
                            //get stored data for course in user meta 
                            $courseInfo = get_user_meta($u->ID, 'ldm_course_info_' . $course->ID, true);

                            if ($courseInfo && is_array($courseInfo)) { //loop over course history
                                foreach ($courseInfo as $key => $value) {
                                    if (next($courseInfo) === false) { //????
                                        if ($value['completion_date']) {
                                            $completionDate = new DateTime('@' . $value['completion_date']);
                                            $overdueDate = clone $completionDate;
                                            $overdueDate->modify('+' . $expPeriod . ' months');
                                            $requiredDate = clone $overdueDate;
                                            $requiredDate->modify('-' . $refPeriod . ' days');

                                            //if it is refresher required date then send email to user then clear all user progress in course according to reset type
                                            if (strtotime($requiredDate->format('Y-m-d')) < time() && $value['is_email_sent'] == false) {
                                                
                                                ldr_send_refresher_email($u, $course);

                                                $value['is_email_sent'] = true;
                                                $courseInfo[$key] = $value;

                                                //clear learndash meta about course completion
                                                update_user_meta($u->ID, 'course_completed_' . $course->ID, '');
                                                if ($courseFullReset != 'course_reset_exam_only') { //clear all course progress
                                                    learndash_delete_course_progress($course->ID, $u->ID);
                                                    ldr_remove_bookmarked_tincanny($u->ID, $course->ID);
                                                } else {//clear all course progress by quizes
                                                    ldr_delete_course_progress_exam_only($course->ID, $u->ID);
                                                    $course_progress = get_user_meta($u->ID, '_sfwd-course_progress', true);
                                                    if ($course_progress && is_array($course_progress)) {
                                                        foreach ($course_progress as $keyprogress => $valueprogress) {

                                                            if ($keyprogress == $course->ID) {
                                                                $valueprogress['completed'] = $valueprogress['completed'] - 1;
                                                                $course_progress[$keyprogress] = $valueprogress;
                                                            }
                                                        }
                                                        update_user_meta($u->ID, '_sfwd-course_progress', $course_progress);
                                                    }
                                                }

                                                //create new array element for the next refreshment
                                                $unique = uniqid();
                                                $courseInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);
                                                update_user_meta($u->ID, 'ldm_course_info_' . $course->ID, $courseInfo);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

    }

    $LearnDash_Refresher_Cronjob = new LearnDash_Refresher_Cronjob();
}