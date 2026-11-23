<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class LanguageBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Lang()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_LanguageExport'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@yanochka_dev']),
            Locale::getMessage('BUILDER_LanguageExport_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();
        $allCults = $helper->Culture()->exportCultures();

        $cultCodes = $this->askField('cult_codes',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_LanguageExport_cult_codes'))
                ->setOptions($allCults, 'CODE', 'NAME'),
        );

        $allLangs = $helper->Lang()->exportLangs();

        $langIds = $this->askField('lang_ids',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_LanguageExport_lang_ids'))
                ->setOptions($allLangs, 'LID', 'NAME'),
        );

        $this->confirmVersionPlan();

        $this->createVersionFile(
            Module::getModuleTemplateFile('LanguageExport'),
            [
                'cultures'  => array_filter($allCults, fn($item) => in_array($item['CODE'], $cultCodes)),
                'languages' => array_filter($allLangs, fn($item) => in_array($item['LID'], $langIds)),
            ],
        );
    }

}
