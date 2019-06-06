<?php

namespace Sprint\Migration\Helpers\UserOptions;

trait IblockElementTrait
{
    public function exportElementForm($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    public function buildElementForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    public function saveElementForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    public function exportElementList($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        $this->exportList([
            'name' => 'tbl_iblock_element_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function buildElementList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        $this->buildList($listData, [
            'name' => 'tbl_iblock_element_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function saveElementList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        $this->saveList($listData, [
            'name' => 'tbl_iblock_element_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

}
