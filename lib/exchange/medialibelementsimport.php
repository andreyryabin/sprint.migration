<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Helpers\MedialibExchangeHelper;

class MedialibElementsImport extends ExchangeReader
{

    /**
     * @throws HelperException
     */
    protected function convertRecord(array $record): array
    {
        $medialibExchangeHelper = new MedialibExchangeHelper();

        $fields = [];
        foreach ($record['fields'] as $field) {
            if ($field['name'] == 'FILE') {
                $fields['FILE'] = $this->makeFileValue($field['value'][0]);
            } elseif ($field['name'] == 'COLLECTION_PATH') {
                $paths = array_column($field['value'], 'value');
                $fields['COLLECTION_ID'] = $medialibExchangeHelper->saveCollectionByPath(
                    $medialibExchangeHelper::TYPE_IMAGE,
                    $paths
                );
            } else {
                $fields[$field['name']] = $field['value'][0]['value'];
            }
        }
        return $fields;
    }
}
