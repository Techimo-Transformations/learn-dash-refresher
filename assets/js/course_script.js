/**********************************Course Meta Box*********************************************/

jQuery(document).on('change', '#is_course_refreshed', function ($) {
    var is_checked = jQuery("#is_course_refreshed").prop('checked');

    if (is_checked) {
        jQuery("#refreshed_settings").attr('style', 'display:block');
        jQuery("#refresher_period").attr('required', true);
        jQuery("#expiration_period").attr('required', true);

    } else {
        jQuery("#refreshed_settings").attr('style', 'display:none');
        jQuery("#refresher_period").attr('required', false);
        jQuery("#expiration_period").attr('required', false);
    }
});