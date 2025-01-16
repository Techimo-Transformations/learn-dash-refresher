/*******************************Refresher Settings Page***************************************/

//Add New Site 
jQuery(document).on('click', '#add_site', function (e) {
    var name = jQuery('#site_name').val();
    var exists = false;
    if (name) {
        // check if exists
        jQuery('#ldmv2_sites_select option').each(function () {
            if (this.value == name) {
                exists = true;
            }
        });
        if (!exists) {
            jQuery('#ldmv2_sites_select').append('<option value="' + name + '" selected>' + name + '</option>');
            jQuery('#ldmv2refresher_sites_container').append('<li>' + name + '<span class="delete_site" data-site="' + name + '">X</span></li>');
        } else {
            alert('Site already exists!');
        }
        jQuery('#site_name').val('');
    } else {
        alert('You must add site first.');
    }
});

//Delete Site
jQuery(document).on('click', '.delete_site', function (e) {
    var site = jQuery(this).data('site');
    jQuery(this).parent().remove();
    jQuery('#ldmv2_sites_select option').each(function () {
        if (this.value == site) {
            jQuery(this).remove();
        }
    });
});

// migration actions
jQuery(document).on('click', '#ldr_migrate_new_site', function(){
    jQuery.confirm({
        title: user_data.migrate_title,
        content: user_data.migrate_new_site_message,
        useBootstrap: false,
        buttons: {
            confirm:{
                btnClass: 'btn-blue',
                action: function () {
                    jQuery('#ldr_migrate_new_site').attr('disabled', true);

                    var data = {
                        'action': 'migrate_to_new_site'
                    };
                    //alert(data.toSource());
                    jQuery.post(ajaxurl, data, function (response) {
                        //alert('Got this from the server: ' + response);
                        var obj = JSON.parse(response);
                        if (obj.status) {
                            jQuery('#ldr_migrate_new_site').parent().html(obj.message);
                        }
                    });
                }
            },
            cancel: function () {
                
            }
        }
    });
});

jQuery(document).on('click', '#ldr_migrate_version_three', function(){
    jQuery.confirm({
        title: user_data.migrate_title,
        content: user_data.migrate_v3_message,
        useBootstrap: false,
        buttons: {
            confirm:{
                btnClass: 'btn-blue',
                action: function () {
                    jQuery('#ldr_migrate_version_three').attr('disabled', true);

                    var data = {
                        'action': 'migrate_to_version_three'
                    };
                    //alert(data.toSource());
                    jQuery.post(ajaxurl, data, function (response) {
                        //alert('Got this from the server: ' + response);
                        var obj = JSON.parse(response);
                        if (obj.status) {
                            jQuery('#ldr_migrate_version_three').parent().html(obj.message);
                        }
                    });
                }
            },
            cancel: function () {
                
            }
        }
    });
});

/********************************Refresher Enrol New Users*************************************/

/**
 * Load group courses
 */
jQuery(document).on('change', '#ldmrefresher_group_user', function ($) {
    get_group_jobs_courses();
});

/**
 * Save Templates
 */
jQuery(document).on('click', '#save_template', function ($) {
    jQuery("#save_template").attr('disabled', true);
    jQuery("#save_template_success").hide();
    jQuery("#save_template_error").hide();
    var errors = [];
    var ids = [];
    var name = jQuery("#template_name").val();
    var group = jQuery("#ldmrefresher_group_user").val();
    jQuery('input[type=checkbox]:checked').each(function () {
        ids.push(jQuery(this).val());
    });

    if (!name) {
        errors.push('<p><strong>Template name is required</strong></p>');
    }
    if (!group) {
        errors.push('<p><strong>Group is required</strong></p>');
    }
    if (ids.length == 0) {
        errors.push('<p><strong>You must choose at least one course</strong></p>');
    }

    if (errors.length > 0) {
        var errorsStr = errors.join(' ');
        jQuery("#save_template_error").html(errorsStr);
        jQuery("#save_template_error").show();
    } else {
        var data = {
            'action': 'save_course_template',
            'name': name,
            'group': group,
            'courses': ids
        };

        jQuery.post(ajaxurl, data, function (response) {

            var obj = JSON.parse(response);
            jQuery("#template_name").val('');
            if (jQuery("#templates_select option[value='" + obj.name + "']").length == 0) {
                jQuery('#templates_select').append('<option value="' + obj.name + '">' + obj.name + '</option>');
                jQuery("#save_template_success").show();
            }
        });
    }

    jQuery("#save_template").attr('disabled', false);
});

