<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
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
     * @throws HelperException
     */
    protected function execute()
    {
        $exhelper = $this->getHelperManager()->MedialibExchange();

        $collectionIds = $this->addFieldAndReturn(
            'collection_id',
            [
                'title' => Locale::getMessage('BUILDER_MedialibElements_CollectionId'),
                'placeholder' => '',
                'width' => 250,
                'select' => $exhelper->getCollectionStructure(
                    $exhelper::TYPE_IMAGE
                ),
                'multiple' => true,
            ]
        );

        $exportFields = [
            'NAME',
            'DESCRIPTION',
            'KEYWORDS',
            'COLLECTION_ID',
            'SOURCE_ID',
        ];

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('medialib_elements.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes(),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount($collectionIds),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $collectionIds,
                    $exportFields
                ),
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('MedialibElementsExport')
        );
    }
}
