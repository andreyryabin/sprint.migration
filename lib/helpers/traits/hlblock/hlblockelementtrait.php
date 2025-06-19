<?php

namespace Sprint\Migration\Helpers\Traits\Hlblock;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
use Exception;
use Sprint\Migration\Exceptions\HelperException;

trait HlblockElementTrait
{
    /**
     * @param       $hlblockName
     * @param array $params
     *
     * @throws HelperException
     * @return array|void
     */
    public function getElements($hlblockName, $params = [])
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            return $dataManager::getList($params)->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $hlblockName
     *
     * @throws HelperException
     * @return DataManager|void
     */
    public function getDataManager($hlblockName)
    {
        try {
            $hlblock = $this->getHlblockIfExists($hlblockName);
            $entity = HighloadBlockTable::compileEntity($hlblock);
            return $entity->getDataClass();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $hlblockName
     * @param string $xmlId
     *
     * @throws HelperException
     * @return array|void
     */
    public function getElementByXmlId($hlblockName, $xmlId)
    {
        return $this->getElement($hlblockName, ['UF_XML_ID' => $xmlId]);
    }

    /**
     * @throws HelperException
     */
    public function getElement($hlblockName, array $filter)
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            return $dataManager::getList([
                'filter' => $filter,
                'offset' => 0,
                'limit'  => 1,
            ])->fetch();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getElementsCount($hlblockName, array $filter = [])
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            $item = $dataManager::getList(
                [
                    'select'  => ['CNT'],
                    'filter'  => $filter,
                    'runtime' => [
                        new ExpressionField('CNT', 'COUNT(*)'),
                    ],
                ]
            )->fetch();

            return ($item) ? $item['CNT'] : 0;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function saveElementByXmlId(string|int $hlblockName, array $fields): int
    {
        return $this->saveElementWithEqualKeys($hlblockName, $fields, ['UF_XML_ID']);
    }

    /**
     * @throws HelperException
     */
    public function saveElementWithEqualKeys(string|int $hlblockName, array $fields, array $equalKeys): int
    {
        $filter = $this->makeEqualFilter($fields, $equalKeys);

        $elementId = $this->getElementId($hlblockName, $filter);

        if ($elementId) {
            return $this->updateElement($hlblockName, $elementId, $fields);
        }

        return $this->addElement($hlblockName, $fields);
    }

    /**
     * @throws HelperException
     */
    public function getElementId($hlblockName, array $filter): int
    {
        $item = $this->getElement($hlblockName, $filter);
        return (int)($item['ID'] ?? 0);
    }

    /**
     * @param $hlblockName
     * @param $elementId
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function updateElement($hlblockName, $elementId, $fields)
    {
        $dataManager = $this->getDataManager($hlblockName);

        try {
            $result = $dataManager::update($elementId, $fields);

            if ($result->isSuccess()) {
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $hlblockName
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function addElement($hlblockName, $fields)
    {
        $dataManager = $this->getDataManager($hlblockName);

        try {
            $result = $dataManager::add($fields);

            if ($result->isSuccess()) {
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deleteElementByXmlId($hlblockName, $xmlId)
    {
        if (!empty($xmlId)) {
            $id = $this->getElementIdByXmlId($hlblockName, $xmlId);
            if ($id) {
                return $this->deleteElement($hlblockName, $id);
            }
        }
        return false;
    }

    public function getElementIdByXmlId($hlblockName, $xmlId): int
    {
        return $this->getElementId($hlblockName, ['UF_XML_ID' => $xmlId]);
    }

    public function deleteElement($hlblockName, $elementId)
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            $result = $dataManager::delete($elementId);

            if ($result->isSuccess()) {
                return true;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
