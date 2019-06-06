<?php

namespace Sprint\Migration\Helpers\UserOptions;

trait IblockSectionTrait
{
    public function exportSectionForm($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    public function buildSectionForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    public function saveSectionForm($iblockId, $formData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    public function exportSectionList($iblockId)
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportList([
            'name' => 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function buildSectionList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildList($listData, [
            'name' => 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

    public function saveSectionList($iblockId, $listData = [])
    {
        $this->initializeIblockVars($iblockId);

        return $this->saveList($listData, [
            'name' => 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . "." . $iblockId),
        ]);
    }

}
