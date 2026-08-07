function migrationMigrationsUpConfirm() {
    if (confirm('Confirm install migrations')) {
        migrationExecuteStep('migration_execute', {
            'next_action': 'up'
        });
    }
}

function migrationMigrationsDownConfirm() {
    if (confirm('Confirm rollback migrations')) {
        migrationExecuteStep('migration_execute', {
            'next_action': 'down'
        });
    }
}

function migrationMigrationsUpWithTag() {
    var settag = prompt('Set migrations tag');
    if (settag !== null) {
        migrationExecuteStep('migration_execute', {
            'next_action': 'up',
            'settag': settag
        });
    }
}

function migrationMigrationUp(version) {
    migrationExecuteStep('migration_execute', {
        'version': version,
        'action': 'up'
    });
}

function migrationMigrationDown(version) {
    migrationExecuteStep('migration_execute', {
        'version': version,
        'action': 'down'
    });
}

function migrationMigrationSetTag(version, defaultTag) {
    var settag = prompt('Set migration tag', defaultTag);
    if (settag !== null) {
        migrationExecuteStep('migration_settag', {
            'version': version,
            'settag': settag
        });
    }
}

function migrationMigrationMark(version, status) {
    migrationExecuteStep('migration_mark', {
        'version': version,
        'status': status,
    });
}

function migrationMigrationTransfer(version, transferTo) {
    migrationExecuteStep('migration_transfer', {
        'version': version,
        'transfer_to': transferTo,
    });
}

function migrationMigrationDelete(version) {
    if (confirm('Confirm delete migration file')) {
        migrationExecuteStep('migration_delete', {
            'version': version,
        });
    }
}

function migrationOutLog(result) {
    let $res = jQuery('<div>' + result + '</div>');
    let $el = jQuery('#migration_log');
    let $pg = jQuery('#migration_progress');

    $pg.html($res.children('.sp-progress'));

    $el.append($res.children());

    $el.scrollTop($el.prop("scrollHeight"));
}

function migrationExecuteStep(step_code, postData, succesCallback) {
    postData = postData || {};
    postData['step_code'] = step_code;
    postData['sessid'] = jQuery('#migration_container').data('sessid');
    postData['search'] = jQuery('#migration_search').val();
    postData['migration_view'] = jQuery('#migration_view').val();

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
            migrationOutLog(result.responseText);
            migrationEnableButtons(1);
        }
    });
}

function migrationEnableButtons(enable) {
    let $container = jQuery('#migration_container');
    let $loader = jQuery('#migration_loading');
    let $buttons = $container.find('input,select,.adm-btn');
    if (enable) {
        $buttons.removeAttr('disabled').removeClass('sp-disabled');
        $loader.hide();
    } else {
        $buttons.attr('disabled', 'disabled').addClass('sp-disabled');
        $loader.show();
    }
}

function migrationListRefresh(callbackAfterRefresh) {
    jQuery('#migration_actions').empty();
    migrationExecuteStep(
        jQuery('#migration_view').val(),
        {},
        function (data) {
            jQuery('#migration_migrations').empty().html(data);
            if (callbackAfterRefresh) {
                callbackAfterRefresh()
            } else {
                migrationEnableButtons(1);
            }
        });
}

function migrationBuilder(postData) {
    migrationExecuteStep('migration_create', postData, function (result) {
        migrationBuilderRender(result)
    });
}

function migrationBuilderRestart() {
    let postData = jQuery('#migration_builder form').serializeFormJSON();
    migrationBuilder(postData);
}

function migrationBuilderRebuild($field) {
    let postData = jQuery('#migration_builder form').serializeFormJSON();

    if ($field.attr('name') === 'table_mode') {
        delete postData.table_name;
        delete postData.new_table_name;
        delete postData.fields_json;
    }

    migrationBuilder(postData);
}

function migrationReset(postData) {
    migrationExecuteStep('migration_reset', postData, function (result) {
        migrationBuilderRender(result, {})
    });
}

