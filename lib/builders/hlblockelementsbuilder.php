<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Fields\TextareaField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockElementsBuilder extends VersionBuilder
{
    const UPDATE_METHOD_NOT = 'not';
    const UPDATE_METHOD_XML_ID = 'xml_id';
    const UPDATE_METHOD_EQUAL_KEYS = 'equal_keys';

    /**
     * @return bool
     */
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Hlblock()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_HlblockElementsExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_HlblockElementsExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Hlblock'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws HelperException
     * @throws RebuildException
     * @throws RestartException
     */
    protected function execute(): void
    {
        $exhelper = $this->getExchangeHelpers();

        $hlblockId = $this->askField('hlblock_id',
            (new RadioListField(Locale::getMessage('BUILDER_HlblockElementsExport_HlblockId')))
                ->setOptions($exhelper->HlblockExchange()->getHlblocksSelect(), 'ID', 'TITLE'),
        );

        $fieldsSelect = $exhelper->HlblockExchange()->getHlblockFieldsSelect($hlblockId);
        $exportFields = array_column($fieldsSelect, 'NAME');

        $exportFilter = $this->getFieldValueExportFilter($exportFields);
        $updateMethod = $this->getFieldValueUpdateMethod($exportFields);

        $equalKeys = $this->getFieldValueEqualKeys($updateMethod, $fieldsSelect);


        $this->confirmCreateVersion();

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('hlblock_elements.xml')
            ->execute(
                attributesFn: fn() => $exhelper->HlblockExchange()->getWriterAttributes($hlblockId),
                totalCountFn: fn() => $exhelper->HlblockExchange()->getWriterRecordsCount(
                    $hlblockId,
                    $exportFilter,
                ),
                recordsFn: fn($offset, $limit) => $exhelper->HlblockExchange()->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $hlblockId,
                    $exportFilter,
                    $exportFields,
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress',
                    $value,
                    $totalCount,
                ),
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('HlblockElementsExport'),
            [
                'updateMethod' => $updateMethod,
                'equalKeys'    => $equalKeys,
            ],
        );
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueExportFilter(array $exportFields): array
    {
        $filterModeSelect = (new RadioListField(Locale::getMessage('BUILDER_HlblockElementsExport_Filter')));

        $filterModeSelect->addOption('all', Locale::getMessage('BUILDER_SelectAll'));
        $filterModeSelect->addOption('list_id', Locale::getMessage('BUILDER_HlblockElementsExport_SelectSomeId'));

        if (in_array('UF_XML_ID', $exportFields)) {
            $filterModeSelect->addOption('list_xml_id',
                Locale::getMessage('BUILDER_HlblockElementsExport_SelectSomeXmlId'));
        }

        $filterMode = $this->askField('filter_mode', $filterModeSelect);

        if ($filterMode == 'list_id') {
            $filterIds = $this->askField('export_filter_list_id',
                (new TextareaField(Locale::getMessage('BUILDER_HlblockElementsExport_FilterListId')))
                    ->setCols(35)
                    ->setRows(3),
            );

            $exportFilter = ['ID' => $this->explodeString($filterIds)];
        } elseif ($filterMode == 'list_xml_id') {
            $filterXmlIds = $this->askField('export_filter_list_xml_id',
                (new TextareaField(Locale::getMessage('BUILDER_HlblockElementsExport_FilterListXmlId')))
                    ->setCols(35)
                    ->setRows(3),
            );

            $exportFilter = ['UF_XML_ID' => $this->explodeString($filterXmlIds)];
        } else {
            $exportFilter = [];
        }

        return $exportFilter;
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueUpdateMethod(array $exportFields)
    {
        $updateMethodSelect = (new RadioListField(Locale::getMessage('BUILDER_HlblockElementsExport_UpdateMethod')));

        $updateMethodSelect->addOption(self::UPDATE_METHOD_NOT,
            Locale::getMessage('BUILDER_HlblockElementsExport_NotUpdate'));
        $updateMethodSelect->addOption(self::UPDATE_METHOD_EQUAL_KEYS,
            Locale::getMessage('BUILDER_HlblockElementsExport_SaveElementWithEqualKeys'));

        if (in_array('UF_XML_ID', $exportFields)) {
            $updateMethodSelect->addOption(self::UPDATE_METHOD_XML_ID,
                Locale::getMessage('BUILDER_HlblockElementsExport_SaveElementByXmlId'));
        }

        return $this->askField('update_method', $updateMethodSelect);
    }

    /**
     * @throws RebuildException
     */
    private function getFieldValueEqualKeys($updateMethod, $fieldsSelect)
    {
        if ($updateMethod == self::UPDATE_METHOD_EQUAL_KEYS) {
            return $this->askField('equal_keys',
                (new CheckboxListField(Locale::getMessage('BUILDER_HlblockElementsExport_EqualKeys')))
                    ->setOptions($fieldsSelect, 'NAME', 'TITLE'),
            );
        }
        return [];
    }
}
