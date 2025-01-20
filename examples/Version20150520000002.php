<?php

namespace Sprint\Migration;

use CIBlockElement;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;

class Version20150520000002 extends Version
{
    protected $description = "Пошаговая миграция";

    /**
     * @throws HelperException
     * @throws RestartException
     */
    public function up()
    {
    }

    /**
     * @throws HelperException
     * @throws RestartException
     */
    public function down()
    {

    }
}