/**
 * Load Template 
 */
jQuery(document).on('click', '#load_template', function ($) {
    jQuery("#load_template").attr('disabled', true);

    var template = jQuery("#templates_select").val();

    if (!template) {
        alert('You must choose template first');
    } else {
        var data = {
            'action': 'load_course_template',
            'template': template
        };
        //alert(data.toSource());
        jQuery.post(ajaxurl, data, function (response) {
            //alert('Got this from the server: ' + response);
            var obj = JSON.parse(response);
            if (obj.status) {
                jQuery("#ldmrefresher_group_user").html(obj.groupHTML);
                jQuery("#courses-lists").html(obj.coursesHTML);
                jQuery("#templates_select").val('');
            }
        });
    }

    jQuery("#load_template").attr('disabled', false);
});

/*********************************Edit Enroll User Page****************************************/

jQuery(document).ready(function ($) {
    if ($('#ldmrefresher_group_user').length > 0 && $('#ldmrefresher_group_user').val()) {
        get_group_jobs_courses();
    }
});

/*********************************Refresher Report Page****************************************/

/**
 * Get Courses In Group Change
 */
jQuery(document).on('change', '#ldmrefresher_refresher_group', function ($) {
    jQuery("#ldmrefresher_refresher_course").attr('disabled', true);
    jQuery("#ldmrefresher_refresher_submit").attr('disabled', true);
    var data = {
        'action': 'get_refresher_group_courses',
        'group': jQuery("#ldmrefresher_refresher_group").val()
    };
    jQuery.post(ajaxurl, data, function (response) {
        var obj = JSON.parse(response);
        if (obj.status) {
            jQuery("#ldmrefresher_refresher_course").html(obj.html);
        }
        jQuery("#ldmrefresher_refresher_course").attr('disabled', false);
    });
    jQuery("#ldmrefresher_refresher_submit").attr('disabled', false);
});

/********************************Refresher History Page****************************************/

jQuery(document).on('change', '.user_courses_dropdown', function (e) {
    var dropDown = jQuery(this);

    var course_selected_info = dropDown.children(":selected").attr("id");

    var user_id = course_selected_info.split("_")[0];
    var course_id = course_selected_info.split("_")[1];

    dropDown.attr('disabled', 'disabled');

    var data = {
        'action': 'get_course_info_dynamically',
        'u_id': user_id,
        'course_id': course_id
    };

    jQuery.post(ajaxurl, data, function (response) {
        var obj = JSON.parse(response);
        if(obj.status){
            var firstTD = dropDown.closest('tr').children('td:first').html();
            var secondTD = dropDown.closest('tr').children('td:nth-child(2)').html();

            dropDown.closest("tr").html("<td>" + firstTD + "</td><td>" + secondTD + "</td>" + obj.html);

            var selector = "#" + course_selected_info;

            jQuery(selector).prop("selected", true);
            
            jQuery(selector).parent().attr('disabled', false);
        }
    });
});

/***********************************User Matrix Page******************************************/

jQuery(document).on('click', '#ldr_remove_grp_lnk', function () {
    if (confirm("Are you sure you want to remove user from this group?")) {
        var link = jQuery(this);
        var user = link.data('user');
        var group = link.data('group');

        var data = {
            'action': 'ldr_remove_usr_from_grp',
            'user': user,
            'group': group
        };

        jQuery.post(ajaxurl, data, function (response) {
            var obj = JSON.parse(response);
            if (obj.status) {
                alert(obj.message);
                location.reload();
            }
            else{alert("this fucked up");}
        });
    }
});

/***********************************User History Page******************************************/

/***
 * Change Course Completion Date
 */
jQuery(document).on('click', '.edit_history_button', function () {
    var button = jQuery(this);
    var user = button.data('user');
    var course = button.data('course');
    var index = button.data('index');
    var date = button.data('date');
    var last = button.data('last');

    button.attr('disabled', 'disabled');

    jQuery("#datepicker").datepicker({dateFormat: "yy-mm-dd"}).val(date);
    jQuery("#datepicker").data('course', course);
    jQuery("#datepicker").data('user', user);
    jQuery("#datepicker").data('index', index);
    jQuery("#datepicker").data('last', last);
    jQuery("#datepicker").data('button', button);
    jQuery("#datepickerholder").show();
    
});

