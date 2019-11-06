<?php

namespace Sprint\Migration\Builders;

use CUserTypeEntity;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserTypeEntitiesBuilder extends VersionBuilder
{

    protected function isBuilderEnabled()
    {
        return true;
    }


    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_UserTypeEntities1'));
        $this->setDescription(Locale::getMessage('BUILDER_UserTypeEntities2'));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $this->addField('type_codes', [
            'title' => Locale::getMessage('BUILDER_UserTypeEntities_EntityId'),
            'placeholder' => '',
            'width' => 250,
            'multiple' => 1,
            'items' => $this->getEntitiesStructure(),
            'value' => [],
        ]);

        $typeCodes = $this->getFieldValue('type_codes');
        if (empty($typeCodes)) {
            $this->rebuildField('type_codes');
        }

        $typeCodes = is_array($typeCodes) ? $typeCodes : [$typeCodes];

        $entities = [];

        foreach ($typeCodes as $fieldId) {
            $entity = $helper->UserTypeEntity()->exportUserTypeEntity($fieldId);
            if (!empty($entity)) {
                $entities[] = $entity;
            }
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/UserTypeEntities.php',
            [
                'entities' => $entities,
            ]
        );
    }


    protected function getEntitiesStructure()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = CUserTypeEntity::GetList([], []);

        $structure = [];
        while ($item = $dbRes->Fetch()) {
            $entId = $item['ENTITY_ID'];

            if (!isset($structure[$entId])) {
                $structure[$entId] = [
                    'title' => $entId,
                    'items' => [],
                ];
            }

            $structure[$entId]['items'][] = [
                'title' => $item['FIELD_NAME'],
                'value' => $item['ID'],
            ];
        }

        return $structure;
    }
}