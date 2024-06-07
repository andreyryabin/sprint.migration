<?php

namespace Sprint\Migration\Helpers;

use CFile;
use CMedialib;
use CMedialibCollection;
use CMedialibItem;
use CTask;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

/**
 * Class MedialibHelper
 *
 * @package Sprint\Migration\Helpers
 */
class MedialibHelper extends Helper
{
    const TYPE_IMAGE = 'image';

    public function __construct()
    {
        parent::__construct();

        CMedialib::Init();
    }

    public function isEnabled()
    {
        return $this->checkModules(['fileman']);
    }

    public function getTypes()
    {
        return CMedialib::GetTypes();
    }

    /**
     * @param string $code
     *
     * @throws HelperException
     * @return int|void
     */
    public function getTypeIdByCode($code)
    {
        foreach ($this->getTypes() as $type) {
            if ($type['code'] == $code) {
                return (int)$type['id'];
            }
        }
        throw new HelperException('type not found');
    }

    /**
     * @param string|int $typeId
     * @param array      $params
     *
     * @throws HelperException
     * @return array
     */
    public function getCollections($typeId, $params = [])
    {
        $params = array_merge(
            [
                'filter' => [],
            ], $params
        );

        if (!is_numeric($typeId)) {
            $typeId = $this->getTypeIdByCode($typeId);
        }

        $params['filter']['TYPES'] = [$typeId];

        $result = CMedialibCollection::GetList(
            [
                'arFilter' => $params['filter'],
                'arOrder'  => ['ID' => 'asc'],
            ]
        );

        if (isset($params['filter']['NAME'])) {
            //чистим результаты нечеткого поиска
            return $this->filterByKey($result, 'NAME', $params['filter']['NAME']);
        }
        return $result;
    }

    /**
     * @param       $typeId
     * @param int   $parentId
     * @param int   $depth
     * @param array $path
     *
     * @throws HelperException
     * @return array
     */
    public function getCollectionsTree($typeId, $parentId = 0, $depth = 0, $path = [])
    {
        $result = $this->getCollections($typeId, ['filter' => ['PARENT_ID' => $parentId]]);
        foreach ($result as $index => $item) {
            $item['DEPTH_LEVEL'] = $depth;
            $item['DEPTH_NAME'] = str_repeat(' . ', $depth) . $item['NAME'];
            $item['PATH'] = array_merge($path, [$item['NAME']]);
            $item['CHILDS'] = $this->getCollectionsTree($typeId, $item['ID'], $item['DEPTH_LEVEL'] + 1, $item['PATH']);
            $result[$index] = $item;
        }
        return $result;
    }

    public function getCollectionsFlatTree($typeId)
    {
        $tree = $this->getCollectionsTree($typeId);
        $flat = [];
        foreach ($tree as $category) {
            $this->flatTree($category, $flat);
        }
        return $flat;
    }