jQuery(document).on('click', '.history_pop_confirm', function (e) {
        e.preventDefault();
        var newDate = jQuery("#datepicker").datepicker({dateFormat: 'yy-mm-dd'}).val();
        var user = jQuery("#datepicker").data('user');
        var course = jQuery("#datepicker").data('course');
        var index = jQuery("#datepicker").data('index');
        var last = jQuery("#datepicker").data('last');
        var button = jQuery("#datepicker").data('button');

        var data = {
            'action': 'change_user_history_date',
            'user': user,
            'course': course,
            'index': index,
            'last': last,
            'date': newDate
        };

        jQuery.post(ajaxurl, data, function (response) {
            var obj = JSON.parse(response);
            jQuery("#datepicker").datepicker("setDate", null);
            jQuery("#datepickerholder").hide();
            if (obj.status && obj.date) {
                button.prev('span').html(obj.date);
                jQuery("#datepickerholder").hide();
                button.attr('disabled', false);
            }
        });
    });

    jQuery(document).on('click', '#history_pop_cancel', function (e) {
        e.preventDefault();
        
        jQuery("#datepicker").datepicker("setDate", null);
        var button = jQuery("#datepicker").data('button');
        
        button.attr('disabled', false);
        jQuery("#datepickerholder").hide();
    });

/***
 * Delete Course Completion Date
 */

jQuery(document).on('click', '.delete_date_history_button', function () {

    if (confirm("Are you sure you want to delete this date?")) {

        var del_button = jQuery(this);
        var del_user = del_button.data('user');
        var del_course = del_button.data('course');
        var del_index = del_button.data('index');

        del_button.attr('disabled', 'disabled');

        var data = {
            'action': 'delete_user_history_date',
            'user': del_user,
            'course': del_course,
            'index': del_index
        };

        jQuery.post(ajaxurl, data, function (response) {
            var del_obj = JSON.parse(response);
            if (del_obj.status) { 
                del_button.attr('disabled', false);
                location.reload();
            }
        });
    }

    else{
        del_button.attr('disabled', false);
    }
});

/************************************Group Matrix Page******************************/

jQuery(document).ready(function ($) {
    get_group_jobs(true);

    if (jQuery('#course_progress_details a.leandash-profile-couse-details-link').length) {
        jQuery('#course_progress_details a.leandash-profile-couse-details-link').click(function () {
            var clicked_el = jQuery(this);
            var clicked_div = jQuery(clicked_el).next();
            jQuery('.widget_course_return', clicked_div).hide();
            if (jQuery(clicked_div).is(':visible')) {
                jQuery(clicked_div).slideUp('fast');
            } else {
                jQuery(clicked_div).slideDown('slow');
            }
            return false;
        });
    }

    // add leader update user url
    if ($('table.wp-list-table.groups').length) {
        var hash = get_parameters_hash(window.location.href);

        //alert(hash['page']);
        //alert(hash.toSource());
        if (hash['page'] == 'group_admin_page' && hash['group_id']) {
            $('table.wp-list-table.groups tbody tr td.column-user_actions').each(function () {
                var html = '';
                var href = $(this).find('a').attr('href');
                var rowHash = get_parameters_hash(href);
                if (rowHash['user_id']) {
                    html += ' | ';

                    html += '<a href="' + user_data.page + '&useId=' + rowHash['user_id'] + '">' + user_data.text + '</a>';
                }
                $(this).append(html);
            });
        }
    }

    jQuery(".matrix_fix").tableHeadFixer({"left": 1});
});


jQuery(document).on('change', '#ldmrefresher_group', function () {
    get_group_jobs();
});

jQuery(document).on('change', '#ldmrefresher_job', function () {
    get_job_courses();
});

jQuery(document).on('change', '#ldmrefresher_course', function () {
    if(jQuery(this).val() && jQuery("#ldmrefresher_group").val()){
        jQuery("#ldmrefresher_status").val('').show();
    }else{
        jQuery("#ldmrefresher_status").val('').hide();
    }
});

