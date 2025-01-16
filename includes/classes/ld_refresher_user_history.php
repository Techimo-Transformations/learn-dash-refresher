<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_User_History')) {

    class LearnDash_Refresher_User_History {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_user_history"), 125);
        }

        function add_ld_refresher_user_history() {
            add_submenu_page(null, __('Go To User History', 'ld_refresher'), __('Go To User History', 'ld_refresher'), 'manage_ldmv2_reports', 'user_history_ldmv2', array($this, 'ld_refresher_user_history_options_callback'));
        }

        function ld_refresher_user_history_options_callback() {

            if (isset($_GET['userId']) && ($userId = $_GET['userId']) && ($u = get_userdata($userId)) && isset($_GET['courseId']) && ($courseId = $_GET['courseId']) && ($courseData = get_post($courseId))) {
                $showButton = false;
                $currentUser = wp_get_current_user();
                $allowLeaders = get_option('ldmv2_allow_leaders', '0');
                if (learndash_course_status($courseId, $userId) != __('Completed', 'learndash')) {
                    $showButton = true;
                }
                ?>
                <h3><?php _e('Certificates History for User:', 'ld_refresher'); ?> <?php echo ldr_get_user_name($u);?></h3>

                <h4><?php _e('Course:', 'ld_refresher'); ?> <?php echo $courseData->post_title;?></h4>

                <table class="widefat" id="refresher_history_report">
                    <thead>
                        <tr>
                            <th><?php _e('Date Of Completion', 'ld_refresher') ?></th>
                            <th><?php _e('Certificate Link', 'ld_refresher') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $course_completed = get_user_meta($u->ID, 'ldm_course_completed_' . $courseData->ID, true);
                        
                        $counter = 1;
                        foreach ($course_completed as $index => $timestamp) {
                            $time = new DateTime('@' . $timestamp);
                            ?>
                            <tr>
                                <td>
                                    <span><?php echo $time->format('d-m-Y'); ?></span>
                                    <?php if (count($course_completed) == $counter) { ?>
                                        <?php /*if ($showButton && (($currentUser && in_array('administrator', (array) $currentUser->roles)) || ($currentUser && in_array('group_leader', (array) $currentUser->roles) && $allowLeaders))) { ?>
                                            <button class="button button-primary edit_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" style="float:right;"><?php _e('Edit Date', 'ld_refresher') ?></button>
                                        <?php }*/ ?>
                                        <?php
                                        //if($showButton){
                                            if($currentUser && in_array('group_leader', (array) $currentUser->roles)){
                                                if($allowLeaders){
                                                    ?>
                                                <button class="button button-primary delete_date_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" style="float:right; margin-left: 10px;"><?php _e('Delete Date', 'ld_refresher') ?></button>
                                                <button class="button button-primary edit_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" data-last="<?php if($showButton){ ?>0<?php }else{ ?>1<?php } ?>" style="float:right;"><?php _e('Edit Date', 'ld_refresher') ?></button>
                                                    <?php
                                                }
                                            }else{
                                                ?>
                                                <button class="button button-primary delete_date_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" style="float:right; margin-left: 10px;"><?php _e('Delete Date', 'ld_refresher') ?></button>
                                                <button class="button button-primary edit_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" data-last="<?php if($showButton){ ?>0<?php }else{ ?>1<?php } ?>" style="float:right;"><?php _e('Edit Date', 'ld_refresher') ?></button>
                                                <?php
                                            }
                                        //}
                                        ?>
                                    <?php } else { ?>
                                        <?php /* if ((($currentUser && in_array('administrator', (array) $currentUser->roles)) || ($currentUser && in_array('group_leader', (array) $currentUser->roles) && $allowLeaders))) { ?>
                                        <button class="button button-primary edit_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" style="float:right;"><?php _e('Edit Date', 'ld_refresher') ?></button>
                                        <?php }*/ ?>
                                        <?php
                                        if($currentUser && in_array('group_leader', (array) $currentUser->roles)){
                                            if($allowLeaders){
                                                ?>
                                                <button class="button button-primary delete_date_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" style="float:right; margin-left: 10px;"><?php _e('Delete Date', 'ld_refresher') ?></button>
                                                <button class="button button-primary edit_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" data-last="0" style="float:right;"><?php _e('Edit Date', 'ld_refresher') ?></button>
                                                <?php
                                            }
                                        }else{
                                            ?>
                                            <button class="button button-primary delete_date_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" style="float:right; margin-left: 10px;"><?php _e('Delete Date', 'ld_refresher') ?></button>
                                            <button class="button button-primary edit_history_button" data-user="<?php echo $userId; ?>" data-course="<?php echo $courseId; ?>" data-index="<?php echo $index; ?>" data-date="<?php echo $time->format('Y-m-d'); ?>" data-last="0" style="float:right;"><?php _e('Edit Date', 'ld_refresher') ?></button>
                                            <?php
                                        }
                                        ?> 
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php
                                    $cert_link = $this->ldr_get_course_certificate_link($courseId, $u->ID);
                                    $doc_link = null;
                                    $is_trad = get_post_meta($courseId, 'is_course_traditional', true);
                                    if ($is_trad == "on")$doc_link = $this->ldr_get_trad_course_doc_link($courseId, $u->ID);
                                    if ($doc_link && $counter == count($course_completed)) {?>
                                    <a href="<?php echo $doc_link; ?>" class="button button-primary" target="_blank"><?php _e('Open Uploaded Certificate', 'ld_refresher') ?></a>
                                    <?php }
                                    else if($cert_link){?>
                                    <a href="<?php echo $cert_link; ?>&cert_index=<?php echo $index; ?>" class="button button-primary" target="_blank"><?php _e('Open Certificate', 'ld_refresher') ?></a>
                                    <?php } ?>
                                </td>

                            </tr>
                            <?php
                            $counter++;
                        }
                        ?>
                    </tbody>
                </table>

                <div id="datepickerholder" class="modal" style="display: none;">
                    <div class="modal-content">

                        <div id="datepicker" data-courseId="" data-userId="" data-status="" data-dropdown="" data-selector=""></div>
                        <a  class='history_pop_confirm' href=""><?php _e('Confirm', 'ld_refresher') ?></a><a id="history_pop_cancel" href=""><?php _e('Cancel', 'ld_refresher') ?></a>
                    </div>

                </div>
                <?php
            }
        }

        function ldr_get_course_certificate_link($course_id, $cert_user_id = null) {
            $cert_user_id = !empty($cert_user_id) ? intval($cert_user_id) : get_current_user_id();

            if (( empty($course_id) ) || ( empty($cert_user_id) )) {
                return '';
            }

            $certificate_id = learndash_get_setting($course_id, 'certificate');
            if (empty($certificate_id)) {
                return '';
            }

            if (( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ))
                $view_user_id = get_current_user_id();
            else
                $view_user_id = $cert_user_id;

            $cert_query_args = array(
                "course_id" => $course_id,
            );

            // We add the user query string key/value if the viewing user is an admin. This 
            // allows the admin to view other user's certificated
            if (( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) )) {
                $cert_query_args['user'] = $cert_user_id;
            }
            $cert_query_args['cert-nonce'] = wp_create_nonce($course_id . $cert_user_id . $view_user_id);

            $url = add_query_arg($cert_query_args, get_permalink($certificate_id));
            return $url;
        }

        function ldr_get_trad_course_doc_link($course_id, $cert_user_id) {
           

            if (( empty($course_id) ) || ( empty($cert_user_id) )) {
                return '';
            }

            $url = get_user_meta($cert_user_id, '_ldr_subed_doc_' . $course_id, true);

            return $url;
        }


    }

    $LearnDash_Refresher_User_History = new LearnDash_Refresher_User_History();
}

