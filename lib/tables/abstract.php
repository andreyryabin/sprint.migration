<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Entity;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Module;

abstract class AbstractTable
{
    protected int  $tableVersion = 1;
    private string $tableName;
    private string $entityName;
    private Entity $entity;

    /**
     * @throws MigrationException
     */
    public function __construct(string $tableName)
    {
        try {
            $this->entityName = $this->makeEntityName($tableName);
            $this->tableName = $tableName;

            $className = __NAMESPACE__ . '\\' . $this->entityName;

            if (Entity::has($className)) {
                $this->entity = Entity::getInstance($className);
            } else {
                $this->entity = Entity::compileEntity(
                    $this->entityName,
                    $this->getMap(),
                    [
                        'table_name' => $tableName,
                        'namespace'  => __NAMESPACE__,
                    ]
                );
            }
        } catch (Exception $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }

        $this->createTable();
    }

    abstract public function getMap(): array;

    /**
     * @throws MigrationException
     */
    public function createTable(): void
    {
        $version = (int)Module::getDbOption($this->entityName);
        if ($version !== $this->tableVersion) {
            try {
                $this->createDbTable();
            } catch (HelperException $e) {
                throw new MigrationException($e->getMessage(), $e->getCode(), $e);
            }

            Module::setDbOption($this->entityName, $this->tableVersion);
        }
    }

    /**
     * @throws HelperException
     */
    protected function createDbTable(): void
    {
        $helper = HelperManager::getInstance();

        $tableName = $this->getTableName();

        if ($helper->Sql()->hasTable($tableName)) {
            $helper->Sql()->restoreColumns($this->entity);
        } else {
            $helper->Sql()->createTable($this->entity);
        }
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @throws MigrationException
     */
    public function dropTable(): void
    {
        try {
            $this->dropDbTable();
        } catch (HelperException $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }

        Module::removeDbOption($this->entityName);
    }

    /**
     * @throws HelperException
     */
    protected function dropDbTable(): void
    {
        $helper = HelperManager::getInstance();

        $tableName = $this->getTableName();

        if ($helper->Sql()->hasTable($tableName)) {
            $helper->Sql()->dropTable($tableName);
        }
    }

    /**
     * @throws MigrationException
     */
    protected function getOnce(array $filter = []): ?array
    {
        try {
            $res = $this->getDataManager()::getList(['filter' => $filter, 'limit' => 1]);

            $res->addFetchDataModifier(fn($item) => $this->fetchDataModifier($item));

            $row = $res->fetch();

            return is_array($row) ? $row : null;
        } catch (Exception $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MigrationException
     */
    protected function getAll(array $filter = []): array
    {
        try {
            $res = $this->getDataManager()::getList(['filter' => $filter]);

            $res->addFetchDataModifier(fn($item) => $this->fetchDataModifier($item));

            return $res->fetchAll();
        } catch (Exception $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function fetchDataModifier(array $item): array
    {
        $result = [];
        foreach ($item as $key => $val) {
            $result[strtolower($key)] = $val;
        }
        return $result;
    }

    /**
     * @throws MigrationException
     */
    protected function add(array $data): AddResult
    {
        try {
            return $this->getDataManager()::add($data);
        } catch (Exception $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MigrationException
     */
    protected function update($primary, array $data): UpdateResult
    {
        try {
            return $this->getDataManager()::update($primary, $data);
        } catch (Exception $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MigrationException
     */
    protected function delete($primary): DeleteResult
    {
        try {
            return $this->getDataManager()::delete($primary);
        } catch (Exception $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return DataManager|string
     */
    private function getDataManager(): DataManager|string
    {
        return $this->entity->getDataClass();
    }

    private function makeEntityName(string $tableName): string
    {
        $arr = explode('_', $tableName);

        $arr = array_map('ucfirst', $arr);

        return implode('', $arr);
    }
}



