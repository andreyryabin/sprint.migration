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

function migrationCreate(postData){

    var $block = $('[data-builder="'+postData['builder_name']+'"]');

    migrationExecuteStep('migration_create', postData, function (result) {
        $block.html(result);
    });
}

jQuery(document).ready(function ($) {

    $.fn.serializeFormJSON = function () {

        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

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


    $('[data-builder]').on('submit', 'form', function (e) {
        e.preventDefault();
        var postData = $(this).serializeFormJSON();
        migrationCreate(postData);
    });

    $('[data-builder]').on('reset', 'form', function (e) {
        e.preventDefault();
        var postData = $(this).serializeFormJSON();



        migrationCreate({builder_name: postData['builder_name']});
    });


    var openblockIx = 0;
    if (localStorage) {
        openblockIx = localStorage.getItem('migrations_open_block');
        openblockIx = (openblockIx) ? parseInt(openblockIx, 10) : 0;
    }

    $('.sp-block_body').eq(openblockIx).show();

    $('.sp-block_title').on('click', function () {

        if (localStorage) {
            openblockIx = $('.sp-block_title').index(this);
            openblockIx = parseInt(openblockIx, 10);
            localStorage.setItem('migrations_open_block', openblockIx);
        }

        var $body = $(this).siblings('.sp-block_body');
        $body.show();
        $('.sp-block_body').not($body).hide();

        $('.sp-block').find('.adm-info-message-wrap').remove();
    });


});