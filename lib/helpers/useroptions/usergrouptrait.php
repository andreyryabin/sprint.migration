<?php

namespace Sprint\Migration\Helpers\UserOptions;

trait UserGroupTrait
{
    public function exportUserGroupList()
    {
        return $this->exportList([
            'name' => 'tbl_user_group',
        ]);
    }

    public function buildUserGroupList($listData = [])
    {
        return $this->buildList($listData, [
            'name' => 'tbl_user_group',
        ]);
    }

    public function saveUserGroupList($listData = [])
    {
        return $this->saveList($listData, [
            'name' => 'tbl_user_group',
        ]);
    }

}
