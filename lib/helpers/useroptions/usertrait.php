<?php

namespace Sprint\Migration\Helpers\UserOptions;

trait UserTrait
{
    public function exportUserList()
    {
        return $this->exportList([
            'name' => 'tbl_user',
        ]);
    }

    public function buildUserList($listData = [])
    {
        return $this->buildList($listData, [
            'name' => 'tbl_user',
        ]);
    }

    public function saveUserList($listData = [])
    {
        return $this->saveList($listData, [
            'name' => 'tbl_user',
        ]);
    }

    public function exportUserForm()
    {
        return $this->exportForm([
            'name' => 'user_edit',
        ]);
    }

    public function buildUserForm($formData = [])
    {
        $this->buildForm($formData, [
            'name' => 'user_edit',
        ]);
    }

    public function saveUserForm($formData = [])
    {
        $this->saveForm($formData, [
            'name' => 'user_edit',
        ]);
    }
}
