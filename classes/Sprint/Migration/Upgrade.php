<?php

namespace Sprint\Migration;

abstract class Upgrade extends Db
{

    abstract public function doUpgrade();
}
