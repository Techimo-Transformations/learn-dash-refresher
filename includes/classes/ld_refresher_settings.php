<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Settings')) {

    class LearnDash_Refresher_Settings {

        public function __construct() {
            add_action('admin_menu', array($this, "add_ld_refresher_settings"), 125);
        }

        function add_ld_refresher_settings() {
            add_submenu_page('learndash-lms', __('Refresher Settings', 'ld_refresher'), __('Refresher Settings', 'ld_refresher'), 'manage_ldmv2_reports_settings', 'manage_ldmv2_refresher_options', array($this, 'ld_refresher_settings_options_callback'));
        }

        function ld_refresher_settings_options_callback() {


            //Saving Notifiction Form
            if (isset($_POST["ldmv2refresher_submit"])) {
                update_option('text_refresher', $_POST["text_refresher"]);
                update_option('text_expired', $_POST["text_expired"]);
                update_option('ldmv2_users_per_page', $_POST["ldmv2_users_per_page"]);
                
                if(isset($_POST["ldmv2_allow_leaders"]) && $_POST["ldmv2_allow_leaders"]){
                    update_option('ldmv2_allow_leaders', 1);
                }else{
                    update_option('ldmv2_allow_leaders', 0);
                }
                
                if(isset($_POST["ldmv2_leaders_emails"]) && $_POST["ldmv2_leaders_emails"]){
                    update_option('ldmv2_leaders_emails', 1);
                }else{
                    update_option('ldmv2_leaders_emails', 0);
                }
            }

            //Saving Sites Form
            if (isset($_POST["ldmv2refresher_sites_submit"]))
                update_option('ldmv2_sites', $_POST["ldmv2_sites_select"]);

            //get saved sites
            $sites = ($sites = get_option('ldmv2_sites')) ? $sites : array();
            ?>
            <h1><?php _e('Refresher Settings', 'ld_refresher'); ?></h1>
            
            <h2><?php _e('Refresher Notification Text:', 'ld_refresher'); ?></h2>
            
            <!--Refresher Notifications  Form--> 
            <form action="" method="post" id="ldmv2refresher_options">
                <table>
                    <tr>
                        <td><label for="text_refresher"><?php _e('Refreshed Notice Text', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></td>
                        <td>
                            <input type="text" id="text_refresher" name="text_refresher" placeholder="<?php _e('Course1 is refresher overdue', 'ld_refresher'); ?>" value="<?php echo get_option('text_refresher'); ?>" required="true"/>
                        </td>
                    </tr>

                    <tr>
                        <td><label for="text_expired"><?php _e('Expiration Notice Text', 'ld_refresher') ?> <span class="description">(<?php _e('required', 'ld_refresher') ?>)</span></label></td>
                        <td>   
                            <input type="text" id="text_expired" name="text_expired" placeholder="<?php _e('Course1 has been expired', 'ld_refresher'); ?>" value="<?php echo get_option('text_expired'); ?>" required="true"/>
                        </td>
                    </tr>

                    <tr>
                        <td> <label for="ldmv2_users_per_page"><?php _e('Users Per Page', 'ld_refresher') ?> <span class="description"></span></label></td>
                        <td>    
                            <input type="text" id="ldmv2_users_per_page" name="ldmv2_users_per_page" placeholder="10" value="<?php echo get_option('ldmv2_users_per_page'); ?>"/>
                            <p class="description"><?php _e('Number of users in matrix page default is 10', 'ld_refresher') ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td> <label for="ldmv2_allow_leaders"><?php _e('Allow leaders to edit', 'ld_refresher') ?> <span class="description"></span></label></td>
                        <td>    
                            <input type="checkbox" id="ldmv2_allow_leaders" name="ldmv2_allow_leaders" value="1" <?php if(get_option('ldmv2_allow_leaders', '0')){ ?>checked<?php } ?>/>
                            <p class="description"><?php _e('Check this if you want to allow group leaders to manage reports', 'ld_refresher') ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <td> <label for="ldmv2_leaders_emails"><?php _e('Leaders emails', 'ld_refresher') ?> <span class="description"></span></label></td>
                        <td>    
                            <input type="checkbox" id="ldmv2_leaders_emails" name="ldmv2_leaders_emails" value="1" <?php if(get_option('ldmv2_leaders_emails', '1')){ ?>checked<?php } ?>/>
                            <p class="description"><?php _e('Check this if you want group leaders to receive notification emails', 'ld_refresher') ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="ldmv2refresher_submit" id="ldmv2refresher_submit" class="button button-primary" value="<?php _e('Save Options', 'ld_refresher') ?>">
                </p>
            </form>
            <!--End Refresher Notifications  Form--> 


            <!--Refresher Sites Form--> 
            <h2><?php _e('Manage Sites:', 'ld_refresher'); ?></h2>
            
            <form action="" method="post" id="ldmv2refresher_sites">
                <input type="text" id="site_name" name="site_name">
                <button type="button" id="add_site" class="button button-secondary"><?php _e('Add Site', 'ld_refresher'); ?></button>
                
                <ul id="ldmv2refresher_sites_container">
                    <?php foreach ($sites as $site) { ?>
                        <li><?php echo $site; ?><span class="delete_site" data-site="<?php echo $site; ?>">X</span></li>
                    <?php } ?>
                </ul>
                
                <select id="ldmv2_sites_select" name="ldmv2_sites_select[]" style="display: none;" multiple="">
                    <?php foreach ($sites as $site) { ?>
                        <option value="<?php echo $site; ?>" selected><?php echo $site; ?></option>
                    <?php } ?>
                </select>
                
                <p class="submit">
                    <input type="submit" name="ldmv2refresher_sites_submit" id="ldmv2refresher_sites_submit" class="button button-primary" value="<?php _e('Save Sites', 'ld_refresher') ?>">
                </p>
            </form>

            <!--End Refresher Sites Form--> 
            
            <?php
            $newsite = get_option('ldr_migrate_new_site');
            $version3 = get_option('ldr_migrate_version_three');
            if((!$newsite) || (!$version3)){
            ?>
            <!--Refresher Migrations--> 
            <h2><?php _e('Migrations:', 'ld_refresher'); ?></h2>
            <table>
                <?php if(!$newsite){ ?>
                <tr>
                    <td><label><?php _e('Migrate to new website', 'ld_refresher') ?></label></td>
                    <td>
                        <button type="button" class="button button-primary" id="ldr_migrate_new_site"><?php _e('Migrate', 'ld_refresher') ?></button>
                        <p class="description"><?php _e('Use this button if you are installing the plugin for the first time and users have data.', 'ld_refresher') ?></p>
                    </td>
                </tr>
                <?php } ?>
                
                <?php if(!$version3){ ?>
                <tr>
                    <td><label><?php _e('Migrate to version 3', 'ld_refresher') ?></label></td>
                    <td>
                        <button type="button" class="button button-primary" id="ldr_migrate_version_three"><?php _e('Migrate', 'ld_refresher') ?></button>
                        <p class="description"><?php _e('Use this button if you are installing the plugin and you had a previous version of the plugin', 'ld_refresher') ?></p>
                    </td>
                </tr>
                <?php } ?>
            </table>
            <!--End Refresher Migrations--> 
            <?php
            }
        }

    }

    $LearnDash_Refresher_Settings = new LearnDash_Refresher_Settings();
}

