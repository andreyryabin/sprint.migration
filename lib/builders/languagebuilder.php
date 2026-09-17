<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class LanguageBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Lang()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_LanguageExport'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@yanochka_dev']),
            Locale::getMessage('BUILDER_LanguageExport_Info')
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();


        $allCults = $helper->Culture()->exportCultures();

        $cultCodes = $this->addFieldAndReturn(
            'cult_codes',
            [
                'title'       => Locale::getMessage('BUILDER_LanguageExport_cult_codes'),
                'placeholder' => '',
                'multiple'    => 1,
                'value'       => [],
                'width'       => 250,
                'select'      => $this->createSelect(
                    $allCults,
                    'CODE',
                    'NAME',
                    true
                ),
            ]
        );


        $allLangs = $helper->Lang()->exportLangs();

        $langIds = $this->addFieldAndReturn(
            'lang_ids',
            [
                'title'       => Locale::getMessage('BUILDER_LanguageExport_lang_ids'),
                'placeholder' => '',
                'multiple'    => 1,
                'value'       => [],
                'width'       => 250,
                'select'      => $this->createSelect(
                    $allLangs,
                    'LID',
                    'NAME',
                    true
                ),
            ]
        );

        $this->createVersionFile(
            Module::getModuleTemplateFile('LanguageExport'),
            [
                'cultures'  => array_filter($allCults, fn($item) => in_array($item['CODE'], $cultCodes)),
                'languages' => array_filter($allLangs, fn($item) => in_array($item['LID'], $langIds)),
            ]
        );
    }

}
