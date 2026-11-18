<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\VersionConfig;

trait VersionConfigTrait
{
    private readonly ?VersionConfig $versionConfig;

    public function getVersionConfig(): VersionConfig
    {
        return $this->versionConfig;
    }

    public function setVersionConfig(VersionConfig $versionConfig): void
    {
        $this->versionConfig = $versionConfig;
    }

}
