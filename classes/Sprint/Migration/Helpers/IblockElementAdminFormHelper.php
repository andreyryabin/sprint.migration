<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockElementAdminFormHelper extends Helper
{

    private $tabIndex = -1;
    private $tabCode = '';
    private $sectionIndex = 0;
    private $usedFields = array();
    private $iblockId = 0;

    private $properties = array();

    protected $tabs = array();

    protected $titles = array();

    public function __construct($iblockId) {
        $this->iblockId = $iblockId;

        $dbResult = \CIBlockProperty::GetList(array("sort" => "asc"), array("IBLOCK_ID" => $iblockId, "CHECK_PERMISSIONS" => "N"));
        while ($aItem = $dbResult->GetNext(true, false)) {
            $this->titles['PROPERTY_' . $aItem['ID']] = $aItem['NAME'];
            $this->properties[$aItem['CODE']] = $aItem;
        }

        global $MESS;
        foreach ($MESS as $code => $value){
            if (false !== strpos($code, 'IBLOCK_FIELD_')){
                $fcode = str_replace('IBLOCK_FIELD_', '', $code);
                $this->titles[$fcode] = $value;
            }
        }
    }

    public function addTab($title) {
        $this->tabIndex++;
        $this->sectionIndex = 0;
        $this->tabCode = 'edit' . ($this->tabIndex + 1);

        $val = ($this->tabIndex == 0) ? $this->tabCode : '--' . $this->tabCode;
        $val .= '--#--' . $title . '--';
        $this->tabs[$this->tabIndex] = array(
            $val
        );
        return $this;
    }

    protected function addTabIfEmpty() {
        if (empty($this->tabs[$this->tabIndex])) {
            $msg = GetMessage('IBEL_E_IBLOCK_ELEMENT');
            $this->addTab($msg ? $msg : 'Tab1');
        }
    }

    public function addField($code, $title = '') {
        if (false !== strpos($code, 'PROPERTY_')) {

            $code = str_replace('PROPERTY_', '', $code);
            if (intval($code) <= 0) {
                $code = isset($this->properties[$code]) ? $this->properties[$code]['ID'] : 0;
            }

            $code = 'PROPERTY_' . $code;
        }

        if (empty($title)) {
            $title = isset($this->titles[$code]) ? $this->titles[$code] : $code;
        }

        if (!in_array($code, $this->usedFields)) {
            $this->addTabIfEmpty();
            $val = '--' . $code . '--#--' . $title . '--';
            $this->tabs[$this->tabIndex][] = $val;
        }

        $this->usedFields[] = $code;
        return $this;
    }

    public function addSection($title) {
        $this->addTabIfEmpty();
        $val = '--' . $this->tabCode . 'csection' . ($this->sectionIndex + 1) . '--#----' . $title . '--';
        $this->tabs[$this->tabIndex][] = $val;
        $this->sectionIndex++;
        return $this;
    }

    public function execute() {
        $category = 'form';
        $name = 'form_element_' . $this->iblockId;
        $value = array(
            'tabs' => $this->getOptions()
        );

        \CUserOptions::DeleteOptionsByName($category, $name);
        \CUserOptions::SetOption($category, $name, $value, true);
    }

    protected function getOptions() {
        $opts = array();
        foreach ($this->tabs as $aTab) {
            $opts[] = implode(',', $aTab);
        }

        $opts = implode(';', $opts) . ';--';
        return $opts;
    }

}