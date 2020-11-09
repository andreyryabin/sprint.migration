<?php
/** @var $builder AbstractBuilder */

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Locale;
use Sprint\Migration\Out;

?>
<form method="post">
    <? $fields = $builder->getFields() ?>
    <? foreach ($fields as $fieldCode => $fieldItem): ?>
        <? if ($fieldItem['type'] == 'hidden'): ?>
            <input type="hidden" name="<?= $fieldCode ?>" value="<?= $fieldItem['value'] ?>"/>
        <? else: ?>
            <div class="sp-field">
                <? if (!empty($fieldItem['title'])): ?><?= $fieldItem['title'] ?><br/><? endif; ?>
                <? if (!empty($fieldItem['note'])): ?>
                    <div class="sp-field-note"><?= $fieldItem['note'] ?></div><? endif; ?>
                <? if (!empty($fieldItem['height'])): ?>
                    <textarea name="<?= $fieldCode ?>"
                        <? if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;height: <?= $fieldItem['height'] ?>px;"
                        <? else: ?>
                            style="height: <?= $fieldItem['height'] ?>px;"
                        <? endif; ?>
                    ><?= $fieldItem['value'] ?></textarea>
                <? elseif (isset($fieldItem['select']) && !$fieldItem['multiple']): ?>
                    <select name="<?= $fieldCode ?>"
                        <? if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;"
                        <? endif; ?>
                    ><? foreach ($fieldItem['select'] as $item): ?>
                            <option value="<?= $item['value'] ?>"
                                <? if ($fieldItem['value'] == $item['value']): ?>
                                    selected="selected"
                                <? endif; ?>
                            ><?= $item['title'] ?></option>
                        <? endforeach; ?>
                    </select>
                <? elseif (isset($fieldItem['select']) && $fieldItem['multiple']): ?>
                    <div class="sp-optgroup">
                        <div style="padding: 5px 0;">
                            <a href="#" class="sp-optgroup-check"><?= Locale::getMessage('SELECT_ALL') ?></a>
                        </div>
                        <? foreach ($fieldItem['select'] as $item): ?>
                            <label>
                                <input name="<?= $fieldCode ?>[]"
                                       value="<?= $item['value'] ?>"
                                    <? if (in_array($item['value'], $fieldItem['value'])): ?>
                                        checked="checked"
                                    <? endif; ?>
                                       type="checkbox"
                                ><?= $item['title'] ?></label> <br/>
                        <? endforeach; ?>
                    </div>
                <? elseif (isset($fieldItem['items']) && !$fieldItem['multiple']): ?>
                    <select name="<?= $fieldCode ?>"
                        <? if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;"
                        <? endif; ?>
                    ><? foreach ($fieldItem['items'] as $group): ?>
                            <optgroup label="<?= $group['title'] ?>">
                                <? if (isset($group['items'])): ?>
                                    <? foreach ($group['items'] as $item): ?>
                                        <option value="<?= $item['value'] ?>"
                                            <? if ($fieldItem['value'] == $item['value']): ?>
                                                selected="selected"
                                            <? endif; ?>
                                        ><?= $item['title'] ?></option>
                                    <? endforeach; ?>
                                <? endif; ?>
                            </optgroup>
                        <? endforeach; ?>
                    </select>
                <? elseif (isset($fieldItem['items']) && $fieldItem['multiple']): ?>
                    <? foreach ($fieldItem['items'] as $group): ?>
                        <div class="sp-optgroup">
                            <? if (!empty($group['title'])): ?><?= $group['title'] ?><br/><? endif; ?>
                            <? if (isset($group['items'])): ?>
                                <div style="padding: 5px 0;">
                                    <a href="#"
                                       class="sp-optgroup-check"><?= Locale::getMessage('SELECT_ALL') ?></a>
                                </div>
                                <? foreach ($group['items'] as $item): ?>
                                    <label>
                                        <input name="<?= $fieldCode ?>[]"
                                               value="<?= $item['value'] ?>"
                                            <? if (in_array($item['value'], $fieldItem['value'])): ?>
                                                checked="checked"
                                            <? endif; ?>
                                               type="checkbox"
                                        ><?= $item['title'] ?></label> <br/>
                                <? endforeach; ?>
                            <? endif; ?>
                        </div>
                    <? endforeach; ?>
                <? else: ?>
                    <input name="<?= $fieldCode ?>"
                           type="text"
                           value="<?= $fieldItem['value'] ?>"
                        <? if (!empty($fieldItem['placeholder'])): ?>
                            placeholder="<?= $fieldItem['placeholder'] ?>"
                        <? endif; ?>
                        <? if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;"
                        <? endif; ?>
                    />
                <? endif; ?>
            </div>
        <? endif ?>
    <? endforeach ?>
    <? if ($builder->hasDescription()): ?>
        <div class="sp-field sp-info-message">
            <? Out::out($builder->getDescription()) ?>
        </div>
    <? endif; ?>
    <div class="sp-field">
        <input type="submit" value="<?= Locale::getMessage('BUILDER_NEXT') ?>"/>
        <input type="reset" value="<?= Locale::getMessage('BUILDER_RESET') ?>"/>
    </div>
</form>
