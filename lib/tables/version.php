<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\CurrentUserTrait;

class VersionTable extends AbstractTable
{
    use CurrentUserTrait;

    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @throws MigrationException
     */
    public function getRecords(): array
    {
        return $this->getAll();
    }

    /**
     * @throws MigrationException
     */
    public function getRecord(string $versionName): array
    {
        return $this->getOnce(['version' => $versionName]) ?: [];
    }

    /**
     * @throws MigrationException
     */
    public function addRecord(array $record)
    {
        $record['meta'] = [
            'created_by' => $this->getCurrentUserLogin(),
            'created_at' => date(VersionTable::DATE_FORMAT),
        ];

        $this->add($record);
    }

    /**
     * @throws MigrationException
     */
    public function removeRecord(array $record)
    {
        $row = $this->getOnce(['version' => $record['version']]);
        if ($row) {
            $this->delete($row['id']);
        }
    }

    /**
     * @throws MigrationException
     */
    public function updateTag(string $versionName, string $tag = '')
    {
        $row = $this->getOnce(['version' => $versionName]);
        if ($row) {
            $this->update($row['id'], ['tag' => $tag]);
        }
    }

    public function getMap(): array
    {
        return [
            new IntegerField('id', [
                'primary'      => true,
                'autocomplete' => true,
            ]),
            new StringField('version', [
                'size'   => 255,
                'unique' => true,
            ]),
            new StringField('hash', [
                'size' => 255,
            ]),
            new StringField('tag', [
                'size' => 50,
            ]),
            new TextField('meta', [
                'long'       => true,
                'serialized' => true,
            ]),
        ];
    }
}
