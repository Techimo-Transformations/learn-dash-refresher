<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_History')) {

    class LearnDash_Refresher_History {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_history"), 125);
        }

        function add_ld_refresher_history() {
            add_submenu_page('learndash-lms', __('Refresher History', 'ld_refresher'), __('Refresher History', 'ld_refresher'), 'manage_ldmv2_reports', 'refresher_history_ldmv2', array($this, 'ld_refresher_history_callback'));
        }

        function ld_refresher_history_callback() {

            //get groups by user
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
            } else {
                $groups_query_args = array(
                    'post_type' => 'groups',
                    'nopaging' => true,
                    'orderby' => 'title',
                    'order' => 'ASC'
                );

                $groups_query = new WP_Query($groups_query_args);
                $groups = $groups_query->posts;
            }
            /*$groups_query_args = array(
                'post_type' => 'groups',
                'nopaging' => true
            );
            $groups = get_posts($groups_query_args);*/

            if(isset($_GET["ldmrefresherhistory_group"]) && $_GET["ldmrefresherhistory_group"]) {
                $selectedGroup = $_GET["ldmrefresherhistory_group"];
                $jobsIds = get_post_meta($selectedGroup, 'ldmv2_jobs', true);
                $courses = get_posts(array(
                    'post_type' => 'sfwd-courses',
                    'showposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'ld_course_category',
                            'field' => 'term_id',
                            'terms' => $jobsIds
                        )
                    )
                ));
            }else{
                $selectedGroup = '';
                /*$courses = get_posts(array(
                    'post_type' => 'sfwd-courses',
                    'showposts' => -1
                ));*/
                $courses = array();
                if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {
                    $courses = ldr_get_leader_courses($groupIds);
                }else{
                    $course_args = array(
                        'post_type' => 'sfwd-courses',
                        'showposts' => -1
                    );
                    $courses = get_posts($course_args);
                }
            }

            //check functionality in learndash_get_groups_users
            $current_page = $_GET['paged'] ? (int) $_GET['paged'] : 1;
            $users_per_page = get_option('ldmv2_users_per_page') ? (int) get_option('ldmv2_users_per_page') : 10;
            $user_query_args = array(
                'number' => $users_per_page,
                'paged' => $current_page,
                'orderby' => 'display_name',
                'order' => 'ASC',
            );
            
            if($selectedGroup){
                $user_query_args['meta_query'][] = array(
                    'key' => 'learndash_group_users_' . intval($selectedGroup),
                    'compare' => 'EXISTS',
                );
            }

            //check if search by first name
            if (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"]) {
                $user_query_args ['meta_query'][] = array(
                    'key' => 'first_name',
                    'value' => '^' . strtolower($_GET["character_selected_first_name"]) . '.*',
                    'compare' => 'REGEXP',
                );
            }

            if (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"]) { //check if search by first name
                $user_query_args ['meta_query'][] = array(
                    'key' => 'last_name',
                    'value' => '^' . strtolower($_GET["character_selected_last_name"]) . '.*',
                    'compare' => 'REGEXP',
                );
            }
            $user_query = new WP_User_Query($user_query_args);
            $users = $user_query->results;
            $total_users = $user_query->get_total(); // How many users we have in total (beyond the current page)
            $num_pages = ceil($total_users / $users_per_page);
            ?>
            <h2 style="font-size:23px; font-weight: 400;"><?php _e('Course History', 'ld_refresher') ?></h2>

            <!--Search Form-->
            <form action="" method="get" id="ldmrefresher_history_filter">

                <!--Group List-->
                <select name="ldmrefresherhistory_group" id="ldmrefresherhistory_group">
                    <option value=""><?php _e('All Groups', 'ld_refresher') ?></option>
                    <?php
                    foreach ($groups as $group) {
                        ?>
                        <option value="<?php echo $group->ID; ?>" <?php if ($group->ID == $selectedGroup) { ?>selected<?php } ?>><?php echo $group->post_title; ?></option>
                        <?php
                    }
                    ?>
                </select>
                <!--End Group List-->

                <span class="submit">
                    <input type="submit" name="ldmv2refresherhistory_submit" id="ldmv2refresherhistory_submit" class="button button-primary" value="<?php _e('Filter', 'ld_refresher') ?>">
                </span>
                
                <!--First Name Filter-->
                <div class="first-name-filter">
                <span class='int_names'><?php _e('First name: ', 'ld_refresher'); ?></span>
                    <?php
                    $alphas = range('A', 'Z');
                    foreach ($alphas as $letter) {
                        ?>
                        <span> &nbsp;&nbsp; </span>
                        <span class="filter_first_name" name="filter_first_name" <?php echo (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"] == $letter) ? "style='color: rgb(255, 0, 0);'" : ""; ?>><?php echo $letter; ?></span>
                        <?php
                    }
                    ?>
                </div>
                <!--End First Name Filter-->

                <!--Last Name Filter-->
                
                <div class="last-name-filter">
                <span class='int_names' style='padding-right:11px;'><?php _e('Surname: ', 'ld_refresher'); ?></span>
                    <?php
                    foreach ($alphas as $letter) {
                        ?>
                        <span> &nbsp;&nbsp; </span>
                        <span class="filter_last_name" name="filter_last_name" <?php echo (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"] == $letter) ? "style='color: rgb(255, 0, 0);'" : ""; ?>><?php echo $letter; ?></span>

                        <?php
                    }
                    ?>
                </div>
                <!--End Last Name Filter-->
                
                <input type="hidden" name="character_selected_first_name" id="character_selected_first_name" value="<?php echo (isset($_GET["character_selected_first_name"]) && $_GET["character_selected_first_name"]) ? $_GET["character_selected_first_name"] : ""; ?>"/>
                <input type="hidden" name="character_selected_last_name" id="character_selected_last_name"  value="<?php echo (isset($_GET["character_selected_last_name"]) && $_GET["character_selected_last_name"]) ? $_GET["character_selected_last_name"] : ""; ?>"  />
                <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">

            </form>
            <!--End Search Form-->

            <table class="widefat" style="margin-top: 20px;" id="refresher_history_report">
                <thead>
                    <tr>
                        <th><?php _e('User', 'ld_refresher') ?></th>
                        <th><?php _e('Course', 'ld_refresher') ?></th>
                        <th><?php _e('Date Of Completion', 'ld_refresher') ?></th>
                        <th><?php _e('Certificates List', 'ld_refresher') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(count($users)){
                        foreach ($users as $user) {
                            $record = $this->ldr_get_user_history_record($user, $courses);
                            ?>
                            <tr>
                                <td><?php echo $record['user']; ?></td>
                                <td><?php echo $record['courses']; ?></td>
                                <td><?php echo $record['completion']; ?></td>
                                <td><?php echo $record['link']; ?></td>
                            </tr>
                            <?php
                        }
                    }else{
                    ?>
                        <tr><td colspan="4"><?php _e('No records available', 'ld_refresher') ?></td></tr>
                    <?php    
                    }
                    ?>
                </tbody>
            </table>
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

                    if (isset($_GET["ldmrefresherhistory_group"]) && $_GET["ldmrefresherhistory_group"] != "") {
                        $paginationArgs["ldmrefresherhistory_group"] = $_GET["ldmrefresherhistory_group"];
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

                    if (isset($_GET["ldmrefresherhistory_group"]) && $_GET["ldmrefresherhistory_group"] != "") {
                        $paginationArgs["ldmrefresherhistory_group"] = $_GET["ldmrefresherhistory_group"];
                    }

                    echo '<a href="' . add_query_arg($paginationArgs) . '">Next Page >></a>';
                }
                ?>
            </div>
            <?php
        }
        
        private function ldr_get_user_history_record($user, $courses) {
            $record = array('user' => ldr_get_user_name($user), 'courses' => '---', 'completion' => '---', 'link' => '---');
            $firstCourse = null;
            $counter = 0;
            if(count($courses)){
                $select = '<select class="user_courses_dropdown">';
                foreach($courses as $course){
                    if($counter == 0){
                        $firstCourse = $course;
                    }
                    
                    $select .= '<option id="'.$user->ID.'_'.$course->ID.'">'.$course->post_title.'</option>';
                    
                    $counter++;
                }
                $select .= '</select>';
                
                $record['courses'] = $select;
            }
            
            if($firstCourse){
                $course_completed = get_user_meta($user->ID, 'ldm_course_completed_' . $firstCourse->ID, true);
                
                if(is_array($course_completed) && count($course_completed)){
                    $historyLastElement = end($course_completed);
                    reset($course_completed);
                    
                    $completionDate = new DateTime('@' . $historyLastElement);
                    $record['completion'] = $completionDate->format('d-m-Y');
                    $record['link'] = '<a href="'.admin_url('admin.php').'?page=user_history_ldmv2&userId='.$user->ID.'&courseId='.$firstCourse->ID.'" class="button button-primary">'. __('View Certificates', 'ld_refresher').'</a>';
                }
            }
            
            return $record;
        }

    }

    $LearnDash_Refresher_History = new LearnDash_Refresher_History();
}

