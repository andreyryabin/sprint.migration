<?php

namespace Sprint\Migration\Helpers;

use CLang;
use CSite;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class SiteHelper extends Helper
{

    /**
     * @throws HelperException
     * @return mixed
     */
    public function getDefaultSiteIdIfExists()
    {
        $by = 'def';
        $order = 'desc';

        $item = CSite::GetList($by, $order, ['ACTIVE' => 'Y'])->Fetch();

        if ($item) {
            return $item['LID'];
        }
        throw new HelperException(
            Locale::getMessage(
                'ERR_DEFAULT_SITE_NOT_FOUND'
            )
        );
    }

    /**
     * @param array $filter
     * @return array
     */
    public function getSites($filter = [])
    {
        $by = 'def';
        $order = 'desc';

        $sids = [];
        $dbres = CSite::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $sids[] = $item;
        }

        return $sids;
    }

    /**
     * @throws HelperException
     * @return array
     */
    public function getSitesIfExists()
    {
        $items = $this->getSites(['ACTIVE' => 'Y']);
        if (!empty($items)) {
            return $items;
        }
        throw new HelperException(
            Locale::getMessage(
                'ERR_ACTIVE_SITES_NOT_FOUND'
            )
        );
    }

    /**
     * @param $siteId
     * @return array
     */
    public function getSiteTemplates($siteId)
    {
        $templates = [];

        $dbres = CSite::GetTemplateList($siteId);
        while ($item = $dbres->Fetch()) {
            $templates[] = [
                "TEMPLATE" => $item['TEMPLATE'],
                "SORT" => $item['SORT'],
                "CONDITION" => $item['CONDITION'],
            ];
        }

        return $templates;
    }

    /**
     * Устанавливает шаблоны сайта
     * @param $siteId
     * @param array $templates
     * @return bool
     */
    public function setSiteTemplates($siteId, $templates = [])
    {
        $sort = 150;

        $validTemplates = [];
        foreach ($templates as $template) {
            if (!empty($template['IN_DIR'])) {
                $template['CONDITION'] = sprintf('CSite::InDir(\'%s\')',
                    $template['IN_DIR']
                );
            } elseif (!empty($template['IN_PERIOD']) && is_array($template['IN_PERIOD'])) {
                list($t1, $t2) = $template['IN_PERIOD'];
                $t1 = is_numeric($t1) ? $t1 : strtotime($t1);
                $t2 = is_numeric($t2) ? $t2 : strtotime($t2);
                $template['CONDITION'] = sprintf('CSite::InPeriod(%s,%s)',
                    $t1,
                    $t2
                );
            } elseif (!empty($template['IN_GROUP']) && is_array($template['IN_GROUP'])) {
                $template['CONDITION'] = sprintf('CSite::InGroup(array(%s))',
                    implode(',', $template['IN_GROUP'])
                );
            } elseif (!empty($template['GET_PARAM']) && is_array($template['GET_PARAM'])) {
                $val = reset($template['GET_PARAM']);
                $key = key($template['GET_PARAM']);
                $template['CONDITION'] = sprintf('$_GET[\'%s\']==\'%s\'',
                    $key,
                    $val
                );
            }

            if (empty($template['TEMPLATE'])) {
                continue;
            }

            if (!isset($template['CONDITION'])) {
                continue;
            }

            $validTemplates[] = [
                'TEMPLATE' => $template['TEMPLATE'],
                'CONDITION' => $template['CONDITION'],
                'SORT' => $sort,
            ];

            $sort++;
        }

        $langs = new CLang;
        return $langs->Update($siteId, [
            'TEMPLATE' => $validTemplates,
        ]);
    }

}