function migrationListScroll() {
    var $el = jQuery('#migration_migrations');
    $el.scrollTop($el.prop("scrollHeight"));
}

function migrationBuilderRender(html) {
    let $builder = jQuery('#migration_builder');
    let formAttrs = $builder.serializeFormAttrs();

    $builder.html(html);
    migrationInitOrmFields($builder);

    jQuery.each(formAttrs, function (name, value) {
        let $el = $builder.find('[data-attrs=' + name + ']');
        if ($el.length > 0) {
            $el.val(value).trigger('input');
        }
    });
}

function migrationAutocompleteValidate($input) {
    let value = $input.val();
    let selectedValue = $input.attr('data-autocomplete-selected-value') || '';
    let $root = $input.closest('.sp-autocomplete');
    let $message = $root.find('.sp-autocomplete-message');

    if (!value || value === selectedValue) {
        $root.removeClass('sp-autocomplete-invalid');
        $message.empty().hide();
        return true;
    }

    $root.addClass('sp-autocomplete-invalid');
    $message.text($input.data('autocomplete-message') || 'Select item from list').show();
    return false;
}

function migrationAutocompleteSelect($input, value) {
    $input.val(value);
    $input.attr('data-autocomplete-selected-value', value);
    migrationAutocompleteValidate($input);
}

function migrationAutocompleteSearch($input) {
    let source = $input.data('autocomplete-source');
    let search = $input.val();
    let $items = $input.closest('.sp-autocomplete').find('.sp-autocomplete-items');

    $input.attr('data-autocomplete-selected-value', '');

    if (!source || search.length < 2) {
        $items.empty().hide();
        migrationAutocompleteValidate($input);
        return;
    }

    clearTimeout($input.data('autocomplete-timeout'));
    $input.data('autocomplete-timeout', setTimeout(function () {
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            data: {
                step_code: 'migration_autocomplete',
                sessid: jQuery('#migration_container').data('sessid'),
                source: source,
                search: search
            },
            success: function (result) {
                $items.empty();
                jQuery.each(result.items || [], function (_, item) {
                    jQuery('<button type="button" class="sp-autocomplete-item"></button>')
                        .text(item.title)
                        .attr('data-value', item.value)
                        .appendTo($items);
                });

                if ($items.children().length > 0) {
                    $items.show();
                } else {
                    $items.hide();
                }
            }
        });
    }, 250));
}

function migrationInitOrmFields($container) {
    $container.find('.sp-orm-fields').each(function () {
        let $root = jQuery(this);
        let $input = $root.find('input[type=hidden]');
        let items = [];

        try {
            items = JSON.parse($input.val() || '[]');
        } catch (e) {
            items = [];
        }

        $root.find('.sp-orm-fields-list').empty();
        jQuery.each(items, function (_, item) {
            migrationAddOrmFieldRow($root, item);
        });

        if (items.length === 0) {
            migrationAddOrmFieldRow($root, {});
        }

        migrationSyncOrmFields($root);
    });
}

