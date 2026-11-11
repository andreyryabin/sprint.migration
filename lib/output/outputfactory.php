<?php

namespace Sprint\Migration\Output;

use Sprint\Migration\VersionConfig;

class OutputFactory
{

    public static function create(VersionConfig $versionConfig): OutputInterface
    {
        if (php_sapi_name() == 'cli') {
            return new ConsoleOutput($versionConfig->getLogger());
        }
        return new BrowserOutput($versionConfig->getLogger());
    }

}
