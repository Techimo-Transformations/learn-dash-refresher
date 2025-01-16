<?php

function ldr_get_cell_data($userId, $courseId, $allowLeaders, $currentUser, $selectedGroup = null) {
    $data = array('status' => '', 'html' => '');
    
    $is_completed = false;
    $is_tradish = false;
    $status = '';
    $color = '';
    $font = '';
    $cell = '';
    $hasHistory = false;
    $editable = false;
    $historyLastElement = null;
    $currentUserAllowed = false;
    
    /*if(($currentUser && in_array('administrator', (array) $currentUser->roles)) || ($currentUser && in_array('group_leader', (array) $currentUser->roles) && $allowLeaders)){
        $editable = true;
        $currentUserAllowed = true;
    }*/
    
    if($currentUser && in_array('group_leader', (array) $currentUser->roles)){
        if($allowLeaders){
            $editable = true;
            $currentUserAllowed = true;
        }
    }else{
        $editable = true;
        $currentUserAllowed = true;
    }
    
    $hasAccess = sfwd_lms_has_access($courseId, $userId);
    $historyArr = get_user_meta($userId, 'ldm_course_completed_' . $courseId, true);
    //$sub_doc = get_user_meta( $userId, '_ldr_sub_doc_' . $courseId, true);
    
    if(is_array($historyArr) && count($historyArr)){
        $hasHistory = true;
        $historyLastElement = end($historyArr);
        reset($historyArr);
    }
    
    if((!$hasAccess) && $hasHistory){
        $editable = false;
    }

    if((!$hasAccess) && (!$hasHistory)){
        if((!$selectedGroup)){
            $is_available = ldr_is_course_in_user_group($userId, $courseId);
            if($is_available && $currentUserAllowed){
                $editable = true;
            }else{
                $editable = false;
            }
        }
    }
    
   
    if((!$hasAccess) && (!$hasHistory)){
        if($selectedGroup){
            $status = __('Not Enrolled', 'ld_refresher');
            $color = 'white';
            $font = 'black';
        }else{
            if($is_available){
                $status = __('Not Enrolled', 'ld_refresher');
                $color = 'white';
                $font = 'black';
            }else{
                $status = __('N/A', 'ld_refresher');
                $color = 'white';
                $font = 'black';
            }
        }

    }else{
        $font = 'white';
        $status = learndash_course_status($courseId, $userId);
        if ($status == __('Submit Document', 'ld_refresher')){
            $color = '#f9f97f';
            $font = 'black';
            $is_tradish = true;
        }
        else if ($status == __('Not Started', 'learndash')) {
            $color = '#7F7F7F';
            $expPeriod = get_post_meta($courseId, 'expiration_period', true);
            $refPeriod = get_post_meta($courseId, 'refresher_period', true);
            $courseInfo = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
            $is_trad = get_post_meta($courseId, 'is_course_traditional', true);
            if ($expPeriod && $refPeriod && $courseInfo && is_array($courseInfo) && count($courseInfo) > 0) {
                if ($historyLastElement) {
                    $completionDate = new DateTime('@' . $historyLastElement);
                    $overdueDate = clone $completionDate;
                    $overdueDate->modify('+' . $expPeriod . ' months');
                    $requiredDate = clone $overdueDate;
                    $requiredDate->modify('-' . $refPeriod . ' days');
                    if (strtotime($overdueDate->format('Y-m-d')) < time()) {
                        $status = __('Refresher Overdue', 'ld_refresher');
                        $color = '#FF0000';
                        if ($is_trad == "on")$is_tradish = true;
                    } elseif (strtotime($requiredDate->format('Y-m-d')) < time()) {
                        $status = __('Refresher Required', 'ld_refresher');
                        $color = '#FFA500';
                        if ($is_trad == "on")$is_tradish = true;
                    }
                }
            }
            else if ($is_trad == "on"){
                $status = __('Submit Document', 'ld_refresher');
                $color = '#f9f97f';
                $font = 'black';
                $is_tradish = true;
            }
        } elseif ($status == __('In Progress', 'learndash')) {
            $color = '#0000FF';
            $expPeriod = get_post_meta($courseId, 'expiration_period', true);
            $refPeriod = get_post_meta($courseId, 'refresher_period', true);
            $courseInfo = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
            if ($expPeriod && $refPeriod && $courseInfo && is_array($courseInfo) && count($courseInfo) > 0) {
                if ($historyLastElement) {
                    $completionDate = new DateTime('@' . $historyLastElement);
                    $overdueDate = clone $completionDate;
                    $overdueDate->modify('+' . $expPeriod . ' months');
                    $requiredDate = clone $overdueDate;
                    $requiredDate->modify('-' . $refPeriod . ' days');
                    if (strtotime($overdueDate->format('Y-m-d')) < time()) {
                        $status = __('Refresher Overdue', 'ld_refresher');
                        $color = '#FF0000';
                    } elseif (strtotime($requiredDate->format('Y-m-d')) < time()) {
                        $status = __('Refresher Required', 'ld_refresher');
                        $color = '#FFA500';
                    }
                }
            }
        }       
        elseif ($status == __('Completed', 'learndash')) {
            $is_completed = true;
            $color = '#49E20E';
            if ($historyLastElement) {
                $status = date("d-m-Y", $historyLastElement);
            }
        }
    }
    
    if($editable){
        /**
         * rf@objects
         * function take status as aparameter and rerurn select html as output
         */
        $select_html = ldr_get_users_course_status_dropdown($status, $userId, $courseId);

        $cell .= '<td id="' . $userId . '_' . $courseId . '" class="user-course-cell"   style="color: ' . $font . '; background-color: ' . $color . '">' . $select_html;
        if($is_completed){
            $cell .= '<button type="button" data-user="' . $userId . '" data-course="' . $courseId . '" class="button button-primary matrix_edit_date">Edit</button>';
        }
        else if($is_tradish){
            $cell .= '<button type="button" data-user="' . $userId . '" data-course="' . $courseId . '" class="button button-primary matrix_upload_button">Upload</button>';
        }
        $cell .= '</td>';
    }else{
        $cell .= '<td id="' . $userId . '_' . $courseId . '" class="user-course-cell"   style="color: ' . $font . '; background-color: ' . $color . '">' . $status.'</td>';
    }
    
    $data['status'] = ldr_get_status_value_from_string($status);
    $data['html'] = $cell;
    
    return $data;
}

