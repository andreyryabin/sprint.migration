<?php

namespace Sprint\Migration\Helpers\Traits\UserOptions;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\HlblockHelper;

trait HlblockTrait
{
    /**
     * @throws HelperException
     */
    public function getHlblockGridId($hlblockId): string
    {
        $tableName = (new HlblockHelper())->getHlblockTableName($hlblockId);

        if (empty($tableName)) {
            throw new HelperException(
                sprintf('Highload-block "%s" not found', $hlblockId)
            );
        }

        return 'tbl_' . $tableName;
    }

    /**
     * @throws HelperException
     */
    public function getHlblockFormId($hlblockId): string
    {
        if (empty($hlblockId)) {
            throw new HelperException(
                sprintf('Highload-block "%s" not found', $hlblockId)
            );
        }

        return 'hlrow_edit_' . $hlblockId;
    }

    /**
     * @throws HelperException
     */
    public function exportHlblockList($hlblockId)
    {
        return $this->exportList([
            'name' => $this->getHlblockGridId($hlblockId),
        ]);
    }

    /**
     * @throws HelperException
     */
    public function buildHlblockList($hlblockId, $listData = [])
    {
        return $this->buildList($listData, [
            'name' => $this->getHlblockGridId($hlblockId),
        ]);
    }

    public function saveHlblockList($hlblockId, $listData = [])
    {
        return $this->saveList($listData, [
            'name' => $this->getHlblockGridId($hlblockId),
        ]);
    }

    /**
     * @throws HelperException
     */
    public function exportHlblockForm($hlblockId)
    {
        return $this->exportForm([
            'name' => $this->getHlblockFormId($hlblockId),
        ]);
    }

    /**
     * @throws HelperException
     */
    public function buildHlblockForm($hlblockId, $formData = [])
    {
        $this->buildForm($formData, [
            'name' => $this->getHlblockFormId($hlblockId),
        ]);
    }

    /**
     * @throws HelperException
     */
    public function saveHlblockForm($hlblockId, $formData = [])
    {
        $this->saveForm($formData, [
            'name' => $this->getHlblockFormId($hlblockId),
        ]);
    }
}
