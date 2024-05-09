<?php
/** @var $results array */
?>
<div class="sp-db-wrap">
    <table class="sp-db-table">
        <?php foreach ($results as $item) { ?>
            <tr>
                <td class="sp-db-col-type"><?= $item['title'] ?></td>
                <td class="sp-db-col-value">
                    <div class="lamp-<?= $item['state'] ?>" title="<?= $item['text'] ?>"></div>
                </td>
                <td class="sp-db-col-text"><?= $item['text'] ?></td>
                <td>
                    <?php foreach ($item['buttons'] as $button) { ?>
                        <a href="<?= $button['url'] ?>" class="adm-btn" title="<?= $button['title'] ?>">
                            <?= $button['text'] ?>
                        </a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