// change status functionality

/**
 * rf@objects
 * on users course dropdown change 
 */

var previous = null;

jQuery(document).on('focus', '.user_course_status_dropdown', function (e) {
    var dropDown = jQuery(this);
    previous = dropDown.find(":selected").attr("id");
});

jQuery(document).on('change', '.user_course_status_dropdown', function (e) {
    var prevSelect = previous;
    var dropDown = jQuery(this);

    var newStatus = dropDown.find(":selected").text();
    var course_selected_info = dropDown.children(":selected").attr("id");
    var oldStatus = dropDown.children(":selected").html();
    var courseId = course_selected_info.split("_")[0];
    var userId = course_selected_info.split("_")[1];

    jQuery("#datepicker").data('courseId', courseId);
    jQuery("#datepicker").data('userId', userId);
    jQuery("#datepicker").data('status', newStatus);
    jQuery("#datepicker").data('dropdown', dropDown);
    jQuery("#datepicker").data('selector', course_selected_info);


    if (newStatus != "Not Enrolled" && newStatus != "Not Started" && newStatus != "In Progress" && newStatus != "Refresher Required" && newStatus != "Refresher Overdue" && newStatus != "Submit Document") {
        jQuery("#datepicker").datepicker({dateFormat: "yy-mm-dd", defaultDate: null});
        jQuery("#datepickerholder").show();
    } else {
        var note = '';
        var arr = ["Not Enrolled", "Not Started", "In Progress", "Refresher Overdue"];
        if((newStatus == "Refresher Required" || newStatus == "Refresher Overdue" ) && prevSelect){
            if(jQuery.inArray(dropDown.find("option#" + prevSelect).text(), arr) == -1){
                note = '<b><br/>'+user_data.change_note+'</b>';
            }
        }

        if(newStatus == "Not Enrolled" || (newStatus == "Not Started" && dropDown.find("option#" + prevSelect).text() != "Not Enrolled")){
            note = '<b><br/>'+user_data.delete_note+'</b>';
        }
        
        jQuery.confirm({
            title: user_data.confirm_status_title,
            content: user_data.confirm_status+" "+newStatus+"?"+note,
            useBootstrap: false,
            buttons: {
                confirm:{
                    btnClass: 'btn-blue',
                    action: function () {
                        var data = {
                            'action': 'change_user_course_status',
                            'user_id': userId,
                            'course_id': courseId,
                            'new_status': newStatus,
                            'old_status': oldStatus,
                        };

                        jQuery.post(ajaxurl, data, function (response) {
                            var obj = JSON.parse(response);
                            if (obj.status) {
                                dropDown.parent("td#" + userId + "_" + courseId).replaceWith(obj.html);
                                var selector = "#" + userId + "_" + courseId + "_1";
                                jQuery(selector).prop("selected", true);
                            }else{
                                if (prevSelect) {
                                    dropDown.find("option#" + prevSelect).prop("selected", true);
                                }
                                jQuery.alert({
                                    title: user_data.confirm_status_title,
                                    content: obj.message,
                                    useBootstrap: false
                                });
                            }
                        });
                    }
                },
                cancel: function () {
                    if (prevSelect) {
                        dropDown.find("option#" + prevSelect).prop("selected", true);
                    }
                }
            }
        });
    }

});

jQuery(document).on('click', '#pop_confirm', function (e) {
    e.preventDefault();
    var date = jQuery("#datepicker").datepicker({dateFormat: 'yy-mm-dd'}).val();
    var courseId = jQuery("#datepicker").data('courseId');
    var userId = jQuery("#datepicker").data('userId');
    var newStatus = jQuery("#datepicker").data('status');
    var dropDown = jQuery("#datepicker").data('dropdown');
    var oldStatus = dropDown.children(":selected").html();


    var data = {
        'action': 'change_user_course_status',
        'user_id': userId,
        'course_id': courseId,
        'new_status': newStatus,
        'old_status': oldStatus,
        'date': date,
    };

    jQuery.post(ajaxurl, data, function (response) {
        jQuery("#datepickerholder").hide();
        
        var obj = JSON.parse(response);
        if (obj.status) {
            dropDown.parent("td#" + userId + "_" + courseId).replaceWith(obj.html);
            var selector = "#" + userId + "_" + courseId + "_1";
            console.log(selector);
            jQuery(selector).prop("selected", true);
        }else{

        }
        jQuery("#datepicker").datepicker("setDate", null);
    });


});

