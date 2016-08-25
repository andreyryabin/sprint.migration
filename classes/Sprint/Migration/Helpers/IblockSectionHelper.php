<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockSectionHelper extends Helper
{

    public function addSectionIfNotExists($filter,$fields){
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));
        $sectionId = $this->getSectionId($filter);
        if ($sectionId) {
            return $sectionId;
        }

        $fields['IBLOCK_ID'] = $filter['IBLOCK_ID'];
        return $this->addSection($fields);

    }

    public function updateSectionIfExists($filter,$fields){
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));
        $sectionId = $this->getSectionId($filter);
        if (!$sectionId) {
            return false;
        }

        $ib = new \CIBlockSection;
        $id = $ib->Update($sectionId, $fields);
        if ($id){
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function deleteSectionIfExists($filter) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));
        $sectionId = $this->getSectionId($filter);
        if (!$sectionId) {
            return false;
        }

        $ib = new \CIBlockSection;
        if ($ib->Delete($sectionId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function addSection($fields){
        $this->checkRequiredKeys(__METHOD__, $fields, array('IBLOCK_ID'));

        $default = array(
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => false,
            "NAME" => 'section',
            "CODE" => '',
            "SORT" => 100,
            "PICTURE" => false,
            "DESCRIPTION" => '',
            "DESCRIPTION_TYPE" => 'text'
        );

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlockSection;
        $id = $ib->Add($fields);
        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function getSectionId($filter) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CIBlockSection::GetList(array(
            'SORT' => 'ASC'
        ), $filter, false, array(
            'nTopCount' => 1
        ), array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ))->Fetch();

        return ($aItem && $aItem['ID']) ? $aItem['ID'] : false;
    }


}