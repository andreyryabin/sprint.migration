<?php
/** @var $builder \Sprint\Migration\AbstractBuilder */
?>
<form method="post" class="sp-builder-form">
    <?=$builder->getDescription()?>
    <div class="sp-builder-form-result"></div>
    <input type="hidden" name="builder_name" value="<?=$builder->getName()?>"/>
    <? $fields = $builder->getFields() ?>
    <? foreach ($fields as $fieldCode => $fieldItem): ?>
        <p>
            <?= $fieldItem['title'] ?><br/>
            <? if (!empty($fieldItem['height'])): ?>
                <textarea name="<?= $fieldCode ?>"
                    <? if (!empty($fieldItem['width'])): ?>
                        style="width: <?= $fieldItem['width'] ?>px;height: <?= $fieldItem['height'] ?>px;"
                    <?else:?>
                        style="height: <?= $fieldItem['height'] ?>px;"
                    <? endif; ?>
                ><?= $fieldItem['value'] ?></textarea>

            <? else: ?>
                <input name="<?= $fieldCode ?>"
                       type="text"
                       value="<?= $fieldItem['value'] ?>"
                    <? if (!empty($fieldItem['placeholder'])): ?>
                        placeholder="<?=$fieldItem['placeholder']?>"
                    <? endif; ?>
                    <? if (!empty($fieldItem['width'])): ?>
                        style="width: <?= $fieldItem['width'] ?>px;"
                    <? endif; ?>
                />
            <? endif; ?>
        </p>
    <? endforeach ?>
    <p>
        <input type="submit" value="<?= GetMessage('SPRINT_MIGRATION_GENERATE') ?>"/>
    </p>
</form>