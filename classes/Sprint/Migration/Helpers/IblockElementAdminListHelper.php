<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockElementAdminListHelper extends Helper
{


    private $columns = array();

    private $iblockType = '';
    private $iblockId = 0;

    private $properties = array();

    private $listOrder = 'desc';
    private $listBy = 'id';
    private $pageSize = 20;


    public function __construct($iblockId) {
        $aIblock = \CIBlock::GetList(array('SORT' => 'ASC'), array('ID' => $iblockId))->Fetch();

        $this->iblockType = $aIblock['IBLOCK_TYPE_ID'];
        $this->iblockId = $iblockId;

        $dbResult = \CIBlockProperty::GetList(array("sort" => "asc"), array("IBLOCK_ID" => $iblockId, "CHECK_PERMISSIONS" => "N"));
        while ($aItem = $dbResult->GetNext(true, false)) {
            $this->properties[$aItem['CODE']] = $aItem;
        }
    }

    public function addColumns($columns) {
        if (is_array($columns)) {
            foreach ($columns as $key => $val) {
                $this->addColumn($val);
            }
        }
        return $this;
    }

    public function addColumn($code) {
        if (false !== strpos($code, 'PROPERTY_')) {

            $code = str_replace('PROPERTY_', '', $code);
            if (intval($code) <= 0) {
                $code = isset($this->properties[$code]) ? $this->properties[$code]['ID'] : 0;
            }

            $code = 'PROPERTY_' . $code;
        }

        $this->columns[] = $code;
        return $this;
    }

    public function setPageSize($pageSize = 20) {
        $pageSize = in_array($pageSize, array(10, 20, 50, 100, 200, 500)) ? $pageSize : 20;
        $this->pageSize = $pageSize;
        return $this;
    }

    public function setListSort($listBy = 'id', $listOrder = 'desc') {
        $this->listOrder = $listOrder;
        $this->listBy = $listBy;
        return $this;
    }



    public function execute() {
        $category = 'list';
        $name = "tbl_iblock_element_".md5($this->iblockType.".".$this->iblockId);
        $value = array(
            'columns' => implode(',',$this->columns),
            'order' => $this->listOrder,
            'by' => $this->listBy,
            'page_size' => $this->pageSize
        );

        \CUserOptions::DeleteOptionsByName($category, $name);
        \CUserOptions::SetOption($category, $name, $value, true);

    }


}