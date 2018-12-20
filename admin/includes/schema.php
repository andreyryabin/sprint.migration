<div id="schema-container" data-sessid="<?= bitrix_sessid() ?>">
    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block sp-block-scroll sp-white">
                <div id="schema_list" class="sp-scroll"></div>
            </div>
            <div class="sp-block sp-block-scroll">
                <div id="schema_log" class="sp-scroll"></div>
            </div>
        </div>
    </div>
    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block">
                <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SELECT_ALL') ?>" class="sp-schema-check"/>
                <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SCHEMA_TEST') ?>" class="sp-schema-test adm-btn-green"/>
            </div>
            <div class="sp-block">
                <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SCHEMA_IMPORT') ?>" class="sp-schema-import"/>
                <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SCHEMA_EXPORT') ?>" class="sp-schema-export"/>
                <div style="width: 300px;display: inline-block;margin: 0 10px;">
                    <div id="schema_progress_current"><span class="bar"></span></div>
                    <div id="schema_progress_full"><span class="bar"></span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="sp-separator"></div>
</div>