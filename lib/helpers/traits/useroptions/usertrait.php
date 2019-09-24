<?php

namespace Sprint\Migration\Helpers\Traits\UserOptions;

trait UserTrait
{
    public function getUserGridId()
    {
        return 'tbl_user';
    }

    public function exportUserList()
    {
        return $this->exportList([
            'name' => $this->getUserGridId(),
        ]);
    }

    public function buildUserList($listData = [])
    {
        return $this->buildList($listData, [
            'name' => $this->getUserGridId(),
        ]);
    }

    public function saveUserList($listData = [])
    {
        return $this->saveList($listData, [
            'name' => $this->getUserGridId(),
        ]);
    }

    public function saveUserGrid($params = [])
    {
        return $this->saveGrid($this->getUserGridId(), $params);
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