jQuery(document).on('click', '#pop_cancel', function (e) {
    e.preventDefault();
    if (previous) {
        var dropDown = jQuery("#datepicker").data('dropdown');
        dropDown.find("option#" + previous).prop("selected", true);
    }
    jQuery("#datepicker").datepicker("setDate", null);
    jQuery("#datepickerholder").hide();
});


// upload document functionality

// open upload document popup model

jQuery(document).on('click', '.matrix_upload_button', function (e) {

    var dropDown = jQuery(this).parent().find('.user_course_status_dropdown');
    var courseId = jQuery(this).data('course');
    var userId = jQuery(this).data('user');

    jQuery("#uploaddocument").data('courseId', courseId);
    jQuery("#uploaddocument").data('userId', userId);
    jQuery("#uploaddocument").data('dropdown', dropDown);

    jQuery("#uploaddocumentholder").show();

});

//upload cancel button

jQuery(document).on('click', '#upload_pop_cancel', function (e) {
    e.preventDefault();
    if (previous) {
        var dropDown = jQuery("#uploaddocument").data('dropdown');
        dropDown.find("option#" + previous).prop("selected", true);
    }
    jQuery("#uploaddocumentholder").hide();
});

//upload confirm button

jQuery(document).on('click', '#upload_pop_confirm', function (e) {
    e.preventDefault();

    var upfi_button = jQuery(this);
    upfi_button.attr('disabled', 'disabled');
    
    var courseId = jQuery("#uploaddocument").data('courseId');
    var userId = jQuery("#uploaddocument").data('userId');
    var dropDown = jQuery("#uploaddocument").data('dropdown');
    var filedata = jQuery("#selectfilebtn").prop('files')[0];

    if(filedata){

    var form_data = new FormData();
    form_data.append('action', 'upload_course_document');
    form_data.append('file', filedata);
    form_data.append('userId', userId);
    form_data.append('courseId', courseId);

    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: form_data,
        contentType: false,
        processData: false,
        success: function (response) {
            var obj = JSON.parse(response);
            jQuery("#selectfilebtn").val("");
            jQuery("#uploaddocumentholder").hide();

            alert(obj.message);
            upfi_button.attr('disabled', false);
    
            if (obj.status) {

                var selector = "#" + courseId + "_" + userId + "_3";
                var deselector = "#" + courseId + "_" + userId + "_2";
                console.log(selector);
                jQuery(deselector).prop("selected", false);
                jQuery(selector).prop("selected", true);
                jQuery(selector).parent().trigger('change');

            }
    
        }
    });
    }else{
        alert("No file selected!");
        upfi_button.attr('disabled', false);
    };
});

// end of upload document functionality

// edit completed courses date
jQuery(document).on('click', '.matrix_edit_date', function (e) {

    var dropDown = jQuery(this).parent().find('.user_course_status_dropdown');

    //var course_selected_info = dropDown.children(":selected").attr("id");
    var courseId = jQuery(this).data('course');
    var userId = jQuery(this).data('user');

    jQuery("#editdatepicker").data('courseId', courseId);
    jQuery("#editdatepicker").data('userId', userId);
    jQuery("#editdatepicker").data('dropdown', dropDown);

    jQuery("#editdatepicker").datepicker({dateFormat: "yy-mm-dd"});
    jQuery("#editdatepickerholder").show();

});

jQuery(document).on('click', '#edit_pop_confirm', function (e) {
    e.preventDefault();
    var date = jQuery("#editdatepicker").datepicker({dateFormat: 'yy-mm-dd'}).val();
    var courseId = jQuery("#editdatepicker").data('courseId');
    var userId = jQuery("#editdatepicker").data('userId');
    var dropDown = jQuery("#editdatepicker").data('dropdown');


    var data = {
        'action': 'change_user_complete_date',
        'user_id': userId,
        'course_id': courseId,
        'date': date
    };

    jQuery.post(ajaxurl, data, function (response) {
        var obj = JSON.parse(response);
        jQuery("#editdatepicker").datepicker("setDate", null);
        jQuery("#editdatepickerholder").hide();

        if (obj.status) {
            //dropDown.children(":selected").html(obj.html);
            dropDown.parent("td#" + userId + "_" + courseId).replaceWith(obj.html);
        }

    });
});