function migrationAddOrmFieldRow($root, item) {
    item = item || {};
    let labels = $root.data('labels') || {};
    let options = $root.data('options') || {};
    if (typeof labels === 'string') {
        try {
            labels = JSON.parse(labels);
        } catch (e) {
            labels = {};
        }
    }
    if (typeof options === 'string') {
        try {
            options = JSON.parse(options);
        } catch (e) {
            options = {};
        }
    }
    let types = ['integer', 'string', 'text', 'float', 'boolean', 'date', 'datetime'];
    let $card = jQuery('<div class="sp-orm-field-card"></div>');
    let $grid = jQuery('<div class="sp-orm-field-grid"></div>');

    let $name = jQuery('<input type="text" class="sp-orm-field-name"/>').val(item.name || '');
    let $type = jQuery('<select class="sp-orm-field-type"></select>');
    jQuery.each(types, function (_, type) {
        jQuery('<option></option>').attr('value', type).text(type).appendTo($type);
    });
    $type.val(item.type || 'string');

    $grid.append(migrationOrmFieldControl(labels.name || 'Name', $name));
    $grid.append(migrationOrmFieldControl(labels.type || 'Type', $type));
    $grid.append(migrationOrmFieldControl(
        labels.length || 'Length',
        jQuery('<input type="number" class="sp-orm-field-length" min="0"/>').val(item.length || '')
    ));
    $grid.append(migrationOrmFieldControl(
        labels.default || 'Default',
        jQuery('<input type="text" class="sp-orm-field-default"/>').val(item.default || '')
    ));

    $card.append($grid);
    $card.append(jQuery('<label class="sp-orm-field-check"></label>').append(
        jQuery('<input type="checkbox" class="sp-orm-field-nullable"/>')
            .prop('checked', item.nullable === undefined ? true : !!item.nullable),
        document.createTextNode(labels.nullable || 'Nullable')
    ));

    if (options.primary_enabled !== false) {
        $card.append(jQuery('<label class="sp-orm-field-check"></label>').append(
            jQuery('<input type="checkbox" class="sp-orm-field-primary"/>').prop('checked', !!item.primary),
            document.createTextNode(labels.primary || 'Primary key')
        ));
    }

    if (options.autoincrement_enabled !== false) {
        $card.append(jQuery('<label class="sp-orm-field-check"></label>').append(
            jQuery('<input type="checkbox" class="sp-orm-field-autoincrement"/>').prop('checked', !!item.autoincrement),
            document.createTextNode(labels.autoincrement || 'Autoincrement')
        ));
    }

    $card.append(jQuery('<button type="button" class="adm-btn sp-orm-fields-remove"></button>').text(labels.delete || 'Delete field'));

    $root.find('.sp-orm-fields-list').append($card);
}

function migrationOrmFieldControl(title, $control) {
    return jQuery('<label class="sp-orm-field-control"></label>').append(
        jQuery('<span></span>').text(title),
        $control
    );
}

function migrationSyncOrmFields($root) {
    let items = [];

    $root.find('.sp-orm-field-card').each(function () {
        let $card = jQuery(this);
        let name = $card.find('.sp-orm-field-name').val();

        if (!name) {
            return;
        }

        items.push({
            name: name,
            type: $card.find('.sp-orm-field-type').val(),
            length: $card.find('.sp-orm-field-length').val(),
            nullable: $card.find('.sp-orm-field-nullable').is(':checked') ? 1 : 0,
            default: $card.find('.sp-orm-field-default').val(),
            primary: $card.find('.sp-orm-field-primary').is(':checked') ? 1 : 0,
            autoincrement: $card.find('.sp-orm-field-autoincrement').is(':checked') ? 1 : 0
        });
    });

    $root.find('input[type=hidden]').val(JSON.stringify(items));
}

