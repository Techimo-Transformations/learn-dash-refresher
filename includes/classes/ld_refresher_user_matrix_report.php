<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_User_Matrix_Report')) {

    class LearnDash_Refresher_User_Matrix_Report {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_user_matrix_report"), 125);
        }

        function add_ld_refresher_user_matrix_report() {
            add_submenu_page(null, __('User Matrix Report', 'ld_refresher'), __('User Matrix Report', 'ld_refresher'), 'manage_ldmv2_reports', 'user_matrix_report_ldmv2', array($this, 'ld_refresher_user_matrix_report_options_callback'));
        }

        function ld_refresher_user_matrix_report_options_callback() {
            $allowLeaders = get_option('ldmv2_allow_leaders', '0');
            $currentUser = wp_get_current_user();
            $selectedStatus = '';
            if (isset($_GET['ldmrefresher_submit'])) {
                $selectedStatus = $_GET['ldmrefresher_status'];
            }
            ?>
            <div class="wrap">
                <div id="icon-tools" class="icon32"></div>
                <h2><?php _e('User Matrix Report', 'ld_refresher') ?></h2>
                <?php
                if (isset($_GET['userId']) && $_GET['userId'] && ($user = get_user_by('ID', $_GET['userId']))) {
                    ?>
                    <form action="" method="get" id="ldmrefresher_matrix_filter">
                        <select name="ldmrefresher_status" id="ldrefresher_status">
                            <option value=""><?php _e('All Status', 'ld_refresher') ?></option>
                            <option value="not_enrolled"<?php if($selectedStatus == 'not_enrolled'){ ?> selected<?php } ?>><?php _e('Not Enrolled', 'ld_refresher') ?></option>
                            <option value="not_started"<?php if($selectedStatus == 'not_started'){ ?> selected<?php } ?>><?php _e('Not Started', 'ld_refresher') ?></option>
                            <option value="in_progress"<?php if($selectedStatus == 'in_progress'){ ?> selected<?php } ?>><?php _e('In Progress', 'ld_refresher') ?></option>
                            <option value="completed"<?php if($selectedStatus == 'completed'){ ?> selected<?php } ?>><?php _e('Completed', 'ld_refresher') ?></option>
                            <option value="ref_required"<?php if($selectedStatus == 'ref_required'){ ?> selected<?php } ?>><?php _e('Refresher Required', 'ld_refresher') ?></option>
                            <option value="ref_overdue"<?php if($selectedStatus == 'ref_overdue'){ ?> selected<?php } ?>><?php _e('Refresher Overdue', 'ld_refresher') ?></option>
                        </select>
                        <input type="hidden" name="userId" value="<?php echo $_GET['userId']; ?>">
                        <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">

                        <button type="submit" name="ldmrefresher_submit" id="ldmrefresher_submit"><?php _e('Filter', 'ld_refresher') ?></button>
                    </form>
                    <div class="user_info">
                        <!--User Name-->
                        <div class="user_name">
                            <span><?php _e('Username', 'ld_refresher') ?></span>
                            <span><?php echo ldr_get_user_name($user); ?></span>
                        </div>
                        <!--End User Name-->

                        <!--User Groups-->
                        
                        <div class="user_groups">
                            <span><?php _e('Enrolled Groups', 'ld_refresher') ?></span>
                            <span><?php 
                            $usr_groups = learndash_get_users_group_ids($user->ID,true);
                            if(!empty($usr_groups)){
                            $i=1;
                            
                            foreach ($usr_groups as $groupID) {
                            
                                if($i < count($usr_groups)) {
                                    $cm = ", ";
                                }
                                else{
                                    $cm="";
                                }
                                echo get_the_title($groupID) . '<a id="ldr_remove_grp_lnk" data-user="'. $user->ID .'" data-group="'. $groupID .'"> [remove]</a>' .$cm;

                                $i++;
                            }}
                            else{echo "No Enrolled Groups";}

                            ?></span>
                        </div>
                        
                        <!--End User Groups-->

                        <!--User Sites-->
                        <div class="user_sites">
                            <span><?php _e('Sites', 'ld_refresher') ?></span>
                            <?php
                            $userSites = ($userSites = get_user_meta($user->ID, 'ldmv2_user_sites', true)) ? $userSites : array();
                            ?>
                            <ul>
                                <?php
                                if (!empty($userSites)) {
                                    foreach ($userSites as $userSite) {
                                        ?>
                                        <li><?php echo $userSite; ?></li>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <li><?php _e('No sites found', 'ld_refresher') ?></li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                        <!--End User Sites-->
                    </div>
                    <?php
                    // create user table data
                    $groups = learndash_get_users_group_ids($user->ID);
                    $countGroupsArr = array();
                    $countJobsArr = array();
                    $groupsArr = array();
                    $basicCount = 0;
                    foreach ($groups as $groupId) {
                        $groupName = get_the_title($groupId);
                        $countGroupsArr[$groupName] = 0;
                        $jobsIds = get_post_meta($groupId, 'ldmv2_jobs', true);
                        $jobsArgs = array(
                            'taxonomy' => 'ld_course_category',
                            'hide_empty' => true,
                            'include' => $jobsIds
                        );
                        $jobs = get_terms($jobsArgs);
                        $headerArr = array();
                        foreach ($jobs as $job) {
                            $countJobsArr[$groupName][$job->name] = 0;
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
                                $data = ldr_get_cell_data($user->ID, $course->ID, $allowLeaders, $currentUser, $groupName);
                                if((!$selectedStatus) || ($selectedStatus && $selectedStatus == $data['status'])){
                                    $coursesArr[$course->ID] = array('name' => $course->post_title, 'html' => $data['html']);
                                    $countGroupsArr[$groupName] ++;
                                    $countJobsArr[$groupName][$job->name] ++;
                                    
                                    $basicCount++;
                                }
                            }
                            
                            if(count($coursesArr)){
                                $headerArr[$job->name] = $coursesArr;
                            }
                        }
                        $groupsArr[$groupName] = $headerArr;
                    }
                    
                    if($basicCount){
                    ?>
                    <table class="widefat has_export" id="user_matrix_report">
                        <tbody>
                            <?php
                            foreach ($groupsArr as $group => $groupJobs) {
                                $groupCounter = 0;
                                ?>
                                <tr>
                                    <td rowspan="<?php echo $countGroupsArr[$group]; ?>"><?php echo $group; ?></td>
                                    <?php
                                    foreach ($groupJobs as $job => $jobCourses) {
                                        $counter = 0;
                                        if ($groupCounter > 0 && $counter == 0) {
                                            echo '<tr>';
                                        }
                                        ?>
                                        <td rowspan="<?php echo $countJobsArr[$group][$job]; ?>"><?php echo $job; ?></td>
                                        <?php
                                        foreach ($jobCourses as $id => $course) {
                                            if ($counter > 0) {
                                                echo '<tr>';
                                            }
                                            echo '<td>' . $course['name'] . '</td>';
                                            echo $course['html'];
                                            $counter++;
                                            ?>
                                        </tr>
                                        <?php
                                    }

                                    $groupCounter++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>

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
                    
                    <div id="export_container" style="display:none;">
                        <div id="export_table"></div>
                        <a id="export_download_button" download="<?php echo $user->user_login; ?>.xls" href="#" onclick="return ExcellentExport.excel(this, 'export_report_table', 'sheet1');"><?php _e('Export to Excel', 'ld_refresher') ?></a>
                    </div>
                    <a class="ldmrefresher_export_csv" href="#"><?php _e('Export to Excel', 'ld_refresher') ?></a>
                    <?php
                    }else{
                        ?>
                        <div id="setting-error-settings_updated" class="error settings-error">
                            <?php _e('No Data Found!!', 'ld_refresher'); ?>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div id="setting-error-settings_updated" class="error settings-error">
                        <?php _e('User Not Found', 'ld_refresher'); ?>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        }

    }

    $LearnDash_Refresher_User_Matrix_Report = new LearnDash_Refresher_User_Matrix_Report();
}