    /**
     * @param array|int $collectionId
     * @param array     $params
     *
     * @throws HelperException
     * @return array|void
     */
    public function getElements($collectionId, $params = [])
    {
        $sqlhelper = (new SqlHelper());

        $params = array_merge(
            [
                'offset' => 0,
                'limit'  => 0,
                'filter' => [],
            ], $params
        );

        $whereQuery = $this->createWhereQuery($collectionId, $params);
        $limitQuery = $this->createLimitQuery($collectionId, $params);

        $sqlQuery /** @lang Text */ = <<<TAG
SELECT MI.ID, MI.NAME, MI.DESCRIPTION, MI.KEYWORDS, MI.SOURCE_ID, MCI.COLLECTION_ID
        FROM 
            b_medialib_collection_item MCI
        INNER JOIN 
            b_medialib_item MI ON (MI.ID=MCI.ITEM_ID)
        INNER JOIN 
            b_file F ON (F.ID=MI.SOURCE_ID) 
        WHERE {$whereQuery} {$limitQuery} ;
TAG;

        try {
            return $sqlhelper->query($sqlQuery)->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array|int $collectionId
     * @param array     $params
     *
     * @throws HelperException
     * @return int
     */
    public function getElementsCount($collectionId, $params = [])
    {
        $sqlhelper = (new SqlHelper());

        $where = $this->createWhereQuery($collectionId, $params);
        $sqlQuery /** @lang Text */ = <<<TAG
SELECT COUNT(*) CNT
        FROM 
            b_medialib_collection_item MCI
        INNER JOIN 
            b_medialib_item MI ON (MI.ID=MCI.ITEM_ID)
        INNER JOIN 
            b_file F ON (F.ID=MI.SOURCE_ID) 
        WHERE {$where};
TAG;

        try {
            $result = $sqlhelper->query($sqlQuery)->fetch();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
        return (int)$result['CNT'];
    }

    /**
     * @param $typeId
     * @param $fields
     *
     * @throws HelperException
     * @return false|mixed
     */
    public function addCollection($typeId, $fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        if (!is_numeric($typeId)) {
            $typeId = $this->getTypeIdByCode($typeId);
        }

        $fields = array_merge(
            [
                //'ID'          => 0, // ID элемента для обновления, 0 для добавления
                'NAME'        => '',
                'DESCRIPTION' => '',
                'OWNER_ID'    => $GLOBALS['USER']->GetId(),
                'PARENT_ID'   => 0,
                'KEYWORDS'    => '',
                'ACTIVE'      => 'Y',
                'ML_TYPE'     => '',
            ], $fields
        );

        $fields['ML_TYPE'] = $typeId;

        return CMedialibCollection::Edit(['arFields' => $fields]);
    }

    /**
     * @param $typeId
     * @param $fields
     *
     * @throws HelperException
     * @return false|mixed
     */
    public function saveCollection($typeId, $fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $parentId = !empty($fields['PARENT_ID']) ? (int)$fields['PARENT_ID'] : 0;
        $name = (string)$fields['NAME'];

        $collections = $this->getCollections(
            $typeId,
            [
                'filter' => [
                    'NAME'      => $name,
                    'PARENT_ID' => $parentId,
                ],
            ]
        );

        if (!empty($collections[0]['ID'])) {
            return $collections[0]['ID'];
        } else {
            return $this->addCollection(
                $typeId,
                [
                    'NAME'      => $name,
                    'PARENT_ID' => $parentId,
                ]
            );
        }
    }

    /**
     * @param $typeId
     * @param $path
     *
     * @throws HelperException
     * @return int|void
     */
    public function saveCollectionByPath($typeId, $path)
    {
        $parentId = 0;
        foreach ($path as $name) {
            $parentId = $this->saveCollection(
                $typeId,
                [
                    'NAME'      => $name,
                    'PARENT_ID' => $parentId,
                ]
            );
        }
        if ($parentId) {
            return $parentId;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_SAVE_COLLECTION_BY_PATH',
                [
                    '#PATH#' => implode(' - ', $path),
                ]
            )
        );
    }

    /**
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function addElement($fields = [])
    {
        $fields['ID'] = 0;
        return $this->editElement($fields);
    }

    /**
     * @param $id
     * @param $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function updateElement($id, $fields)
    {
        $fields['ID'] = $id;
        return $this->editElement($fields);
    }

    /**
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function saveElement($fields = [])
    {
        $this->checkRequiredKeys($fields, ['NAME', 'FILE', 'COLLECTION_ID']);

        $elements = $this->getElements(
            $fields['COLLECTION_ID'],
            [
                'filter' => ['NAME' => $fields['NAME']],
                'limit'  => 1,
                'offset' => 0,
            ]
        );

        if (!empty($elements[0]['ID'])) {
            return $this->updateElement($elements[0]['ID'], $fields);
        } else {
            return $this->addElement($fields);
        }
    }

    public function deleteElement($id)
    {
        CMedialibItem::Delete($id, false, false);
    }

    public function deleteCollection($id)
    {
        CMedialibCollection::Delete($id, true);
    }

    /**
     * Получает права доступа к медиабиблиотеке для групп
     * возвращает массив вида [$groupId => $letter]
     * при $collectionId = 0 права запрашиваются для всех коллекций
     *
     * D - Доступ закрыт
     * F - Просмотр коллекций
     * R - Создание новых
     * V - Редактирование элементов
     * W - Редактирование элементов и коллекций
     * X - Полный доступ
     *
     * @param int $collectionId
     *
     * @return array
     */
    public function getGroupPermissions($collectionId = 0)
    {
        $collectionTree = CMedialib::GetCollectionTree(['CheckAccessFunk' => '__CanDoAccess']);
        $accessRights = CMedialib::GetAccessPermissionsArray($collectionId, $collectionTree['Collections']);

        $result = [];
        foreach ($accessRights as $groupId => $taskId) {
            $letter = CTask::GetLetter($taskId);
            if (empty($letter)) {
                continue;
            }
            $result[$groupId] = $letter;
        }

        return $result;
    }

    /**
     * Устанавливает права доступа к медиабиблиотеке для групп
     * предыдущие права сбрасываются,
     * принимает массив вида [$groupId => $letter]
     * при $collectionId = 0 права устанавливаются для всех коллекций
     *
     * D - Доступ закрыт
     * F - Просмотр коллекций
     * R - Создание новых
     * V - Редактирование элементов
     * W - Редактирование элементов и коллекций
     * X - Полный доступ
     *
     * @param       $collectionId
     * @param array $permissions
     */
    public function setGroupPermissions($collectionId = 0, $permissions = [])
    {
        $accessRights = [];
        foreach ($permissions as $groupId => $letter) {
            $taskId = CTask::GetIdByLetter($letter, 'fileman', 'medialib');

            if (empty($taskId)) {
                continue;
            }

            $accessRights[$groupId] = $taskId;
        }

        CMedialib::SaveAccessPermissions($collectionId, $accessRights);
    }

    /**
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    private function editElement($fields = [])
    {
        $this->checkRequiredKeys($fields, ['NAME', 'FILE', 'COLLECTION_ID']);

        if (!is_array($fields['FILE'])) {
            $fields['FILE'] = CFile::MakeFileArray($fields['FILE']);
        }

        $result = CMedialibItem::Edit(
            [
                'file'          => $fields['FILE'],
                'path'          => false,
                'arFields'      => [
                    'ID'          => !empty($fields['ID']) ? (int)$fields['ID'] : 0,
                    'NAME'        => !empty($fields['NAME']) ? $fields['NAME'] : '',
                    'DESCRIPTION' => !empty($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '',
                    'KEYWORDS'    => !empty($fields['KEYWORDS']) ? $fields['KEYWORDS'] : '',
                ],
                'arCollections' => [(int)$fields['COLLECTION_ID']],
            ]
        );

        return !empty($result['ID']) ? $result['ID'] : 0;
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function createLimitQuery($collectionId, $params = [])
    {
        if ($params['limit'] > 0) {
            return 'LIMIT ' . (int)$params['offset'] . ',' . (int)$params['limit'];
        }
        return '';
    }

    private function createWhereQuery($collectionId, $params = [])
    {
        $sqlhelper = (new SqlHelper());

        $parts = [];
        if (is_array($collectionId)) {
            $collectionId = array_map('intval', $collectionId);
            $parts[] = 'MCI.COLLECTION_ID IN (' . implode(',', $collectionId) . ')';
        } else {
            $parts[] = 'MCI.COLLECTION_ID = "' . (int)$collectionId . '"';
        }

        if (isset($params['filter']['NAME'])) {
            $parts[] = 'MI.NAME = "' . $sqlhelper->forSql($params['filter']['NAME']) . '"';
        }

        return implode(' AND ', $parts);
    }

    private function flatTree($collection, &$flat)
    {
        $childs = $collection['CHILDS'];
        unset($collection['CHILDS']);

        $flat[] = $collection;
        if (!empty($childs)) {
            foreach ($childs as $subcategory) {
                $this->flatTree($subcategory, $flat);
            }
        }
    }
}
