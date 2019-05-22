<?php

namespace Sprint\Migration\Helpers;

use CIBlock;
use CIBlockProperty;
use CUserOptions;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use function IncludeModuleLangFile;

class UserOptionsHelper extends Helper
{

    private $titles = [];
    private $props = [];
    private $iblock = [];

    public function __construct()
    {
        $this->checkModules(['iblock']);
    }


    /**
     * Извлекает настройки формы инфоблока в массив, бросает исключение если их не существует
     * @param $iblockId
     * @param array $params
     * @throws HelperException
     * @return array
     */
    public function extractElementForm($iblockId, $params = [])
    {
        $result = $this->exportElementForm($iblockId, $params);

        if (!empty($result)) {
            return $result;
        }

        $this->throwException(__METHOD__, 'Iblock form options not found');
    }

    /**
     * Извлекает настройки формы инфоблока в массив
     * @param $iblockId
     * @param array $params
     * @return array
     */
    public function exportElementForm($iblockId, $params = [])
    {
        $this->initializeVars($iblockId);

        $params = array_merge([
            'name_prefix' => 'form_element_',
            'category' => 'form',
        ], $params);

        $params['name'] = $params['name_prefix'] . $iblockId;


        $option = CUserOptions::GetOption($params['category'], $params['name'], false, false);

        $extractedTabs = [];

        if (!$option || empty($option['tabs'])) {
            return $extractedTabs;
        }

        $optionTabs = explode(';', $option['tabs']);
        foreach ($optionTabs as $tabStrings) {
            $extractedFields = [];
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

        return $extractedTabs;

    }

    /**
     * Сохраняет настройки формы инфоблока, если они отличаются
     * @param $iblockId
     * @param array $elementForm массив вида:
     * [
     *     'Tab1' => [
     *         'ACTIVE' => 'Активность',
     *         'ACTIVE_FROM' => '',
     *         'ACTIVE_TO' => '',
     *         'NAME' => 'Название',
     *         'CODE' => Символьный код',
     *         'SORT' => '',
     *     ],
     *     'Tab2' => [
     *         'PREVIEW_TEXT' => '',
     *         'PROPERTY_LINK' => '',
     *     ]
     * ]
     * @param array $params
     * @return bool
     */
    public function saveElementForm($iblockId, $elementForm = [], $params = [])
    {
        $exists = $this->exportElementForm($iblockId, $params);
        if ($this->hasDiff($exists, $elementForm)) {
            $ok = $this->getMode('test') ? true : $this->buildElementForm($iblockId, $elementForm, $params);
            $this->outNoticeIf($ok, 'Инфоблок %s: форма редактирования сохранена', $iblockId);
            $this->outDiffIf($ok, $exists, $elementForm);
            return $ok;
        } else {
            if ($this->getMode('out_equal')) {
                $this->out('Инфоблок %s: форма редактирования совпадает', $iblockId);
            }
            return true;
        }
    }

    public function saveSectionForm($iblockId, $elementForm = [], $params = [])
    {
        $params['name_prefix'] = 'form_section_';
        return $this->saveElementForm($iblockId, $elementForm, $params);
    }

    /**
     * Сохраняет настройки списка инфоблока
     * @param $iblockId
     * @param array $columns массив вида:
     * [
     *     'NAME',
     *     'SORT',
     *     'ID',
     *     'PROPERTY_LINK',
     * ];
     *
     * @param array $params
     */
    public function saveElementList($iblockId, $columns = [], $params = [])
    {
        $this->initializeVars($iblockId);

        $opts = [];
        foreach ($columns as $columnCode) {
            $opts[] = $this->transformCode($columnCode);
        }
        $opts = implode(',', $opts);

        $params = array_merge([
            'name_prefix' => 'tbl_iblock_element_',
            'category' => 'list',
            'page_size' => 20,
            'order' => 'desc',
            'by' => 'id',
        ], $params);

        $name = $params['name_prefix'] . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId);
        $value = [
            'columns' => $opts,
            'order' => $params['order'],
            'by' => $params['by'],
            'page_size' => $params['page_size'],
        ];

        CUserOptions::DeleteOptionsByName($params['category'], $name);
        CUserOptions::SetOption($params['category'], $name, $value, true);
    }

    public function saveSectionList($iblockId, $columns = [], $params = [])
    {
        $params['name_prefix'] = 'tbl_iblock_section_';
        return $this->saveElementList($iblockId, $columns, $params);
    }

    /**
     * Сохраняет настройки формы инфоблока
     * @param $iblockId
     * @param array $elementForm массив типа
     * [
     *     'Tab1' => [
     *         'ACTIVE' => 'Активность',
     *         'ACTIVE_FROM' => '',
     *         'ACTIVE_TO' => '',
     *         'NAME' => 'Название',
     *         'CODE' => Символьный код',
     *         'SORT' => '',
     *     ],
     *     'Tab2' => [
     *         'PREVIEW_TEXT' => '',
     *         'PROPERTY_LINK' => '',
     *     ]
     * ]
     *
     * @param array $params
     * @return bool
     */
    public function buildElementForm($iblockId, $elementForm = [], $params = [])
    {
        $this->initializeVars($iblockId);

        $params = array_merge([
            'name_prefix' => 'form_element_',
            'category' => 'form',
        ], $params);

        $params['name'] = $params['name_prefix'] . $iblockId;

        if (empty($elementForm)) {
            CUserOptions::DeleteOptionsByName($params['category'], $params['name']);
            return true;
        }

        $tabIndex = 0;
        $tabVals = [];
        foreach ($elementForm as $tabTitle => $fields) {

            if ($tabTitle == 'SEO' && empty($fields)) {
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

                $fcode = $this->transformCode($fcode);
                $ftitle = $this->prepareTitle($fcode, $ftitle);

                $tabVals[$tabIndex][] = '--' . $fcode . '--#--' . $ftitle . '--';
            }

            $tabIndex++;
        }

        $opts = [];
        foreach ($tabVals as $fields) {
            $opts[] = implode(',', $fields);
        }

        $opts = implode(';', $opts) . ';--';

        $value = [
            'tabs' => $opts,
        ];

        CUserOptions::DeleteOptionsByName($params['category'], $params['name']);
        CUserOptions::SetOption($params['category'], $params['name'], $value, true);

        return true;
    }

    /**
     * @param $iblockId
     * @param array $columns
     * @param array $params
     * @deprecated use saveElementList
     */
    public function buildElementList($iblockId, $columns = [], $params = [])
    {
        $this->saveElementList($iblockId, $columns, $params);
    }

    protected function initializeVars($iblockId)
    {
        $this->props = [];
        $this->titles = [];

        $this->iblock = CIBlock::GetList(['SORT' => 'ASC'], [
            'ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
        ])->Fetch();
        if (!$this->iblock) {
            $this->throwException(__METHOD__, 'Iblock %d not found', $iblockId);
        }

        $dbResult = CIBlockProperty::GetList(["sort" => "asc"], [
            "IBLOCK_ID" => $iblockId,
            "CHECK_PERMISSIONS" => "N",
        ]);

        while ($aItem = $dbResult->Fetch()) {
            if (!empty($aItem['CODE'])) {
                $this->titles['PROPERTY_' . $aItem['ID']] = $aItem['NAME'];
                $this->props[] = $aItem;
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
    }

    protected function getSeoTab()
    {
        $seoMess = IncludeModuleLangFile('/bitrix/modules/iblock/admin/iblock_element_edit.php', 'ru', true);

        return [
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
        ];
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