jQuery(document).on('click', '#edit_pop_cancel', function (e) {
    e.preventDefault();
    jQuery("#editdatepicker").datepicker("setDate", null);
    jQuery("#editdatepickerholder").hide();
});

//cancel all result export
jQuery(document).on('click', "#ldmrefresher_export_all_bck_btn", function (e) { 
    window.stop();
    history.back();
});

jQuery(document).on('click', "#ldmrefresher_export_all_csv", function (e) { 
    if ( !confirm("Please wait while your data exports as this could take some time. Click OK to proceed") ) {
        e.preventDefault();
    }
});




/************************************Export functionality******************************/

jQuery(document).on('click', '.ldmrefresher_export_csv', function(e){
    e.preventDefault();
    var button = jQuery(this);
    button.attr('disabled', 'disabled');
    
    var table = jQuery('.has_export').clone();
    jQuery('#export_container #export_table').html(table);
    jQuery('#export_container #export_table table').attr('id', 'export_report_table');
    
    jQuery("table#export_report_table td").each(function(){
        var td = jQuery(this);
        var data = td.html();
        if(td.find('select').length){
            data = td.find('select').children(":selected").html();
        }else{
            data = data.replace(/<(?:.|\n)*?>/gm, '');
        }
        td.html(data);
    });
    
    jQuery('#export_download_button')[0].click();
    button.attr('disabled', false);
});

/************************************Letter filter functionality******************************/

jQuery(document).on('click', '.filter_first_name', function (e) {
    var selectedSpan = jQuery(this);
    if (jQuery('#character_selected_first_name').val() == selectedSpan.html()) {
        jQuery('span.filter_first_name').css('color', '#444');
        jQuery('#character_selected_first_name').val('');
    } else {
        jQuery('#character_selected_first_name').val(selectedSpan.html());
        jQuery('span.filter_first_name').css('color', '#444');
        selectedSpan.css('color', 'red');
    }
});


jQuery(document).on('click', '.filter_last_name', function (e) {
    var selectedSpan = jQuery(this);
    if (jQuery('#character_selected_last_name').val() == selectedSpan.html()) {
        jQuery('span.filter_last_name').css('color', '#444');
        jQuery('#character_selected_last_name').val('');
    } else {
        jQuery('#character_selected_last_name').val(selectedSpan.html());
        jQuery('span.filter_last_name').css('color', '#444');
        selectedSpan.css('color', 'red');
    }
});

/***********************************Functions**********************************************/

function get_group_jobs(check) {
    var firstFilter = typeof check !== 'undefined' ? check : false;
    jQuery("#ldmrefresher_job").attr('disabled', true);
    jQuery("#ldmrefresher_submit").attr('disabled', true);
    var data = {
        'action': 'get_group_jobs',
        'group': jQuery("#ldmrefresher_group").val(),
        'job': jQuery("#ldmrefresher_group").data('job'),
        'course': jQuery("#ldmrefresher_group").data('course'),
    };
//    alert(data.toSource());
    jQuery.post(ajaxurl, data, function (response) {
//        alert('Got this from the server: ' + response);
        var obj = JSON.parse(response);
        if (obj.status) {
            jQuery("#ldmrefresher_job").html(obj.jobHTML);
            jQuery("#ldmrefresher_course").html(obj.courseHTML);
            if(obj.showjob){
                jQuery("#ldmrefresher_job").show();
            }else{
                jQuery("#ldmrefresher_job").hide();
            }
            
            if(obj.showstatus){
                jQuery("#ldmrefresher_status").show();
            }else{
                jQuery("#ldmrefresher_status").hide();
            }
        }
        jQuery("#ldmrefresher_job").attr('disabled', false);
        jQuery("#ldmrefresher_submit").attr('disabled', false);
        
        if(firstFilter){
            jQuery('#ldmrefresher_matrix_filter').show();
        }
    });
}

