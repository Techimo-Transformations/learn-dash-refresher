<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Course_Metabox')) {

    class LearnDash_Refresher_Course_Metabox {

        function __construct() {
            // add courses meta boxes for groups
            add_action('add_meta_boxes', array($this, 'add_courses_refresher_meta_box'));

            //save refresher metabox data
            add_action('save_post', array($this, 'save_courses_refresher_meta_box_data'));

            //save traditional course metabox data
            add_action('save_post', array($this, 'save_traditional_courses_refresher_meta_box_data'));

        }

        /**
         * Add course page meta boxes
         */
        function add_courses_refresher_meta_box() {
            add_meta_box('meta_refresher_traditional', __('Traditional Course', 'ld_refresher'), array($this, 'add_traditional_courses_refresher_meta_html_callback'), 'sfwd-courses');
            add_meta_box('meta_refresher', __('Course Refresher', 'ld_refresher'), array($this, 'add_courses_refresher_meta_html_callback'), 'sfwd-courses');
        }


        /**
         * Add traditional course refresher meta box html
         */
        function add_traditional_courses_refresher_meta_html_callback($post) {
            $id = $post->ID;
            $is_course_traditional = get_post_meta($id, 'is_course_traditional', true);
            ?>

            <!--traditional course submit document-->
                
                    <span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
                        <a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e('Click for Help!', 'ld_refresher'); ?>" onclick="toggleVisibility('sub_doc_tip');">
                            <img src="<?php echo LEARNDASH_LMS_PLUGIN_URL ?>/assets/images/question.png">
                            <label class="sfwd_label textinput"><?php _e('Does Course Require A Document Submission?', 'ld_refresher'); ?></label>
                        </a>
                    </span>
                    <span class="sfwd_option_input">
                        <div class="sfwd_option_div">
                            <input type="checkbox"  id="is_course_traditional" name="is_course_traditional" <?php echo ($is_course_traditional == "on") ? "checked" : ""; ?> />
                        </div>
                        <div class="sfwd_help_text_div" style="display:none" id="sub_doc_tip">
                            <label class="sfwd_help_text">
                                <?php _e('Tick box if a document requires submitting for this course', 'ld_refresher'); ?>
                            </label>
                        </div>
                    </span>
                    <p style="clear:left"></p>
                
            <!--End submit document-->

        <?php }


        /**
         * Save traditional course refresher metabox 
         * @param type $post_id
         */
        function save_traditional_courses_refresher_meta_box_data($post_id) {

            if ('trash' == get_post_status($post_id))
                return;

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return;
            if (isset($_POST['post_type']) && 'sfwd-courses' == $_POST['post_type']) {
                if ($_POST['is_course_traditional']) {                
                    update_post_meta($post_id, 'is_course_traditional', $_POST['is_course_traditional']);                  
                } else {
                    update_post_meta($post_id, 'is_course_traditional', false);
                }
            }
        }


        /**
         * Add course refresher mete box html
         */
        function add_courses_refresher_meta_html_callback($post) {

            $id = $post->ID;
            $is_course_refreshed = get_post_meta($id, 'is_course_refreshed', true);
            $course_full_reset = get_post_meta($id, 'course_full_reset', true);
            $refresher_period = get_post_meta($id, 'refresher_period', true);
            $expiration_period = get_post_meta($id, 'expiration_period', true);
            ?>

            <div class="sfwd_input " id="sfwd-courses_course_price_type">
                <span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
                    <a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e('Click for Help!', 'ld_refresher'); ?>" onclick="toggleVisibility('is_course_refreshed_tip');">
                        <img src="<?php echo LEARNDASH_LMS_PLUGIN_URL ?>/assets/images/question.png">
                        <label class="sfwd_label textinput"><?php _e('Refresher required', 'ld_refresher'); ?></label>
                    </a>
                </span>
                <span class="sfwd_option_input">
                    <div class="sfwd_option_div">
                        <input type="checkbox"  id="is_course_refreshed"  name="is_course_refreshed" <?php echo ($is_course_refreshed == "on") ? "checked" : ""; ?>/>
                    </div>
                    <div class="sfwd_help_text_div" style="display:none" id="is_course_refreshed_tip">
                        <label class="sfwd_help_text">
                            <?php _e('Check here to enable refresher for this course', 'ld_refresher'); ?>
                        </label>
                    </div>
                </span>
                <p style="clear:left"></p>
            </div>



            <div id="refreshed_settings" style="display:<?php echo($is_course_refreshed) ? "block" : "none"; ?>">


                <div class="sfwd_input " id="sfwd-courses_course_price_type">
                    <span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
                        <a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e('Click for Help!', 'ld_refresher'); ?>" onclick="toggleVisibility('course_full_reset_tip');">
                            <img src="<?php echo LEARNDASH_LMS_PLUGIN_URL ?>/assets/images/question.png">
                            <label class="sfwd_label textinput"><?php _e('Retake Course / Quiz', 'ld_refresher'); ?></label>
                        </a>
                    </span>
                    <span class="sfwd_option_input">
                        <div class="sfwd_option_div">
                            <label><input type="radio" name="course_full_reset" value="course_full_reset" <?php echo ($course_full_reset != 'course_reset_exam_only') ? "checked" : ""; ?>> <?php _e('Full reset of the course and quiz', 'ld_refresher'); ?></label>
                            <br>
                            <label><input type="radio" name="course_full_reset" value="course_reset_exam_only" <?php echo ($course_full_reset == 'course_reset_exam_only') ? "checked" : ""; ?>> <?php _e('Reset exam only', 'ld_refresher'); ?></label>
                        </div>
                        <div class="sfwd_help_text_div" style="display:none" id="course_full_reset_tip">
                            <label class="sfwd_help_text">
                                <?php _e('Check here to enable refresher for this course', 'ld_refresher'); ?>
                            </label>
                        </div>
                    </span>
                    <p style="clear:left"></p>
                </div>

                <!--Expiration Period-->
                <div class="sfwd_input " id="sfwd-courses_course_price_type">
                    <span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
                        <a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e('Click for Help!', 'ld_refresher'); ?>" onclick="toggleVisibility('expiration_period_tip');">
                            <img src="<?php echo LEARNDASH_LMS_PLUGIN_URL ?>/assets/images/question.png">
                            <label class="sfwd_label textinput"><?php _e('Expiration Period', 'ld_refresher'); ?></label>
                        </a>
                    </span>
                    <span class="sfwd_option_input">
                        <div class="sfwd_option_div">
                            <input type="text"  id="expiration_period" name="expiration_period" value="<?php echo esc_attr($expiration_period); ?>"/>  <label><?php _e('Months', 'ld_refresher'); ?></label>
                        </div>
                        <div class="sfwd_help_text_div" style="display:none" id="expiration_period_tip">
                            <label class="sfwd_help_text">
                                <?php _e('Set refreshed expiration period for course', 'ld_refresher'); ?>
                            </label>
                        </div>
                    </span>
                    <p style="clear:left"></p>
                </div>
                <!--End Expiration Period-->


                <!--Refresher Period-->
                <div class="sfwd_input " id="sfwd-courses_course_price_type">
                    <span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
                        <a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php _e('Click for Help!', 'ld_refresher'); ?>" onclick="toggleVisibility('refresher_period_tip');">
                            <img src="<?php echo LEARNDASH_LMS_PLUGIN_URL ?>/assets/images/question.png">
                            <label class="sfwd_label textinput"><?php _e('Refresher Period', 'ld_refresher'); ?></label>
                        </a>
                    </span>
                    <span class="sfwd_option_input">
                        <div class="sfwd_option_div">
                            <input type="text"  id="refresher_period" name="refresher_period" value="<?php echo esc_attr($refresher_period); ?>"/>  <label><?php _e('Days', 'ld_refresher'); ?></label>
                        </div>
                        <div class="sfwd_help_text_div" style="display:none" id="refresher_period_tip">
                            <label class="sfwd_help_text">
                                <?php _e('Set refreshed expiration period for course', 'ld_refresher'); ?>
                            </label>
                        </div>
                    </span>
                    <p style="clear:left"></p>
                </div>
                <!--End Refresher Period-->

            </div>

            <?php
        }

        /**
         * Save course refresher metabox 
         * @param type $post_id
         */
        function save_courses_refresher_meta_box_data($post_id) {

            if ('trash' == get_post_status($post_id))
                return;


            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return;
            if (isset($_POST['post_type']) && 'sfwd-courses' == $_POST['post_type']) {
                if ($_POST['is_course_refreshed']) {
                    if ($_POST['course_full_reset'] && $_POST['refresher_period'] && $_POST['expiration_period']) {
                        update_post_meta($post_id, 'is_course_refreshed', $_POST['is_course_refreshed']);

                        if ($_POST['course_full_reset'] == "course_full_reset") {
                            update_post_meta($post_id, 'course_full_reset', 'course_full_reset');
                        } else {
                            update_post_meta($post_id, 'course_full_reset', 'course_reset_exam_only');
                        }

                        update_post_meta($post_id, 'notice_period', $_POST['notice_period']);
                        update_post_meta($post_id, 'notification_period', $_POST['notification_period']);
                        update_post_meta($post_id, 'email_frequency', $_POST['email_frequency']);
                        update_post_meta($post_id, 'refresher_period', $_POST['refresher_period']);
                        update_post_meta($post_id, 'expiration_period', $_POST['expiration_period']);
                    }
                } else {
                    update_post_meta($post_id, 'is_course_refreshed', false);
                }
            }
        }

    }

    $LearnDash_Refresher_Course_Metabox = new LearnDash_Refresher_Course_Metabox();
}