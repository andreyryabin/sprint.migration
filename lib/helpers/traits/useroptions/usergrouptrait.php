<?php

namespace Sprint\Migration\Helpers\Traits\UserOptions;

trait UserGroupTrait
{

    public function getUserGroupGridId()
    {
        return 'tbl_user_group';
    }

    public function exportUserGroupList()
    {
        return $this->exportList([
            'name' => $this->getUserGroupGridId(),
        ]);
    }

    public function buildUserGroupList($params = [])
    {
        return $this->buildList($params, [
            'name' => $this->getUserGroupGridId(),
        ]);
    }

    public function saveUserGroupList($params = [])
    {
        return $this->saveList($params, [
            'name' => $this->getUserGroupGridId(),
        ]);
    }

    public function saveUserGroupGrid($params = [])
    {
        return $this->saveGrid($this->getUserGroupGridId(), $params);
    }

}
