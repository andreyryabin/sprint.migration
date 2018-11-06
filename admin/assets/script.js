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

function migrationOutLog(result) {
    var $el = $('#migration_progress');
    var lastOutElem = $el.children('div').last();
    if (lastOutElem.hasClass('migration-bar') && $(result).first().hasClass('migration-bar')) {
        lastOutElem.replaceWith(result);
    } else {
        $el.append(result);
        $el.scrollTop($el.prop("scrollHeight"));
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
                migrationOutLog(result);
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

function migrationBuilder(postData) {
    var $block = $('[data-builder="' + postData['builder_name'] + '"]');
    migrationExecuteStep('migration_create', postData, function (result) {
        $block.html(result);
    });
}

function migrationBuilderReset(postData) {
    var $block = $('[data-builder="' + postData['builder_name'] + '"]');
    migrationExecuteStep('migration_reset', postData, function (result) {
        $block.html(result);
    });
}

function migrationScrollList() {
    var $el = $('#migration_migrations');
    $el.scrollTop($el.prop("scrollHeight"));
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
        migrationScrollList();
    });

    $('#migration-container').on('change', '.sp-stat', function () {
        migrationMigrationRefresh(function () {
            migrationEnableButtons(1);
            migrationScrollList();
            $('#tab_cont_tab1').click();
        });
    });

    $('#migration-container').on('keypress', 'input[name=migration_search]', function (e) {
        if (e.keyCode == 13) {
            migrationMigrationRefresh(function () {
                migrationEnableButtons(1);
                migrationScrollList();
                $('#tab_cont_tab1').click();
            });
        }
    });

    $('#migration-container').on('click', '.sp-search', function () {
        migrationMigrationRefresh(function () {
            migrationEnableButtons(1);
            migrationScrollList();
            $('#tab_cont_tab1').click();
        });
    });

    $('#migration-container').on('click', '.sp-optgroup-check', function (e) {
        var checkboxes = $(this).closest('.sp-optgroup').find(':checkbox');
        checkboxes.attr("checked", !checkboxes.attr("checked"));
        e.preventDefault();
    });

    $('[data-builder]').on('submit', 'form', function (e) {
        e.preventDefault();
        var postData = $(this).serializeFormJSON();
        migrationBuilder(postData);
    });

    $('[data-builder]').on('reset', 'form', function (e) {
        e.preventDefault();
        var postData = $(this).serializeFormJSON();
        migrationBuilderReset(postData);
    });

    var openblockIx = 0;
    if (localStorage) {
        openblockIx = localStorage.getItem('migrations_open_block');
        openblockIx = (openblockIx) ? parseInt(openblockIx, 10) : 0;
    }

    $('.sp-block').eq(openblockIx).addClass('sp-active');

    $('.sp-block_title').on('click', function () {

        if (localStorage) {
            openblockIx = $('.sp-block_title').index(this);
            openblockIx = parseInt(openblockIx, 10);
            localStorage.setItem('migrations_open_block', openblockIx);
        }

        var $block = $(this).closest('.sp-block');

        $('.sp-block').not($block).removeClass('sp-active');
        $block.addClass('sp-active');

        var docViewTop = $(window).scrollTop();
        var elemTop = $(this).offset().top;
        if (elemTop <= docViewTop) {
            $(document).scrollTop(elemTop - 25);
        }


        $('.sp-block').find('.adm-info-message-wrap').remove();
    });

});