jQuery(document).ready(function ($) {

    $.fn.serializeFormJSON = function () {
        let o = {};
        let a = this.serializeArray();
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
    $.fn.serializeFormAttrs = function () {
        let o = {};
        $(this).find('[data-attrs]').each(function () {
            let name = $(this).data('attrs');
            let val = $(this).val();
            if (val) {
                o[name] = val;
            }
        });
        return o;
    };

    (function () {
        let viewName = localStorage.getItem('sprint_migrations_view');
        if (viewName) {
            $('#migration_view').val(viewName);
        }

        let searchName = localStorage.getItem('sprint_migrations_search');
        if (searchName) {
            $('#migration_search').val(searchName);
        }

        let builderName = localStorage.getItem('sprint_migrations_builder');
        if (builderName) {
            $('#migration_container [data-builder="' + builderName + '"]').addClass('sp-active');
            migrationReset({builder_name: builderName});
        }
    })($);

    migrationListRefresh(function () {
        migrationEnableButtons(1);
        migrationListScroll();
    });

    $('#migration_view').on('change', function () {
        localStorage.setItem('sprint_migrations_view', $(this).val())
        migrationListRefresh(function () {
            migrationEnableButtons(1);
            migrationListScroll();
        });
    });

    $('#migration_search').on('keypress', function (e) {
        if (e.keyCode === 13) {
            localStorage.setItem('sprint_migrations_search', $(this).val())
            migrationListRefresh(function () {
                migrationEnableButtons(1);
                migrationListScroll();
            });
        }
    });

    $('#migration_refresh').on('click', function () {
        localStorage.setItem('sprint_migrations_search', $('#migration_search').val())
        migrationListRefresh(function () {
            migrationEnableButtons(1);
            migrationListScroll();
        });
    });

    $('#migration_builder').on('click', '.sp-optgroup-check', function (e) {
        e.preventDefault();
        var checkboxes = $(this).closest('.sp-optgroup').find('[type=checkbox]').not(':hidden');
        checkboxes.prop("checked", !checkboxes.prop("checked"));
    });

    $('#migration_builder').on('input', '.sp-optgroup-search', function (e) {
        e.preventDefault();
        let searchText = $(this).val().toLowerCase();

        $(this).closest('.sp-optgroup').find('.sp-optgroup-group').each(function () {
            let all = 0;
            let hide = 0;

            $(this).find('label').each(function () {
                let labelText = $(this).text().toLowerCase();

                all++;
                if (labelText.includes(searchText)) {
                    $(this).show()
                } else {
                    hide++;
                    $(this).hide()
                }
            });

            if (hide > 0 && all === hide) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });

    $('#migration_builder').on('change', '[data-rebuild-on-change]', function () {
        migrationBuilderRebuild($(this));
    });

    $('#migration_builder').on('input', '[data-autocomplete-source]', function () {
        migrationAutocompleteSearch($(this));
    });

    $('#migration_builder').on('keydown', '[data-autocomplete-source]', function (e) {
        if (e.keyCode === 13 && !migrationAutocompleteValidate($(this))) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    $('#migration_builder').on('click', '.sp-autocomplete-item', function () {
        let $item = $(this);
        let $root = $item.closest('.sp-autocomplete');
        migrationAutocompleteSelect($root.find('[data-autocomplete-source]'), $item.data('value'));
        $root.find('.sp-autocomplete-items').empty().hide();
    });

    $('#migration_builder').on('click', '.sp-orm-fields-add', function (e) {
        e.preventDefault();
        let $root = $(this).closest('.sp-orm-fields');
        migrationAddOrmFieldRow($root, {});
        migrationSyncOrmFields($root);
    });

    $('#migration_builder').on('click', '.sp-orm-fields-remove', function (e) {
        e.preventDefault();
        let $root = $(this).closest('.sp-orm-fields');
        $(this).closest('.sp-orm-field-card').remove();
        if ($root.find('.sp-orm-field-card').length === 0) {
            migrationAddOrmFieldRow($root, {});
        }
        migrationSyncOrmFields($root);
    });

    $('#migration_builder').on('input change', '.sp-orm-fields input, .sp-orm-fields select', function () {
        migrationSyncOrmFields($(this).closest('.sp-orm-fields'));
    });

    $('#migration_builder').on('submit', 'form', function (e) {
        e.preventDefault();
        let autocompleteValid = true;
        $(this).find('[data-autocomplete-source]').each(function () {
            autocompleteValid = migrationAutocompleteValidate($(this)) && autocompleteValid;
        });
        if (!autocompleteValid) {
            return;
        }

        let postData = $(this).serializeFormJSON();
        migrationBuilder(postData);
    });

    $('#migration_builder').on('reset', 'form', function (e) {
        e.preventDefault();
        let postData = $(this).serializeFormJSON();
        migrationReset(postData);
    });

    $('#migration_container').on('click', '.sp-builder_title', function () {

        var builderName = $(this).data('builder');

        $('.sp-builder_title').not(this).removeClass('sp-active');

        $(this).addClass('sp-active');

        localStorage.setItem('sprint_migrations_builder', builderName);

        migrationReset({builder_name: builderName});
    });


});
