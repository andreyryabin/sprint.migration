<?php
/** @var $builder \Sprint\Migration\AbstractBuilder */
?>
<form method="post">
    <?= $builder->getDescription() ?>
    <input type="hidden" name="builder_name" value="<?= $builder->getName() ?>"/>

    <? $fields = $builder->getFields() ?>
    <? foreach ($fields as $fieldCode => $fieldItem): ?>
        <p>
            <?= $fieldItem['title'] ?><br/>
            <? if (!empty($fieldItem['height'])): ?>
                <textarea name="<?= $fieldCode ?>"
                    <? if (!empty($fieldItem['width'])): ?>
                        style="width: <?= $fieldItem['width'] ?>px;height: <?= $fieldItem['height'] ?>px;"
                    <? else: ?>
                        style="height: <?= $fieldItem['height'] ?>px;"
                    <? endif; ?>
                ><?= $fieldItem['value'] ?></textarea>

            <? elseif (!empty($fieldItem['items']) && !$fieldItem['multiple']): ?>

                <select name="<?= $fieldCode ?>"
                    <? if (!empty($fieldItem['width'])): ?>
                        style="width: <?= $fieldItem['width'] ?>px;"
                    <? endif; ?>
                >
                    <? foreach ($fieldItem['items'] as $group): ?>
                        <optgroup label="<?= $group['title'] ?>">
                            <? foreach ($group['items'] as $item): ?>
                                <option value="<?= $item['value'] ?>"
                                <?if ($fieldItem['value'] == $item['value']): ?>
                                    selected="selected"
                                <?endif;?>
                                ><?= $item['title'] ?></option>
                            <? endforeach; ?>
                        </optgroup>
                    <? endforeach; ?>
                </select>
            <? elseif (!empty($fieldItem['items']) && $fieldItem['multiple']): ?>
                <? foreach ($fieldItem['items'] as $group): ?>
                    <? foreach ($group['items'] as $item): ?>
                        <label>
                            <input name="<?= $fieldCode ?>[]"
                                   value="<?= $item['value'] ?>"
                                <?if (in_array($item['value'],$fieldItem['value'])): ?>
                                    checked="checked"
                                <?endif;?>
                                   type="checkbox"
                            ><?= $item['title'] ?></label> <br/>
                    <? endforeach; ?>
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
        </p>
    <? endforeach ?>
    <p>
        <input type="submit" value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"/>
    </p>
</form>