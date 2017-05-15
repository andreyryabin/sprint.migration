function migrationMigrationsUpConfirm() {
    if (confirm('Confirm action')) {
        migrationExecuteStep('migration_execute', {
            'next_action': 'up'
        });
    }
}

function migrationMigrationsDownConfirm() {
    if (confirm('Confirm action')) {
        migrationExecuteStep('migration_execute', {
            'next_action': 'down'
        });
    }
}

function migrationOutProgress(result) {
    var outProgress = $('#migration_progress');
    var lastOutElem = outProgress.children('div').last();
    if (lastOutElem.hasClass('migration-bar') && $(result).first().hasClass('migration-bar')) {
        lastOutElem.replaceWith(result);
    } else {
        outProgress.append(result);
        outProgress.scrollTop(outProgress.prop("scrollHeight"));
    }
}

function migrationExecuteStep(step_code, postData, succesCallback) {
    postData = postData || {};
    postData['step_code'] = step_code;
    postData['send_sessid'] = $('input[name=send_sessid]').val();
    postData['search'] = $('input[name=migration_search]').val();

    console.log(postData);


    migrationEnableButtons(0);

    jQuery.ajax({
        type: "POST",
        dataType: "html",
        data: postData,
        success: function (result) {
            if (succesCallback) {
                succesCallback(result)
            } else {
                migrationOutProgress(result);
            }
        },
        error: function (result) {

        }
    });
}

function migrationEnableButtons(enable) {
    var buttons = $('#migration-container').find('input,select');
    if (enable) {
        buttons.removeAttr('disabled');
    } else {
        buttons.attr('disabled', 'disabled');
    }
}

function migrationMarkMigration(status) {
    $('#migration_migration_mark_result').html('');
    migrationExecuteStep('migration_mark', {
        version: $('#migration_migration_mark').val(),
        status: status
    }, function (result) {
        $('#migration_migration_mark_result').html(result);
        migrationMigrationRefresh();
    });
}

function migrationMigrationRefresh(callbackAfterRefresh) {
    var view = $('.sp-stat').val();
    migrationExecuteStep('migration_' + view, {}, function (data) {
        $('#migration_migrations').empty().html(data);
        if (callbackAfterRefresh) {
            callbackAfterRefresh()
        } else {
            migrationEnableButtons(1);
        }
    });
}

jQuery(document).ready(function ($) {
    migrationMigrationRefresh(function () {
        migrationEnableButtons(1);
    });

    $('#tab_cont_tab3').on('click', function () {
        var outProgress = $('#migration_progress');
        outProgress.scrollTop(outProgress.prop("scrollHeight"));
    });

    $('.sp-stat').on('change', function () {
        migrationMigrationRefresh(function () {
            migrationEnableButtons(1);
            $('#tab_cont_tab1').click();
        });
    });

    $('input[name=migration_search]').on('keypress', function (e) {
        if (e.keyCode == 13) {
            migrationMigrationRefresh(function () {
                migrationEnableButtons(1);
                $('#tab_cont_tab1').click();
            });
        }
    });

    $('.sp-search').on('click', function () {
        migrationMigrationRefresh(function () {
            migrationEnableButtons(1);
            $('#tab_cont_tab1').click();
        });
    });


    $('.sp-builder-form').on('submit', function (e) {
        e.preventDefault();

        var postdata = {};
        var arr = $(this).serializeArray();

        $.each(arr, function(index, item){
            postdata[item.name] = item.value;
        });

        var $res = $(this).find('.sp-builder-form-result');

        migrationExecuteStep('migration_create', postdata, function (result) {
            $res.html(result);
            migrationMigrationRefresh();
        });

    });

    $('.sp-block_body').first().show();

    $('.sp-block_title').on('click',function(){
        var $body = $(this).siblings('.sp-block_body');
        $body.show();
        $('.sp-block_body').not($body).hide();

        // if ($body.is(':visible')){
        //     $body.hide();
        // } else {
        //     $body.show();
        //     $('.sp-block_body').not($body).hide();
        // }

        $('.sp-block').find('.adm-info-message-wrap').remove();
    });

});