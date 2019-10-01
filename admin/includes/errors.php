<?

?><? if (isset($sperrors) && is_array($sperrors)): ?>
    <? foreach ($sperrors as $sperror): ?>
        <div class="sp-block">
            <?= $sperror ?>
        </div>
    <? endforeach; ?>
<? endif; ?>