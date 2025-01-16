<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Enrol_User')) {

    class LearnDash_Refresher_Enrol_User {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_enrol_user_settings"), 110);
        }

        function add_ld_refresher_enrol_user_settings() {
            add_submenu_page('learndash-lms', __('Enrol New Users', 'ld_refresher'), __('Enrol New Users', 'ld_refresher'), 'manage_ldmv2_reports', 'enrol_new_users_ldmv2', array($this, 'add_ld_refresher_enrol_user_settings_callback'));
        }

        function add_ld_refresher_enrol_user_settings_callback() {

            $templates = ldr_get_templates_by_users();
            ?>
            <div class="wrap">
                <div id="icon-tools" class="icon32"></div>
                <h2><?php _e('Enrol New User', 'ld_refresher') ?></h2>
                <?php $this->import_company_users_callback($templates); ?>
                <?php $this->create_company_user_callback($templates); ?>
            </div>
            <?php
        }
        
        /**
         * Import company users using csv
         */
        function import_company_users_callback($templates = array()) {
            $errors = array();
            $success = 0;

            if (isset($_POST['submitCSV'])) {
                global $wpdb;
                if (isset($_FILES['usersCSV']['tmp_name'])) {

                    $csv_mimetypes = array(
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'text/comma-separated-values',
                        'application/excel',
                        'application/vnd.ms-excel',
                        'application/vnd.msexcel',
                        'text/anytext',
                        'application/octet-stream',
                        'application/txt',
                    );
                    if (in_array($_FILES['usersCSV']['type'], $csv_mimetypes)) {
                        //read csv
                        $csv = array_map('str_getcsv', file($_FILES['usersCSV']['tmp_name']));
                        $header = array_shift($csv);
                        $users = array();
                        foreach ($csv as $row) {
                            $users[] = array_combine($header, $row);
                        }

                        foreach ($users as $user) {
                            $user_errors = array();

                            //check user name
                            if (isset($user['username']) && !trim($user['username'])) {
                                $user_errors[] = __('Username is required', 'ld_refresher');
                            } elseif (username_exists($user['username'])) {
                                $user_errors[] = __('Username already exists', 'ld_refresher');
                            }


                            //check email
                            if (isset($user['email']) && !trim($user['email'])) {
                                $user_errors[] = __('Email is required', 'ld_refresher');
                            } elseif (email_exists($user['email'])) {
                                $user_errors[] = __('Email already exists', 'ld_refresher');
                            }

                            //check password
                            if (isset($user['password']) && !$user['password']) {
                                $user_errors[] = __('Password is required', 'ld_refresher');
                            }

                            if (empty($user_errors)) {
                                $userdata = array(
                                    'user_pass' => $user['password'],
                                    'user_login' => esc_attr($user['username']),
                                    'user_nicename' => esc_attr($user['username']),
                                    'user_email' => esc_attr($user['email']),
                                    'display_name' => esc_attr($user['username']),
                                    'user_registered' => current_time('mysql'),
                                    'role' => 'subscriber'
                                );

                                //check first name
                                if (isset($user['first_name'])) {
                                    $userdata['first_name'] = esc_attr($user['first_name']);
                                }

                                //check first name
                                if (isset($user['last_name'])) {
                                    $userdata['last_name'] = esc_attr($user['last_name']);
                                }

                                //insert user
                                $user_id = wp_insert_user($userdata);

                                //check is success
                                if ($user_id) {
                                    if (isset($_POST['templates_csv_select']) && $_POST['templates_csv_select']) {
                                        $allTemplates = get_option('ldmv2_plugin_templates');
                                        $tempName = $_POST['templates_csv_select'];
                                        if (isset($allTemplates[$tempName]['group']) && $allTemplates[$tempName]['group']) {
                                            update_user_meta($user_id, 'learndash_group_users_' . $allTemplates[$tempName]['group'], $allTemplates[$tempName]['group']);
                                        }

                                        if (isset($allTemplates[$tempName]['courses']) && count($allTemplates[$tempName]['courses']) > 0) {
                                            foreach ($allTemplates[$tempName]['courses'] as $courseId) {
                                                ld_update_course_access($user_id, $courseId);
                                            }
                                        }
                                    } elseif (isset($user['group']) && $user['group']) {
                                        $groupId = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'groups' AND post_title = %s", $user['group']));
                                        if ($groupId) {
                                            update_user_meta($user_id, 'learndash_group_users_' . $groupId, $groupId);
                                        }
                                    }

                                    //assign sites to users
                                    if (isset($user['sites']) && $user['sites']) {
                                        $sites = explode('|', $user['sites']);
                                        update_user_meta($user_id, 'ldmv2_user_sites', $sites);
                                    }

                                    $success++;
                                }
                            } else {
                                $errors[$user['username']] = $user_errors;
                            }
                        }
                    } else {
                        $errors[] = __('File type must be CSV.', 'ld_refresher');
                    }
                } else {
                    $errors[] = __('File is required.', 'ld_refresher');
                }
            }
			//New code To import user via CSV FILE 28-3-2023
			if(isset($_POST['importUserSubmitnew'])){
					// Allowed mime types
					if ( ! function_exists( 'get_editable_roles' ) ) {
							require_once ABSPATH . 'wp-admin/includes/user.php';
						}
					if(!function_exists('wp_get_current_user')) {
					include(ABSPATH . "wp-includes/pluggable.php"); 
					}
					if ( ! current_user_can( 'create_users' ) ){
							wp_die( __( 'You do not have sufficient permissions to access this page.' , 'import-users-from-csv') );
						}

				   // require( ABSPATH . WPINC . '/user.php' );
					$csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
    
					// Validate whether selected file is a CSV file
				if(!empty($_FILES['usersCSV']['name']) && in_array($_FILES['usersCSV']['type'], $csvMimes)){
        
						// If the file is uploaded
						if(is_uploaded_file($_FILES['usersCSV']['tmp_name'])){
            
							// Open uploaded CSV file with read-only mode
							$csvFile = fopen($_FILES['usersCSV']['tmp_name'], 'r');

            
								// Skip the first line
							$validatehyphen = "yes";
							$firstrow = fgetcsv($csvFile);
							$colcount = count($firstrow);
							if(trim($firstrow[0]) == "User Name" &&  trim($firstrow[1]) == "Email" && trim($firstrow[2]) == "First Name" && trim($firstrow[3]) == "Last Name" && trim($firstrow[4]) == "Password" && trim($firstrow[5]) == "Sites" && trim($firstrow[6]) == "Group" && trim($firstrow[7]) == "Types" ){

				           
								global $wpdb;

								$pattern = '/[\'\/~`\!@#\$%\^&\*\(\)_\\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/';
								$utype = array("senior-management","management-supervisory","technical-qa","operative","engineering","office-hr","food-safety-quality","sales-customer-service","logistics-distribution");
								$sites_array = array();
								$sites = get_option( 'fs_sites' );

								foreach ( $sites as $site ) {
								$key = sanitize_key( $site );
								//$sites_array[$key] = $site;
								$sites_array[] = $key;
								}
								
								//check each row if have any space
								$mycnt = 1;                      
                           
                   
                                $csvFile = fopen($_FILES['usersCSV']['tmp_name'], 'r');
					            // Parse data from CSV file line by line
								  // Skip the first line
								fgetcsv($csvFile);
					            while(($line = fgetcsv($csvFile)) !== FALSE){
					                // Get row data
					                 $username   = $line[0];
					                 $email = $line[1];
					                 $first_name = $line[2];
					                 $last_name = $line[3];
					                 $password = $line[4];
					                 $sites = $line[5];
					                 $group = $line[6];
					                 $type = $line[7];
					              //echo "<br>";
									
					                $sites = str_replace( array( '\'', '"', ',' , ';', '<', '>', '-', ' ', '.' ), '', $sites);
					                $sites = strtolower($sites);
					                //$groupname = "learndash_group_users_".$group;
					                

									$user_data = array(
									        'user_login' => $username,
									        'user_email' => $email,
									        'user_pass' => $password,
									        'user_nicename' => $first_name,
									        'display_name' => $first_name,
									        'role' => 'subscriber',
									    );

								    $user_id = wp_insert_user($user_data);
					                if (!is_wp_error($user_id)) { 
					                    wp_update_user([
										    'ID' => $user_id, // this is the ID of the user you want to update.
										    'first_name' => $first_name,
										    'last_name' => $last_name,
										]); 
								    	//update_user_meta($user_id, 'group', $group );
								    	update_user_meta($user_id, 'group', $group );
								    	update_user_meta($user_id, 'fs_site', $sites);
								    	update_user_meta($user_id, 'fs_type', $type);
										$sitesarray = array(); 
										$sitesarray[] = str_replace("-"," ",$line[5]);
										update_user_meta($user_id, 'ldmv2_user_sites', $sitesarray);
								    } else {   str_replace("-"," ",$strring);

								    	//$errors = $user_id;
								    	
								    }
									//update group data
									
									if ($user_id) {
										if (isset($_POST['templates_csv_select']) && $_POST['templates_csv_select']) {
											$allTemplates = get_option('ldmv2_plugin_templates');
											$tempName = $_POST['templates_csv_select'];
											if (isset($allTemplates[$tempName]['group']) && $allTemplates[$tempName]['group']) {
												update_user_meta($user_id, 'learndash_group_users_' . $allTemplates[$tempName]['group'], $allTemplates[$tempName]['group']);
											}

											if (isset($allTemplates[$tempName]['courses']) && count($allTemplates[$tempName]['courses']) > 0) {
												foreach ($allTemplates[$tempName]['courses'] as $courseId) {
													ld_update_course_access($user_id, $courseId);
												}
											}
										} elseif (isset($group)) {
											$groupId = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'groups' AND post_title = %s", $group));
											if ($groupId) {
												update_user_meta($user_id, 'learndash_group_users_' . $groupId, $groupId);
											}
										}
  
									}
									
									//end group data
									
									
									
									
							    }


			                    fclose($csvFile);
			                    //echo "<div class='wrap'><div class='container'><div class='alert alert-success' role='alert'>Data Import Successfully</div></div></div>";
								$success++;

			                 

							} else {
										//echo "<div class='wrap'><div class='container'><div class='alert alert-danger' role='alert'>CSV Fields issue</div></div></div>";
										
										$errors[] = "CSV Fields issue";
									}
            
						}else{
								//echo "<div class='wrap'><div class='container'><div class='alert alert-danger' role='alert'>CSV File Faild to upload, Please Try again.</div></div></div>";
								$errors[] = "CSV File Faild to upload, Please Try again.";
							}
				} else {

							//echo "<div class='wrap'><div class='container'><div class='alert alert-danger' role='alert'>Issue find in your CSV File.</div></div></div>";
							$errors[] = "Issue find in your CSV File.";

						}
    
			}
			
			//END NEW CODE

            //display errors
            if(!empty($errors)) {
                ?>
                <div id="setting-error-settings_updated" class="error settings-error">
                    <?php
                    foreach ($errors as $index => $error) {
                        if (is_array($error)) {
                            foreach ($error as $singleError) {
                                ?>
                                <p><strong><?php echo $index . ': '; ?></strong><?php echo $singleError; ?></p>
                                <?php
                            }
                        } else {
                            ?>
                            <p><strong><?php echo $error; ?></strong></p>
                        <?php } ?>
                    <?php } ?>
                </div>
                <?php
            }

            //Import successed
			 
            if($success) {
                ?>
                <div style="border: 1px solid;color: green;padding: 0 10px;">
                    <p><?php
                       // echo $success;
                        _e(' User(s) imported Successfully', 'ld_refresher');
                        ?></p>
                </div>
            <?php } ?>
            <!--Form Import Users-->            
            <form class="formImportUsers" action="" method="post" enctype="multipart/form-data">
                <input type="file" name="usersCSV" required>
                <div id="load-template">                      
                    <?php
                    if (!empty($templates)) {
                        ?>
                        <select name="templates_csv_select" id="templates_csv_select">
                            <option value=""><?php _e('Select Template', 'ld_refresher') ?></option>
                            <?php
                            foreach ($templates as $template => $value) {
                                ?>
                                <option value="<?php echo $template ?>"><?php echo $template ?></option>
                            <?php } ?>
                        </select>
                        <?php
                    } else {
                        ?>
                        <p><?php _e('No templates available', 'ld_refresher'); ?></p>
                    <?php } ?>
                </div>
                <!--<button type="submit" name="submitCSV"><?php //_e('Import Users', 'ld_refresher') ?></button>-->
				<button type="submit" name="importUserSubmitnew"><?php _e('Import Users', 'ld_refresher') ?></button>
                <a href="<?php echo LDR_MODIFY_PLUGIN_URL . 'assets/csv/sample-new-format.csv'; ?>" download><?php _e('Download User CSV Template', 'ld_refresher') ?></a>
            </form>
            <!--End Form Import Users-->            

            <?php
        }

        /**
         * Create new user form and handle adding user
         */
        function create_company_user_callback($templates = array()) {
            $errors = array();
            $message = FALSE;

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
            $currentUser = wp_get_current_user();


            //form create user is submitted
            if (isset($_POST['createCompanyUser'])) {

                //check user name
                if (isset($_POST['user_login']) && !trim($_POST['user_login'])) {
                    $errors[] = __('Username is required', 'ld_refresher');
                } elseif (username_exists($_POST['user_login'])) {
                    $errors[] = __('Username already exists', 'ld_refresher');
                }

                //check email
                if (isset($_POST['email']) && !trim($_POST['email'])) {
                    $errors[] = __('Email is required', 'ld_refresher');
                } elseif (email_exists($_POST['email'])) {
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

                //check password && confirm password
                if ((isset($_POST['pass']) && !$_POST['pass']) || (isset($_POST['pass2']) && !$_POST['pass2'])) {//pass are required
                    $errors[] = __('Both password fields are required', 'ld_refresher');
                } elseif ($_POST['pass'] != $_POST['pass2']) { //pass not matched
                    $errors[] = __('Both password fields must match', 'ld_refresher');
                }

                if (empty($errors)) { //no errors (ready to add user)
                    $userdata = array(
                        'user_pass' => $_POST['pass'],
                        'user_login' => esc_attr($_POST['user_login']),
                        'user_nicename' => esc_attr($_POST['user_login']),
                        'user_email' => esc_attr($_POST['email']),
                        'display_name' => esc_attr($_POST['user_login']),
                        'user_registered' => current_time('mysql'),
                        'role' => 'subscriber'
                    );

                    if (isset($_POST['first_name'])) {
                        $userdata['first_name'] = esc_attr($_POST['first_name']);
                    }

                    if (isset($_POST['last_name'])) {
                        $userdata['last_name'] = esc_attr($_POST['last_name']);
                    }

                    $user_id = wp_insert_user($userdata);

                    if ($user_id) {

                        //enroll user to group
                        if (isset($_POST['ldmrefresher_group_user']) && $_POST['ldmrefresher_group_user']) {
                            update_user_meta($user_id, 'learndash_group_users_' . $_POST['ldmrefresher_group_user'], $_POST['ldmrefresher_group_user']);
                        }


                        //enroll user to courses and add acessabilty to user to course
                        if (isset($_POST['ldmrefresher_course']) && count($_POST['ldmrefresher_course']) > 0) {
                            foreach ($_POST['ldmrefresher_course'] as $course_id) {
                                ld_update_course_access($user_id, $course_id);
                            }
                        }
                          $newsites = array();
                        if (isset($_POST['ldmrefresher_site']) && count($_POST['ldmrefresher_site']) > 0) {
                            update_user_meta($user_id, 'ldmv2_user_sites', $_POST['ldmrefresher_site']);
							       //code to update data in user edit in admin
								    $newsites = $_POST['ldmrefresher_site'];
									$usersite = reset($newsites);
									$usersite = str_replace( array( '\'', '"', ',' , ';', '<', '>', '-', ' ', '.' ), '', $usersite);
					                $usersite = strtolower($usersite);
									update_user_meta($user_id, 'fs_site', $usersite);
									//end user edit admin
							
                        } elseif (isset($_POST['ldmrefresher_site']) && count($_POST['ldmrefresher_site']) == 0) {
                            update_user_meta($user_id, 'ldmv2_user_sites', array());
                        }
						
						//code to update types
                        if (isset($_POST['ldmrefresher_utypes'])) {
                            update_user_meta($user_id, 'fs_type', $_POST['ldmrefresher_utypes']);
                        } else {
                            update_user_meta($user_id, 'fs_type', "");
                        }
						
						
                        $message = true;
                    }
                }
            }

            if (!empty($errors)) { //display errors
                ?>
                <div id="setting-error-settings_updated" class="error settings-error">
                    <?php foreach ($errors as $error) { ?>
                        <p><strong><?php echo $error; ?></strong></p>
                    <?php } ?>
                </div>
                <?php
            } elseif ($message) { //display added user success message
                ?>
                <div id="message" class="updated notice notice-success">
                    <p><?php _e('User added Succesfully', 'ld_refresher'); ?></p>
                </div>
            <?php } ?>

            <!--Add Company User-->
            <form method="post" name="createCompanyUserForm" id="createCompanyUserForm">
                <table class="form-table">
                    <tbody>

                        <!--Username-->
                        <tr class="form-field form-required">
                            <th scope="row"><label for="user_login"><?php _e('Username', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                            <td><input name="user_login" type="text" id="user_login" value="<?php echo (!empty($errors) && isset($_POST['user_login'])) ? $_POST['user_login'] : ""; ?>" required="true" autocapitalize="none" autocorrect="off" maxlength="60"></td>
                        </tr>
                        <!--End Username-->

                        <!--Email-->
                        <tr class="form-field form-required">
                            <th scope="row"><label for="email"><?php _e('Email', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                            <td><input name="email" type="email" id="email" value="<?php echo (!empty($errors) && isset($_POST['email'])) ? $_POST['email'] : ""; ?>" required="true"></td>
                        </tr>
                        <!--Email-->

                        <!--First Name-->
                        <tr class="form-field">
                            <th scope="row"><label for="first_name"><?php _e('First Name', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                            <td><input name="first_name" type="text" id="first_name" value="<?php echo (!empty($errors) && isset($_POST['first_name'])) ? $_POST['first_name'] : ""; ?>" required="true"></td>
                        </tr>
                        <!--End First Name-->

                        <!--Last Name-->
                        <tr class="form-field">
                            <th scope="row"><label for="last_name"><?php _e('Last Name', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                            <td><input name="last_name" type="text" id="last_name" value="<?php echo (!empty($errors) && isset($_POST['last_name'])) ? $_POST['last_name'] : ""; ?>" required="true"></td>
                        </tr>
                        <!--End Last Name-->

                        <!--Password-->
                        <tr class="form-field form-required">
                            <th scope="row"><label for="pass"><?php _e('Password', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                            <td>
                                <input name="pass" type="password" id="pass" required="true">
                            </td>
                        </tr>
                        <!--End Password-->

                        <!--Confirm Password-->
                        <tr class="form-field form-required">
                            <th scope="row"><label for="pass2"><?php _e('Repeat Password', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></th>
                            <td>
                                <input name="pass2" type="password" id="pass2" required="true">
                            </td>
                        </tr>
                        <!--End Confirm Password-->

                        <!--Sites-->
                        <tr class="form-field">
                            <th scope="row"><label for="ldmrefresher_site"><?php _e('Sites', 'ld_refresher') ?> </label></th>
                            <td>
                                <select name="ldmrefresher_site[]" class="ldr_multi_select" id="ldmrefresher_site" multiple="true">
                                    <?php
                                    foreach ($sites as $site) {
                                        ?>
                                        <option value="<?php echo $site; ?>" <?php echo (!empty($errors) && in_array($site, $_POST['ldmrefresher_site'])) ? "selected" : ""; ?>><?php echo $site; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <!--End Sites-->
						
						<!--User types-->
                        <tr class="form-field">
                            <th scope="row"><label for="ldmrefresher_utypes"><?php _e('Types', 'ld_refresher') ?> </label></th>
                            <td>
                                <select name="ldmrefresher_utypes" class="ldr_multi_select" id="ldmrefresher_utypes" >
                                    <?php
                                    foreach ($utypes as $utype => $utype_value) {
                                        ?>
                                        <option value="<?php echo $utype; ?>" <?php echo (!empty($errors) && in_array($utype, $_POST['ldmrefresher_utypes'])) ? "selected" : ""; ?>><?php echo $utype_value; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <!--End User Types-->


                        <!--Groups/Courses-->
                        <tr class="form-field">
                            <th scope="row"><label for="ldmrefresher_group_user"><?php _e('Groups', 'ld_refresher') ?> </label></th>
                            <td>
                                <select name="ldmrefresher_group_user" id="ldmrefresher_group_user">
                                    <option value=""><?php _e('Select group', 'ld_refresher') ?></option>
                                    <?php
                                    $groupIds = ldr_get_groups_by_logged_in_user($currentUser);
                                    foreach ($groupIds as $groupId) {
                                        ?>
                                        <option value="<?php echo $groupId; ?>" ><?php echo get_the_title($groupId); ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <!--End Groups/Courses-->
                    </tbody>
                </table>

                <!--Enrolled Courses-->
                <div id="jobs-courses-contanier">
                    <h3><?php _e('Course Enrolment', 'ld_refresher') ?></h3>
                    <div id="courses-lists">
                        <p><?php _e('Choose a group first', 'ld_refresher'); ?></p>
                    </div>
                </div>
                <!--End Enrolled Courses-->

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
                        <input type="text" id="template_name" placeholder="<?php _e('Template Name', 'v'); ?>">
                        <button type="button" id="save_template"><?php _e('Save template', 'ld_refresher'); ?></button><br/>
                        <span class="description">* <?php _e('If the template name already exists, the template will be updated.', 'ld_refresher') ?></span>
                    </div>
                </div>
                <!--End Save Templates-->

                <p class="submit"><input type="submit" name="createCompanyUser" id="createCompanyUser" class="button button-primary" value="<?php _e('Add User', 'ld_refresher') ?>"></p>
            </form>

            <!--End Add Company User-->
            <?php
        }

    }

    $LearnDash_Refresher_Enrol_User = new LearnDash_Refresher_Enrol_User();
}

