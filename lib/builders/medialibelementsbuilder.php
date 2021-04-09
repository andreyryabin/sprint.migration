<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class MedialibElementsBuilder extends VersionBuilder
{
    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return (!Locale::isWin1251() && $this->getHelperManager()->MedialibExchange()->isEnabled());
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_MedialibElements1'));
        $this->setDescription(Locale::getMessage('BUILDER_MedialibElements2'));
        //$this->addVersionFields();

    }

    /**
     * @throws RebuildException
     * @throws ExchangeException
     * @throws RestartException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute()
    {
        $collectionIds = $this->addFieldAndReturn(
            'collection_id',
            [
                'title'       => 'collection_id',
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->getHelperManager()->MedialibExchange()->getCollectionStructure(),
                'multiple'    => true,
            ]
        );

        $this->getExchangeManager()
             ->MedialibElementsExport()
             ->setLimit(20)
             ->setCollectionIds($collectionIds)
             ->setExchangeFile(
                 $this->getVersionResourceFile(
                     $this->getVersionName(),
                     'medialib_elements.xml'
                 )
             )->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/MedialibElementsExport.php'
        );
    }


}
