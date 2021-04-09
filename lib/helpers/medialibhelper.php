<?php

namespace Sprint\Migration\Helpers;

use Adv\McdonaldsCmsBundle\ExternalAdmin\Fields\ProductCategoryFields;
use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use CFile;
use CMedialib;
use CMedialibCollection;
use CMedialibItem;
use CTask;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

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
        $this->throwException(__METHOD__, 'type not found');
    }

    /**
     * @param mixed $typeId
     * @param array $path
     *
     * @throws HelperException
     * @return int|void
     */
    public function getCollectionIdByNamePath($typeId, $path = [])
    {
        if (!is_numeric($typeId)) {
            $typeId = $this->getTypeIdByCode($typeId);
        }

        $parentId = 0;
        foreach ($path as $name) {
            $parentId = $this->getCollectionId(
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

        $this->throwException(__METHOD__, 'collection not found');
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

    protected function flatTree($collection, &$flat)
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

    /**
     * @param array|int $collectionId
     * @param array     $params
     *
     * @throws HelperException
     * @return array|void
     */
    public function getElements($collectionId, $params = [])
    {
        $connection = Application::getConnection();

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

        $result = [];
        try {
            $result = $connection->query($sqlQuery)->fetchAll();
        } catch (SqlQueryException $e) {
            $this->throwException(__METHOD__, $e->getMessage());
        }

        foreach ($result as $index => $item) {
            $item['FILE'] = CFile::GetFileArray($item['SOURCE_ID']);
            $result[$index] = $item;
        }

        if (isset($params['filter']['NAME'])) {
            //чистим результаты нечеткого поиска
            $result = $this->filterByKey($result, 'NAME', $params['filter']['NAME']);
        }

        return $result;
    }

    /**
     * @param array|int $collectionId
     * @param array     $params
     *
     * @throws SqlQueryException
     * @return int
     */
    public function getElementsCount($collectionId, $params = [])
    {
        $connection = Application::getConnection();

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

        $result = $connection->query($sqlQuery)->fetch();
        return (int)$result['CNT'];
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function createLimitQuery($collectionId, $params = [])
    {
        if ($params['limit'] > 0) {
            return 'LIMIT ' . (int)$params['offset'] . ',' . (int)$params['limit'];
        }
        return '';
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function createWhereQuery($collectionId, $params = [])
    {
        if (is_array($collectionId)) {
            $collectionId = array_map('intval', $collectionId);
            return 'MCI.COLLECTION_ID IN (' . implode(',', $collectionId) . ')';
        }
        return 'MCI.COLLECTION_ID = "' . (int)$collectionId . '"';
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
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME']);

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
     * @param $collectionId
     * @param $name
     *
     * @throws HelperException
     * @return false|mixed
     */
    public function getElementByName($collectionId, $name)
    {
        $elements = $this->getElements(
            $collectionId,
            [
                'filter' => ['NAME' => $name],
                'limit'  => 1,
                'offset' => 0,
            ]
        );
        return !empty($elements) ? $elements[0] : false;
    }

    /**
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function saveElement($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME', 'FILE', 'COLLECTION_ID']);

        $exists = $this->getElementByName($fields['COLLECTION_ID'], $fields['NAME']);

        if (empty($exists)) {
            return $this->addElement($fields);
        } else {
            return $this->updateElement($exists['ID'], $fields);
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
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    protected function editElement($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME', 'FILE', 'COLLECTION_ID']);

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
     * предыдущие права сбрасываются
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
}
