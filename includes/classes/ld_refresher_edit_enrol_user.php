<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Edit_Enrol_User')) {

    class LearnDash_Refresher_Edit_Enrol_User {

        public function __construct() {
            //Add  Edit User (Enroll User Group) Menu Item
            add_action('admin_menu', array($this, "add_ld_refresher_edit_enrol_user_settings"), 110);
            // Add Edit User (Enroll User Group) To User Actions
            add_filter('user_row_actions', array($this, 'edit_enroll_groups_action'), 20, 2);
            // override default learndash template(override course info shortcode)
            add_filter('learndash_template', array($this, 'override_ld_course_info_shortcode'), 20, 5);
        }

        function add_ld_refresher_edit_enrol_user_settings() {
            add_submenu_page(null, __('Edit User', 'ld_refresher'), __('Edit User', 'ld_refresher'), 'edit_ldmv2_users', 'junior_edit_user', array($this, 'add_ld_refresher_edit_enrol_user_settings_callback'));
        }

        /**
         * Add Edit User (Enroll User Group) To User Actions
         * @param array $actions
         * @param type $user
         * @return string
         */
        function edit_enroll_groups_action($actions, $user) {
            if (!current_user_can('edit_ldmv2_users')) {
                return $actions;
            }
            $actions['ldmv2_junior_edit'] = '<a href="' . menu_page_url('junior_edit_user', false) . '&useId=' . $user->ID . '">' . __('Update User', 'ld_refresher') . '</a>';
            return $actions;
        }

        /*         * *
         * Edit Enroll User Html
         */

        function add_ld_refresher_edit_enrol_user_settings_callback() {

            if (isset($_GET['useId']) && $_GET['useId']) {

                $errors = array();
                $message = $courseMessage = false;
                $currentUser = wp_get_current_user();

                $user_id = intval($_GET['useId']);

                //get sites option
                $sites = ($sites = get_option('ldmv2_sites')) ? $sites : array();
				//Updated -- Get all types option here //
			/*$utypes = array("senior-management","management-supervisory","technical-qa","operative","engineering","office-hr","food-safety-quality","sales-customer-service","logistics-distribution");*/
			$utypes = array(
							'senior-management' 		=> 'Senior Management',
							'management-supervisory'	=> 'Management/Supervisory',
							'technical-qa'				=> 'Technical/QA',
							'operative'					=> 'Operative',
							'engineering'				=> 'Engineering',
							'office-hr'					=> 'Office Hr',
							'food-safety-quality'		=> 'Food Safety Quality',
							'sales-customer-service'	=> 'Sales Customer Service',
							'logistics-distribution'	=> 'Logistics Distribution'
						);
			//end types
                //get templates by users
                $templates = ldr_get_templates_by_users();

                //get users by id
                $user = get_user_by('ID', $user_id);
                if ($user) { //users exists
                    //update user form is submitted
                    if (isset($_POST['createCompanyUser'])) {

                        //check email
                        if (isset($_POST['email']) && !trim($_POST['email'])) {
                            $errors[] = __('Email is required', 'ld_refresher');
                        } elseif ((($submittedEmailExist = email_exists($_POST['email'])) && $submittedEmailExist && $submittedEmailExist != $user->ID)) {
                            $errors[] = __('Email already exists', 'ld_refresher');
                        }

                        //check first name
                        if (isset($_POST['first_name']) && !trim($_POST['first_name'])) {
                            $errors[] = __('First Name is required', 'ld_refresher');
                        }

                        //check last name
                        if (isset($_POST['last_name']) && !trim($_POST['last_name'])) {
                            $errors[] = __('Last Name is required', 'ld_refresher');
                        }

                        if ($_POST['pass'] != $_POST['pass2']) {
                            $errors[] = __('Both password fields must match', 'ld_refresher');
                        }

                        if (empty($errors)) {//no errors (ready to update user)
                            $userdata = array(
                                'ID' => $user_id
                            );

                            if (isset($_POST['first_name']) && $_POST['first_name']) {
                                $userdata['first_name'] = esc_attr($_POST['first_name']);
                            }

                            if (isset($_POST['last_name']) && $_POST['last_name']) {
                                $userdata['last_name'] = esc_attr($_POST['last_name']);
                            }

                            if (isset($_POST['email']) && $_POST['email']) {
                                $userdata['user_email'] = esc_attr($_POST['email']);
                            }

                            if (isset($_POST['pass']) && $_POST['pass']) {
                                $userdata['user_pass'] = esc_attr($_POST['pass']);
                            }

                            $user_id = wp_update_user($userdata); //update users

                            if (!is_wp_error($user_id)) {
                                // remove all groups assocciated to this user first
                                $groupIds = learndash_get_users_group_ids($user_id);
                                foreach ($groupIds as $groupId) {
                                    $isGroup = get_user_meta($user_id, 'learndash_group_users_' . $groupId, true);
                                    if ($isGroup) {
                                        delete_user_meta($user_id, 'learndash_group_users_' . $groupId);
                                    }
                                }

                                //add new group
                                if (isset($_POST['ldmrefresher_group_user']) && $_POST['ldmrefresher_group_user']) {
                                    update_user_meta($user_id, 'learndash_group_users_' . $_POST['ldmrefresher_group_user'], $_POST['ldmrefresher_group_user']);
                                }

                                // remove all courses assocciated to this user first
                                $userCourses = ld_get_mycourses($user_id);
                                foreach ($userCourses as $courseId) {
                                    ld_update_course_access($user_id, $courseId, true);
                                }

                                //assign courses to user
                                if (isset($_POST['ldmrefresher_course']) && count($_POST['ldmrefresher_course']) > 0) {
                                    foreach ($_POST['ldmrefresher_course'] as $course_id) {
                                        ld_update_course_access($user_id, $course_id);
                                    }
                                }

                                //assign sites to user
                                $newsites = array();
                                if (isset($_POST['ldmrefresher_site']) && !empty($_POST['ldmrefresher_site'])) {
                                    $newsites = $_POST['ldmrefresher_site'];
									//code to update data in user edit in admin
									$usersite = reset($newsites);
									$usersite = str_replace( array( '\'', '"', ',' , ';', '<', '>', '-', ' ', '.' ), '', $usersite);
					                $usersite = strtolower($usersite);
									update_user_meta($user_id, 'fs_site', $usersite);
									//end user edit admin
                                }
                                update_user_meta($user_id, 'ldmv2_user_sites', $newsites);
                                 //code to update types
								if (isset($_POST['ldmrefresher_utypes'])) {
									update_user_meta($user_id, 'fs_type', $_POST['ldmrefresher_utypes']);
								} else {
									update_user_meta($user_id, 'fs_type', "");
								}

                                $user = get_user_by('ID', $user_id);

                                $message = true;
                            }
                        }
                    }

                    if (isset($_POST['updateCourseInfo'])) {
                        if (( isset($_POST['user_progress']) ) && ( isset($_POST['user_progress'][$user_id]) ) && (!empty($_POST['user_progress'][$user_id]) )) {
                            if (( isset($_POST['user_progress-' . $user_id . '-nonce']) ) && (!empty($_POST['user_progress-' . $user_id . '-nonce']) )) {
                                if (wp_verify_nonce($_POST['user_progress-' . $user_id . '-nonce'], 'user_progress-' . $user_id)) {
                                    $user_progress = (array) json_decode(stripslashes($_POST['user_progress'][$user_id]));
                                    $user_progress = json_decode(json_encode($user_progress), true);

                                    $processed_course_ids = array();

                                    if (( isset($user_progress['course']) ) && (!empty($user_progress['course']) )) {

                                        $usermeta = get_user_meta($user_id, '_sfwd-course_progress', true);
                                        $course_progress = empty($usermeta) ? array() : $usermeta;

                                        $_COURSE_CHANGED = false; // Simple flag to let us know we changed the quiz data so we can save it back to user meta.

                                        foreach ($user_progress['course'] as $course_id => $course_data) {

                                            $processed_course_ids[] = $course_id;

                                            $course_progress[$course_id] = $course_data;
                                            $_COURSE_CHANGED = true;
                                        }

                                        if ($_COURSE_CHANGED === true)
                                            update_user_meta($user_id, '_sfwd-course_progress', $course_progress);
                                    }

                                    if (( isset($user_progress['quiz']) ) && (!empty($user_progress['quiz']) )) {

                                        $usermeta = get_user_meta($user_id, '_sfwd-quizzes', true);
                                        $quizz_progress = empty($usermeta) ? array() : $usermeta;
                                        $_QUIZ_CHANGED = false; // Simple flag to let us know we changed the quiz data so we can save it back to user meta.

                                        foreach ($user_progress['quiz'] as $course_id => $course_quiz_set) {
                                            foreach ($course_quiz_set as $quiz_id => $quiz_new_status) {
                                                $quiz_meta = get_post_meta($quiz_id, '_sfwd-quiz', true);


                                                if (!empty($quiz_meta)) {
                                                    $quiz_old_status = !learndash_is_quiz_notcomplete($user_id, array($quiz_id => 1));

                                                    if ($quiz_new_status == true) {
                                                        if ($quiz_old_status != true) {

                                                            // If the admin is marking the quiz complete AND the quiz is NOT already complete...
                                                            // Then we add the minimal quiz data to the user profile
                                                            $quizdata = array(
                                                                'quiz' => $quiz_id,
                                                                'score' => 0,
                                                                'count' => 0,
                                                                'pass' => true,
                                                                'rank' => '-',
                                                                'time' => time(),
                                                                'pro_quizid' => $quiz_meta['sfwd-quiz_quiz_pro'],
                                                                'course' => $course_id,
                                                                'points' => 0,
                                                                'total_points' => 0,
                                                                'percentage' => 0,
                                                                'timespent' => 0,
                                                                'has_graded' => false,
                                                                'statistic_ref_id' => 0,
                                                                'm_edit_by' => get_current_user_id(), // Manual Edit By ID
                                                                'm_edit_time' => time()   // Manual Edit timestamp
                                                            );

                                                            $quizz_progress[] = $quizdata;
                                                            /* $_QUIZ_CHANGED = true;
                                                              $pass = true; */
                                                            if ($quizdata['pass'] == true)
                                                                $quizdata_pass = true;
                                                            else
                                                                $quizdata_pass = false;

                                                            // Then we add the quiz entry to the activity database. 
                                                            learndash_update_user_activity(
                                                                    array(
                                                                        'course_id' => $course_id,
                                                                        'user_id' => $user_id,
                                                                        'post_id' => $quiz_id,
                                                                        'activity_type' => 'quiz',
                                                                        'activity_action' => 'insert',
                                                                        'activity_status' => $quizdata_pass,
                                                                        'activity_started' => $quizdata['time'],
                                                                        'activity_completed' => $quizdata['time'],
                                                                        'activity_meta' => $quizdata
                                                                    )
                                                            );

                                                            $_QUIZ_CHANGED = true;
                                                        }
                                                    } else if ($quiz_new_status != true) {
                                                        if ($quiz_old_status == true) {

                                                            if (!empty($quizz_progress)) {
                                                                foreach ($quizz_progress as $quiz_idx => $quiz_item) {

                                                                    if (($quiz_item['quiz'] == $quiz_id) && ($quiz_item['pass'] == true)) {
                                                                        $quizz_progress[$quiz_idx]['pass'] = false;

                                                                        // We need to update the activity database records for this quiz_id
                                                                        $activity_query_args = array(
                                                                            'post_ids' => $quiz_id,
                                                                            'user_ids' => $user_id,
                                                                            'activity_type' => 'quiz'
                                                                        );
                                                                        $quiz_activity = learndash_reports_get_activity($activity_query_args);
                                                                        if (( isset($quiz_activity['results']) ) && (!empty($quiz_activity['results']) )) {
                                                                            foreach ($quiz_activity['results'] as $result) {
                                                                                if (( isset($result->activity_meta['pass']) ) && ( $result->activity_meta['pass'] == true )) {

                                                                                    // If the activity meta 'pass' element is set to true we want to update it to false. 
                                                                                    learndash_update_user_activity_meta($result->activity_id, 'pass', false);

                                                                                    //Also we need to update the 'activity_status' for this record
                                                                                    learndash_update_user_activity(
                                                                                            array(
                                                                                                'activity_id' => $result->activity_id,
                                                                                                'course_id' => $course_id,
                                                                                                'user_id' => $user_id,
                                                                                                'post_id' => $quiz_id,
                                                                                                'activity_type' => 'quiz',
                                                                                                'activity_action' => 'update',
                                                                                                'activity_status' => false,
                                                                                            //'activity_started'		=>	$result->activity_started,
                                                                                            )
                                                                                    );
                                                                                }
                                                                            }
                                                                        }

                                                                        $_QUIZ_CHANGED = true;
                                                                    }

                                                                    /**
                                                                     * Remove the quiz lock. 
                                                                     * @since 2.3.1
                                                                     */
                                                                    if (( isset($quiz_item['pro_quizid']) ) && (!empty($quiz_item['pro_quizid']) )) {
                                                                        learndash_remove_user_quiz_locks($user_id, $quiz_item['quiz']);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    $processed_course_ids[intval($course_id)] = intval($course_id);

                                                    // add refresher logic here
                                                    if ($_QUIZ_CHANGED) {
                                                        $expPeriod = get_post_meta($course_id, 'expiration_period', true);
                                                        $refPeriod = get_post_meta($course_id, 'refresher_period', true);

                                                        if ($expPeriod && $refPeriod) {
                                                            $courseId = get_post_meta($quiz_id, 'course_id', true);
                                                            $lessonId = get_post_meta($quiz_id, 'lesson_id', true);
                                                            if ($courseId && $lessonId == '0') {
                                                                $courseInfo = get_user_meta($user_id, 'ldm_course_info_' . $course_id, true);
                                                                if ($quizdata_pass) {
                                                                    if ($courseInfo && is_array($courseInfo)) {
                                                                        foreach ($courseInfo as $key => $value) {
                                                                            if (next($courseInfo) === false) {
                                                                                $unique = $key;
                                                                            }
                                                                        }
                                                                    } else {
                                                                        $unique = uniqid();
                                                                        $newInfo = array();
                                                                        $newInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);
//                                                                $newInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);

                                                                        update_user_meta($user_id, 'ldm_course_info_' . $course_id, $newInfo);
                                                                    }

                                                                    // add completed quiz data in history meta
                                                                    $quizdata['uniqueId'] = $unique;

                                                                    $historyArr = get_user_meta($user_id, '_ldm-sfwd-quizzes', true);
                                                                    if (!is_array($historyArr)) {
                                                                        $historyArr = array();
                                                                    }
                                                                    $historyArr[] = $quizdata;
                                                                    update_user_meta($user_id, '_ldm-sfwd-quizzes', $historyArr);
                                                                } else {
                                                                    $unique = uniqid();
                                                                    if ($courseInfo && is_array($courseInfo)) {
                                                                        $newInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);
                                                                    } else {
                                                                        $newInfo = array();
                                                                        $newInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);
                                                                    }
                                                                    update_user_meta($user_id, 'ldm_course_info_' . $course_id, $newInfo);
                                                                }
                                                            }
                                                        }
                                                    }
                                                    // end refresher logic
                                                    //}
                                                }
                                            }
                                        }

                                        if ($_QUIZ_CHANGED == true) {
                                            $ret = update_user_meta($user_id, '_sfwd-quizzes', $quizz_progress);
                                        }
                                    }

                                    if (!empty($processed_course_ids)) {
                                        foreach (array_unique($processed_course_ids) as $course_id) {
                                            learndash_process_mark_complete($user_id, $course_id);
                                        }
                                    }

                                    $courseMessage = true;
                                }
                            }
                        }
                    }
                    ?>
                    <div class="wrap">
                        <div id="icon-tools" class="icon32"></div>
                        <h2><?php _e('Edit User', 'ld_refresher') ?></h2>

                        <!--Form Errors-->
                        <?php
                        if (!empty($errors)) {
                            ?>
                            <div id="setting-error-settings_updated" class="error settings-error">
                                <?php foreach ($errors as $error) { ?>
                                    <p><strong><?php echo $error; ?></strong></p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <!--End Form Errors-->

                        <!--Form Updated Succesfully-->
                        <?php
                        if ($message) {
                            ?>
                            <div id="message" class="updated notice notice-success">
                                <p><?php _e('User updated Succesfully', 'ld_refresher'); ?></p>
                            </div>
                        <?php } ?>
                        <!--Form Updated Succesfully-->

                        <?php
                        if ($courseMessage) {
                            ?>
                            <div id="message" class="updated notice notice-success">
                                <p><?php _e('Course info updated Succesfully', 'ld_refresher'); ?></p>
                            </div>
                        <?php } ?>
                        <!--Edit User Enroll Form--> 
                        <form method="post" name="createCompanyUserForm" id="createCompanyUserForm">
                            <table class="form-table">
                                <tbody>

                                    <!--Username-->
                                    <tr class="form-field form-required">
                                        <th scope="row"><label for="user_login"><?php _e('Username', 'ld_refresher') ?> <span class="description">(<?php _e('can not be changed', 'ld_refresher') ?>)</span></label></th>
                                        <td><input name="user_login" type="text" id="user_login" value="<?php echo $user->user_login; ?>"  autocapitalize="none" maxlength="60" disabled="true"></td>
                                    </tr>
                                    <!--End Username-->


                                    <!--Email-->
                                    <tr class="form-field form-required">
                                        <th scope="row"><label for="email"><?php _e('Email', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                                        <td><input name="email" type="email" id="email" value="<?php echo (!empty($errors) && isset($_POST['email'])) ? $_POST['email'] : $user->user_email; ?>" required="true"></td>
                                    </tr>
                                    <!--End Email-->

                                    <!--First Name-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="first_name"><?php _e('First Name', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                                        <td><input name="first_name" type="text" id="first_name" value="<?php echo (!empty($errors) && isset($_POST['first_name'])) ? $_POST['first_name'] : $user->first_name; ?>" required="true"></td>
                                    </tr>
                                    <!--End First Name-->

                                    <!--Last Name-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="last_name"><?php _e('Last Name', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                                        <td><input name="last_name" type="text" id="last_name" value="<?php echo (!empty($errors) && isset($_POST['last_name'])) ? $_POST['last_name'] : $user->last_name; ?>" required="true"></td>
                                    </tr>
                                    <!--End Last Name-->

                                    <!--Password-->
                                    <tr class="form-field form-required">
                                        <th scope="row"><label for="pass"><?php _e('Password', 'ld_refresher') ?></label></th>
                                        <td>
                                            <input name="pass" type="password" id="pass">
                                        </td>
                                    </tr>
                                    <!--End Password-->

                                    <!--Confirm Password-->
                                    <tr class="form-field form-required">
                                        <th scope="row"><label for="pass2"><?php _e('Repeat Password', 'ld_refresher') ?></label></th>
                                        <td>
                                            <input name="pass2" type="password" id="pass2">
                                        </td>
                                    </tr>
                                    <!--End Confirm Password-->

                                    <!--Sites-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="ldmrefresher_site"><?php _e('Sites', 'ld_refresher') ?> </label></th>
                                        <td>
                                            <select name="ldmrefresher_site[]" class="ldr_multi_select" id="ldmrefresher_site" multiple="true">
                                                <?php
                                                //get users sites
                                                $user_sites = ($user_sites = get_user_meta($user->ID, 'ldmv2_user_sites', true)) ? $user_sites : array();
												print_r($user_sites);
												echo "test";
                                                foreach ($sites as $site) {
                                                    ?>
                                                    <option value="<?php echo $site; ?>" <?php echo ((!empty($errors) && isset($_POST['ldmrefresher_site']) && in_array($site, $_POST['ldmrefresher_site'])) || (in_array($site, $user_sites))) ? "selected" : ""; ?> > <?php echo $site; ?></option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <!--End Sites-->
									<!--User types-->
									<tr class="form-field">
										<th scope="row"><label for="ldmrefresher_utypes"><?php _e('Types', 'ld_refresher') ?> </label></th>
									<?php 
								$usertype = ($usertype =	get_user_meta($user_id, 'fs_type', true)) ? $usertype : "";
								
								?>
										<td>
											<select name="ldmrefresher_utypes" class="ldr_multi_select" id="ldmrefresher_utypes" >
												<?php
												
                                    foreach ($utypes as $utype => $utype_value) {
                                        ?>
                                        <option value="<?php echo $utype; ?>" <?php 
										if($usertype == $utype){ echo "selected"; } ?>><?php echo $utype_value; ?></option>
                                    <?php } ?>
											</select>
										</td>
									</tr>
									<!--End User Types-->
                                    <!--User Groups-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="ldmrefresher_group_user"><?php _e('Groups', 'ld_refresher') ?> </label></th>
                                        <td>
                                            <select name="ldmrefresher_group_user" id="ldmrefresher_group_user" data-user="<?php echo $user->ID; ?>">
                                                <option value=""><?php _e('Select group', 'ld_refresher') ?></option>
                                                <?php
                                                //get groups by user is
                                                $groupIds = ldr_get_groups_by_logged_in_user($currentUser);
												//print_r($groupIds);
                                                foreach ($groupIds as $groupId) {
                                                    ?>
                                                    <option value="<?php echo $groupId; ?>" <?php echo( (!empty($errors) && isset($_POST['ldmrefresher_group']) && $groupId == $_POST['ldmrefresher_group']) || (!isset($_POST['ldmrefresher_group']) && learndash_is_user_in_group($user->ID, $groupId))) ? "selected" : ""; ?>  ><?php echo get_the_title($groupId); ?></option>
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <!--End User Groups-->
                                </tbody>
                            </table>

                            <div id="jobs-courses-contanier">
                                <h3><?php _e('Course Enrolment', 'ld_refresher') ?></h3>
                                <div id="courses-lists">
                                    <p><?php _e('Choose a group first', 'ld_refresher'); ?></p>
                                </div>
                            </div>

                            <!--Save Templates-->
                            <div id="template-contanier">
                                <h3><?php _e('Courses Templates', 'ld_refresher') ?></h3>
                                <div id="save_template_error" style="display: none;">
                                </div>
                                <div id="save_template_success" style="display: none;">
                                    <p><?php _e('Template Saved Succesfully', 'ld_refresher'); ?></p>
                                </div>
                                <div id="load-template">
                                    <?php
                                    if (!empty($templates)) {
                                        ?>
                                        <select id="templates_select">
                                            <option value=""><?php _e('Select Template', 'ld_refresher') ?></option>
                                            <?php
                                            foreach ($templates as $template => $value) {
                                                ?>
                                                <option value="<?php echo $template ?>"><?php echo $template ?></option>
                                            <?php } ?>
                                        </select>
                                        <button type="button" id="load_template"><?php _e('Load template', 'ld_refresher'); ?></button>
                                        <?php
                                    } else {
                                        ?>
                                        <p><?php _e('No templates available', 'ld_refresher'); ?></p>
                                    <?php } ?>

                                </div>
                                <div id="save-template">
                                    <input type="text" id="template_name" placeholder="Template Name">
                                    <button type="button" id="save_template"><?php _e('Save template', 'ld_refresher'); ?></button><br/>
                                    <span class="description">* <?php _e('If the template name already exists, the template will be updated.', 'ld_refresher') ?></span>
                                </div>
                            </div>
                            <!--End Save Templates-->

                            <p class="submit"><input type="submit" name="createCompanyUser" id="createCompanyUser" class="button button-primary" value="<?php _e('Update User', 'ld_refresher') ?>"></p>
                        </form>
                        <!--End Edit User Enroll Form-->


                        <!--Course Info-->
                        <div class="junior_course_info">
                            <form method="post">
                                <?php
                                echo '<h3>' . sprintf(_x('%s Info', 'Course Info Label', 'learndash'), LearnDash_Custom_Label::get_label('course')) . '</h3>';
                                echo $this->ld_referesher_get_course_info($user_id);
                                ?>
                                <p class="submit"><input type="submit" name="updateCourseInfo" id="updateCourseInfo" class="button button-primary" value="<?php _e('Update Course Info', 'ld_refresher') ?>"></p>
                            </form>
                        </div>
                        <!--End Course Info-->

                    </div>
                    <?php
                } else {//if user not exist
                    ?>
                    <div id="setting-error-settings_updated" class="error settings-error">
                        <p><strong><?php _e('No user found', 'ld_refresher'); ?></strong></p>
                    </div>
                    <?php
                }
            }
        }

        /**
         * Get Courses Info Section(same in user profile page)
         * @param type $user_id
         * @return type
         */
        function ld_referesher_get_course_info($user_id) {
            $courses_registered = ld_get_mycourses($user_id);

            $usermeta = get_user_meta($user_id, '_sfwd-course_progress', true);
            $course_progress = empty($usermeta) ? false : $usermeta;

            $usermeta = get_user_meta($user_id, '_sfwd-quizzes', true);
            $quizzes = empty($usermeta) ? false : $usermeta;

            return SFWD_LMS::get_template('course_info_shortcode', array(
                        'user_id' => $user_id,
                        'courses_registered' => $courses_registered,
                        'course_progress' => $course_progress,
                        'quizzes' => $quizzes
                            )
            );
        }

        /**
         * override course info shortcode
         * @param string $filepath
         * @param type $name
         * @param type $args
         * @param type $echo
         * @param type $return_file_path
         * @return string
         */
        function override_ld_course_info_shortcode($filepath, $name, $args, $echo, $return_file_path) {
            if ($name == 'course_info_shortcode') {
                $filepath = LDR_MODIFY_PLUGIN_PATH . 'includes/templates/course_info_shortcode.php';
                return $filepath;
            }
            return $filepath;
        }

    }

    $LearnDash_Refresher_Edit_Enrol_User = new LearnDash_Refresher_Edit_Enrol_User();
}

