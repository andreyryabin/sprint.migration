<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class MedialibElementsBuilder extends VersionBuilder
{
    /**
     * @return bool
     */
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Medialib()->isEnabled();
    }

    protected function initialize(): void
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
    protected function execute(): void
    {
        $exhelper = $this->getExchangeHelpers()->MedialibExchange();

        $collectionIds = $this->askField('collection_id',
            (new CheckboxListField(Locale::getMessage('BUILDER_MedialibElements_CollectionId')))
                ->setOptions($exhelper->getCollectionSelect($exhelper::TYPE_IMAGE), 'ID', 'TITLE'),
        );

        $exportFields = [
            'NAME',
            'DESCRIPTION',
            'KEYWORDS',
            'COLLECTION_ID',
            'SOURCE_ID',
        ];

        $this->confirmCreateVersion();

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('medialib_elements.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes(),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount($collectionIds),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $collectionIds,
                    $exportFields,
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress',
                    $value,
                    $totalCount,
                ),
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('MedialibElementsExport'),
        );
    }
}
