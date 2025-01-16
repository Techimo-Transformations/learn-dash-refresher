<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Group_Metabox')) {

    class LearnDash_Refresher_Group_Metabox {

        function __construct() {
            // add jobs meta box for groups
            add_action('add_meta_boxes', array($this, 'add_jobs_meta_box'));

            //save group jobs
            add_action('save_post', array($this, 'jobs_save_meta_box_data'));
        }

        /**
         * Add job mete box
         */
        function add_jobs_meta_box() {
            add_meta_box('group-jobs', __('Jobs', 'ld_refresher'), array($this, 'jobs_meta_box_html_callback'), 'groups');
        }

        /**
         * Add job mete box html
         */
        function jobs_meta_box_html_callback($post) {
            
            $group_jobs = ($group_jobs = get_post_meta($post->ID, 'ldmv2_jobs', true)) ? $group_jobs : array();
          
            ?>
            <div class="sfwd_input" id="ldmv2_group_jobs">
                <span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
                    <a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php  _e('Click for Help!', 'ld_refresher'); ?>" onclick="toggleVisibility('ldmv2_group_jobs_tip');">
                        <img src="<?php echo LEARNDASH_LMS_PLUGIN_URL ?>assets/images/question.png">
                        <label class="sfwd_label textinput"><?php _e('Group Jobs', 'ld_refresher'); ?></label>
                    </a>
                </span>
                
                <span class="sfwd_option_input">
                    <div class="sfwd_option_div">
                        <select name="group_jobs[]" class="ldr_multi_select" multiple="">
                            <?php
                            $jobs = get_terms(array(
                                'taxonomy' => 'ld_course_category',
                                'hide_empty' => false
                            ));

                            foreach ($jobs as $job) {
                                ?>
                                <option value="<?php echo $job->term_id; ?>"<?php if (in_array($job->term_id, $group_jobs)) { ?> selected<?php } ?>><?php echo $job->name; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="sfwd_help_text_div" style="display:none" id="ldmv2_group_jobs_tip">
                        <label class="sfwd_help_text"><?php _e('Choose the jobs that you want to associate with this group', 'ld_refresher'); ?></label>
                    </div>
                    
                </span>
                <p style="clear:left"></p>
            </div>    
            <?php
        }

        /**
         * Save job metabox 
         * @param type $post_id
         */
        function jobs_save_meta_box_data($post_id) {
            
             if ('trash' == get_post_status($post_id))
                return;


            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return;
            
            if (isset($_POST['post_type']) && 'groups' == $_POST['post_type']) {
                if (isset($_POST['group_jobs']) && !empty($_POST['group_jobs'])) {
                    update_post_meta($post_id, 'ldmv2_jobs', $_POST['group_jobs']);
                } else {
                    update_post_meta($post_id, 'ldmv2_jobs', array());
                }
            }
        }

    }

    $LearnDash_Refresher_Group_Metabox = new LearnDash_Refresher_Group_Metabox();
}