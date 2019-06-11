<?php

namespace Sprint\Migration\Helpers\UserOptions;

use Sprint\Migration\HelperManager;

trait IblockTrait
{
    private $titles = [];
    private $props = [];
    private $iblock = [];
    private $lastIblockId = 0;

    public function exportElementForm($iblockId)
    {
        /**
         * @compability
         * @deprecated
         */
        if (func_num_args() > 1) {
            $this->throwException(__METHOD__, '$params is no longer supported, see examples');
        }

        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    public function buildElementForm($iblockId, $formData = [])
    {
        /**
         * @compability
         * @deprecated
         */
        if (func_num_args() > 2) {
            $this->throwException(__METHOD__, '$params is no longer supported, see examples');
        }

        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    public function saveElementForm($iblockId, $formData = [])
    {
        /**
         * @compability
         * @deprecated
         */
        if (func_num_args() > 2) {
            $this->throwException(__METHOD__, '$params is no longer supported, see examples');
        }

        $this->initializeIblockVars($iblockId);

        return $this->saveForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    public function exportElementList($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportList([
            'name' => 'tbl_iblock_element_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function buildElementList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        $this->buildList($listData, [
            'name' => 'tbl_iblock_element_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function saveElementList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        $this->saveList($listData, [
            'name' => 'tbl_iblock_element_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function exportSectionForm($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    public function buildSectionForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    public function saveSectionForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    public function exportSectionList($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportList([
            'name' => 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function buildSectionList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildList($listData, [
            'name' => 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function saveSectionList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveList($listData, [
            'name' => 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }


    /**
     * @param $iblockId
     * @return mixed
     * @deprecated
     */
    public function extractElementForm($iblockId)
    {
        $result = $this->exportElementForm($iblockId);

        if (!empty($result)) {
            return $result;
        }

        $this->throwException(__METHOD__, 'iblock form options not found');
    }


    protected function initializeIblockVars($iblockId)
    {
        $helper = HelperManager::getInstance();

        /** @compability */
        if (empty($iblockId)) {
            $this->throwException(__METHOD__, 'empty param $iblockId is no longer supported, see examples');
        }

        if ($this->lastIblockId == $iblockId) {
            return true;
        }

        $iblock = $helper->Iblock()->getIblock([
            'ID' => $iblockId,
        ]);

        if (empty($iblock)) {
            $this->throwException(__METHOD__, 'iblock %d not found', $iblockId);
        }

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
}
