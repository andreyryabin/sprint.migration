<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use Sprint\Migration\VersionBuilder;

class MedialibElementsBuilder extends VersionBuilder
{
    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return (!Locale::isWin1251() && $this->getHelperManager()->Medialib()->isEnabled());
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
     */
    protected function execute()
    {
        $collectionIds = $this->addFieldAndReturn(
            'collection_id',
            [
                'title'       => 'collection_id',
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->getCollectionStructure(),
                'multiple'    => true,
            ]
        );

        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->getVersionName();
            $versionName = $this->params['~version_name'];
        } else {
            $versionName = $this->params['~version_name'];
        }

        $this->getExchangeManager()
             ->MedialibElementsExport()
             ->setLimit(20)
             ->setCollectionIds($collectionIds)
             ->setExchangeFile(
                 $this->getVersionResourceFile($versionName, 'medialib_elements.xml')
             )
             ->execute();

        unset($this->params['~version_name']);
    }

    protected function getCollectionStructure()
    {
        $helper = $this->getHelperManager()->Medialib();

        $items = $helper->getCollections($helper::TYPE_IMAGE);

        $res = [];
        foreach ($items as $item) {
            $res[] = [
                'title' => '[' . $item['ID'] . '] ' . $item['NAME'],
                'value' => $item['ID'],
            ];
        }
        return $res;
    }
}
