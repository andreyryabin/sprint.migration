<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\HelperManager;

class StorageTable extends AbstractTable
{
    protected string $category;

    /**
     * @param string $tableName
     * @param string $category
     *
     * @throws MigrationException
     */
    public function __construct(string $tableName, string $category)
    {
        parent::__construct($tableName);

        $this->category = $category;

        if (empty($category)) {
            throw new MigrationException('Need storage category');
        }
    }

    /**
     * @throws MigrationException
     */
    public function saveData(string $name, $value = '')
    {
        if (empty($name)) {
            throw new MigrationException('Need name for saved data');
        }

        $row = $this->getOnce([
            'category' => $this->category,
            'name'     => $name,
        ]);

        if ($row) {
            $this->update($row['id'], [
                'data' => $value,
            ]);
        } else {
            $this->add([
                'category' => $this->category,
                'name'     => $name,
                'data'     => $value,
            ]);
        }
    }

    /**
     * @throws MigrationException
     */
    public function getSavedData(string $name, $default = '')
    {
        if (empty($name)) {
            throw new MigrationException('Need name for saved data');
        }

        $row = $this->getOnce([
            'category' => $this->category,
            'name'     => $name,
        ]);

        return ($row) ? $row['data'] : $default;
    }

    /**
     * @throws MigrationException
     */
    public function deleteSavedData(string $name = '')
    {
        if ($name) {
            $rows = $this->getAll([
                'category' => $this->category,
                'name'     => $name,
            ]);
        } else {
            $rows = $this->getAll([
                'category' => $this->category,
            ]);
        }

        foreach ($rows as $row) {
            $this->delete($row['id']);
        }
    }

    public function getMap(): array
    {
        return [
            new IntegerField('id', [
                'primary'      => true,
                'autocomplete' => true,
            ]),
            new StringField('category', [
                'size' => 255,
            ]),
            new StringField('name', [
                'size' => 255,
            ]),
            new TextField('data', [
                'long'       => true,
                'serialized' => true,
            ]),
        ];
    }


    protected function createDbTable()
    {
        parent::createDbTable();

        $helper = HelperManager::getInstance();

        $helper->Sql()->addIndexIfNotExists(
            $this->getTableName(),
            'category',
            ['category']
        );

        $helper->Sql()->addIndexIfNotExists(
            $this->getTableName(),
            'fullname',
            ['category', 'name']
        );
    }
}



