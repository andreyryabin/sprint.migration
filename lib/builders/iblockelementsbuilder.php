<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Fields\StringField;
use Sprint\Migration\Fields\TextareaField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockElementsBuilder extends VersionBuilder
{
    const UPDATE_METHOD_ADD = 'add';
    const UPDATE_METHOD_CODE = 'code';
    const UPDATE_METHOD_XML_ID = 'xml_id';

    /**
     * @return bool
     */
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockElementsExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_IblockElementsExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws MigrationException
     * @throws RestartException
     * @throws HelperException
     */
    protected function execute(): void
    {
        $exhelper = $this->getExchangeHelpers()->IblockExchange();

        $iblockId = $this->getFieldValueIblockId();

        $exportFilter = $this->getFieldValueExportFilter();
        $updateMethod = $this->getFieldValueUpdateMethod();

        $exportFields = $this->getFieldValueExportFields($iblockId, $updateMethod, $exportFilter);
        $exportProps = $this->getFieldValueExportProps($iblockId);

        $this->confirmCreateVersion();

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('iblock_elements.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes(
                    $iblockId,
                ),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount(
                    $iblockId,
                    $exportFilter,
                ),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $iblockId,
                    $exportFilter,
                    $exportFields,
                    $exportProps,
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    Locale::getMessage('PROGRESS'),
                    $value,
                    $totalCount,
                ),
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockElementsExport'),
            [
                'updateMethod' => $updateMethod,
            ],
        );
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     */
    protected function getFieldValueIblockId(): int
    {
        $exhelper = $this->getExchangeHelpers();

        $iblockId = (int) $this->askField('iblock_id',
            (new RadioListField(Locale::getMessage('BUILDER_IblockElementsExport_IblockId')))
                ->setOptions($exhelper->IblockExchange()->getIblocksSelect(), 'ID', 'TITLE', 'GROUP'),
        );

        $iblock = $exhelper->IblockExchange()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->resetField('iblock_id');
        }

        return $iblockId;
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueExportFilter(): array
    {
        $filterMode = $this->askField('filter_mode',
            (new RadioListField(Locale::getMessage('BUILDER_IblockElementsExport_Filter')))
                ->setInPlanForValues('all')
                ->addOption('all', Locale::getMessage('BUILDER_SelectAll'))
                ->addOption('list_id', Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeId'))
                ->addOption('list_xml', Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeXmlId'))
                ->addOption('list_sid', Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeSectionId')),
        );

        if ($filterMode == 'list_id') {
            $filterIds = $this->askField('export_filter_list_id',
                (new TextareaField(Locale::getMessage('BUILDER_IblockElementsExport_FilterListId')))
                    ->setPlaceholder(Locale::getMessage('BY_SPACE_NUMERIC'))
                    ->explodeToNumeric()
                    ->setCols(35)
                    ->setRows(4),
            );

            $exportFilter = ['ID' => $filterIds];
        } elseif ($filterMode == 'list_sid') {
            $filterSectionId = (int) $this->askField('export_filter_list_sid',
                (new StringField(Locale::getMessage('BUILDER_IblockElementsExport_FilterListSectionId')))
                    ->setSize(10),
            );

            if ($filterSectionId <= 0) {
                $this->resetField('export_filter_list_sid');
            }

            $exportFilter = ['IBLOCK_SECTION_ID' => $filterSectionId];
        } elseif ($filterMode == 'list_xml') {
            $filterXmlIds = $this->askField('export_filter_list_xml',
                (new TextareaField(Locale::getMessage('BUILDER_IblockElementsExport_FilterListXmlId')))
                    ->setPlaceholder(Locale::getMessage('BY_SPACE_NUMERIC'))
                    ->explodeToStrings()
                    ->setCols(35)
                    ->setRows(4),
            );

            $exportFilter = ['XML_ID' => $filterXmlIds];
        } else {
            $exportFilter = [];
        }

        return $exportFilter;
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueUpdateMethod()
    {
        return $this->askField('update_method',
            (new RadioListField(Locale::getMessage('BUILDER_IblockElementsExport_UpdateMethod')))
                ->addOption(self::UPDATE_METHOD_ADD, Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'))
                ->addOption(self::UPDATE_METHOD_CODE,
                    Locale::getMessage('BUILDER_IblockElementsExport_SaveElementByCode'))
                ->addOption(self::UPDATE_METHOD_XML_ID,
                    Locale::getMessage('BUILDER_IblockElementsExport_SaveElementByXmlId')),
        );
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueExportFields($iblockId, $updateMethod = false, $exportFilter = [])
    {
        $exhelper = $this->getExchangeHelpers()->IblockExchange();

        $fieldsMode = $this->askField('fields_mode',
            (new RadioListField(Locale::getMessage('BUILDER_IblockElementsExport_Fields')))
                ->setInPlanForValues('all', 'none')
                ->addOption('all', Locale::getMessage('BUILDER_SelectAll'))
                ->addOption('none', Locale::getMessage('BUILDER_SelectNone'))
                ->addOption('some', Locale::getMessage('BUILDER_SelectSome')),
        );

        if ($fieldsMode == 'some') {
            $exportFields = $this->askField('export_filter',
                (new CheckboxListField(Locale::getMessage('BUILDER_IblockElementsExport_Fields')))
                    ->setOptions($exhelper->getIblockElementFieldsSelect($iblockId, true), 'ID', 'TITLE'),
            );
        } elseif ($fieldsMode == 'all') {
            $exportFields = $exhelper->getIblockElementFieldsSelect($iblockId);
            $exportFields = array_column($exportFields, 'ID');
        } else {
            $exportFields = [];
        }

        if ($updateMethod == self::UPDATE_METHOD_CODE) {
            if (!in_array('CODE', $exportFields)) {
                $exportFields[] = 'CODE';
            }
        }

        if ($updateMethod == self::UPDATE_METHOD_XML_ID) {
            if (!in_array('XML_ID', $exportFields)) {
                $exportFields[] = 'XML_ID';
            }
        }
        if (isset($exportFilter['IBLOCK_SECTION_ID'])) {
            if (!in_array('IBLOCK_SECTION', $exportFields)) {
                $exportFields[] = 'IBLOCK_SECTION';
            }
        }

        return $exportFields;
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     */
    protected function getFieldValueExportProps($iblockId)
    {
        $exhelper = $this->getExchangeHelpers()->IblockExchange();

        $propsMode = $this->askField('props_mode',
            (new RadioListField(Locale::getMessage('BUILDER_IblockElementsExport_Properties')))
                ->setInPlanForValues('all', 'none')
                ->addOption('all', Locale::getMessage('BUILDER_SelectAll'))
                ->addOption('none', Locale::getMessage('BUILDER_SelectNone'))
                ->addOption('some', Locale::getMessage('BUILDER_SelectSome')),
        );

        if ($propsMode == 'some') {
            $exportProps = $this->askField('export_props',
                (new CheckboxListField(Locale::getMessage('BUILDER_IblockElementsExport_Properties')))
                    ->setOptions($exhelper->getIblockPropertiesSelect($iblockId), 'CODE', 'TITLE'),
            );
        } elseif ($propsMode == 'all') {
            $exportProps = $exhelper->getIblockPropertiesSelect($iblockId);
            $exportProps = array_column($exportProps, 'CODE');
        } else {
            $exportProps = [];
        }

        return $exportProps;
    }


}
