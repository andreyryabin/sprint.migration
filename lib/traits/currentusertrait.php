<?php

namespace Sprint\Migration\Traits;

trait CurrentUserTrait
{
    public function getCurrentUserLogin(): string
    {
        if (isset($GLOBALS['USER']) && $GLOBALS['USER'] instanceof \CUser) {
            return $GLOBALS['USER']->GetLogin();
        }
        return '';
    }

    public function getCurrentUserId(): string
    {
        if (isset($GLOBALS['USER']) && $GLOBALS['USER'] instanceof \CUser) {
            return $GLOBALS['USER']->GetId();
        }
        return '';
    }
}
