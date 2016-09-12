<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class AdminIblockHelper extends Helper
{

    private $titles = array();
    private $props = array();
    private $iblock = array();

    public function buildElementForm($iblockId, $tabs = array(), $params = array()) {
        $this->initializeVars($iblockId);

        /** @example *//*
        $tabs = array(
            'Tab1' => array(
                'ACTIVE|Активность',
                'ACTIVE_FROM',
                'ACTIVE_TO',
                'NAME|Название',
                'CODE|Символьный код',
                'SORT',
            ),
            'Tab2' => array(
                'PREVIEW_TEXT',
                'PROPERTY_LINK',
            )
        );  */

        $tabIndex = 0;

        $tabVals = array();

        foreach ($tabs as $tabTitle => $fields) {

            $tabCode = ($tabIndex == 0) ? 'edit' . ($tabIndex + 1) : '--edit' . ($tabIndex + 1);
            $tabVals[$tabIndex][] = $tabCode . '--#--' . $tabTitle . '--';

            foreach ($fields as $val) {
                list($fcode, $ftitle) = explode('|', $val);

                $fcode = $this->prepareCode($fcode);
                $ftitle = $this->prepareTitle($fcode, $ftitle);

                $tabVals[$tabIndex][] = '--' . $fcode . '--#--' . $ftitle . '--';
            }

            $tabIndex++;
        }

        $opts = array();
        foreach ($tabVals as $fields) {
            $opts[] = implode(',', $fields);
        }

        $opts = implode(';', $opts) . ';--';

        $params = array_merge(array(
            'name_prefix' => 'form_element_',
            'category' => 'form',
        ), $params);
        
        $name = $params['name_prefix'] . $iblockId;
        $value = array(
            'tabs' => $opts
        );

        \CUserOptions::DeleteOptionsByName($params['category'], $name);
        \CUserOptions::SetOption($params['category'], $name, $value, true);
    }

    public function buildElementList($iblockId, $columns = array(), $params = array()) {
        $this->initializeVars($iblockId);

        /** @example *//*
        $columns = array(
            'NAME',
            'SORT',
            'ID',
            'PROPERTY_LINK',
        );  */

        $opts = array();
        foreach ($columns as $columnCode) {
            $opts[] = $this->prepareCode($columnCode);
        }
        $opts = implode(',', $opts);

        $params = array_merge(array(
            'name_prefix' => 'tbl_iblock_element_',
            'category' => 'list',
            'page_size' => 20,
            'order' => 'desc',
            'by' => 'id',
        ), $params);
        
        $name = $params['name_prefix'] . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId);
        $value = array(
            'columns' => $opts,
            'order' => $params['order'],
            'by' => $params['by'],
            'page_size' => $params['page_size']
        );

        \CUserOptions::DeleteOptionsByName($params['category'], $name);
        \CUserOptions::SetOption($params['category'], $name, $value, true);
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

        $iblockMess = \IncludeModuleLangFile('/bitrix/modules/iblock/iblock.php', 'ru', true);

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