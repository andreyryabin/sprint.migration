<?php

namespace Sprint\Migration\Helpers\Traits\Hlblock;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

trait HlblockElementTrait
{
    /**
     * @throws HelperException
     */
    public function getElements($hlblockName, array $params = []): array
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            return $dataManager::getList($params)->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getDataManager($hlblockName): DataManager
    {
        try {
            $hlblock = $this->getHlblockIfExists($hlblockName);

            $entity = HighloadBlockTable::compileEntity($hlblock);

            $dataManager = $entity->getDataClass();

            return ($dataManager instanceof DataManager) ? $dataManager : (new $dataManager);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getElementByXmlId($hlblockName, $xmlId): bool|array
    {
        return $this->getElement($hlblockName, ['UF_XML_ID' => $xmlId]);
    }

    /**
     * @throws HelperException
     */
    public function getElement($hlblockName, array $filter): array|false
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
    public function getElementIfExists($hlblockName, array $filter): array
    {
        $element = $this->getElement($hlblockName, $filter);
        if (!empty($element['ID'])) {
            return $element;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_ERR_HLBLOCK_ELEMENT_NOT_FOUND',
                [
                    '#HLBLOCK_ID#' => $hlblockName,
                    '#ELEMENT_ID#' => print_r($filter, true),
                ]
            )
        );
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
     * @throws HelperException
     */
    public function getElementIdIfExists($hlblockName, array $filter): int
    {
        $item = $this->getElementIfExists($hlblockName, $filter);
        return (int)($item['ID'] ?? 0);
    }

    /**
     * @throws HelperException
     */
    public function updateElement($hlblockName, $elementId, $fields): int
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
     * @throws HelperException
     */
    public function addElement($hlblockName, array $fields): int
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

    /**
     * @throws HelperException
     */
    public function deleteElementByXmlId($hlblockName, $xmlId): bool
    {
        if (!empty($xmlId)) {
            $id = $this->getElementIdByXmlId($hlblockName, $xmlId);
            if ($id) {
                return $this->deleteElement($hlblockName, $id);
            }
        }
        return false;
    }

    /**
     * @throws HelperException
     */
    public function getElementIdByXmlId($hlblockName, $xmlId): int
    {
        return $this->getElementId($hlblockName, ['UF_XML_ID' => $xmlId]);
    }

    /**
     * @throws HelperException
     */
    public function deleteElement($hlblockName, $elementId): bool
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
