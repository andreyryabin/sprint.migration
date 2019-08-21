<?php

namespace Sprint\Migration;

use Sprint\Migration\Tables\StorageTable;

class StorageManager extends StorageTable
{

    /**
     * @param $category
     * @param $name
     * @param string $value
     */
    public function saveData($category, $name, $value = '')
    {
        $category = $this->forSql($category);
        $name = $this->forSql($name);

        if (!empty($category) && !empty($name)) {
            if (!empty($value)) {
                $value = $this->forSql(serialize($value));
                $this->query('INSERT INTO `#TABLE1#` (`category`,`name`, `data`) VALUES ("%s", "%s", "%s") 
                    ON DUPLICATE KEY UPDATE data = "%s"',
                    $category,
                    $name,
                    $value,
                    $value
                );
            }
        }
    }

    /**
     * @param $category
     * @param $name
     * @param string $default
     * @return mixed|string
     */
    public function getSavedData($category, $name, $default = '')
    {
        $category = $this->forSql($category);
        $name = $this->forSql($name);

        if (!empty($category) && !empty($name)) {
            $value = $this->query('SELECT name, data FROM #TABLE1# WHERE `category` = "%s" AND `name` = "%s"',
                $category,
                $name
            )->Fetch();
            if ($value && $value['data']) {
                return unserialize($value['data']);
            }
        }
        return $default;
    }

    /**
     * @param $category
     * @param bool $name
     */
    public function deleteSavedData($category, $name = false)
    {
        $category = $this->forSql($category);

        if ($category && $name) {
            $name = $this->forSql($name);
            $this->query('DELETE FROM `#TABLE1#` WHERE `category` = "%s" AND `name` = "%s"',
                $category,
                $name
            );
        } elseif ($category) {
            $this->query('DELETE FROM `#TABLE1#` WHERE `category` = "%s"',
                $category
            );
        }
    }

}



