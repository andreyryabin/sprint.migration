<? $builder = $versionManager->createVersionBuilder() ?>
<? $fields = $builder->getFields() ?>
<? foreach ($fields as $fieldCode => $fieldItem): ?>
    <p>
        <?= $fieldItem['title'] ?><br/>

        <? if (!empty($fieldItem['options'])): ?>


        <? elseif (!empty($fieldItem['rows'])): ?>
        <textarea name="<?= $fieldCode ?>"
                  rows="<?= $fieldItem['rows'] ?>"
            <? if (!empty($fieldItem['width'])): ?>
                style="width: <?= $fieldItem['width'] ?>px;"
            <? endif; ?>
        ><?= $fieldItem['value'] ?></textarea>

        <? else: ?>
        <input name="<?= $fieldCode ?>"
               type="text"
               value="<?= $fieldItem['value'] ?>"
            <? if (!empty($fieldItem['width'])): ?>
                style="width: <?= $fieldItem['width'] ?>px;"
            <? endif; ?>
        />
        <? endif; ?>
    </p>
<? endforeach ?>