<?php

namespace Sprint\Migration\Helpers;

use CUserOptions;
use Sprint\Migration\Helper;
use Sprint\Migration\Helpers\UserOptions\IblockTrait;
use Sprint\Migration\Helpers\UserOptions\UserGroupTrait;
use Sprint\Migration\Helpers\UserOptions\UserTrait;

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
    use IblockTrait;
    use UserTrait;
    use UserGroupTrait;

    public function exportList($params = [])
    {
        $this->checkRequiredKeys(__METHOD__, $params, ['name']);

        $params = array_merge([
            'name' => '',
            'category' => 'list',
        ], $params);

        $option = CUserOptions::GetOption(
            $params['category'],
            $params['name'],
            false,
            false
        );

        if (!$option || empty($option['columns'])) {
            return [];
        }

        $option = array_merge([
            'page_size' => 20,
            'order' => 'desc',
            'by' => 'timestamp_x',
        ], $option);

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
            'by' => 'timestamp_x',
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

        CUserOptions::DeleteOptionsByName(
            $params['category'],
            $params['name']
        );

        CUserOptions::SetOption(
            $params['category'],
            $params['name'],
            $value,
            true
        );

        return true;
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
        /** @compability */
        if (isset($params['name_prefix'])) {
            $this->throwException(__METHOD__, 'name_prefix is no longer supported, see examples');
        }

        $params = array_merge([
            'name' => '',
            'category' => 'form',
        ], $params);


        $option = CUserOptions::GetOption(
            $params['category'],
            $params['name'],
            false,
            false
        );

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
        /** @compability */
        if (isset($params['name_prefix'])) {
            $this->throwException(__METHOD__, 'name_prefix is no longer supported, see examples');
        }

        $params = array_merge([
            'name' => '',
            'category' => 'form',
        ], $params);

        if (empty($formData)) {
            CUserOptions::DeleteOptionsByName(
                $params['category'],
                $params['name']
            );
            return true;
        }

        $tabIndex = 0;
        $tabVals = [];

        foreach ($formData as $tabTitle => $fields) {

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

        CUserOptions::DeleteOptionsByName(
            $params['category'],
            $params['name']
        );
        CUserOptions::SetOption(
            $params['category'],
            $params['name'],
            $value,
            true
        );

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

}
