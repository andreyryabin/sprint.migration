<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class SiteHelper extends Helper
{

    public function getDefaultSiteIdIfExists(){
        $by = 'def';
        $order = 'desc';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CSite::GetList($by, $order, array('ACTIVE' => 'Y'))->Fetch();

        if ($aItem){
            return $aItem['LID'];
        }

        $this->throwException(__METHOD__, 'Default site not found');
    }

    public function getSites($filter = array()) {
        $by = 'def';
        $order = 'desc';

        $sids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CSite::GetList($by, $order, $filter);
        while ($aItem = $dbRes->Fetch()) {
            $sids[] = $aItem;
        }

        return $sids;
    }

    public function getSitesIfExists(){
        $items = $this->getSites(array('ACTIVE' => 'Y'));
        if (!empty($items)){
            return $items;
        }
        $this->throwException(__METHOD__, 'Active sites not found');
    }

    public function getSiteTemplates($siteId){
        $templates = array();

        $dbRes = \CSite::GetTemplateList($siteId);
        while($aItem = $dbRes->Fetch()) {
            $templates[] = array(
                "TEMPLATE" => $aItem['TEMPLATE'],
                "SORT" => $aItem['SORT'],
                "CONDITION" => $aItem['CONDITION']
            );
        }

        return $templates;
    }

    public function setSiteTemplates($siteId, $templates = array()){
        $sort = 150;

        $validTemplates = array();
        foreach ($templates as $template){
            if (!empty($template['IN_DIR'])){
                $template['CONDITION'] = sprintf('CSite::InDir(\'%s\')',
                    $template['IN_DIR']
                );
            } elseif (!empty($template['IN_PERIOD']) && is_array($template['IN_PERIOD'])){
                list($t1, $t2) = $template['IN_PERIOD'];
                $t1 = is_numeric($t1) ? $t1 : strtotime($t1);
                $t2 = is_numeric($t2) ? $t2 : strtotime($t2);
                $template['CONDITION'] = sprintf('CSite::InPeriod(%s,%s)',
                    $t1,
                    $t2
                );
            } elseif (!empty($template['IN_GROUP']) && is_array($template['IN_GROUP'])){
                $template['CONDITION'] = sprintf('CSite::InGroup(array(%s))',
                    implode(',', $template['IN_GROUP'])
                );
            } elseif (!empty($template['GET_PARAM']) && is_array($template['GET_PARAM'])){
                $val = reset($template['GET_PARAM']);
                $key = key($template['GET_PARAM']);
                $template['CONDITION'] = sprintf('$_GET[\'%s\']==\'%s\'',
                    $key,
                    $val
                );
            }

            if (empty($template['TEMPLATE'])){
                continue;
            }

            if (!isset($template['CONDITION'])){
                continue;
            }

            $validTemplates[] = array(
                'TEMPLATE' => $template['TEMPLATE'],
                'CONDITION' => $template['CONDITION'],
                'SORT' => $sort
            );

            $sort++;
        }

        $langs = new \CLang;
        $ok = $langs->Update($siteId, array(
            'TEMPLATE' => $validTemplates
        ));

        return $ok;
    }

}