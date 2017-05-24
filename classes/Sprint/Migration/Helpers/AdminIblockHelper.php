<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class AdminIblockHelper extends Helper
{

    private $titles = array();
    private $props = array();
    private $iblock = array();

    public function extractElementForm($iblockId, $params = array()) {
        $this->initializeVars($iblockId);

        $params = array_merge(array(
            'name_prefix' => 'form_element_',
            'category' => 'form',
        ), $params);

        $name = $params['name_prefix'] . $iblockId;
        $option = \CUserOptions::GetOption($params['category'], $name, false, false);

        if (!$option || empty($option['tabs'])) {
            $this->throwException(__METHOD__, 'Iblock form options not found');
        }

        $extractedTabs = array();

        $optionTabs = explode(';', $option['tabs']);
        foreach ($optionTabs as $tabStrings) {
            $extractedFields = array();
            $tabTitle = '';

            $columnString = explode(',', $tabStrings);

            foreach ($columnString as $fieldIndex => $fieldString) {
                if (!strpos($fieldString, '#')) {
                    continue;
                }

                list($fieldCode, $fieldTitle) = explode('#', $fieldString);

                $fieldCode = str_replace('--', '', strval($fieldCode));
                $fieldTitle = str_replace('--', '', strval($fieldTitle));

                $fieldCode = trim($fieldCode, '*');
                $fieldTitle = trim($fieldTitle, '*');

                if ($fieldIndex == 0) {
                    $tabTitle = $fieldTitle;
                } else {
                    $fieldCode = $this->revertCode($fieldCode);
                    $extractedFields[$fieldCode] = $fieldTitle;
                }
            }

            if ($tabTitle) {
                $extractedTabs[$tabTitle] = $extractedFields;
            }

        }

        if (!empty($extractedTabs)) {
            return $extractedTabs;
        }

        $this->throwException(__METHOD__, 'Iblock form options not found');
    }

    public function buildElementForm($iblockId, $tabs = array(), $params = array()) {
        $this->initializeVars($iblockId);

        /** @example *//*
        $tabs = array(
            'Tab1' => array(
                'ACTIVE' => 'Активность',
                'ACTIVE_FROM' => '',
                'ACTIVE_TO' => '',
                'NAME' => 'Название',
                'CODE' => Символьный код',
                'SORT' => '',
            ),
            'Tab2' => array(
                'PREVIEW_TEXT' => '',
                'PROPERTY_LINK' => '',
            )
        );  */

        /** @compability *//*
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

            if ($tabTitle == 'SEO' && empty($fields)){
                $fields = $this->getSeoTab();
            }

            $tabCode = ($tabIndex == 0) ? 'edit' . ($tabIndex + 1) : '--edit' . ($tabIndex + 1);
            $tabVals[$tabIndex][] = $tabCode . '--#--' . $tabTitle . '--';

            foreach ($fields as $fieldKey => $fieldValue) {

                if (is_numeric($fieldKey)) {
                    /** @compability */
                    list($fcode, $ftitle) = explode('|', $fieldValue);
                } else {
                    $fcode = $fieldKey;
                    $ftitle = $fieldValue;
                }

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
        $this->iblock = \CIBlock::GetList(array('SORT' => 'ASC'), array(
            'ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
        ))->Fetch();
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
                $this->props[] = $aItem;
            }
        }

        $iblockMess = \IncludeModuleLangFile('/bitrix/modules/iblock/iblock.php', 'ru', true);

        $this->titles['ACTIVE_FROM'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_FROM'];
        $this->titles['ACTIVE_TO'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_TO'];

        foreach ($iblockMess as $code => $value) {
            if (false !== strpos($code, 'IBLOCK_FIELD_')) {
                $fcode = str_replace('IBLOCK_FIELD_', '', $code);
                $this->titles[$fcode] = $value;
            }
        }
    }

    protected function getSeoTab() {
        $seoMess = \IncludeModuleLangFile('/bitrix/modules/iblock/admin/iblock_element_edit.php', 'ru', true);

        return array(
            'IPROPERTY_TEMPLATES_ELEMENT_META_TITLE' => $seoMess['IBEL_E_SEO_META_TITLE'],
            'IPROPERTY_TEMPLATES_ELEMENT_META_KEYWORDS' => $seoMess['IBEL_E_SEO_META_KEYWORDS'],
            'IPROPERTY_TEMPLATES_ELEMENT_META_DESCRIPTION' => $seoMess['IBEL_E_SEO_META_DESCRIPTION'],
            'IPROPERTY_TEMPLATES_ELEMENT_PAGE_TITLE' => $seoMess['IBEL_E_SEO_ELEMENT_TITLE'],
            'IPROPERTY_TEMPLATES_ELEMENTS_PREVIEW_PICTURE' => $seoMess['IBEL_E_SEO_FOR_ELEMENTS_PREVIEW_PICTURE'],
            'IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_ALT' => $seoMess['IBEL_E_SEO_FILE_ALT'],
            'IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_TITLE' => $seoMess['IBEL_E_SEO_FILE_TITLE'],
            'IPROPERTY_TEMPLATES_ELEMENT_PREVIEW_PICTURE_FILE_NAME' => $seoMess['IBEL_E_SEO_FILE_NAME'],
            'IPROPERTY_TEMPLATES_ELEMENTS_DETAIL_PICTURE' => $seoMess['IBEL_E_SEO_FOR_ELEMENTS_DETAIL_PICTURE'],
            'IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_ALT' => $seoMess['IBEL_E_SEO_FILE_ALT'],
            'IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_TITLE' => $seoMess['IBEL_E_SEO_FILE_TITLE'],
            'IPROPERTY_TEMPLATES_ELEMENT_DETAIL_PICTURE_FILE_NAME' => $seoMess['IBEL_E_SEO_FILE_NAME'],
            'SEO_ADDITIONAL' => $seoMess['IBLOCK_EL_TAB_MO'],
            'TAGS' => '',
        );
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
            foreach ($this->props as $prop){
                if ($prop['CODE'] == $fieldCode){
                    $fieldCode = $prop['ID'];
                    break;
                }
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }
        return $fieldCode;
    }

    protected function revertCode($fieldCode) {
        if (0 === strpos($fieldCode, 'PROPERTY_')) {
            $fieldCode = substr($fieldCode, 9);
            foreach ($this->props as $prop){
                if ($prop['ID'] == $fieldCode){
                    $fieldCode = $prop['CODE'];
                    break;
                }
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }
        return $fieldCode;
    }
}
