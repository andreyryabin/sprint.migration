<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;


class IblockImport extends AbstractExchange
{
    protected $iblockId;

    public function from($file)
    {

    }

    public function to($iblockId)
    {
        $this->iblockId = $iblockId;
    }

    /**
     * @throws HelperException
     * @throws RestartException
     */
    public function execute()
    {
        $helper = $this->getHelperManager();

        if (!isset($this->params['add'])) {
            $this->params['add'] = 0;
        }
        $cnt = 100;

        if ($this->params['add'] <= $cnt) {
            $this->outProgress('Прогресс добавления', $this->params['add'], $cnt);
            $helper->Iblock()->addElement($this->iblockId, ['NAME' => 'name' . microtime()]);
            $this->params['add']++;
            $this->restart();
        }

        $this->saveParams();
    }
}
