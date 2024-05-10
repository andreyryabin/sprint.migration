<?php

namespace Sprint\Migration\Helpers;

use CGridOptions;
use CUserOptions;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Helpers\Traits\UserOptions\HlblockTrait;
use Sprint\Migration\Helpers\Traits\UserOptions\IblockTrait;
use Sprint\Migration\Helpers\Traits\UserOptions\UserGroupTrait;
use Sprint\Migration\Helpers\Traits\UserOptions\UserTrait;
use Sprint\Migration\Locale;

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


Example $data for listForm
$data = [
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
    use HlblockTrait;

    /**
     * @param array $params
     * @throws HelperException
     * @return array|bool|mixed
     */
    public function exportList($params = [])
    {
        $this->checkRequiredKeys($params, ['name']);

        $params = array_merge(
            [
                'name' => '',
                'category' => 'list',
            ],
            $params
        );

        $option = CUserOptions::GetOption(
            $params['category'],
            $params['name'],
            false,
            false
        );

        if (!$option || empty($option['columns'])) {
            return [];
        }

        $option = array_merge(
            [
                'page_size' => 20,
                'order' => 'desc',
                'by' => 'timestamp_x',
            ],
            $option
        );

        $option['columns'] = $this->revertCodesFromColumns($option['columns']);

        return $option;
    }

    /**
     * @param array $data
     * @param array $params
     * @throws HelperException
     * @return bool
     */
    public function buildList($data = [], $params = [])
    {
        $this->checkRequiredKeys($params, ['name']);

        /** @compability with old format */
        if (!isset($data['columns'])) {
            $data = [
                'columns' => is_array($data) ? $data : [],
                'page_size' => isset($params['page_size']) ? $params['page_size'] : '',
                'order' => isset($params['order']) ? $params['order'] : '',
                'by' => isset($params['by']) ? $params['by'] : '',
            ];
        }

        $params = array_merge(
            [
                'name' => '',
                'category' => 'list',
            ],
            $params
        );

        $data = array_merge(
            [
                'columns' => [],
                'page_size' => 20,
                'order' => 'desc',
                'by' => 'timestamp_x',
            ],
            $data
        );

        if (empty($data) || empty($data['columns'])) {
            CUserOptions::DeleteOptionsByName($params['category'], $params['name']);
            return true;
        }

        $value = [
            'columns' => $this->transformCodesToColumns($data['columns']),
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

    /**
     * @param array $data
     * @param array $params
     * @throws HelperException
     * @return bool
     */
    public function saveList($data = [], $params = [])
    {
        $exists = $this->exportList($params);
        if ($this->hasDiff($exists, $data)) {
            $ok = $this->getMode('test') ? true : $this->buildList($data, $params);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_OPTION_LIST_CREATED',
                    [
                        '#NAME#' => $params['name'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exists, $data);
            return $ok;
        }

        return true;
    }

    public function exportGrid($gridId)
    {
        $params = CUserOptions::GetOption(
            "main.interface.grid",
            $gridId,
            []
        );
        if (!empty($params)) {
            $options = (new CGridOptions($gridId))->GetOptions();

            foreach ($options['views'] as $viewCode => $view) {
                $view['columns'] = $this->revertCodesFromColumns($view['columns']);
                $view['custom_names'] = $this->revertCustomNames($view['custom_names']);
                $options['views'][$viewCode] = $view;
            }

            return $options;
        }
        return [];
    }

    public function buildGrid($gridId, $options = [])
    {
        foreach ($options['views'] as $viewCode => $view) {
            $view['columns'] = $this->transformCodesToColumns($view['columns']);
            $view['custom_names'] = $this->transformCustomNames($view['custom_names']);
            $options['views'][$viewCode] = $view;
        }

        CUserOptions::DeleteOptionsByName(
            'main.interface.grid',
            $gridId
        );
        CUserOptions::setOption(
            "main.interface.grid",
            $gridId,
            $options,
            true
        );

        return true;
    }

    public function saveGrid($gridId, $params = [])
    {
        $exists = $this->exportGrid($gridId);
        if ($this->hasDiff($exists, $params)) {
            $ok = $this->getMode('test') ? true : $this->buildGrid($gridId, $params);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_OPTION_GRID_CREATED',
                    [
                        '#NAME#' => $gridId,
                    ]
                )
            );
            $this->outDiffIf($ok, $exists, $params);
            return $ok;
        }

        return true;
    }

    /**
     * @param array $params
     * @throws HelperException
     * @return array
     */
    public function exportForm($params = [])
    {
        /** @compability */
        if (isset($params['name_prefix'])) {
            throw new HelperException('name_prefix is no longer supported, see examples');
        }

        $params = array_merge(
            [
                'name' => '',
                'category' => 'form',
            ],
            $params
        );

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
            $tabId = '';

            $columnString = explode(',', $tabStrings);

            foreach ($columnString as $fieldIndex => $fieldString) {
                if (!strpos($fieldString, '#')) {
                    continue;
                }

                [$fieldCode, $fieldTitle] = explode('#', $fieldString);

                $fieldCode = str_replace('--', '', strval($fieldCode));
                $fieldTitle = str_replace('--', '', strval($fieldTitle));

                $fieldCode = trim($fieldCode, '*');
                $fieldTitle = trim($fieldTitle, '*');

                if ($fieldIndex == 0) {
                    $tabTitle = $fieldTitle;
                    $tabId = $fieldCode;
                } else {
                    $fieldCode = $this->revertCode($fieldCode);
                    $extractedFields[$fieldCode] = $fieldTitle;
                }
            }

            if ($tabTitle) {
                $extractedTabs[$tabTitle . '|' . $tabId] = $extractedFields;
            }
        }

        return $extractedTabs;
    }

    /**
     * @param array $formData
     * @param array $params
     * @throws HelperException
     * @return bool
     */
    public function buildForm($formData = [], $params = [])
    {
        /** @compability */
        if (isset($params['name_prefix'])) {
            throw new HelperException('name_prefix is no longer supported, see examples');
        }

        $params = array_merge(
            [
                'name' => '',
                'category' => 'form',
            ],
            $params
        );

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
            [$tabTitle, $tabId] = explode('|', $tabTitle);

            if (!$tabId) {
                $tabId = 'edit' . ($tabIndex + 1);
            }

            $tabId = ($tabIndex == 0) ? $tabId : '--' . $tabId;

            $tabVals[$tabIndex][] = $tabId . '--#--' . $tabTitle . '--';

            foreach ($fields as $fieldKey => $fieldValue) {
                if (is_numeric($fieldKey)) {
                    /** @compability */
                    [$fcode, $ftitle] = explode('|', $fieldValue);
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

    /**
     * @param array $formData
     * @param array $params
     * @throws HelperException
     * @return bool
     */
    public function saveForm($formData = [], $params = [])
    {
        $exists = $this->exportForm($params);
        if ($this->hasDiffStrict($exists, $formData)) {
            $ok = $this->getMode('test') ? true : $this->buildForm($formData, $params);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_OPTION_FORM_CREATED',
                    [
                        '#NAME#' => $params['name'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exists, $formData);
            return $ok;
        }
        return true;
    }
}
