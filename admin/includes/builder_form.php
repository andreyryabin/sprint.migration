<?php
/** @var $builder AbstractBuilder */

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Locale;
use Sprint\Migration\Out;

?>
<form method="post">
    <?php $fields = $builder->getFields() ?>
    <?php foreach ($fields as $fieldCode => $fieldItem): ?>
        <?php if ($fieldItem['type'] == 'hidden'): ?>
            <input type="hidden" name="<?= $fieldCode ?>" value="<?= $fieldItem['value'] ?>"/>
        <?php else: ?>
            <div class="sp-field">
                <?php if (!empty($fieldItem['title'])): ?><?= $fieldItem['title'] ?><br/><?php endif; ?>
                <?php if (!empty($fieldItem['note'])): ?>
                    <div class="sp-field-note"><?= $fieldItem['note'] ?></div><?php endif; ?>
                <?php if (!empty($fieldItem['height'])): ?>
                    <textarea name="<?= $fieldCode ?>"
                        <?php if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;height: <?= $fieldItem['height'] ?>px;"
                        <?php else: ?>
                            style="height: <?= $fieldItem['height'] ?>px;"
                        <?php endif; ?>
                    ><?= $fieldItem['value'] ?></textarea>
                <?php elseif (isset($fieldItem['select']) && !$fieldItem['multiple']): ?>
                    <select name="<?= $fieldCode ?>"
                        <?php if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;"
                        <?php endif; ?>
                    ><?php foreach ($fieldItem['select'] as $item): ?>
                            <option value="<?= $item['value'] ?>"
                                <?php if ($fieldItem['value'] == $item['value']): ?>
                                    selected="selected"
                                <?php endif; ?>
                            ><?= $item['title'] ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (isset($fieldItem['select']) && $fieldItem['multiple']): ?>
                    <div class="sp-optgroup">
                        <div style="padding: 5px 0;">
                            <a href="#" class="sp-optgroup-check"><?= Locale::getMessage('SELECT_ALL') ?></a>
                        </div>
                        <?php foreach ($fieldItem['select'] as $item): ?>
                            <label>
                                <input name="<?= $fieldCode ?>[]"
                                       value="<?= $item['value'] ?>"
                                    <?php if (in_array($item['value'], $fieldItem['value'])): ?>
                                        checked="checked"
                                    <?php endif; ?>
                                       type="checkbox"
                                ><?= $item['title'] ?></label> <br/>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($fieldItem['items']) && !$fieldItem['multiple']): ?>
                    <select name="<?= $fieldCode ?>"
                        <?php if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;"
                        <?php endif; ?>
                    ><?php foreach ($fieldItem['items'] as $group): ?>
                            <optgroup label="<?= $group['title'] ?>">
                                <?php if (isset($group['items'])): ?>
                                    <?php foreach ($group['items'] as $item): ?>
                                        <option value="<?= $item['value'] ?>"
                                            <?php if ($fieldItem['value'] == $item['value']): ?>
                                                selected="selected"
                                            <?php endif; ?>
                                        ><?= $item['title'] ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (isset($fieldItem['items']) && $fieldItem['multiple']): ?>
                    <?php foreach ($fieldItem['items'] as $group): ?>
                        <div class="sp-optgroup">
                            <?php if (!empty($group['title'])): ?><?= $group['title'] ?><br/><?php endif; ?>
                            <?php if (isset($group['items'])): ?>
                                <div style="padding: 5px 0;">
                                    <a href="#"
                                       class="sp-optgroup-check"><?= Locale::getMessage('SELECT_ALL') ?></a>
                                </div>
                                <?php foreach ($group['items'] as $item): ?>
                                    <label>
                                        <input name="<?= $fieldCode ?>[]"
                                               value="<?= $item['value'] ?>"
                                            <?php if (in_array($item['value'], $fieldItem['value'])): ?>
                                                checked="checked"
                                            <?php endif; ?>
                                               type="checkbox"
                                        ><?= $item['title'] ?></label> <br/>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <input name="<?= $fieldCode ?>"
                           type="text"
                           value="<?= $fieldItem['value'] ?>"
                        <?php if (!empty($fieldItem['placeholder'])): ?>
                            placeholder="<?= $fieldItem['placeholder'] ?>"
                        <?php endif; ?>
                        <?php if (!empty($fieldItem['width'])): ?>
                            style="width: <?= $fieldItem['width'] ?>px;"
                        <?php endif; ?>
                    />
                <?php endif; ?>
            </div>
        <?php endif ?>
    <?php endforeach ?>
    <?php if ($builder->hasDescription()): ?>
        <div class="sp-field sp-info-message">
            <?php Out::out($builder->getDescription()) ?>
        </div>
    <?php endif; ?>
    <div class="sp-field">
        <input type="submit" value="<?= Locale::getMessage('BUILDER_NEXT') ?>"/>
        <input type="reset" value="<?= Locale::getMessage('BUILDER_RESET') ?>"/>
    </div>
</form>
