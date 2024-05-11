<?php

namespace Sprint\Migration\Helpers\Traits\UserOptions;

use Sprint\Migration\Exceptions\HelperException;

trait UserTrait
{
    public function getUserGridId()
    {
        return 'tbl_user';
    }

    /**
     * @throws HelperException
     */
    public function exportUserList()
    {
        return $this->exportList([
            'name' => $this->getUserGridId(),
        ]);
    }

    /**
     * @throws HelperException
     */
    public function buildUserList($listData = [])
    {
        return $this->buildList($listData, [
            'name' => $this->getUserGridId(),
        ]);
    }

    /**
     * @throws HelperException
     */
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

    /**
     * @throws HelperException
     */
    public function exportUserForm()
    {
        return $this->exportForm([
            'name' => 'user_edit',
        ]);
    }

    /**
     * @throws HelperException
     */
    public function buildUserForm($formData = [])
    {
        $this->buildForm($formData, [
            'name' => 'user_edit',
        ]);
    }

    /**
     * @throws HelperException
     */
    public function saveUserForm($formData = [])
    {
        $this->saveForm($formData, [
            'name' => 'user_edit',
        ]);
    }
}
