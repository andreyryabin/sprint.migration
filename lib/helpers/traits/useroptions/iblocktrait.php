<?php

namespace Sprint\Migration\Helpers\Traits\UserOptions;

use CIBlock;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;
use Sprint\Migration\Traits\HelperManagerTrait;

trait IblockTrait
{
    use HelperManagerTrait;

    private $titles       = [];
    private $props        = [];
    private $iblock       = [];
    private $lastIblockId = 0;

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportElementForm($iblockId)
    {
        /**
         * @compability
         * @deprecated
         */
        if (func_num_args() > 1) {
            throw new HelperException('$params is no longer supported, see examples');
        }

        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $formData
     *
     * @throws HelperException
     * @return mixed
     */
    public function buildElementForm($iblockId, $formData = [])
    {
        /**
         * @compability
         * @deprecated
         */
        if (func_num_args() > 2) {
            throw new HelperException('$params is no longer supported, see examples');
        }

        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $formData
     *
     * @throws HelperException
     * @return mixed
     */
    public function saveElementForm($iblockId, $formData = [])
    {
        /**
         * @compability
         * @deprecated
         */
        if (func_num_args() > 2) {
            throw new HelperException('$params is no longer supported, see examples');
        }

        $this->initializeIblockVars($iblockId);

        return $this->saveForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $params
     *
     * @throws HelperException
     * @return mixed
     */
    public function saveElementGrid($iblockId, $params = [])
    {
        return $this->saveGrid($this->getElementGridId($iblockId), $params);
    }

    /**
     * @param       $iblockId
     * @param array $params
     *
     * @throws HelperException
     * @return mixed
     */
    public function saveSectionGrid($iblockId, $params = [])
    {
        return $this->saveGrid($this->getSectionGridId($iblockId), $params);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return string
     */
    public function getElementGridId($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        if (CIBlock::GetAdminListMode($iblockId) == 'S') {
            $prefix = defined('CATALOG_PRODUCT') ? 'tbl_product_admin_' : 'tbl_iblock_element_';
        } else {
            $prefix = defined('CATALOG_PRODUCT') ? 'tbl_product_list_' : 'tbl_iblock_list_';
        }

        //md5 тут выбран сознательно, так сохраняется настройка в битриксе
        return $prefix . md5($this->iblock['IBLOCK_TYPE_ID'] . '.' . $iblockId);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return string
     */
    public function getSectionGridId($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        //md5 тут выбран сознательно, так сохраняется настройка в битриксе
        return 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . '.' . $iblockId);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportElementList($iblockId)
    {
        return $this->exportList([
            'name' => $this->getElementGridId($iblockId),
        ]);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportElementGrid($iblockId)
    {
        return $this->exportGrid($this->getElementGridId($iblockId));
    }
    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportSectionGrid($iblockId)
    {
        return $this->exportGrid($this->getSectionGridId($iblockId));
    }

    /**
     * @param       $iblockId
     * @param array $listData
     *
     * @throws HelperException
     */
    public function buildElementList($iblockId, $listData = [])
    {
        $this->buildList($listData, [
            'name' => $this->getElementGridId($iblockId),
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $listData
     *
     * @throws HelperException
     */
    public function saveElementList($iblockId, $listData = [])
    {
        $this->saveList($listData, [
            'name' => $this->getElementGridId($iblockId),
        ]);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportSectionForm($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $formData
     *
     * @throws HelperException
     * @return mixed
     */
    public function buildSectionForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $formData
     *
     * @throws HelperException
     * @return mixed
     */
    public function saveSectionForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportSectionList($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportList([
            'name' => $this->getSectionGridId($iblockId),
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $listData
     *
     * @throws HelperException
     * @return mixed
     */
    public function buildSectionList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildList($listData, [
            'name' => $this->getSectionGridId($iblockId),
        ]);
    }

    /**
     * @param       $iblockId
     * @param array $listData
     *
     * @throws HelperException
     * @return mixed
     */
    public function saveSectionList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveList($listData, [
            'name' => $this->getSectionGridId($iblockId),
        ]);
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return array|void
     * @deprecated
     */
    public function extractElementForm($iblockId)
    {
        $result = $this->exportElementForm($iblockId);

        if (!empty($result)) {
            return $result;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_FORM_OPTIONS_NOT_FOUND'
            )
        );
    }

    /**
     * @param $iblockId
     *
     * @throws HelperException
     * @return bool
     */
    protected function initializeIblockVars($iblockId)
    {
        $helper = $this->getHelperManager();

        /** @compability */
        if (empty($iblockId)) {
            throw new HelperException('empty param $iblockId is no longer supported, see examples');
        }

        if ($this->lastIblockId == $iblockId) {
            return true;
        }

        $iblock = $helper->Iblock()->getIblockIfExists($iblockId);

        $this->lastIblockId = $iblockId;
        $this->iblock = $iblock;
        $this->props = [];
        $this->titles = [];

        $props = $helper->Iblock()->getProperties($iblockId);
        foreach ($props as $prop) {
            if (!empty($prop['CODE'])) {
                $this->titles['PROPERTY_' . $prop['ID']] = $prop['NAME'];
                $this->props[] = $prop;
            }
        }

        $iblockMess = IncludeModuleLangFile('/bitrix/modules/iblock/iblock.php', 'ru', true);

        $this->titles['ACTIVE_FROM'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_FROM'];
        $this->titles['ACTIVE_TO'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_TO'];

        foreach ($iblockMess as $code => $value) {
            if (false !== strpos($code, 'IBLOCK_FIELD_')) {
                $fcode = str_replace('IBLOCK_FIELD_', '', $code);
                $this->titles[$fcode] = $value;
            }
        }

        return true;
    }

    protected function prepareTitle($fieldCode, $fieldTitle = '')
    {
        if (!empty($fieldTitle)) {
            return $fieldTitle;
        }

        if (isset($this->titles[$fieldCode])) {
            return $this->titles[$fieldCode];
        }

        return $fieldCode;
    }

    protected function transformCode($fieldCode)
    {
        if (0 === strpos($fieldCode, 'PROPERTY_')) {
            $fieldCode = substr($fieldCode, 9);
            foreach ($this->props as $prop) {
                if ($prop['CODE'] == $fieldCode) {
                    $fieldCode = $prop['ID'];
                    break;
                }
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }
        return $fieldCode;
    }

    protected function revertCode($fieldCode)
    {
        if (0 === strpos($fieldCode, 'PROPERTY_')) {
            $fieldCode = substr($fieldCode, 9);
            foreach ($this->props as $prop) {
                if ($prop['ID'] == $fieldCode) {
                    $fieldCode = $prop['CODE'];
                    break;
                }
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }
        return $fieldCode;
    }

    protected function revertCodesFromColumns($columns)
    {
        if (!is_array($columns)) {
            $columns = explode(',', $columns);
            foreach ($columns as $index => $columnCode) {
                $columns[$index] = $this->revertCode($columnCode);
            }
            return array_values($columns);
        }
        return $columns;
    }

    protected function transformCodesToColumns($columns)
    {
        if (is_array($columns)) {
            foreach ($columns as $index => $columnCode) {
                $columns[$index] = $this->transformCode($columnCode);
            }
            return implode(',', $columns);
        }
        return $columns;
    }

    protected function transformCustomNames($customNames)
    {
        $result = [];
        if (is_array($customNames)) {
            foreach ($customNames as $code => $title) {
                $result[$this->transformCode($code)] = $title;
            }
        }
        return $result;
    }

    protected function revertCustomNames($customNames)
    {
        $result = [];
        if (is_array($customNames)) {
            foreach ($customNames as $code => $title) {
                $result[$this->revertCode($code)] = $title;
            }
        }
        return $result;
    }
}
