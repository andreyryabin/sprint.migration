<?php

namespace Sprint\Migration\Helpers;

use CUserOptions;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Helpers\UserOptions\IblockElementTrait;
use Sprint\Migration\Helpers\UserOptions\IblockSectionTrait;
use Sprint\Migration\Helpers\UserOptions\UserGroupTrait;
use Sprint\Migration\Helpers\UserOptions\UserTrait;
use function IncludeModuleLangFile;

/*
Example $formData for buildForm

$formData = [
    'Tab1' => [
        'ACTIVE' => 'Активность',
        'ACTIVE_FROM' => '',
        'ACTIVE_TO' => '',
        'NAME' => 'Название',
        'CODE' => 'Символьный код',
        'SORT' => '',
    ],
    'Tab2' => [
        'PREVIEW_TEXT' => '',
        'PROPERTY_LINK' => '',
    ],
];


Example $listData for listForm
$listData = [
    'columns' => [
        'LOGIN',
        'ACTIVE',
        'TIMESTAMP_X',
        'NAME',
        'LAST_NAME',
        'EMAIL',
        'ID',
    ],
    'page_size' => 20,
    'order' => 'desc',
    'by' => 'timestamp_x',
];
*/

class UserOptionsHelper extends Helper
{

    private $titles = [];
    private $props = [];
    private $iblock = [];
    private $lastIblockId = 0;

    use IblockElementTrait;
    use IblockSectionTrait;
    use UserTrait;
    use UserGroupTrait;

    /**
     * UserOptionsHelper constructor.
     */
    public function __construct()
    {
        $this->checkModules(['iblock']);
    }

    public function exportList($params = [])
    {
        $this->checkRequiredKeys(__METHOD__, $params, ['name']);

        $params = array_merge([
            'name' => '',
            'category' => 'list',
        ], $params);

        $option = CUserOptions::GetOption($params['category'], $params['name'], false, false);

        $option['columns'] = explode(',', $option['columns']);

        return $option;
    }

    public function buildList($listData = [], $params = [])
    {
        $this->checkRequiredKeys(__METHOD__, $params, ['name']);

        /** @compability with old format */
        if (!isset($listData['columns'])) {
            $listData = [
                'columns' => is_array($listData) ? $listData : [],
                'page_size' => isset($params['page_size']) ? $params['page_size'] : '',
                'order' => isset($params['order']) ? $params['order'] : '',
                'by' => isset($params['by']) ? $params['by'] : '',
            ];
        }

        $params = array_merge([
            'name' => '',
            'category' => 'list',
        ], $params);

        $listData = array_merge([
            'columns' => [],
            'page_size' => 20,
            'order' => 'desc',
            'by' => 'id',
        ], $listData);

        if (empty($listData) || empty($listData['columns'])) {
            CUserOptions::DeleteOptionsByName($params['category'], $params['name']);
            return true;
        }

        $opts = [];
        foreach ($listData['columns'] as $columnCode) {
            $opts[] = $this->transformCode($columnCode);
        }
        $opts = implode(',', $opts);

        $value = [
            'columns' => $opts,
            'page_size' => $params['page_size'],
            'order' => $params['order'],
            'by' => $params['by'],
        ];

        CUserOptions::DeleteOptionsByName($params['category'], $params['name']);
        CUserOptions::SetOption($params['category'], $params['name'], $value, true);
    }

    public function saveList($listData = [], $params = [])
    {
        $exists = $this->exportList($params);
        if ($this->hasDiff($exists, $listData)) {
            $ok = $this->getMode('test') ? true : $this->buildList($listData, $params);
            $this->outNoticeIf($ok, 'Список "%s" сохранен', $params['name']);
            $this->outDiffIf($ok, $exists, $listData);
            return $ok;
        } else {
            if ($this->getMode('out_equal')) {
                $this->out('Список "%s" совпадает', $params['name']);
            }
            return true;
        }
    }

    public function exportForm($params = [])
    {
        $params = array_merge([
            'name' => '',
            'category' => 'form',
        ], $params);


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

    public function buildForm($formData = [], $params = [])
    {

        $params = array_merge([
            'name' => '',
            'category' => 'form',
        ], $params);

        if (empty($formData)) {
            CUserOptions::DeleteOptionsByName($params['category'], $params['name']);
            return true;
        }

        $tabIndex = 0;
        $tabVals = [];
        foreach ($formData as $tabTitle => $fields) {

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

    public function saveForm($formData = [], $params = [])
    {
        $exists = $this->exportForm($params);
        if ($this->hasDiff($exists, $formData)) {
            $ok = $this->getMode('test') ? true : $this->buildForm($formData, $params);
            $this->outNoticeIf($ok, 'Форма редактирования "%s" сохранена', $params['name']);
            $this->outDiffIf($ok, $exists, $formData);
            return $ok;
        } else {
            if ($this->getMode('out_equal')) {
                $this->out('Форма редактирования "%s" совпадает', $params['name']);
            }
            return true;
        }
    }

    protected function initializeIblockVars($iblockId)
    {
        $helper = HelperManager::getInstance();

        if (empty($iblockId)) {
            $this->lastIblockId = 0;
            $this->props = [];
            $this->titles = [];
            $this->iblock = [];
            return true;
        }

        if ($this->lastIblockId == $iblockId) {
            return true;
        }

        $iblock = $helper->Iblock()->getIblock([
            'ID' => $iblockId,
        ]);

        if (empty($this->iblock)) {
            $this->throwException(__METHOD__, 'Iblock %d not found', $iblockId);
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

    /**
     * Извлекает настройки формы инфоблока в массив, бросает исключение если их не существует
     * @param $iblockId
     * @param array $params
     * @throws HelperException
     * @return array
     * @deprecated
     * @compability
     */
    public function extractElementForm($iblockId)
    {
        $result = $this->exportElementForm($iblockId);

        if (!empty($result)) {
            return $result;
        }

        $this->throwException(__METHOD__, 'Iblock form options not found');
    }
}
