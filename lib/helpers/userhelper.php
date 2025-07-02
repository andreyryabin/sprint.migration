<?php

namespace Sprint\Migration\Helpers;

use CUser;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

class UserHelper extends Helper
{
    /**
     * @throws HelperException
     */
    public function getUserIdByLogin(string $userLogin): int
    {
        $item = CUser::GetByLogin($userLogin)->Fetch();

        if (!empty($item['ID'])) {
            return (int)$item['ID'];
        }

        throw new HelperException("User with login=\"$userLogin\" not found");
    }

    /**
     * @throws HelperException
     */
    public function getUserLoginById(int $userId): string
    {
        $item = CUser::GetByID($userId)->Fetch();

        if (!empty($item['LOGIN'])) {
            return (string)$item['LOGIN'];
        }

        throw new HelperException("User with id=\"$userId\" not found");
    }
}
