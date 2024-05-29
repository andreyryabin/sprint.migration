<?php

use Sprint\Migration\Locale;

?>
<div id="schema-container" data-sessid="<?php echo bitrix_sessid() ?>">
    <div class="sp-table">
        <div class="sp-row2">
            <div class="sp-col sp-white">
                <div id="schema_list" class="sp-scroll"></div>
            </div>
            <div class="sp-col">
                <div id="schema_log" class="sp-scroll"></div>
            </div>
        </div>
    </div>
    <div class="sp-table">
        <div class="sp-row2">
            <div class="sp-col">
                <input type="button" value="<?php echo Locale::getMessage('SELECT_ALL') ?>" class="sp-schema-check"/>
                <input type="button" value="<?php echo Locale::getMessage('SCHEMA_DIFF') ?>"
                       class="sp-schema-test adm-btn-green"/>
            </div>
            <div class="sp-col">
                <div>
                    <input type="button" value="<?php echo Locale::getMessage('SCHEMA_IMPORT') ?>"
                           class="sp-schema-import"/>
                    <input type="button" value="<?php echo Locale::getMessage('SCHEMA_EXPORT') ?>"
                           class="sp-schema-export"/>
                </div>
                <div style="width: 300px;">
                    <div id="schema_progress_current"><span class="bar"></span></div>
                    <div id="schema_progress_full"><span class="bar"></span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="sp-separator"></div>
</div>
