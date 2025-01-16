<?php
/**
 * Plugin Name: Learn Dash Refresher Updated
 * Description: Extra learn dash matrix reports.
 * Version: 4.0.1
 * Author: totrain.
 * Author URI: https://www.totrain.co.uk/
 * Text Domain: ld_refresher
 * License: GPL
 */
if (in_array('sfwd-lms/sfwd_lms.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    if (!class_exists('LearnDash_Refresher')) {

        define('LDR_MODIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('LDR_MODIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));

        class LearnDash_Refresher {

            public function __construct() {
                //Include Plugin Files
                $this->includes();

                //Assign Capabilities To Admin/Group Leader 
                add_action('admin_init', array($this, 'ld_refresher_add_plugin_capability_callback'));

                //Include Plugin Scripts
                add_action('admin_enqueue_scripts', array($this, 'ld_refresher_add_plugin_scripts'));
            }

            /**
             * Include Plugin Files
             */
            private function includes() {
                //common functions in plugin 
                include LDR_MODIFY_PLUGIN_PATH . 'includes/functions/ld_refresher_functions.php';
                //plugin ajaxs
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_ajaxs.php';
                //plugin cronjob
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_cronjob.php';
                //metabox files
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_group_metabox.php';
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_course_metabox.php';
                //learndash hooks
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_learndash_hooks.php';
                //shortcodes
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_shortcodes.php';
                /************************menu items files************ */
                //enroll/edit enroll user
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_enrol_user.php';
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_edit_enrol_user.php';

                //refresher reports
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_report.php';
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_user_matrix_report.php';

                //refresher history
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_history.php';
                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_user_history.php';

                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_group_matrix.php';

                include LDR_MODIFY_PLUGIN_PATH . 'includes/classes/ld_refresher_settings.php';
            }

            /**
             * Assign Capabilities To Admin/Group Leader 
             */
            public function ld_refresher_add_plugin_capability_callback() {
                //Group Leader Capabilities
                $leaderRole = get_role('group_leader');
                $leaderRole->add_cap('manage_ldmv2_reports');
                $leaderRole->add_cap('edit_ldmv2_users');

                //Admin Capabilities
                $adminRole = get_role('administrator');
                $adminRole->add_cap('manage_ldmv2_reports');
                $adminRole->add_cap('edit_ldmv2_users');
                $adminRole->add_cap('manage_ldmv2_reports_settings');
            }

            /**
             * Include Plugin Scripts
             */
            public function ld_refresher_add_plugin_scripts($hook) {
                if ($hook == 'admin_page_junior_edit_user') {
                    wp_enqueue_style('learndash_style', LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style.min.css', array(), LEARNDASH_VERSION);
                    wp_enqueue_style('sfwd-module-style', LEARNDASH_LMS_PLUGIN_URL . '/assets/css/sfwd_module.min.css', array(), LEARNDASH_VERSION);
                    wp_enqueue_script('sfwd-module-script', LEARNDASH_LMS_PLUGIN_URL . '/assets/js/sfwd_module.min.js', array('jquery'), LEARNDASH_VERSION, true);
                    wp_enqueue_script('learndash-admin-binary-selector-script', LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-binary-selector.min.js', array('jquery'), LEARNDASH_VERSION, true);
                    wp_enqueue_style('learndash-admin-binary-selector-style', LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-binary-selector.min.css', array(), LEARNDASH_VERSION);
                    wp_localize_script('sfwd-module-script', 'sfwd_data', array());
                }
                
                if($this->is_edit_page()){
                    wp_enqueue_script('ldr_course_script', LDR_MODIFY_PLUGIN_URL . 'assets/js/course_script.js', array('jquery'));
                }
                
                if($this->is_edit_page(null, 'groups') || in_array($hook, ['learndash-lms_page_enrol_new_users_ldmv2', 'admin_page_junior_edit_user'])){
                    wp_enqueue_style('ldr_select2_style', "https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css");
                    wp_enqueue_script('ldr_select2_script', "https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js", array('jquery'));
                    wp_enqueue_script('ldr_select2_init_script', LDR_MODIFY_PLUGIN_URL . 'assets/js/select2_script.js', array('ldr_select2_script'));
                    
                    if($this->is_edit_page(null, 'groups')){
                        $placeholder = __('Choose Job(s)', 'ld_refresher');
                    }elseif(in_array($hook, ['learndash-lms_page_enrol_new_users_ldmv2', 'admin_page_junior_edit_user'])){
                        $placeholder = __('Choose Site(s)', 'ld_refresher');
                    }
                    wp_localize_script('ldr_select2_init_script', 'messages', array('placeholder' => $placeholder));
                }

                if((strpos($hook, 'learndash-lms') !== false) || in_array($hook, ['admin_page_junior_edit_user', 'admin_page_user_matrix_report_ldmv2', 'admin_page_user_history_ldmv2'])){
                    wp_enqueue_style('ldr_css', LDR_MODIFY_PLUGIN_URL . 'assets/css/style.css');
                    wp_enqueue_style('ldr_jquery-ui-style', "https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css");
                    wp_enqueue_style('ldr_jquery-confirm-style', "https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css");
                    
                    wp_enqueue_script('ldr_jquery-ui-script', "https://code.jquery.com/ui/1.12.1/jquery-ui.js", array('jquery'));
                    wp_enqueue_script('ldr_jquery-confirm-script', "https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js", array('jquery'));
                    wp_enqueue_script('ldr_fix_table_script', LDR_MODIFY_PLUGIN_URL . 'assets/js/tableHeadFixer.js', array('jquery'));
                    wp_enqueue_script('ldr_excellent_export_script', LDR_MODIFY_PLUGIN_URL . 'assets/js/excellentexport.js', array('jquery'));
                    wp_enqueue_script('ldr_script', LDR_MODIFY_PLUGIN_URL . 'assets/js/script.js', array('jquery'));
                    wp_localize_script('ldr_script', 'user_data', array('page' => menu_page_url('junior_edit_user', false), 'text' => __('Update User', 'ld_refresher'), 'confirm_status_title' => __('Change status', 'ld_refresher'), 'confirm_status' => __('Are you sure you want to change status to', 'ld_refresher'), 'migrate_title' => __('Migrate', 'ld_refresher'), 'migrate_new_site_message' => __('Are you sure you want to run "new website" migration process?', 'ld_refresher'), 'migrate_v3_message' => __('Are you sure you want to run "version 3" migration process?', 'ld_refresher'), 'change_note' => __('Note: Completion date will be changed.', 'ld_refresher'), 'delete_note' => __('Note: All user course data will be deleted.', 'ld_refresher')));
                }
            }

            /**
             * Define cronjob
             */
            public function cronjob_activation() {
                if (!wp_next_scheduled('ldr_check_refresher_event')) {
                    wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'ldr_check_refresher_event');
                }
            }

            /**
             * Delete cronjob
             */
            public function cronjob_deactivation() {
                wp_clear_scheduled_hook('ldr_check_refresher_event');
            }
            
            private function is_edit_page($new_edit = null, $type = 'sfwd-courses'){
                global $pagenow, $typenow;
                //make sure we are on the backend
                if (!is_admin()){ 
                    return false;
                }
                
                if($type != $typenow){
                    return false;
                }

                if($new_edit == "edit"){
                    return in_array($pagenow, array('post.php'));
                }elseif($new_edit == "new"){ //check for new post page
                    return in_array($pagenow, array('post-new.php'));
                }else{ //check for either new or edit
                    return in_array($pagenow, array('post.php', 'post-new.php'));
                }
            }

        }

        $LearnDash_Refresher = new LearnDash_Refresher();

        //plugin cronjobs
        register_activation_hook(__FILE__, array("LearnDash_Refresher", "cronjob_activation"));
        register_deactivation_hook(__FILE__, array("LearnDash_Refresher", "cronjob_deactivation"));
    }
} else {

    /**
     * Add notice to admin if Learndash plugin is not active
     */
    add_action('admin_notices', 'ldr_notify_admin_with_required_plugin_callback');

    function ldr_notify_admin_with_required_plugin_callback() {
        ?>
        <div id="message" class="error">
            <p> <?php _e('Learndash plugin should be installed and activated', 'ld_refresher'); ?></p>
        </div>
        <?php
    }

}