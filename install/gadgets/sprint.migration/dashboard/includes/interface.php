<?php
/** @var $results array */
?>
<div class="sp-db-wrap">
    <table class="sp-db-table">
        <?php foreach ($results as $item) { ?>
            <tr>
                <td class="sp-db-col-type"><?= htmlspecialcharsbx($item['title']) ?></td>
                <td class="sp-db-col-value">
                    <div class="lamp-<?= htmlspecialcharsbx($item['state']) ?>" title="<?= htmlspecialcharsbx($item['text']) ?>"></div>
                </td>
                <td class="sp-db-col-text"><?= htmlspecialcharsbx($item['text']) ?></td>
                <td>
                    <?php foreach ($item['buttons'] as $button) { ?>
                        <a href="<?= htmlspecialcharsbx($button['url']) ?>" class="adm-btn" title="<?= htmlspecialcharsbx($button['title']) ?>">
                            <?= htmlspecialcharsbx($button['text']) ?>
                        </a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
