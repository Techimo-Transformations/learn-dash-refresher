<?php

if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Ajaxs')) {

    class LearnDash_Refresher_Ajaxs {

        public function __construct() {
            
            // get jobs and courses based on group
            add_action('wp_ajax_get_group_jobs', array($this, 'get_group_jobs_callback'));
            
            // get courses based on job
            add_action('wp_ajax_get_job_courses', array($this, 'get_job_courses_callback'));
            
            // change user course status
            add_action('wp_ajax_change_user_course_status', array($this, 'change_user_course_status_callback'));
            
            // change user course date
            add_action('wp_ajax_change_user_complete_date', array($this, 'change_user_complete_date_callback'));

            //Get Courses Associated To Specific Group
            add_action('wp_ajax_get_group_jobs_courses', array($this, 'get_group_jobs_courses_callback'));

            //Save New Courses Template 
            add_action('wp_ajax_save_course_template', array($this, 'save_course_template_callback'));

            //Load Course Template
            add_action('wp_ajax_load_course_template', array($this, 'load_course_template_callback'));

            //Load Courses by Group
            add_action('wp_ajax_get_refresher_group_courses', array($this, 'get_refresher_group_courses_callback'));

            //Get Course info dynamically     
            add_action('wp_ajax_get_course_info_dynamically', array($this, 'get_course_info_dynamically_callback'));

            //Change Course date in history
            add_action('wp_ajax_change_user_history_date', array($this, 'change_user_history_date_callback'));

            //Delete Course date in history
            add_action('wp_ajax_delete_user_history_date', array($this, 'delete_user_history_date_callback'));
            
            // migration action when moved to new site
            add_action('wp_ajax_migrate_to_new_site', array($this, 'migrate_to_new_site_callback'));
            
            // migration action to V3
            add_action('wp_ajax_migrate_to_version_three', array($this, 'migrate_to_version_three_callback'));

            //Email Users about Their Renewals Course date in history
            add_action('wp_ajax_email_user_about_courses_todo', array($this, 'email_user_about_courses_todo_callback'));

            //Upload a course document functionality
            add_action('wp_ajax_upload_course_document', array($this, 'upload_course_document_callback'));

        }
        
        public function get_group_jobs_callback() {
            $group = $_POST['group'];
            $selectedJob = $_POST['job'];
            $selectedCourse = $_POST['course'];
            $jobHTML = '';
            $courseHTML = '';
            $showjob = false;
            $showstatus = false;

            if($group){
                $courseHTML .= '<option value="">' . __('All Courses', 'ld_refresher') . '</option>';
                $jobsIds = get_post_meta($group, 'ldmv2_jobs', true);

                if ($jobsIds != null && count($jobsIds) > 0) {
                    $jobHTML .= '<option value="">' . __('All Jobs', 'ld_refresher') . '</option>';
                    $jobs = get_terms(array(
                        'taxonomy' => 'ld_course_category',
                        'hide_empty' => true,
                        'include' => $jobsIds
                    ));

                    foreach ($jobs as $job) {
                        $jobHTML .= '<option value="' . $job->term_id . '"';
                        if ($job->term_id == $selectedJob) {
                            $jobHTML .= ' selected';
                        }
                        $jobHTML .= '>' . $job->name . '</option>';
                    }
                    
                    if($selectedJob){
                        $coursesjobs = $selectedJob;
                    }else{
                        $coursesjobs = $jobsIds;
                    }
                    
                    $courses = get_posts(array(
                        'post_type' => 'sfwd-courses',
                        'showposts' => -1,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'ld_course_category',
                                'field' => 'term_id',
                                'terms' => $coursesjobs
                            ),
                        'orderby' => 'title',
                        'order' => 'ASC'
                        )
                    ));
                    foreach($courses as $course){
                        $courseHTML .= '<option value="'.$course->ID.'"';
                        if ($course->ID == $selectedCourse) {
                            $courseHTML .= ' selected';
                            $showstatus = true;
                        }
                        $courseHTML .= '>'.$course->post_title.'</option>';
                    }
                } else {
                    $jobHTML .= '<option value="">' . __('All Jobs', 'ld_refresher') . '</option>';
                }
                $showjob = true;
            }else{
                $courses = array();
                $currentUser = wp_get_current_user();
                if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {
                    $groupIds = learndash_get_administrators_group_ids($currentUser->ID);
                    $courses = ldr_get_leader_courses($groupIds);
                }else{
                    $course_args = array(
                        'post_type' => 'sfwd-courses',
                        'showposts' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    );
                    $courses = get_posts($course_args);
                }
                foreach($courses as $course){
                    $courseHTML .= '<option value="'.$course->ID.'"';
                    if ($course->ID == $selectedCourse) {
                        $courseHTML .= ' selected';
                    }
                    $courseHTML .= '>'.$course->post_title.'</option>';
                } 
            }

            print_r(json_encode(array('status' => true, 'jobHTML' => $jobHTML, 'courseHTML' => $courseHTML, 'showjob' => $showjob, 'showstatus' => $showstatus)));

            wp_die(); // this is required to terminate immediately and return a proper response
        }
        
        public function get_job_courses_callback(){
            $group = $_POST['group'];
            $job = $_POST['job'];
            $courseHTML = '<option value="">' . __('All Courses', 'ld_refresher') . '</option>';
            
            if($job){
                $courses = get_posts(array(
                    'post_type' => 'sfwd-courses',
                    'showposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'ld_course_category',
                            'field' => 'term_id',
                            'terms' => $job
                        )
                    ),
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                foreach($courses as $course){
                    $courseHTML .= '<option value="'.$course->ID.'">'.$course->post_title.'</option>';
                }
            }else{
                if($group){
                    $jobsIds = get_post_meta($group, 'ldmv2_jobs', true);

                    if ($jobsIds != null && count($jobsIds) > 0) {
                        $courses = get_posts(array(
                            'post_type' => 'sfwd-courses',
                            'showposts' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'ld_course_category',
                                    'field' => 'term_id',
                                    'terms' => $jobsIds
                                )
                            ),
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ));
                        foreach($courses as $course){
                            $courseHTML .= '<option value="'.$course->ID.'">'.$course->post_title.'</option>';
                        }
                    }
                }
            }
            
            print_r(json_encode(array('status' => true, 'courseHTML' => $courseHTML)));

            wp_die(); // this is required to terminate immediately and return a proper response
        }
        
        public function change_user_complete_date_callback(){
            $userId = $_POST['user_id'];
            $courseId = $_POST['course_id'];
            $date = $_POST['date'];
            $newTime = strtotime($date);
            $oldTime = get_user_meta($userId, 'course_completed_'.$courseId, true);
            $currentUser = wp_get_current_user();

            // need to update 3 metas

            // 1
            update_user_meta($userId, 'course_completed_'.$courseId, $newTime);

            // 2
            $course_completed = get_user_meta($userId, 'ldm_course_completed_'.$courseId, true);

            if (($key = array_search($oldTime, $course_completed)) !== false) {
                $course_completed[$key] = $newTime;

                update_user_meta($userId, 'ldm_course_completed_'.$courseId, $course_completed);
            }

            // 3
            $course_info = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
            if($key && isset($course_info[$key]['completion_date'])){
                $course_info[$key]['completion_date'] = $newTime;
                update_user_meta($userId, 'ldm_course_info_' . $courseId, $course_info);
            }
            
            // check if new completion date is refresher required or overdue
            $this->ldr_check_if_course_needs_refresher($userId, $courseId);
            
            $data = ldr_get_cell_data($userId, $courseId, true, $currentUser, 'yes');

            //print_r(json_encode(array('status' => true, 'html' => date('d-m-Y', $newTime))));
            print_r(json_encode(array('status' => true, 'html' => $data['html'])));

            wp_die(); // this is required to terminate immediately and return a proper response
        }

        /**
         * rf@objects
         * wp ajax to change user course status
         */
       function change_user_course_status_callback() {
           $user_id = $_POST['user_id'];
           $course_id = $_POST['course_id'];
           $new_status = $_POST['new_status'];
           $date = $_POST['date'];
           $currentUser = wp_get_current_user();
           $check = true;


             /* //new status Submit Document added
             if ($new_status == __('Submit Document', 'ld_refresher')) {
             $sub_doc = get_user_meta( $user_id, '_ldr_sub_doc_' . $course_id, true);
                //if user meta doesnt exist create it and set it to yes.
                if ($sub_doc == null){
                    add_user_meta( $user_id, '_ldr_sub_doc_' . $course_id, 'yes');
                }
                else{
                    update_user_meta( $user_id, '_ldr_sub_doc_' . $course_id, 'yes' );
                } 

            $data = ldr_get_cell_data($user_id, $course_id, true, $currentUser, 'yes');

            print_r(json_encode(array('status' => true, 'html' => $data['html'])));
            wp_die();
            } */  
           
           if ($new_status == __('Not Enrolled', 'ld_refresher')) {
               $this->ldr_remove_course_data($user_id, $course_id);
               //remove submit doc status if set
               //    $this->ldr_remove_submit_doc_status($user_id, $course_id);
               ld_update_course_access($user_id, $course_id, true);
               
               $data = ldr_get_cell_data($user_id, $course_id, true, $currentUser, 'yes');

               print_r(json_encode(array('status' => true, 'html' => $data['html'])));
               wp_die();
           }
           
           if($new_status == __('Not Started', 'learndash')){
               //remove submit doc status if set
            //    $this->ldr_remove_submit_doc_status($user_id, $course_id);
               // from "Not Enrolled" to "Not Started"
               if (!sfwd_lms_has_access($course_id, $user_id)) {
                    ld_update_course_access($user_id, $course_id);
               }else{
               // from anything to "Not Started" (need to remove history)
                    $this->ldr_remove_course_data($user_id, $course_id);
               }
               $data = ldr_get_cell_data($user_id, $course_id, true, $currentUser, 'yes');

               print_r(json_encode(array('status' => true, 'html' => $data['html'])));
               wp_die();
           }
           
           if(in_array($new_status, [__('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher')])){
               $expPeriod = get_post_meta($course_id, 'expiration_period', true);
               $refPeriod = get_post_meta($course_id, 'refresher_period', true);
               
               if(!($expPeriod && $refPeriod)){
                   $check = false;
               }
           }
           if($check){
                if (!sfwd_lms_has_access($course_id, $user_id)) {
                    ld_update_course_access($user_id, $course_id);
                }
                if (sfwd_lms_has_access($course_id, $user_id)) {
                    $status = learndash_course_status($course_id, $user_id);
                    if ($new_status == __('In Progress', 'learndash')) {
                        //remove submit doc status if set
                        //    $this->ldr_remove_submit_doc_status($user_id, $course_id);
                        //complete at least one course lesson or quiz
                        $lessons = learndash_get_course_lessons_list($course_id, $user_id);
                        if (!empty($lessons)) {
                            learndash_process_mark_complete($user_id, $lessons[1]['post']->ID);
                        }else{
                            print_r(json_encode(array('status' => false, 'message' => __('Can not change course to "In Progress", no lessons to complete', 'ld_refresher'))));
                            wp_die();
                        }
                    } elseif ($new_status == __('Completed', 'learndash')) {
                        $this->ldr_change_status_to_complete($user_id, $course_id, $date);
                        $this->ldr_check_if_course_needs_refresher($user_id, $course_id);
                        //remove submit doc status if set
                        //    $this->ldr_remove_submit_doc_status($user_id, $course_id);
                    } else if ($new_status == __('Refresher Required', 'ld_refresher')) {
                        //remove submit doc status if set
                        //    $this->ldr_remove_submit_doc_status($user_id, $course_id);
                        if ($status == __('Completed', 'learndash')) {
                            //change status
                            $result = $this->ldr_change_status_to_refresher_required($user_id, $course_id, $refPeriod, $expPeriod);
                            if(!$result){
                                print_r(json_encode(array('status' => false, 'message' => __('Can not change course to "Refresher Required"', 'ld_refresher'))));
                                wp_die();
                            }
                        } else {
                            //complete course first
                            $this->ldr_change_status_to_complete($user_id, $course_id, $date);
                            //then change status
                            $result = $this->ldr_change_status_to_refresher_required($user_id, $course_id, $refPeriod, $expPeriod);
                            if(!$result){
                                print_r(json_encode(array('status' => false, 'message' => __('Can not change course to "Refresher Required"', 'ld_refresher'))));
                                wp_die();
                            }
                        }
                    } else if ($new_status == __('Refresher Overdue', 'ld_refresher')) {
                        //remove submit doc status if set
                        //    $this->ldr_remove_submit_doc_status($user_id, $course_id);
                        if ($status == __('Completed', 'learndash') || __('Refresher Required', 'ld_refresher')) {
                            //change status
                            $result = $this->ldr_change_status_to_refresher_overdue($user_id, $course_id, $refPeriod, $expPeriod);
                            if(!$result){
                                print_r(json_encode(array('status' => false, 'message' => __('Can not change course to "Refresher Overdue"', 'ld_refresher'))));
                                wp_die();
                            }
                        } else {
                            //complete course first
                            $this->ldr_change_status_to_complete($user_id, $course_id, $date);
                            //then change status
                            $result = $this->ldr_change_status_to_refresher_overdue($user_id, $course_id, $refPeriod, $expPeriod);
                            if(!$result){
                                print_r(json_encode(array('status' => false, 'message' => __('Can not change course to "Refresher Overdue"', 'ld_refresher'))));
                                wp_die();
                            }
                        }
                    }
                }

                $data = ldr_get_cell_data($user_id, $course_id, true, $currentUser, 'yes');

                print_r(json_encode(array('status' => true, 'html' => $data['html'])));
                wp_die();
            }else{
                if($new_status == __('Refresher Required', 'ld_refresher')) {
                    $message = __('Can not change course to "Refresher Required", refresher option is missing', 'ld_refresher');
                }elseif($new_status == __('Refresher Overdue', 'ld_refresher')){
                    $message = __('Can not change course to "Refresher Overdue", refresher option is missing', 'ld_refresher');
                }
                print_r(json_encode(array('status' => false, 'message' => $message)));
                wp_die();
            }
       }

       private function ldr_remove_submit_doc_status($user_id, $course_id){
        $sub_doc = get_user_meta( $user_id, '_ldr_sub_doc_' . $course_id, true);
        //if user meta exist set it to no.
            if ($sub_doc != null){
                update_user_meta( $user_id, '_ldr_sub_doc_' . $course_id, 'no' );
            }
        }
       
       private function ldr_remove_course_data($user_id, $course_id){
           // remove course progress
           $progress = get_user_meta($user_id, '_sfwd-course_progress', true);
           if(isset($progress[$course_id])){
               unset($progress[$course_id]);
           }
           update_user_meta($user_id, '_sfwd-course_progress', $progress);
           
           // remove course quizzes
           $user_quizzes = get_user_meta($user_id, '_sfwd-quizzes', true);
           foreach($user_quizzes as $index => $record){
               if(isset($record['course'])){
                   if($record['course'] == $course_id){
                       unset($user_quizzes[$index]);
                   }
               }else{
                   $quiz_course = get_post_meta($record['quiz'], 'course_id', true);
                   if($quiz_course == $course_id){
                       unset($user_quizzes[$index]);
                   }
               }
           }
           update_user_meta($user_id, '_sfwd-quizzes', $user_quizzes);
           
           delete_user_meta($user_id, 'course_completed_' . $course_id);
           
           // remove history
           delete_user_meta($user_id, 'ldm_course_info_' . $course_id);
           delete_user_meta($user_id, 'ldm_course_completed_' . $course_id);
       }

       private function ldr_change_status_to_complete($user_id, $course_id, $date) {
            if ($date) {
                $current_date = date('Y-m-d H:i:s', strtotime($date));
                $timestamp = strtotime($current_date);
            } else {
                $timestamp = time();
            }
            if(learndash_get_course_steps_count($course_id)){
                $this->ldr_complete_course_resources($course_id, $user_id, $timestamp);
            }
            learndash_process_mark_complete($user_id, $course_id);
            
            update_user_meta($user_id, 'course_completed_' . $course_id, $timestamp);
            
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
                $courseInfo[$unique] = array('completion_date' => $timestamp, 'is_email_sent' => false);
                update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
            }
            if ($last) {
                $array['completion_date'] = $timestamp;
                $courseInfo[$last] = $array;
                update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
            }
            
            $course_completed = get_user_meta($user_id, 'ldm_course_completed_' . $course_id, true);
            if ($course_completed && is_array($course_completed)) {
                $lastCompleted = null;
                foreach ($course_completed as $key => $value) {
                    if (next($course_completed) === false) {
                        $lastCompleted = $key;
                    }
                }
                if($lastCompleted !== null){
                    $course_completed[$lastCompleted] = $timestamp;
                }
            } else {
                $course_completed = array();
                $course_completed[$unique] = $timestamp;
            }
            update_user_meta($user_id, 'ldm_course_completed_' . $course_id, $course_completed);
       }
       
       private function ldr_complete_course_resources($course_id, $user_id, $timestamp) {
           $resources = learndash_get_course_steps($course_id);
           foreach($resources as $resource){
               learndash_process_mark_complete($user_id, $resource, false, $course_id);
           }
           
           // complete quizzes
           $quizz_progress = get_user_meta($user_id, '_sfwd-quizzes', true);
           if(empty($quizz_progress)){
               $quizz_progress = array();
           }
           $allCourseQuizzes = get_posts( array( 
                'post_type' => 'sfwd-quiz', 
                'posts_per_page' => -1,
                'meta_key' => 'course_id', 
                'meta_value' => $course_id, 
           ));
           
           foreach($allCourseQuizzes as $quiz){
               $quiz_meta = get_post_meta($quiz->ID, '_sfwd-quiz', true);
               $quizdata = array(
                        'quiz'   => $quiz->ID,
                        'score'  => 0,
                        'count'  => 0,
                        'pass'   => true,
                        'rank'   => '-',
                        'time'   => $timestamp,
                        'pro_quizid' => $quiz_meta['sfwd-quiz_quiz_pro'],
                        'course' => $course_id,
                        'points' => 0,
                        'total_points' => 0,
                        'percentage' => 0,
                        'timespent' => 0,
                        'has_graded' => false,
                        'statistic_ref_id' => 0,
                        'm_edit_by' => get_current_user_id(),
                        'm_edit_time' => $timestamp
                );

                $quizz_progress[] = $quizdata;
           }
           
           update_user_meta($user_id, '_sfwd-quizzes', $quizz_progress);
       }
       
       private function ldr_check_if_course_needs_refresher($user_id, $course_id) {
           $expPeriod = get_post_meta($course_id, 'expiration_period', true);
           $refPeriod = get_post_meta($course_id, 'refresher_period', true);
           
           if ($expPeriod && $refPeriod && sfwd_lms_has_access($course_id, $user_id)){
                //get stored data for course in user meta 
                $courseInfo = get_user_meta($user_id, 'ldm_course_info_' . $course_id, true);

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
                                    
                                    ldr_send_refresher_email($user_id, $course_id);

                                    $value['is_email_sent'] = true;
                                    $courseInfo[$key] = $value;

                                    //clear learndash meta about course completion
                                    update_user_meta($user_id, 'course_completed_' . $course_id, '');
                                    $courseFullReset = get_post_meta($course_id, 'course_full_reset', true);
                                    if ($courseFullReset != 'course_reset_exam_only') { //clear all course progress
                                        learndash_delete_course_progress($course_id, $user_id);
                                        ldr_remove_bookmarked_tincanny($user_id, $course_id);
                                    } else {//clear all course progress by quizes
                                        ldr_delete_course_progress_exam_only($course_id, $user_id);
                                        $course_progress = get_user_meta($user_id, '_sfwd-course_progress', true);
                                        if ($course_progress && is_array($course_progress)) {
                                            foreach ($course_progress as $keyprogress => $valueprogress) {

                                                if ($keyprogress == $course_id) {
                                                    $valueprogress['completed'] = $valueprogress['completed'] - 1;
                                                    $course_progress[$keyprogress] = $valueprogress;
                                                }
                                            }
                                            update_user_meta($user_id, '_sfwd-course_progress', $course_progress);
                                        }
                                    }

                                    //create new array element for the next refreshment
                                    $unique = uniqid();
                                    $courseInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);
                                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
                                }
                            }
                        }
                    }
                }
            }
       }
       
       private function ldr_change_status_to_refresher_required($user_id, $course_id, $refPeriod, $expPeriod) {
            if(sfwd_lms_has_access($course_id, $user_id)) {

                $time = time();
                $requiredDate = new DateTime('@' . $time);
                $overdueDate = clone $requiredDate;
                $overdueDate->modify('+' . $refPeriod . ' days');
                $completionDate = clone $overdueDate;
                $completionDate->modify('-' . $expPeriod . ' months');

                //check whether he want course full reset, or quiz only resetting
                $courseFullReset = get_post_meta($course_id, 'course_full_reset', true);
                if ($courseFullReset != 'course_reset_exam_only') {
                    // remove course progress and quizzes data
                    update_user_meta($user_id, 'course_completed_' . $course_id, '');
                    learndash_delete_course_progress($course_id, $user_id);
                    ldr_remove_bookmarked_tincanny($user_id, $course_id);
                } else {
                    update_user_meta($user_id, 'course_completed_' . $course_id, '');
                    ldr_delete_course_progress_exam_only($course_id, $user_id);
                    $course_progress = get_user_meta($user_id, '_sfwd-course_progress', true);

                    if ($course_progress && is_array($course_progress)) {
                        foreach ($course_progress as $keyprogress => $valueprogress) {
                            if ($keyprogress == $course_id) {
                                $valueprogress['completed'] = $valueprogress['completed'] - 1;
                                $course_progress[$keyprogress] = $valueprogress;
                            }
                        }
                        update_user_meta($user_id, '_sfwd-course_progress', $course_progress);
                    }
                }

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
                    $courseInfo[$unique] = array('completion_date' => strtotime($completionDate->format('Y-m-d')), 'is_email_sent' => true);
                    
                    ldr_send_refresher_email($user_id, $course_id);
                    
                    $new_unique = uniqid();
                    $courseInfo[$new_unique] = array('completion_date' => '', 'is_email_sent' => false);
                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
                }
                if ($last) {
                    $array['completion_date'] = strtotime($completionDate->format('Y-m-d'));
                    $array['is_email_sent'] = true;
                    $courseInfo[$last] = $array;
                    
                    ldr_send_refresher_email($user_id, $course_id);
                    
                    $new_unique = uniqid();
                    $courseInfo[$new_unique] = array('completion_date' => '', 'is_email_sent' => false);
                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
                }

                $course_completed = get_user_meta($user_id, 'ldm_course_completed_' . $course_id, true);
                if ($course_completed && is_array($course_completed)) {
                    $lastCompleted = null;
                    foreach ($course_completed as $key => $value) {
                        if (next($course_completed) === false) {
                            $lastCompleted = $key;
                        }
                    }
                    if($lastCompleted !== null){
                        $course_completed[$lastCompleted] = strtotime($completionDate->format('Y-m-d'));
                    }
                } else {
                    $course_completed = array();
                    $course_completed[$unique] = strtotime($completionDate->format('Y-m-d'));
                }
                update_user_meta($user_id, 'ldm_course_completed_' . $course_id, $course_completed);

                return true;
            }else{
                return false;
            }
       }
       
       private function ldr_change_status_to_refresher_overdue($user_id, $course_id, $refPeriod, $expPeriod) {
            if(sfwd_lms_has_access($course_id, $user_id)) {
                $time = time();
                $overdueDate = new DateTime('@' . $time);
                $completionDate = clone $overdueDate;
                $completionDate->modify('-' . $expPeriod . ' months');

                //check whether he want course full reset, or quiz only resetting
                $courseFullReset = get_post_meta($course_id, 'course_full_reset', true);
                if ($courseFullReset != 'course_reset_exam_only') {
                    // remove course progress and quizzes data
                    update_user_meta($user_id, 'course_completed_' . $course_id, '');
                    learndash_delete_course_progress($course_id, $user_id);
                    ldr_remove_bookmarked_tincanny($user_id, $course_id);
                } else {
                    update_user_meta($user_id, 'course_completed_' . $course_id, '');
                    ldr_delete_course_progress_exam_only($course_id, $user_id);
                    $course_progress = get_user_meta($user_id, '_sfwd-course_progress', true);

                    if ($course_progress && is_array($course_progress)) {
                        foreach ($course_progress as $keyprogress => $valueprogress) {
                            if ($keyprogress == $course_id) {
                                $valueprogress['completed'] = $valueprogress['completed'] - 1;
                                $course_progress[$keyprogress] = $valueprogress;
                            }
                        }
                        update_user_meta($user_id, '_sfwd-course_progress', $course_progress);
                    }
                }

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
                    $courseInfo[$unique] = array('completion_date' => strtotime($completionDate->format('Y-m-d')), 'is_email_sent' => true);
                    
                    ldr_send_refresher_email($user_id, $course_id);
                    
                    $new_unique = uniqid();
                    $courseInfo[$new_unique] = array('completion_date' => '', 'is_email_sent' => false);
                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
                }
                if ($last) {
                    $array['completion_date'] = strtotime($completionDate->format('Y-m-d'));
                    $array['is_email_sent'] = true;
                    $courseInfo[$last] = $array;
                    
                    ldr_send_refresher_email($user_id, $course_id);
                    
                    $new_unique = uniqid();
                    $courseInfo[$new_unique] = array('completion_date' => '', 'is_email_sent' => false);
                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $courseInfo);
                }

                $course_completed = get_user_meta($user_id, 'ldm_course_completed_' . $course_id, true);
                if ($course_completed && is_array($course_completed)) {
                    $lastCompleted = null;
                    foreach ($course_completed as $key => $value) {
                        if (next($course_completed) === false) {
                            $lastCompleted = $key;
                        }
                    }
                    if($lastCompleted !== null){
                        $course_completed[$lastCompleted] = strtotime($completionDate->format('Y-m-d'));
                    }
                } else {
                    $course_completed = array();
                    $course_completed[$unique] = strtotime($completionDate->format('Y-m-d'));
                }
                update_user_meta($user_id, 'ldm_course_completed_' . $course_id, $course_completed);

                return true;
            }else{
                return false;
            }
       }

        /**
         * Get Courses Associated To Specific Group
         */
        function get_group_jobs_courses_callback() {
            $html = '';
            $group = (isset($_POST['group']) && $_POST['group']) ? intval($_POST['group']) : NULL;
            $user = (isset($_POST['user']) && $_POST['user']) ? intval($_POST['user']) : NULL;


            if ($group) {
                //get jobs(courses Categories) associated to group
                $jobsIds = get_post_meta($group, 'ldmv2_jobs', true);

                if (!empty($jobsIds)) {

                    $html .= ldr_get_enroled_courses_html($jobsIds, $user);
                } else { //if no courses categories associated to this group
                    $html .= '<p>' . __('No jobs for this group', 'ld_refresher') . '</p>';
                }
            } else { //if user didn't choose group
                $html .= '<p>' . __('Choose a group first', 'ld_refresher') . '</p>';
            }

            print_r(json_encode(array('status' => true, 'html' => $html)));

            wp_die();
        }

        /**
         * Save New Courses Template 
         */
        function save_course_template_callback() {
            $name = $_POST['name'];
            $group = $_POST['group'];
            $courses = $_POST['courses'];

            $templates = ($templates = get_option('ldmv2_plugin_templates')) ? $templates : array();


            $templates[$name]['group'] = $group;
            $templates[$name]['courses'] = $courses;

            $status = update_option('ldmv2_plugin_templates', $templates);

            print_r(json_encode(array('status' => $status, 'name' => $name)));

            wp_die();
        }

        /**
         * Load Courses Template 
         */
        function load_course_template_callback() {
            $groupHTML = $coursesHTML = '';
            $template = $_POST['template'];

            //get plugin templates
            $templates = get_option('ldmv2_plugin_templates');
            $status = false;

            if (isset($templates[$template])) {//Check if template exists in saved templates
                $selectedGroupId = $templates[$template]['group']; //group id

                $currentUser = wp_get_current_user();
                $groupIds = ldr_get_groups_by_logged_in_user($currentUser);

                $groupHTML .= '<option value="">' . __('Select group', 'ld_refresher') . '</option>';
                //Load Group html
                foreach ($groupIds as $groupId) {
                    $groupHTML .= '<option value="' . $groupId . '"';
                    if ($groupId == $selectedGroupId) {
                        $groupHTML .= ' selected';
                    }
                    $groupHTML .= '>' . get_the_title($groupId) . '</option>';
                }

                //Load courses checked in template
                $coursesIds = $templates[$template]['courses'];
                //get jobs(courses Categories) associated to group
                $jobsIds = get_post_meta($selectedGroupId, 'ldmv2_jobs', true);
                if (!empty($jobsIds)) {

                    //Load Courses Html
                    $coursesHTML = ldr_get_enroled_courses_html($jobsIds, NULL, $coursesIds);
                }

                $status = true;
            }

            print_r(json_encode(array('status' => $status, 'groupHTML' => $groupHTML, 'coursesHTML' => $coursesHTML)));

            wp_die();
        }

        /**
         * Load Courses by Group
         */
        function get_refresher_group_courses_callback() {

            $group = (isset($_POST['group']) && $_POST['group']) ? $_POST['group'] : "";
            $html = '<option value="">' . __('All Courses', 'ld_refresher') . '</option>';

            if ($group) {
                $jobsIds = get_post_meta($group, 'ldmv2_jobs', true);

                if (!empty($jobsIds)) {
                    $courses = get_posts(array(
                        'post_type' => 'sfwd-courses',
                        'posts_per_page' => -1,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'ld_course_category',
                                'field' => 'term_id',
                                'terms' => $jobsIds
                            )
                        ),
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));

                    foreach ($courses as $course) {
                        $html .= '<option value="' . $course->ID . '">' . $course->post_title . '</option>';
                    }
                }
            }

            print_r(json_encode(array('status' => true, 'html' => $html)));

            wp_die(); // this is required to terminate immediately and return a proper response
        }

        function get_course_info_dynamically_callback() {

            $u_id = $_POST['u_id'];
            $course_id = $_POST['course_id'];
            $record_course_info = '';
            $completion = '---';
            $link = '---';
            if($course_id){
                $course_completed = get_user_meta($u_id, 'ldm_course_completed_' . $course_id, true);
                
                if(is_array($course_completed) && count($course_completed)){
                    $historyLastElement = end($course_completed);
                    reset($course_completed);
                    
                    $completionDate = new DateTime('@' . $historyLastElement);
                    $completion = $completionDate->format('d-m-Y');
                    $record_course_info .= '<td>' . $completion . '</td>';
                    $link = '<a href="'.admin_url('admin.php').'?page=user_history_ldmv2&userId='.$u_id.'&courseId='.$course_id.'" class="button button-primary">'. __('View Certificates', 'ld_refresher').'</a>';
                    $record_course_info .= '<td>' . $link . '</td>';
                }else{
                    $record_course_info .= '<td>' . $completion . '</td>';
                    $record_course_info .= '<td>' . $link . '</td>';
                }
            }

            print_r(json_encode(array('status' => true, 'html' => $record_course_info)));
            
            wp_die();
        }
        
        /***
         * Change Course date in history
         */
        function change_user_history_date_callback() {
            $index = $_POST['index'];
            $userId = intval($_POST['user']);
            $courseId = intval($_POST['course']);
            $last = $_POST['last'];
            $newDate = $_POST['date'];
            $newDateObj = new DateTime($newDate);
            $date = $newDateObj->format('d-m-Y');
            
            if($last){
                update_user_meta($userId, 'course_completed_'.$courseId, $newDateObj->getTimestamp());
            }

            $course_completed = get_user_meta($userId, 'ldm_course_completed_' . $courseId, true);
            if(isset($course_completed[$index])){
                $course_completed[$index] = $newDateObj->getTimestamp();
                
                update_user_meta($userId, 'ldm_course_completed_' . $courseId, $course_completed);
                
                $courseInfo = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
                if(isset($courseInfo[$index]['completion_date'])){
                    $courseInfo[$index]['completion_date'] = $newDateObj->getTimestamp();
                    
                    update_user_meta($userId, 'ldm_course_info_' . $courseId, $courseInfo);
                }
            }

            print_r(json_encode(array('status' => true, 'date' => $date)));

            wp_die(); // this is required to terminate immediately and return a proper response
        }

        /***
        * Delete Course date in history
        */
        function delete_user_history_date_callback() {

            $index = $_POST['index'];
            $userId = intval($_POST['user']);
            $courseId = intval($_POST['course']);
            $course_completed = get_user_meta($userId, 'ldm_course_completed_' . $courseId, true);

            if(isset($course_completed[$index])){

                $oldTime = get_user_meta($userId, 'course_completed_'.$courseId, true);              

                if ($course_completed[$index] == $oldTime) {
                    update_user_meta($userId, 'course_completed_' . $courseId, '');
                }

                unset($course_completed[$index]);
                update_user_meta( $userId, 'ldm_course_completed_'.$courseId, $course_completed );

                $courseInfo = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
                if(isset($courseInfo[$index]['completion_date'])){
                    unset($courseInfo[$index]['completion_date']);                  
                    update_user_meta($userId, 'ldm_course_info_' . $courseId, $courseInfo);
                }

            }


            print_r(json_encode(array('status' => true, 'message' => __('Date deleted successfully', 'ld_refresher'))));

            wp_die(); // this is required to terminate immediately and return a proper response
        }

        public function migrate_to_new_site_callback() {
            set_time_limit(0);
            global $wpdb;

            $courses = new WP_Query(array('post_type' => 'sfwd-courses', 'posts_per_page' => -1));
            $users = $wpdb->get_results("SELECT * FROM $wpdb->users");
            foreach ($users as $u) {
                foreach ($courses->get_posts() as $course) {
                    $timestampCompletion = get_user_meta($u->ID, 'course_completed_' . $course->ID, true);

                    if($timestampCompletion){
                        $unique = uniqid();
                        
                        $course_completed = array();
                        $courseInfo = array();
                        
                        $course_completed[$unique] = $timestampCompletion;
                        update_user_meta($u->ID, 'ldm_course_completed_' . $course->ID, $course_completed);
                        
                        $courseInfo[$unique] = array('completion_date' => $timestampCompletion, 'is_email_sent' => false);
                        update_user_meta($u->ID, 'ldm_course_info_' . $course->ID, $courseInfo);
                        
                        // check if new completion date is refresher required or overdue
                        $this->ldr_check_if_course_needs_refresher($u->ID, $course->ID);
                    }
                }
            }
            
            update_option('ldr_migrate_new_site', 1);
            
            print_r(json_encode(array('status' => true, 'message' => __('Migration process finished successfully', 'ld_refresher'))));

            wp_die(); // this is required to terminate immediately and return a proper response
        }
        
        public function migrate_to_version_three_callback() {
            set_time_limit(0);
            global $wpdb;

            $quiz_courses = array();
            $courses = new WP_Query(array('post_type' => 'sfwd-courses', 'posts_per_page' => -1));
            $users = $wpdb->get_results("SELECT * FROM $wpdb->users");
            foreach ($users as $u) {
                $usermeta = get_user_meta($u->ID, null, true);
                $ldm_quizzes = maybe_unserialize($usermeta['_ldm-sfwd-quizzes'][0]);//get_user_meta($u->ID, '_ldm-sfwd-quizzes', true);
                foreach ($courses->get_posts() as $course) {
                    $course_completed = array();
                    $courseInfo = array();
                    $has_quiz = false;
                    if(is_array($ldm_quizzes) && count($ldm_quizzes)){
                        $oldCourseInfo = maybe_unserialize($usermeta['ldm_course_info_'. $course->ID][0]);//get_user_meta($u->ID, 'ldm_course_info_' . $course->ID, true);
                        // get history from quizzes
                        foreach($ldm_quizzes as $index => $single){
                            $courseId = null;
                            if(isset($single['quiz']) && $single['quiz']){
                                if(isset($quiz_courses[$single['quiz']]) && $quiz_courses[$single['quiz']]){
                                    $courseId = $quiz_courses[$single['quiz']];
                                }else{
                                    $courseId = get_post_meta($single['quiz'], 'course_id', true);
                                    $quiz_courses[$single['quiz']] = $courseId;
                                }
                            }
                            
                            if($courseId && $courseId == $course->ID && $single['pass']){
                                if(isset($single['uniqueId']) && $single['uniqueId']){
                                    $has_quiz = true;
                                    $unique = $single['uniqueId'];
                                    
                                    $course_completed[$unique] = $single['time'];
                                    
                                    $is_email_sent = false;
                                    if(isset($oldCourseInfo[$unique]['is_email_sent'])){
                                        $is_email_sent = $oldCourseInfo[$unique]['is_email_sent'];
                                    }
                                    $courseInfo[$unique] = array('completion_date' => $single['time'], 'is_email_sent' => $is_email_sent);
                                }elseif(isset($single['time']) && $single['time']){
                                    $has_quiz = true;
                                    $unique = uniqid();
                                    
                                    $course_completed[$unique] = $single['time'];
                                    
                                    $is_email_sent = false;
                                    if(isset($oldCourseInfo[$unique]['is_email_sent'])){
                                        $is_email_sent = $oldCourseInfo[$unique]['is_email_sent'];
                                    }
                                    $courseInfo[$unique] = array('completion_date' => $single['time'], 'is_email_sent' => $is_email_sent);
                                }
                            }
                        }
                    }
                    
                    // handel if course has no quiz
                    if(!$has_quiz){
                        $timestampCompletion = get_user_meta($u->ID, 'course_completed_' . $course->ID, true);
                        if($timestampCompletion){
                            $unique = uniqid();

                            $course_completed[$unique] = $timestampCompletion;
                            update_user_meta($u->ID, 'ldm_course_completed_' . $course->ID, $course_completed);

                            $courseInfo[$unique] = array('completion_date' => $timestampCompletion, 'is_email_sent' => false);
                            update_user_meta($u->ID, 'ldm_course_info_' . $course->ID, $courseInfo);
                        }
                    }else{
                        $last = null;
                        $array = null;
                        if (is_array($oldCourseInfo)) {
                            foreach ($oldCourseInfo as $key => $value) {
                                if (next($courseInfo) === false) {
                                    $last = $key;
                                    $array = $value;
                                }
                            }
                        }
                        if (isset($array['completion_date']) && $array['completion_date'] == '') {
                            $courseInfo[$last] = $array;
                        }
                        
                        update_user_meta($u->ID, 'ldm_course_completed_' . $course->ID, $course_completed);
                        update_user_meta($u->ID, 'ldm_course_info_' . $course->ID, $courseInfo);
                    }
                }
            }
            
            update_option('ldr_migrate_version_three', 1);
            
            print_r(json_encode(array('status' => true, 'message' => __('Migration process finished successfully', 'ld_refresher'))));

            wp_die(); // this is required to terminate immediately and return a proper response
        }

        //emailing checked users on refresher report.
        function email_user_about_courses_todo_callback(){
            
            $data = $_POST['info'];
            //$data = json_decode($data);
            //$userID = intval($data[0]->user_Id);
            $the_Message = '<ul>';

            if ($data != null){
                foreach($data as $singleData) {
                    $userId = intval( $singleData['user_id'] );
                    $courseId = intval( $singleData['course_id'] );
                    $courseStatus = $singleData['status'];
                    $course = get_post($courseId);
                    $the_Message = $the_Message . '<li>' . $course->post_title . ' currently has the status of ' . $courseStatus . '</li>';
                }
            }

            $u = get_userdata($userId);
            $admin_email = get_option('admin_email');
            $sitename = get_option('blogname', 'Training Matrix');
            
            // info to send in email to user
            $headers = array();
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'From: '.$sitename.' <' . $admin_email . '>';
            $the_Subject = "Course Status Alert";
            $the_Message = 'Hi ' . $u->first_name . ',<br /><br />The following courses require a refresh: <br />' . $the_Message . '</ul><br />Kind Regards,<br /><br />' . $sitename . ' Admin';

            //send email to user
            wp_mail($u->user_email, $the_Subject, $the_Message, $headers);

            print_r(json_encode(array('status' => true, 'message' => __('User ' .$u->first_name . ' ' . $u->last_name .' emailed successfully', 'ld_refresher'))));

            wp_die(); // this is required to terminate immediately and return a proper response 
        }

        //uploading a document
        function upload_course_document_callback(){
            
            $user_id = intval( $_POST['userId'] );
            $course_id = intval( $_POST['courseId'] );
            $u = get_userdata($user_id);

            $upload_dir = wp_upload_dir(); 
            $user_dirname = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR . $u->user_login;
            $url_dirname = $upload_dir['baseurl'] . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR . $u->user_login . DIRECTORY_SEPARATOR . current_time('d-m-Y_h:i:s_',1) . $_FILES["file"]["name"];
            if(!file_exists($user_dirname)) wp_mkdir_p($user_dirname);

            $path = $user_dirname;
            $source = $_FILES["file"]["tmp_name"];
            $moveFileUpped = move_uploaded_file($source,$user_dirname  . DIRECTORY_SEPARATOR . current_time('d-m-Y_h:i:s_',1) . $_FILES["file"]["name"]);
            if($moveFileUpped){
            $message = "Stored in: " . $user_dirname;
            }else{$message = "Not Stored in: " . $user_dirname;}

            update_user_meta( $user_id, '_ldr_subed_doc_' . $course_id, $url_dirname);
            
            print_r(json_encode(array('status' => true, 'message' => __($message, 'ld_refresher'))));
            wp_die(); // this is required to terminate immediately and return a proper response 
        }


    }

    $LearnDash_Refresher_Ajaxs = new LearnDash_Refresher_Ajaxs();
}