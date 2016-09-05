<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class AdminIblockHelper extends Helper
{

    private $titles = array();
    private $props = array();
    private $iblock = array();

    public function buildElementForm($iblockId, $tabs = array()) {
        $this->initializeVars($iblockId);

        $tabIndex = 0;

        $tt = array();

        foreach ($tabs as $tabTitle => $fields) {

            $tabCode = ($tabIndex == 0) ? 'edit' . ($tabIndex + 1) : '--edit' . ($tabIndex + 1);
            $tt[$tabIndex][] = $tabCode . '--#--' . $tabTitle . '--';

            foreach ($fields as $fieldCode => $fieldTitle) {
                $fieldCode = $this->prepareCode($fieldCode);
                $fieldTitle = $this->prepareTitle($fieldCode, $fieldTitle);
                $tt[$tabIndex][] = '--' . $fieldCode . '--#--' . $fieldTitle . '--';
            }

            $tabIndex++;
        }

        $opts = array();
        foreach ($tt as $fields) {
            $opts[] = implode(',', $fields);
        }

        $opts = implode(';', $opts) . ';--';


        $category = 'form';
        $name = 'form_element_' . $iblockId;
        $value = array(
            'tabs' => $opts
        );

        \CUserOptions::DeleteOptionsByName($category, $name);
        \CUserOptions::SetOption($category, $name, $value, true);
    }

    public function buildElementList($iblockId, $columns = array(), $params = array()) {
        $this->initializeVars($iblockId);

        $params = array_merge(array(
            'page_size' => 20,
            'order' => 'desc',
            'by' => 'id',
        ), $params);

        $opts = array();
        foreach ($columns as $columnCode) {
            $opts[] = $this->prepareCode($columnCode);
        }

        $opts = implode(',', $opts);

        $category = 'list';
        $name = "tbl_iblock_list_" . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId);
        $value = array(
            'columns' => $opts,
            'order' => $params['order'],
            'by' => $params['by'],
            'page_size' => $params['page_size']
        );

        \CUserOptions::DeleteOptionsByName($category, $name);
        \CUserOptions::SetOption($category, $name, $value, true);
    }

    protected function initializeVars($iblockId) {
        $this->iblock = \CIBlock::GetList(array('SORT' => 'ASC'), array('ID' => $iblockId))->Fetch();
        if (!$this->iblock) {
            $this->throwException(__METHOD__, 'Iblock %d not found', $iblockId);
        }

        $dbResult = \CIBlockProperty::GetList(array("sort" => "asc"), array(
            "IBLOCK_ID" => $iblockId,
            "CHECK_PERMISSIONS" => "N"
        ));

        while ($aItem = $dbResult->Fetch()) {
            if (!empty($aItem['CODE'])) {
                $this->titles['PROPERTY_' . $aItem['ID']] = $aItem['NAME'];
                $this->props[$aItem['CODE']] = $aItem;
            }
        }

        $iblockMess = \IncludeModuleLangFile('/bitrix/modules/iblock/admin/iblock_element_edit.php', 'ru', true);

        $iblockMess['IBLOCK_FIELD_ACTIVE_FROM'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_FROM'];
        $iblockMess['IBLOCK_FIELD_ACTIVE_TO'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_TO'];

        foreach ($iblockMess as $code => $value) {
            if (false !== strpos($code, 'IBLOCK_FIELD_')) {
                $fcode = str_replace('IBLOCK_FIELD_', '', $code);
                $this->titles[$fcode] = $value;
            }
        }
    }

    protected function prepareTitle($fieldCode, $fieldTitle = '') {
        $fieldTitle = ($fieldTitle == '*') ? '' : $fieldTitle;

        if (!empty($fieldTitle)) {
            return $fieldTitle;
        }

        if (isset($this->titles[$fieldCode])) {
            return $this->titles[$fieldCode];
        }

        return $fieldCode;
    }

    protected function prepareCode($fieldCode) {
        if (0 === strpos($fieldCode, 'PROPERTY_')) {
            $fieldCode = substr($fieldCode, 9);
            if (isset($this->props[$fieldCode])) {
                $fieldCode = $this->props[$fieldCode]['ID'];
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }
        return $fieldCode;
    }
}