<?php if (isset($sperrors) && is_array($sperrors)): ?>
    <?php foreach ($sperrors as $sperror) { ?>
        <div class="sp-col">
            <?= $sperror ?>
        </div>
    <?php } ?>
<?php endif; ?>
