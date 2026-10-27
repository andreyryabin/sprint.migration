<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class SaleDiscountBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->SaleDiscount()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Sale'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_SaleDiscount'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('DEVELOPER_URI', ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/181']),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     * @throws HelperException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $discountIds = $this->addFieldAndReturn('discounts', [
            'title'    => Locale::getMessage('BUILDER_SaleDiscount_Discounts'),
            'width'    => 350,
            'multiple' => 1,
            'value'    => [],
            'items'    => $this->getDiscountsSelect(),
        ]);

        $items = $helper->SaleDiscount()->exportDiscounts($discountIds);

        if (empty($items)) {
            $this->rebuildField('discounts');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('SaleDiscountExport'),
            [
                'items' => $items,
            ]
        );
    }

    protected function getDiscountsSelect(): array
    {
        $items = $this->getHelperManager()->SaleDiscount()->getDiscounts();
        $items = $this->getHelperManager()->SaleDiscount()->ensureDiscountXmlIds($items);

        $items = array_map(function ($item) {
            $item['TITLE'] = '[' . $item['XML_ID'] . '] ' . $item['NAME'];
            return $item;
        }, $items);

        return $this->createSelectWithGroups($items, 'ID', 'TITLE', 'LID');
    }
}
