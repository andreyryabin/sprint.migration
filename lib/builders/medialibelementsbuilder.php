<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\ExchangeWriter;
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

        $writer = (new ExchangeWriter)
            ->setCopyFiles(true)
            ->setExchangeFile($this->getExchangeFile('medialib_elements.xml'));

        $this->restartOnce('step1', fn() => $writer->createExchangeFile([]));

        $this->restartWithOffset('step2', function (int $offset) use ($exhelper, $writer, $collectionIds) {
            $totalCount = $this->restartOnce('step2_1', fn() => $exhelper->getElementsCount($collectionIds));

            $limit = 20;

            $exportFields = [
                'NAME',
                'DESCRIPTION',
                'KEYWORDS',
                'COLLECTION_ID',
                'SOURCE_ID',
            ];

            $tags = $exhelper->createRecordsTags(
                $collectionIds,
                $offset,
                $limit,
                $exportFields
            );

            $writer->appendTagsToExchangeFile($tags);

            $this->outProgress('Progress: ', $offset, $totalCount);

            return ($tags->countChilds() >= $limit) ? $offset + $tags->countChilds() : false;
        });


        $this->restartOnce('step3', fn() => $writer->closeExchangeFile());

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/MedialibElementsExport.php'
        );
    }
}