/**
 * rf@objects
 * function take status as aparameter and rerurn select html as output
 * @param type $status course status
 * @return type $output status select tag html
 */
function ldr_get_users_course_status_dropdown($status, $userId, $courseId) {
    $options = array();
    $is_trad = get_post_meta($courseId, 'is_course_traditional', true);

    if ($is_trad == "on"){

        $default_status = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('Completed', 'learndash'), __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'),__('Submit Document', 'ld_refresher'));
        $start = array_search($status, $default_status);

        if ($start) {
            if ($status == __('Not Enrolled', 'ld_refresher') || $status == __('Not Started', 'learndash')) {
                 $options = array(__('Not Enrolled', 'ld_refresher'), __('Submit Document', 'ld_refresher'));
            } else {
                $options = array_unique(array_merge(array(__('Not Enrolled', 'ld_refresher')), array_slice($default_status, $start)));
            }
            if ($status == __('Refresher Overdue', 'ld_refresher')) {
                $options = array(__('Not Enrolled', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'),__('Completed', 'learndash'));
            } else if ($status == __('Refresher Required', 'ld_refresher')) {
                $options = array(__('Not Enrolled', 'ld_refresher'), __('Refresher Required', 'ld_refresher'),__('Completed', 'learndash'),__('Refresher Overdue', 'ld_refresher'));
            }
            else if ($status == __('Submit Document', 'ld_refresher')) {
                $options = array(__('Not Enrolled', 'ld_refresher'),__('Submit Document', 'ld_refresher'),__('Completed', 'learndash'));
            }
            else {
                $options = array_unique(array_merge(array(__('Not Enrolled', 'ld_refresher')), array_slice($default_status, $start)));
            } 
        } 
        else {
            if (DateTime::createFromFormat('d-m-Y', $status) !== FALSE) {
                $options = array(__('Not Enrolled', 'ld_refresher'), $status, __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
            } else {
                if (!sfwd_lms_has_access($courseId, $userId)) {
                    $options = array(__('Not Enrolled', 'ld_refresher'), __('Submit Document', 'ld_refresher'));
                } else {
                    $options = array(__('Not Enrolled', 'ld_refresher'), __('Submit Document', 'ld_refresher'));
                }
            }
        }
    }
    else{
        $default_status = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('In Progress', 'learndash'), __('Completed', 'learndash'), __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
        $start = array_search($status, $default_status);

        if ($start) {
            if ($status == __('Not Enrolled', 'ld_refresher') || $status == __('Not Started', 'learndash')) {
                 $options = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('In Progress', 'learndash'), __('Completed', 'learndash'), __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
            } else {
                $options = array_unique(array_merge(array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash')), array_slice($default_status, $start)));
            }
            if ($status == __('Refresher Overdue', 'ld_refresher')) {
                $options = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('Completed', 'learndash'), __('Refresher Overdue', 'ld_refresher'));
            } else if ($status == __('Refresher Required', 'ld_refresher')) {
                $options = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('Completed', 'learndash'), __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
            }
            else {
                $options = array_unique(array_merge(array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash')), array_slice($default_status, $start)));
            } 
        } 
        else {
            if (DateTime::createFromFormat('d-m-Y', $status) !== FALSE) {
                $options = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), $status, __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
            } else {
                if (!sfwd_lms_has_access($courseId, $userId)) {
                    $options = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('In Progress', 'learndash'), __('Completed', 'learndash'), __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
                } else {
                    $options = array(__('Not Enrolled', 'ld_refresher'), __('Not Started', 'learndash'), __('In Progress', 'learndash'), __('Completed', 'learndash'), __('Refresher Required', 'ld_refresher'), __('Refresher Overdue', 'ld_refresher'));
                }
            }
        }
    }

    if ($options) {
        $output = '<select class="user_course_status_dropdown" >';
        $index = 0;
        foreach ($options as $key => $option) {
            $index = $index + 1;
            if ($status == $option) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            $output .= '<option id="' . $courseId . '_' . $userId . '_' . $index . '" ' . $selected . '>' . $option . '</option>';
        }
        $output .='</select>';
    } else {
        $output = $status;
    }
    return $output;
}

function ldr_get_user_name($user){
    $name = '';
    
    if($user->first_name || $user->last_name){
        if($user->first_name || $user->last_name){
            $name = $user->first_name . " " . $user->last_name;
        }elseif($user->first_name){
            $name = $user->first_name;
        }elseif($user->last_name){
            $name = $user->first_name;
        }
    }elseif($user->display_name){
        $name = $user->display_name;
    }elseif($user->user_login){
        $name = $user->user_login;
    }
    
    return $name;
}

function ldr_get_status_value_from_string($status){
    $value = '';
    
    $statusesArr = array(
        __('N/A', 'ld_refresher') => __('N/A', 'ld_refresher'),
        __('Not Enrolled', 'ld_refresher') => 'not_enrolled',
        __('Not Started', 'learndash') => 'not_started',
        __('In Progress', 'learndash') => 'in_progress',
        __('Completed', 'learndash') => 'completed',
        __('Refresher Required', 'ld_refresher') => 'ref_required',
        __('Refresher Overdue', 'ld_refresher') => 'ref_overdue',
        __('Submit Document', 'ld_refresher') => 'sub_document'
    );
    
    if(isset($statusesArr[$status])){
        $value = $statusesArr[$status];
    }elseif($status){
        $value = 'completed';
    }
    
    return $value;
}

function ldr_is_course_in_user_group($userId, $courseId){
    $is_available = false;
    
    $groupIds = learndash_get_users_group_ids($userId);
    
    if(is_array($groupIds) && count($groupIds)){
        $jobs = get_the_terms($courseId, 'ld_course_category');
        if(is_array($jobs) && count($jobs)){
            $courseJobs = array();
            foreach($jobs as $job){
                $courseJobs[] = $job->term_id;
            }
            
            foreach($groupIds as $groupId){
                $jobsIds = get_post_meta($groupId, 'ldmv2_jobs', true);
                if(is_array($jobsIds) && count($jobsIds)){
                    $intersectArr = array_intersect($courseJobs, $jobsIds);
                    if(count($intersectArr)){
                        $is_available = true;
                        break;
                    }
                }else{
                    continue;
                }
            }
        }
    }
    
    return $is_available;
}

/* * *
 * Get html of enrolled courses
 */

function ldr_get_enroled_courses_html($jobsIds, $user = NULL, $loadedCoursesIds = array()) {
    $html = "";

    if (!empty($jobsIds)) {
        $jobs = get_terms(
                array(
                    'taxonomy' => 'ld_course_category',
                    'hide_empty' => true,
                    'include' => $jobsIds
                )
        );

        foreach ($jobs as $job) { //loop over each courses categories and get courses
            $courses = get_posts(array(
                'post_type' => 'sfwd-courses',
                'showposts' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'ld_course_category',
                        'field' => 'term_id',
                        'terms' => $job->term_id
                    )
                ),
                'orderby' => 'title',
                'order' => 'ASC'
            ));

            //courses category container
            $html .= '<div class="ldmrefresher_group_job"><h4 clase="job-header">' . $job->name . '</h4>';

            foreach ($courses as $course) {
                $html .= '<div><input type="checkbox" class="course-checkbox" name="ldmrefresher_course[' . $course->post_name . ']" value="' . $course->ID . '"';

                //check course if user can access it
                if ($user && sfwd_lms_has_access($course->ID, $user)) {
                    $html .= ' checked';
                } elseif (in_array($course->ID, $loadedCoursesIds)) { //check if course in loaded courses ids
                    $html .= ' checked';
                }

                $html .= '><label>' . $course->post_title . '</label></div>';
            }

            $html .= '</div>'; //end courses category container
        }
    }
    return $html;
}

/**
 * Get Templates By Logged In User
 * @return type
 */
function ldr_get_templates_by_users() {
    
    $currentUser = wp_get_current_user();

    //get saved templates
    $templates = ($templates = get_option('ldmv2_plugin_templates')) ? $templates : array();

    //customize templates if it is group leader
    if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {
        $groupIds = learndash_get_administrators_group_ids($currentUser->ID); //get leader groups
        $finalTemplates = array();
        foreach ($templates as $template => $value) {
            if (isset($value['group']) && in_array($value['group'], $groupIds)) {
                $finalTemplates[] = $template;
            }
        }
        $templates = $finalTemplates;
    }

    return $templates;
}

/**
 * Get groups based on logged in users
 * @return type
 */
function ldr_get_groups_by_logged_in_user($currentUser) {
    $groupIds = array();
    
    if($currentUser){
        if (in_array('group_leader', (array) $currentUser->roles)) {//get group leader groups
            $groupIds = learndash_get_administrators_group_ids(get_current_user_id()); //get leader groups
        }elseif(in_array('administrator', (array) $currentUser->roles)) {
            $groupIds = learndash_get_groups(TRUE); //get admin groups
        }else{
            $groups_query_args = array(
                    'post_type' => 'groups',
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'nopaging' => true,
                    'fields' => 'ids'
            );
	
            $groups_query = new WP_Query( $groups_query_args );
            $groupIds = $groups_query->posts;
        }
    }

    return $groupIds;
}

function ldr_delete_course_progress_exam_only($course_id, $user_id) {
    $quizzes = get_posts(
            array(
                'post_type' => 'sfwd-quiz',
                'meta_key' => 'course_id',
                'meta_value' => $course_id
            )
    );
    foreach ($quizzes as $quiz) {
        learndash_delete_quiz_progress($user_id, $quiz->ID);
    }
}

function ldr_send_refresher_email($user, $post){
    if (!($post instanceof WP_Post)) {
        $course = get_post($post);
    }else{
        $course = $post;
    }
    
    if (!($user instanceof WP_User)) {
        $u = get_userdata($user);
    }else{
        $u = $user;
    }
    
    $admin_email = get_option('admin_email');
    $sitename = get_option('blogname', 'Training Matrix');
    
    
    // send email to user and leader(s)
    $headers = array();
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: '.$sitename.' <' . $admin_email . '>';
    //send email to users
    wp_mail($u->user_email, $course->post_title . ' needs to be refreshed', $course->post_title . ' needs to be refreshed', $headers);

    //send email to group leaders
    if(get_option('ldmv2_leaders_emails', '1')){
        $groups = learndash_get_users_group_ids($u->ID);
        foreach ($groups as $groupId) {
            $leaders = learndash_get_groups_administrators($groupId);
            foreach ($leaders as $leader) {
                wp_mail($leader->user_email, $course->post_title . ' needs to be refreshed for user:' . ldr_get_user_name($u), $course->post_title . ' needs to be refreshed for user:' . ldr_get_user_name($u), $headers);
            }
        }
    }
}

function ldr_get_leader_courses($groupIds){
    $courses = array();
    
    if(count($groupIds)){
        $finalJobs = array();
        foreach($groupIds as $groupId){
            $jobsIds = get_post_meta($groupId, 'ldmv2_jobs', true);
            $finalJobs = array_merge($finalJobs, $jobsIds);
        }
        
        if(count($finalJobs)){
            $courses = get_posts(array(
                'post_type' => 'sfwd-courses',
                'showposts' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'ld_course_category',
                        'field' => 'term_id',
                        'terms' => array_unique($finalJobs)
                    )
                ),
                'orderby' => 'title',
                'order' => 'ASC'
            ));
        }
    }
    
    return $courses;
}

/**
* Removes the resume course bookmarks for all tincanny moduels within a course for a specific user
*/
function ldr_remove_bookmarked_tincanny($user_id, $course_id){


    $lessons = learndash_get_course_lessons_list($course_id, $user_id);

        if (!empty($lessons)) {

            //put all lesson ids into an array.
            $lesson_IDs = array();
                foreach ($lessons as $lesson) {
                    array_push($lesson_IDs, $lesson['post']->ID);
                }

                //get module ids from post content of those lessons
            $modules = array();
                foreach ($lesson_IDs as $les_id){
                    $content_post = get_post($les_id);
                    $content = $content_post->post_content;
                    preg_match_all('#item_id="([^"]+)#', $content, $match);
                    $output_mod =  implode(' ', $match[1]);

                    if (!empty(trim($output_mod)))
                    array_push($modules, intval($output_mod));
                }

                //use module ids to delete that data from the SQL database
            if (!empty($modules)) {
                //error_log("modules not empty for course " . get_the_title($course_id) . " " . print_r($modules,true));
                global $wpdb;
                foreach($modules as $module){
                    $tableName = $wpdb->prefix . 'uotincan_resume';
                    $wpdb->delete( $tableName, array( 'user_id' => $user_id, 'module_id' => $module ) );
                }
            }
            else{
                //error_log("modules empty for course " . get_the_title($course_id));
                return;
            }
            
        }
        else{
            //error_log("lessons empty");
            return;
        }
}