function get_job_courses(){
    jQuery("#ldmrefresher_course").attr('disabled', true);
    jQuery("#ldmrefresher_submit").attr('disabled', true);
    var data = {
        'action': 'get_job_courses',
        'group': jQuery("#ldmrefresher_group").val(),
        'job': jQuery("#ldmrefresher_job").val(),
    };
//    alert(data.toSource());
    jQuery.post(ajaxurl, data, function (response) {
//        alert('Got this from the server: ' + response);
        var obj = JSON.parse(response);
        if (obj.status) {
            jQuery("#ldmrefresher_course").html(obj.courseHTML);
        }
        jQuery("#ldmrefresher_status").val('').hide();
        jQuery("#ldmrefresher_course").attr('disabled', false);
        jQuery("#ldmrefresher_submit").attr('disabled', false);
    });
}


function get_parameters_hash(href) {
    var hash = {};
    var parser = document.createElement('a');

    parser.href = href;

    var parameters = parser.search.split(/\?|&/);

    for (var i = 0; i < parameters.length; i++) {
        if (!parameters[i])
            continue;

        var ary = parameters[i].split('=');
        hash[ary[0]] = ary[1];
    }

    return hash;
}

function get_students_under_group(group_id) {

    var data = {
        'action': 'get_students_under_group',
        'group': group_id
    };

    jQuery.post(ajaxurl, data, function (response) {
        var obj = JSON.parse(response);
        if (obj.status) {
            jQuery("#ldmrefresherhistory_user").html(obj.html);
        }
    });
}

/**
 * Get Courses Associated To Specific Group
 * @returns {undefined}
 */
function get_group_jobs_courses() {
    jQuery("#createCompanyUser").attr('disabled', true);
    var userId = '';
    var user = jQuery("#ldmrefresher_group_user").data('user');
    if (user) {
        userId = user;
    }
    var data = {
        'action': 'get_group_jobs_courses',
        'group': jQuery("#ldmrefresher_group_user").val(),
        'user': userId
    };
    //alert(data.toSource());
    jQuery.post(ajaxurl, data, function (response) {
        //alert('Got this from the server: ' + response);
        var obj = JSON.parse(response);
        if (obj.status) {
            jQuery("#courses-lists").html(obj.html);
        }
        jQuery("#createCompanyUser").attr('disabled', false);
    });
}

/************************************email users check boxes functionality on reports page******************************/

jQuery(document).on('click', "#ldr_clear_all", function (e) { 
    jQuery('.email_chkbx:checkbox').prop("checked", false);
});

jQuery(document).on('click', "#ldr_select_all", function (e) { 
    jQuery('.email_chkbx:checkbox').prop("checked", true);
});

jQuery(document).on('click', ".ldr_email_btn", function (e) {

    var chqboxes = jQuery('.email_chkbx:checkbox:checked');
    var user_grp = new Array();
    var last_id = 0;
    var count = 0;

    if(!(chqboxes.length === 0)){

        chqboxes.each(function(i){
            
            var courseId = jQuery(this).data('course');
            var userId = jQuery(this).data('user');
            var status = jQuery(this).data('status');

            if( (userId === last_id || last_id === 0) &&  userId != null){     
                var data1 = {
                    'user_id': userId,
                    'course_id': courseId,
                    'status': status
                };
                user_grp.push(data1);
                last_id = userId;
                count++;
            }
            else
            {
                var data = {
                    'action': 'email_user_about_courses_todo',
                    'info' : user_grp
                }

                jQuery.post(ajaxurl, data, function (response) {
                var emailed_them = JSON.parse(response);
                if (emailed_them.status) {
                    alert(emailed_them.message);
                    jQuery('.email_chkbx:checkbox').prop("checked", false);
                }
                });
                //empty array
                user_grp = new Array();
                var data1 = {
                    'user_id': userId,
                    'course_id': courseId,
                    'status': status
                };
                user_grp.push(data1);
                last_id = userId;
                count++;
            }
            //if user_grp has data and its the last checkbox then email
            if (count == chqboxes.length && user_grp.length != 0)
            {
                    var data = {
                        'action': 'email_user_about_courses_todo',
                        'info' : user_grp
                    }

                    jQuery.post(ajaxurl, data, function (response) {
                    var emailed_them = JSON.parse(response);
                    if (emailed_them.status) {
                        alert(emailed_them.message);
                        jQuery('.email_chkbx:checkbox').prop("checked", false);
                    }
                });
            }

        });

    }else{alert('nothing checked!');}

});

/************************************************************************************************/