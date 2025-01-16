<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Report')) {

    class LearnDash_Refresher_Report {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_report"), 125);
        }

        function add_ld_refresher_report() {
            add_submenu_page('learndash-lms', __('Refresher Report', 'ld_refresher'), __('Refresher Report', 'ld_refresher'), 'manage_ldmv2_reports', 'refresher_report_ldmv2', array($this, 'ld_refresher_report_callback'));
        }

        function ld_refresher_report_callback() {
            //set_time_limit(0);  ///?????
            //Get Selected data if user submitted filter
            $selectedGroup = $selectedStatus = $selectedCourse = '';
            if (isset($_POST['ldmrefresher_refresher_submit'])) {
                $selectedGroup = $_POST['ldmrefresher_refresher_group'];
                $selectedStatus = $_POST['ldmrefresher_refresher_status'];
                $selectedCourse = $_POST['ldmrefresher_refresher_course'];
            }

            //array of user courses will be displayed in table
            $finalArr = array();

            //get leader groups
            $leaderUsersArr = array();
            $currentUser = wp_get_current_user();
            $groupIds = ldr_get_groups_by_logged_in_user($currentUser); //get groups by user
            if (count($groupIds)) {
                foreach ($groupIds as $groupId) {
                    $usersArr = learndash_get_groups_user_ids($groupId);
                    $leaderUsersArr = array_merge($leaderUsersArr, $usersArr);
                }

                //remove repeated users
                $leaderUsersArr = array_unique($leaderUsersArr);
            }


            //get selected groupif not setted
            if (!$selectedGroup && isset($groupIds[0])) {
                $selectedGroup = $groupIds[0];
            }

            //prepare users query
            $usersArgs = array('orderby' => 'display_name', 'order' => 'ASC', 'number' => -1);
            if ($selectedGroup) {
                $usersArgs['meta_key'] = 'learndash_group_users_' . $selectedGroup;
                $usersArgs['meta_value'] = $selectedGroup;
            }

            if (!empty($leaderUsersArr)) {
                $usersArgs['include'] = $leaderUsersArr;
            }
            
            //get users
            $users = new WP_User_Query($usersArgs);
            $usersObjs = $users->results;


            // get group courses
            $coursesfilter = array(); //is array of courses will be showen in courses drop down based on selected group
            $coursesArr = array(); //is array of courses will be showen in table
            if ($selectedCourse) {//if specific course is selected
                $coursesArr[] = get_post($selectedCourse);
            }

            if ($selectedGroup) { //if a group is selected
                $jobsIds = get_post_meta($selectedGroup, 'ldmv2_jobs', true);
                //get groups in specific job
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

                    $coursesfilter = $courses;

                    if (!$selectedCourse) {//if no course is selected
                        $coursesArr = $courses;
                    }
                }
            }

          

            foreach ($usersObjs as $u) {
                
                $usermeta = get_user_meta($u->ID);

                foreach ($coursesArr as $course) {
                    $historyLastElement = null;
                    //get course meta
                    $courseMetasArr = get_post_meta($course->ID);
                    $expPeriod = isset($courseMetasArr['expiration_period'][0]) ? $courseMetasArr['expiration_period'][0] : "";
                    $refPeriod = isset($courseMetasArr['refresher_period'][0]) ? $courseMetasArr['refresher_period'][0] : "";
                    $courseFullReset = isset($courseMetasArr['course_full_reset'][0]) ? $courseMetasArr['course_full_reset'][0] : "";

                    //get course meta for this user
                    $courseInfo = maybe_unserialize($usermeta['ldm_course_info_' . $course->ID][0]); //get_user_meta($u->ID, 'ldm_course_info_' . $course->ID, true);

                    if ($courseFullReset != 'course_reset_exam_only') {
                        $courseFullReset = "Retake whole course";
                    } else {
                        $courseFullReset = "Retake quiz only";
                    }

                    $course_status = learndash_course_status($course->ID, $u->ID);
                  
                    if ($expPeriod && $refPeriod && sfwd_lms_has_access($course->ID, $u->ID) && $courseInfo && is_array($courseInfo) && count($courseInfo) > 0) {
                        $timestampArr = maybe_unserialize($usermeta['ldm_course_completed_' . $course->ID][0]);
                        $historyLastElement = end($timestampArr);
                        reset($timestampArr);
                        if ($course_status == __('Not Started', 'learndash')) {
                            if ($historyLastElement) {
                                $completionDate = new DateTime('@' . $historyLastElement);
                                $overdueDate = clone $completionDate;
                                $overdueDate->modify('+' . $expPeriod . ' months');
                                $requiredDate = clone $overdueDate;
                                $requiredDate->modify('-' . $refPeriod . ' days');

                                if (strtotime($overdueDate->format('Y-m-d')) < time() && ($selectedStatus == 'overdue' || !$selectedStatus)) {
                                    $overArr = array();
                                    $overArr['user'] = $u;
                                    $overArr['course'] = $course;
                                    $overArr['refresher'] = $requiredDate;
                                    $overArr['expiration'] = $overdueDate;
                                    $overArr['retake_exam_only'] = $courseFullReset;
                                    $overArr['status'] = '<td class="user-course-cell" style="color: white; background-color: #FF0000">' . __('Refresher Overdue', 'ld_refresher') . '</td>';
                                    $overArr['status_text'] = 'Refresher Overdue';


                                    $finalArr[] = $overArr;
                                } elseif (strtotime($requiredDate->format('Y-m-d')) < time() && strtotime($overdueDate->format('Y-m-d')) > time() && ($selectedStatus == 'required' || !$selectedStatus)) {
                                    $refArr = array();
                                    $refArr['user'] = $u;
                                    $refArr['course'] = $course;
                                    $refArr['refresher'] = $requiredDate;
                                    $refArr['expiration'] = $overdueDate;
                                    $refArr['retake_exam_only'] = $courseFullReset;
                                    $refArr['status'] = '<td class="user-course-cell" style="color: white; background-color: #FFA500">' . __('Refresher Required', 'ld_refresher') . '</td>';
                                    $refArr['status_text'] = 'Refresher Required';

                                    $finalArr[] = $refArr;
                                }
                            }
                        }


                        if ($course_status == __('In Progress', 'learndash')) {
                            if ($historyLastElement) {
                                $completionDate = new DateTime('@' . $historyLastElement);
                                $overdueDate = clone $completionDate;
                                $overdueDate->modify('+' . $expPeriod . ' months');
                                $requiredDate = clone $overdueDate;
                                $requiredDate->modify('-' . $refPeriod . ' days');

                                if (strtotime($overdueDate->format('Y-m-d')) < time() && ($selectedStatus == 'overdue' || !$selectedStatus)) {
                                    $overArr = array();
                                    $overArr['user'] = $u;
                                    $overArr['course'] = $course;
                                    $overArr['refresher'] = $requiredDate;
                                    $overArr['expiration'] = $overdueDate;
                                    $overArr['retake_exam_only'] = $courseFullReset;
                                    $overArr['status'] = '<td class="user-course-cell" style="color: white; background-color: #FF0000">' . __('Refresher Overdue', 'ld_refresher') . '</td>';
                                    $overArr['status_text'] = 'Refresher Overdue';

                                    $finalArr[] = $overArr;
                                } elseif (strtotime($requiredDate->format('Y-m-d')) < time() && strtotime($overdueDate->format('Y-m-d')) > time() && ($selectedStatus == 'required' || !$selectedStatus)) {
                                    $refArr = array();
                                    $refArr['user'] = $u;
                                    $refArr['course'] = $course;
                                    $refArr['refresher'] = $requiredDate;
                                    $refArr['expiration'] = $overdueDate;
                                    $refArr['retake_exam_only'] = $courseFullReset;
                                    $refArr['status'] = '<td class="user-course-cell" style="color: white; background-color: #FFA500">' . __('Refresher Required', 'ld_refresher') . '</td>';
                                    $refArr['status_text'] = 'Refresher Required';

                                    $finalArr[] = $refArr;
                                }
                            }
                        }
                    }
                }
            }
            
            ?>
            <div class="wrap">
                <div id="icon-tools" class="icon32"></div>
                <h2><?php _e('Refresher Report', 'ld_refresher') ?></h2>
                <!--Filter Form-->
                <form action="" method="post" id="ldmrefresher_refresher_filter">

                    <!--List Groups-->
                    <select name="ldmrefresher_refresher_group" id="ldmrefresher_refresher_group">
                        <?php
                        foreach ($groupIds as $groupId) {
                            ?>
                            <option value="<?php echo $groupId; ?>" <?php echo ($groupId == $selectedGroup) ? "selected" : ""; ?> ><?php echo get_the_title($groupId); ?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <!--End List Groups-->

                    <!--List Courses-->
                    <select name="ldmrefresher_refresher_course" id="ldmrefresher_refresher_course">
                        <option value=""><?php _e('All Courses', 'ld_refresher') ?></option>
                        <?php foreach ($coursesfilter as $course) { ?>
                            <option value="<?php echo $course->ID; ?>" <?php if ($course->ID == $selectedCourse) { ?>selected<?php } ?>><?php echo $course->post_title; ?></option>
                        <?php } ?>
                    </select>
                    <!--End List Courses-->

                    <!--List Status-->
                    <select name="ldmrefresher_refresher_status" id="ldmrefresher_refresher_status">
                        <option value=""><?php _e('All Status', 'ld_refresher') ?></option>
                        <option value="required" <?php if ('required' == $selectedStatus) { ?>selected<?php } ?>><?php _e('Refresher Required', 'ld_refresher') ?></option>
                        <option value="overdue" <?php if ('overdue' == $selectedStatus) { ?>selected<?php } ?>><?php _e('Refresher Overdue', 'ld_refresher') ?></option>
                    </select>
                    <!--End List Status-->

                    <button type="submit" name="ldmrefresher_refresher_submit" id="ldmrefresher_refresher_submit"><?php _e('Filter', 'ld_refresher') ?></button>
                </form>
                <!--End Filter Form-->

                <!--Users List Table-->
                <table class="widefat has_export" id="group_matrix_report">
                    <!--Table Head-->
                    <style>
                        #group_matrix_report th{text-align:center;}
                    </style>
                    <thead>
                        <tr>
                            <th><?php _e('Send Email Notifications', 'ld_refresher') ?><br /><div style="font-size: xx-small;"><a id="ldr_select_all" href="#">Select All</a> / <a id="ldr_clear_all" href="#">Clear All</a></div></th>
                            <th><?php _e('User', 'ld_refresher') ?></th>
                            <th><?php _e('Course', 'ld_refresher') ?></th>
                            <th><?php _e('Refresher Required Date', 'ld_refresher') ?></th>
                            <th><?php _e('Refresher Overdue Date', 'ld_refresher') ?></th>
                            <th><?php _e('Retake Course / Quiz', 'ld_refresher') ?></th>
                            <th><?php _e('Status', 'ld_refresher') ?></th>
                        </tr>
                    </thead>
                    <!--End Table Head-->
                    <tbody>
                        <?php
                        if (!empty($finalArr)) {
                            foreach ($finalArr as $record) {

                                $chkbx_course_status = $record['status_text'];

                                ?>
                                <tr>
                                    <td style="text-align:center;">
                                    <input type="checkbox" class="email_chkbx" data-user="<?php echo $record['user']->ID; ?>" data-course="<?php echo $record['course']->ID; ?>" data-status="<?php echo $chkbx_course_status ?>">
                                    </td>

                                    <td><a href="<?php echo admin_url('admin.php') ?>?page=user_matrix_report_ldmv2&userId=<?php echo $record['user']->ID; ?>" ><?php echo $record['user']->first_name . " " . $record['user']->last_name; ?></a></td>
                                    <td><?php echo $record['course']->post_title; ?></td>

                                    <td>                        
                                        <?php echo $record['refresher']->format('d-m-Y'); ?>
                                    </td>
                                    <td>                        
                                        <?php echo $record['expiration']->format('d-m-Y'); ?>
                                    </td>

                                    <td>                        
                                        <?php echo $record['retake_exam_only']; ?>
                                    </td>

                                        <?php echo $record['status'] ?>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr><td colspan="6"><?php _e('No records available', 'ld_refresher') ?></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
                <!--End Users List Table-->
                
                <div id="export_container" style="display:none;">
                    <div id="export_table"></div>
                    <a id="export_download_button" download="refresher_report.xls" href="#" onclick="return ExcellentExport.excel(this, 'export_report_table', 'sheet1');"><?php _e('Export to Excel', 'ld_refresher') ?></a>
                </div>
                <a class="ldr_email_btn" href="#"><?php _e('Send Emails', 'ld_refresher') ?></a>
                <a class="ldmrefresher_export_csv" href="#"><?php _e('Export to Excel', 'ld_refresher') ?></a>
            </div>
            <?php
        }

    }

    $LearnDash_Refresher_Report = new LearnDash_Refresher_Report();
}
