<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Group_Matrix')) {

    class LearnDash_Refresher_Group_Matrix {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_group_matrix_settings"), 110);
        }

        function add_ld_refresher_group_matrix_settings() {
            add_submenu_page('learndash-lms', __('Groups Matrix Report', 'ld_refresher'), __('Groups Matrix Report', 'ld_refresher'), 'manage_ldmv2_reports', 'groups_matrix_report_ldmv2', array($this, 'add_ld_refresher_group_matrix_settings_callback'));
        }

        function add_ld_refresher_group_matrix_settings_callback() {
            $selectedGroup = '';
            $selectedJob = '';
            $selectedCourse = '';
            $selectedStatus = '';
            $selectedSite = '';
            $exportAllRecs = '';

            if (isset($_POST["ldmrefresher_export_all_csv"])){
                $exportAllRecs = true;
            }

            if (isset($_GET['ldmrefresher_submit'])) {
                $selectedGroup = $_GET['ldmrefresher_group'];
                $selectedJob = $_GET['ldmrefresher_job'];
                $selectedCourse = $_GET['ldmrefresher_course'];
                $selectedStatus = $_GET['ldmrefresher_status'];
                $selectedSite = $_GET['ldmrefresher_site'];
            }
            
            $allowLeaders = get_option('ldmv2_allow_leaders', '0');
            $usersCache = array();
            ?>

            <h2 <?php if (!$exportAllRecs) echo 'style="display:none;"' ?>>Once your data has downloaded click the button below to return to the previous page.</h2>
            <form><input type="button" <?php if (!$exportAllRecs) echo 'style="display:none;"' ?> id="ldmrefresher_export_all_bck_btn" value="Go Back To Matrix Report"></form>

            <div class="wrap" <?php if ($exportAllRecs) echo 'style="display:none;"' ?>>
                <div id="icon-tools" class="icon32"></div>
                <h2><?php _e('Training Matrix Report' , 'ld_refresher') ?></h2>
                <form action="" method="get" id="ldmrefresher_matrix_filter" style="display: none;">
                    <select name="ldmrefresher_group" id="ldmrefresher_group" data-job="<?php echo $selectedJob; ?>" data-course="<?php echo $selectedCourse; ?>">
                        <?php
                        $currentUser = wp_get_current_user();
                        if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {

                            $groupIds = learndash_get_administrators_group_ids($currentUser->ID);

                            $groups_query_args = array(
                                'post_type' => 'groups',
                                'nopaging' => true,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            );
                            $groups_query = new WP_Query($groups_query_args);
                            $allGroups = $groups_query->posts;
                            $groups = array();
                            foreach ($allGroups as $group) {
                                if (in_array($group->ID, $groupIds)) {
                                    $groups[] = $group;
                                }
                            }
                            if(count($groups)){
                            ?>
                            <option value=""><?php _e('All Groups', 'ld_refresher') ?></option>    
                            <?php
                            }
                        } else {
                            $groups_query_args = array(
                                'post_type' => 'groups',
                                'nopaging' => true,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            );

                            $groups_query = new WP_Query($groups_query_args);
                            $groups = $groups_query->posts;
                            
                            ?>
                            <option value=""><?php _e('All Groups', 'ld_refresher') ?></option>    
                            <?php
                        }

                        foreach ($groups as $group) {
                            ?>
                            <option value="<?php echo $group->ID; ?>" <?php if ($group->ID == $selectedGroup) { ?>selected<?php } ?>><?php echo $group->post_title; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <select name="ldmrefresher_job" id="ldmrefresher_job" <?php if(!$selectedGroup){ ?>style="display:none;"<?php } ?>>
                        
                    </select>
                    <select name="ldmrefresher_course" id="ldmrefresher_course">
                        <?php
                        $courses = array();
                        if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {
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
                        $counter = 0;
                        foreach($courses as $course){
                            if ($selectedGroup == '' && $selectedCourse == '' && $counter == 0) {
                                $selectedCourse = $course->ID;
                            }
                        ?>
                        <option value="<?php echo $course->ID; ?>"><?php echo $course->post_title; ?></option>
                        <?php 
                            $counter++;
                        } 
                        ?>
                    </select>
                    <select name="ldmrefresher_status" id="ldmrefresher_status" <?php if(!($selectedCourse && $selectedGroup)){ ?>style="display:none;"<?php } ?>>
                        <option value=""><?php _e('All Status', 'ld_refresher') ?></option>
                        <option value="not_enrolled"<?php if($selectedStatus == 'not_enrolled'){ ?> selected<?php } ?>><?php _e('Not Enrolled', 'ld_refresher') ?></option>
                        <option value="not_started"<?php if($selectedStatus == 'not_started'){ ?> selected<?php } ?>><?php _e('Not Started', 'ld_refresher') ?></option>
                        <option value="in_progress"<?php if($selectedStatus == 'in_progress'){ ?> selected<?php } ?>><?php _e('In Progress', 'ld_refresher') ?></option>
                        <option value="completed"<?php if($selectedStatus == 'completed'){ ?> selected<?php } ?>><?php _e('Completed', 'ld_refresher') ?></option>
                        <option value="ref_required"<?php if($selectedStatus == 'ref_required'){ ?> selected<?php } ?>><?php _e('Refresher Required', 'ld_refresher') ?></option>
                        <option value="ref_overdue"<?php if($selectedStatus == 'ref_overdue'){ ?> selected<?php } ?>><?php _e('Refresher Overdue', 'ld_refresher') ?></option>
                    </select>
                    <select name="ldmrefresher_site" id="ldmrefresher_site">
                        <option value=""><?php _e('All Sites', 'ld_refresher') ?></option>
                        <?php
                        $sites = get_option('ldmv2_sites');
                        if (!is_array($sites)) {
                            $sites = array();
                        }
                        foreach ($sites as $site) {
                            ?>
                            <option value="<?php echo $site; ?>" <?php if ($site == $selectedSite) { ?>selected<?php } ?>><?php echo $site; ?></option>
                        <?php } ?>
                    </select><button type="submit" name="ldmrefresher_submit" id="ldmrefresher_submit"><?php _e('Filter', 'ld_refresher') ?></button>
                    <br />
                    <span class='int_names'><?php _e('First name: ', 'ld_refresher') ?></span>
                    <?php
                    $alphas = range('A', 'Z');
                    foreach ($alphas as $letter) {
                        echo "<span> " . str_repeat('&nbsp;', 2) . " </span><span class='filter_first_name' name='filter_first_name'";
                        if(isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"] == $letter){
                            echo ' style="color: rgb(255, 0, 0);"';
                        }
                        echo ">" . $letter . "</span><span> </span>";
                    }
                    ?>
                    <br />
                    <span class='int_names' style='padding-right:11px;' ><?php _e('Surname: ', 'ld_refresher') ?></span>
                    <?php
                    foreach ($alphas as $letter) {
                        echo "<span> " . str_repeat('&nbsp;', 2) . " </span><span class='filter_last_name' name='filter_last_name'";
                        if(isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"] == $letter){
                            echo ' style="color: rgb(255, 0, 0);"';
                        }
                        echo ">" . $letter . "</span><span> </span>";
                    }
                    ?>
                    <h4></h4>

                    <input type="hidden" name="character_selected_first_name" id="character_selected_first_name" value="<?php if (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"] != "") { echo $_GET["character_selected_first_name"]; } ?>" />
                    <input type="hidden" name="character_selected_last_name" id="character_selected_last_name" value="<?php if (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"] != "") { echo $_GET["character_selected_last_name"]; } ?>"/>
                    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">

                    
                </form>

                <?php
                if($selectedGroup && $selectedCourse){
                    $headerArr = array();
                    $coursesArr = array($selectedCourse => get_the_title($selectedCourse));
                    $jobs = get_the_terms($selectedCourse, 'ld_course_category');
                    if(is_array($jobs)){
                        $jobName = '';
                        $jobsIds = get_post_meta($selectedGroup, 'ldmv2_jobs', true);
                        if(is_array($jobsIds)){
                            foreach($jobsIds as $jobsId){
                                foreach($jobs as $job){
                                    if($job->term_id == $jobsId){
                                        $jobName = $job->name;
                                        break 2; 
                                    }
                                }
                            }
                        }
                        
                        $headerArr[$jobName] = $coursesArr;
                    }elseif($jobs === false){
                        $headerArr[''] = $coursesArr;
                    }
                }elseif($selectedGroup){
                    $check = true;
                    $jobsArgs = array(
                        'taxonomy' => 'ld_course_category',
                        'hide_empty' => true
                    );
                    if ($selectedJob) {
                        $jobsArgs['include'] = $selectedJob;
                    } else {
                        $jobsIds = get_post_meta($selectedGroup, 'ldmv2_jobs', true);
                        if(is_array($jobsIds) && count($jobsIds)){
                            $jobsArgs['include'] = $jobsIds;
                        }else{
                            $check = false;
                        }
                    }
                    if($check){
                        $jobs = get_terms($jobsArgs);
                    }else{
                        $jobs = array();
                    }
                    $headerArr = array();
                    foreach ($jobs as $job) {
                        $courses = get_posts(array(
                            'post_type' => 'sfwd-courses',
                            'showposts' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'ld_course_category',
                                    'field' => 'term_id',
                                    'terms' => $job->term_id
                                )
                            )
                        ));
                        $coursesArr = array();
                        foreach ($courses as $course) {
                            $coursesArr[$course->ID] = $course->post_title;
                        }

                        $headerArr[$job->name] = $coursesArr;
                    }
                }elseif($selectedCourse){
                    $headerArr = array();
                    $coursesArr = array($selectedCourse => get_the_title($selectedCourse));
                    $jobs = get_the_terms($selectedCourse, 'ld_course_category');
                    if(is_array($jobs)){
                        $headerArr[$jobs[0]->name] = $coursesArr;
                    }elseif($jobs === false){
                        $headerArr[''] = $coursesArr;
                    }
                }

                //$current_page = get_query_var('paged') ? (int) get_query_var('paged') : 1;
                $current_page = $_GET['paged'] ? (int) $_GET['paged'] : 1;
                $users_per_page = get_option('ldmv2_users_per_page') ? (int) get_option('ldmv2_users_per_page') : 10;

                if ($exportAllRecs){
                    $users_per_page = 999999;
                }

                $user_query_args = array(
                    'orderby' => 'display_name',
                    'order' => 'ASC',
                );

                if($selectedGroup){
                    $user_query_args['meta_query'][] = array(
                        'key' => 'learndash_group_users_' . intval($selectedGroup),
                        'compare' => 'EXISTS',
                    );
                }else{
                    if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {
                        $groupsMetaArr = array('relation' => 'OR');
                        foreach($groups as $group){
                            $groupsMetaArr[] = array(
                                'key' => 'learndash_group_users_' . $group->ID,
                                'value' => $group->ID,
                            );
                        }
                        if(count($groupsMetaArr) > 1){
                            $user_query_args['meta_query'][] = $groupsMetaArr;       
                        }
                    }
                }
                
                if(!$selectedStatus){
                    $user_query_args['number'] = $users_per_page;
                    $user_query_args['paged'] = $current_page;
                }
                
                //if (!isset($_GET['ldmrefresher_submit_load_all'])) {
                if (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"] != "") {
                    $user_query_args['meta_query'][] = array(
                        'key' => 'first_name',
                        'value' => '^' . $_GET["character_selected_first_name"] . '.*',
                        'compare' => 'REGEXP'
                    );
                }

                if (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"] != "") {
                    $user_query_args['meta_query'][] = array(
                        'key' => 'last_name',
                        'value' => '^' . $_GET["character_selected_last_name"] . '.*',
                        'compare' => 'REGEXP'
                    );
                }

                if ($selectedSite) {
                    $user_query_args['meta_query'][] = array(
                        'key' => 'ldmv2_user_sites',
                        'compare' => 'EXISTS'
                    );
                    $user_query_args['meta_query'][] = array(
                        'key' => 'ldmv2_user_sites',
                        'value' => $selectedSite,
                        'compare' => 'LIKE'
                    );
                }

                $user_query = new WP_User_Query($user_query_args);
                if (isset($user_query->results)) {
                    $usersArr = $user_query->results;
                } else {
                    $usersArr = array();
                }
                $total_users = $user_query->get_total(); // How many users we have in total (beyond the current page)
                $num_pages = ceil($total_users / $users_per_page); // How many pages of users we will need
                ?>


                <div id="matrix_parent">
                    <table class="widefat matrix_fix has_export" id="group_matrix_report">
                        <thead>
                            <tr>
                                <th rowspan="2"><?php _e('User', 'ld_refresher') ?></th>
                                <?php
                                $cols = 1;
                                foreach ($headerArr as $job => $jobCourses) {
                                    ?>
                                    <th colspan="<?php echo count($jobCourses); ?>"><?php echo $job; ?></th>
                                    <?php
                                    $cols += count($jobCourses);
                                }
                                ?>
                            </tr>
                            <tr>
                                <?php
                                foreach ($headerArr as $job => $jobCourses) {
                                    foreach ($jobCourses as $id => $jobCourse) {
                                        ?>
                                        <th><?php echo $jobCourse; ?></th>
                                        <?php
                                    }
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if(count($usersArr)){
                                $finalArr = array();
                                foreach ($usersArr as $userObj) {
                                    foreach ($headerArr as $job => $jobCourses) {
                                        foreach ($jobCourses as $id => $jobCourse) {
                                            $cell_data = ldr_get_cell_data($userObj->ID, $id, $allowLeaders, $currentUser, $selectedGroup);
                                            $usersCache[$userObj->ID] = $userObj;
                                            $finalArr[$userObj->ID][$job][$id] = $cell_data;
                                        }
                                    }
                                }

                                $rowsHTML = '';
                                foreach($finalArr as $key => $job_data){
                                    $rowsExists = 0;
                                    $rowHTML = '';

                                    $rowHTML .= '<tr>';
                                    $rowHTML .= '<td><a href="'.admin_url('admin.php').'?page=user_matrix_report_ldmv2&userId='.$key.'">'.ldr_get_user_name($usersCache[$key]).'</a></td>';
                                    foreach($job_data as $jobKey => $user_data){
                                        foreach($user_data as $id => $data){
                                            if((!$selectedStatus) || ($selectedStatus && $selectedStatus == $data['status'])){
                                                $rowHTML .= $data['html'];
                                                $rowsExists++;
                                            }
                                        }
                                    }
                                    if($rowsExists){
                                        $rowHTML .= '</tr>';
                                    }else{
                                        $rowHTML = '';
                                    }

                                    $rowsHTML .= $rowHTML;
                                }
                                if($rowsHTML){
                                    echo $rowsHTML;
                                }else{
                                    ?>
                                    <tr><td colspan="<?php echo $cols; ?>"><?php _e('No Data Found!!', 'ld_refresher') ?></td></tr>
                                    <?php
                                }
                            }else{
                            ?>
                            <tr><td colspan="<?php echo $cols; ?>"><?php _e('No Data Found!!', 'ld_refresher') ?></td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div id="datepickerholder" class="modal" style="display: none;">
                    <div class="modal-content">

                        <div id="datepicker" data-courseId="" data-userId="" data-status="" data-dropdown="" data-selector=""></div>
                        <a id="pop_confirm" href=""><?php _e('Confirm', 'ld_refresher') ?></a><a id="pop_cancel" href=""><?php _e('Cancel', 'ld_refresher') ?></a>
                    </div>

                </div>

                <div id="editdatepickerholder" class="modal" style="display: none;">
                    <div class="modal-content">

                        <div id="editdatepicker" data-courseId="" data-userId="" data-dropdown=""></div>
                        <a id="edit_pop_confirm" href=""><?php _e('Confirm', 'ld_refresher') ?></a><a id="edit_pop_cancel" href=""><?php _e('Cancel', 'ld_refresher') ?></a>
                    </div>

                </div>

                <div id="uploaddocumentholder" class="modal" style="display: none;">
                    <div class="modal-content modal-content-upload">
                        
                        <div id="uploaddocument" data-courseId="" data-userId="" data-dropdown=""></div>
                        <div id="up_mod">
                            <input id="selectfilebtn" type="file" name="selectfilebtn" />
                        </div>
                            <a id="upload_pop_confirm" href=""><?php _e('Confirm', 'ld_refresher') ?></a><a id="upload_pop_cancel" href=""><?php _e('Cancel', 'ld_refresher') ?></a>
                        
                    </div>
                </div>

                <div id="export_container" style="display:none;">
                    <div id="export_table"></div>
                    <a id="export_download_button" download="matrix_report.xls" href="#" onclick="return ExcellentExport.excel(this, 'export_report_table', 'sheet1');"><?php _e('Export to Excel', 'ld_refresher') ?></a>
                </div>
                <a class="ldmrefresher_export_csv" href="#"><?php _e('Export to Excel', 'ld_refresher') ?></a>
                <?php if(!$selectedStatus){ ?>
                <div class="matrix-pagination-container">
                    <?php
                    // Previous page
                    if ($current_page > 1) {
                        $paginationArgs = array('paged' => $current_page - 1);
                        
                        if (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"] != "") {
                            $paginationArgs["character_selected_first_name"] = $_GET["character_selected_first_name"];
                        }

                        if (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"] != "") {
                            $paginationArgs["character_selected_last_name"] = $_GET["character_selected_last_name"];
                        }
                        
                        if (isset($_GET["ldmrefresher_group"]) && $_GET["ldmrefresher_group"] != "") {
                            $paginationArgs["ldmrefresher_group"] = $_GET["ldmrefresher_group"];
                        }

                        if (isset($_GET["ldmrefresher_job"]) && $_GET["ldmrefresher_job"] != "") {
                            $paginationArgs["ldmrefresher_job"] = $_GET["ldmrefresher_job"];
                        }
                        
                        if (isset($_GET["ldmrefresher_course"]) && $_GET["ldmrefresher_course"] != "") {
                            $paginationArgs["ldmrefresher_course"] = $_GET["ldmrefresher_course"];
                        }

                        if (isset($_GET["ldmrefresher_site"]) && $_GET["ldmrefresher_site"] != "") {
                            $paginationArgs["ldmrefresher_site"] = $_GET["ldmrefresher_site"];
                        }
                        
                        echo '<a href="' . add_query_arg($paginationArgs) . '"><< Previous Page</a>';
                    }

                    // Next page
                    if ($current_page < $num_pages) {
                        $paginationArgs = array('paged' => $current_page + 1);
                        
                        if (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"] != "") {
                            $paginationArgs["character_selected_first_name"] = $_GET["character_selected_first_name"];
                        }

                        if (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"] != "") {
                            $paginationArgs["character_selected_last_name"] = $_GET["character_selected_last_name"];
                        }
                        
                        if (isset($_GET["ldmrefresher_group"]) && $_GET["ldmrefresher_group"] != "") {
                            $paginationArgs["ldmrefresher_group"] = $_GET["ldmrefresher_group"];
                        }

                        if (isset($_GET["ldmrefresher_job"]) && $_GET["ldmrefresher_job"] != "") {
                            $paginationArgs["ldmrefresher_job"] = $_GET["ldmrefresher_job"];
                        }
                        
                        if (isset($_GET["ldmrefresher_course"]) && $_GET["ldmrefresher_course"] != "") {
                            $paginationArgs["ldmrefresher_course"] = $_GET["ldmrefresher_course"];
                        }

                        if (isset($_GET["ldmrefresher_site"]) && $_GET["ldmrefresher_site"] != "") {
                            $paginationArgs["ldmrefresher_site"] = $_GET["ldmrefresher_site"];
                        }
                        
                        echo '<a href="' . add_query_arg($paginationArgs) . '">Next Page >></a>';
                    }
                    ?>
                </div>
                <?php } ?>
                <form action="" method="post" id="matrix_export_all">
                <input type="submit" name="ldmrefresher_export_all_csv" id="ldmrefresher_export_all_csv" class="ldmrefresher_export_all_csv" value="<?php _e('Export all results to Excel', 'ld_refresher') ?>">
                </form>
                <?php
                if ($exportAllRecs){
                    echo '<script type="text/javascript">',
                    'jQuery(".ldmrefresher_export_csv")[0].click();',
                    '</script>';
                }
                ?>
            </div>
            <?php
        }

    }

    $LearnDash_Refresher_Group_Matrix = new LearnDash_Refresher_Group_Matrix();
}
