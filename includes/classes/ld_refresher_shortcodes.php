<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LearnDash_Refresher_Shortcodes')) {

    class LearnDash_Refresher_Shortcodes {

        public function __construct() {
            add_shortcode('ldmv2_notification_refresher_courses', array($this, 'ldr_get_refresher_courses_notifications'));
            add_shortcode('ldr_refresher_courses_notifications', array($this, 'ldr_get_refresher_courses_notifications'));
        }

        public function ldr_get_refresher_courses_notifications($atts) {
            ob_start();

            if (isset($atts["user_id"]) && $atts["user_id"]) {
                $userId = $atts["user_id"];
            } else {
                $userId = get_current_user_id();
            }

            $error = false;
            $errorMessage = '';
            if ($userId) {
                $user = get_user_by('ID', $userId);
                if (!$user) {
                    $error = true;
                    $errorMessage = __('No user found', 'ld_refresher');
                }
            } else {
                $error = true;
                $errorMessage = __('No user id sent', 'ld_refresher');
            }
            ?>
            <div class="wrap">
                <?php
                $courses = ld_get_mycourses($userId, array('fields' => 'all'));
                /*$courses = get_posts(array(
                    'post_type' => 'sfwd-courses',
                    'showposts' => -1,
                ));*/
                $overdueArray = array();
                $requiredArray = array();
                foreach ($courses as $course) {
                    $status = $this->ldr_get_course_status($user->ID, $course->ID);
                    if ($status == __('Refresher Overdue', 'ld_refresher')) {
                        $overdueArray[] = $course;
                    } elseif ($status == __('Refresher Required', 'ld_refresher')) {
                        $requiredArray[] = $course;
                    }
                }

                if (count($overdueArray)) {
                    if (get_option('text_refresher')) {
                        ?>
                        <h4 class="ldmv2-notification-title"><?php echo get_option('text_refresher'); ?></h4>
                        <?php
                    }
                    ?>
                    <ul class="overdue-courses">
                        <?php
                        foreach ($overdueArray as $overdueCourse) {
                            $examOnly = get_post_meta($overdueCourse->ID, 'course_full_reset', true);
                            if ($examOnly == 'course_reset_exam_only') {
                                $message = __('Click here to retake the exam only.', 'ld_refresher');
                            } else {
                                $message = __('Click here to retake the full course.', 'ld_refresher');
                            }
                            ?>
                            <li><?php echo $overdueCourse->post_title; ?> - <a href="<?php the_permalink($overdueCourse); ?>"><?php echo $message; ?></a></li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }

                if (count($requiredArray)) {
                    if (get_option('text_expired')) {
                        ?>
                        <h4 class="ldmv2-notification-title"><?php echo get_option('text_expired'); ?></h4>
                        <?php
                    }
                    ?>
                    <ul class="overdue-courses">
                        <?php
                        foreach ($requiredArray as $requiredCourse) {
                            $examOnly = get_post_meta($overdueCourse->ID, 'course_full_reset', true);
                            if ($examOnly == 'course_reset_exam_only') {
                                $message = __('Click here to retake the exam only.', 'ld_refresher');
                            } else {
                                $message = __('Click here to retake the full course.', 'ld_refresher');
                            }
                            ?>
                            <li><?php echo $requiredCourse->post_title; ?> - <a href="<?php the_permalink($requiredCourse); ?>"><?php echo $message; ?></a></li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
                ?>
            </div>
            <?php
            return ob_get_clean();
        }

        private function ldr_get_course_status($userId, $courseId){
            $historyLastElement = null;
            $historyArr = get_user_meta($userId, 'ldm_course_completed_' . $courseId, true);
            if(is_array($historyArr) && count($historyArr)){
                $historyLastElement = end($historyArr);
                reset($historyArr);
            }
            $status = learndash_course_status($courseId, $userId);
            if ($status == __('Not Started', 'learndash')) {
                $expPeriod = get_post_meta($courseId, 'expiration_period', true);
                $refPeriod = get_post_meta($courseId, 'refresher_period', true);
                $courseInfo = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
                if ($expPeriod && $refPeriod && $courseInfo && is_array($courseInfo) && count($courseInfo) > 0) {
                    if ($historyLastElement) {
                        $completionDate = new DateTime('@' . $historyLastElement);
                        $overdueDate = clone $completionDate;
                        $overdueDate->modify('+' . $expPeriod . ' months');
                        $requiredDate = clone $overdueDate;
                        $requiredDate->modify('-' . $refPeriod . ' days');
                        if (strtotime($overdueDate->format('Y-m-d')) < time()) {
                            $status = __('Refresher Overdue', 'ld_refresher');
                        } elseif (strtotime($requiredDate->format('Y-m-d')) < time()) {
                            $status = __('Refresher Required', 'ld_refresher');
                        }
                    }
                }
            } elseif ($status == __('In Progress', 'learndash')) {
                $expPeriod = get_post_meta($courseId, 'expiration_period', true);
                $refPeriod = get_post_meta($courseId, 'refresher_period', true);
                $courseInfo = get_user_meta($userId, 'ldm_course_info_' . $courseId, true);
                if ($expPeriod && $refPeriod && $courseInfo && is_array($courseInfo) && count($courseInfo) > 0) {
                    if ($historyLastElement) {
                        $completionDate = new DateTime('@' . $historyLastElement);
                        $overdueDate = clone $completionDate;
                        $overdueDate->modify('+' . $expPeriod . ' months');
                        $requiredDate = clone $overdueDate;
                        $requiredDate->modify('-' . $refPeriod . ' days');
                        if (strtotime($overdueDate->format('Y-m-d')) < time()) {
                            $status = __('Refresher Overdue', 'ld_refresher');
                        } elseif (strtotime($requiredDate->format('Y-m-d')) < time()) {
                            $status = __('Refresher Required', 'ld_refresher');
                        }
                    }
                }
            }

            return $status;
        }

    }

    $LearnDash_Refresher_Shortcodes = new LearnDash_Refresher_Shortcodes();
}

