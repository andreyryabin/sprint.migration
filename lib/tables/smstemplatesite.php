<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\SiteTable;
use Bitrix\Main\SystemException;
use Exception;

class SmsTemplateSiteTable extends Data\DataManager
{
    use Data\Internal\DeleteByFilterTrait;

    public static function getTableName(): string
    {
        return 'b_sms_template_site';
    }

    public static function getMap(): array
    {
        return array(
            'TEMPLATE_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
            ),

            'SITE_ID' => array(
                'data_type' => 'string',
                'primary' => true,
            ),
        );
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws Exception
     */
    public static function updateSites(int $templateId, array $siteIds): void
    {
        self::deleteByFilter(['=TEMPLATE_ID' => $templateId]);

        $dbres = SiteTable::getList([
            'select' => ['LID'],
            'filter' => ['=LID' => $siteIds],
        ]);

        while ($arResultSite = $dbres->fetch()) {
            self::add([
                'TEMPLATE_ID' => $templateId,
                'SITE_ID' => $arResultSite['LID'],
            ]);
        }
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public static function getSites(int $templateId): array
    {
        $sites = self::getList([
            'select' => ['*', '' => 'SITE.*'],
            'filter' => ['=TEMPLATE_ID' => $templateId],
            'runtime' => [
                'SITE' => [
                    'data_type' => 'Bitrix\Main\Site',
                    'reference' => ['=this.SITE_ID' => 'ref.LID'],
                ],
            ],
        ])->fetchAll();

        return array_filter(array_column($sites, 'LID'));
    }
}
