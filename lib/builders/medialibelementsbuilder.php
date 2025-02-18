<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\MedialibElementsExport;
use Sprint\Migration\Helpers\MedialibExchangeHelper;
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
        return $this->getHelperManager()->Medialib()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_MedialibElements1'));
        $this->setDescription(Locale::getMessage('BUILDER_MedialibElements2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Medialib'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     * @throws RestartException
     */
    protected function execute()
    {
        $medialibExchangeHelper = new MedialibExchangeHelper();

        $collectionIds = $this->addFieldAndReturn(
            'collection_id',
            [
                'title'       => Locale::getMessage('BUILDER_MedialibElements_CollectionId'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $medialibExchangeHelper->getCollectionStructure(
                    $medialibExchangeHelper::TYPE_IMAGE
                ),
                'multiple'    => true,
            ]
        );

        (new MedialibElementsExport($this))
             ->setLimit(20)
             ->setCopyFiles(true)
             ->setCollectionIds($collectionIds)
             ->setExchangeFile(
                 $this->getVersionResourceFile(
                     $this->getClassName(),
                     'medialib_elements.xml'
                 )
             )->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/MedialibElementsExport.php'
        );
    }
}
