<?php if (isset($sperrors) && is_array($sperrors)): ?>
    <?php foreach ($sperrors as $sperror): ?>
        <div class="sp-block">
            <?= $sperror ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
