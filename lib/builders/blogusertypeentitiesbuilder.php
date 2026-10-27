<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class BlogUserTypeEntitiesBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Blog()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Blog'));
        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_BlogUserTypeEntitiesExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('BUILDER_BlogUserTypeEntitiesExport_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();
        $fields = $this->getBlogUserTypeEntities();

        $fieldIds = $this->addFieldAndReturn('entity_fields', [
            'title'    => Locale::getMessage('BUILDER_BlogUserTypeEntitiesExport_entity_fields'),
            'width'    => 350,
            'multiple' => 1,
            'value'    => [],
            'select'   => $this->createSelect($fields, 'ID', 'FIELD_TITLE'),
        ]);

        $entities = $helper->UserTypeEntity()->exportUserTypeEntitiesByIds($fieldIds);

        if (empty($entities)) {
            $this->rebuildField('entity_fields');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('UserTypeEntities'),
            ['entities' => $entities]
        );
    }

    /**
     * @throws HelperException
     */
    protected function getBlogUserTypeEntities(): array
    {
        return array_map(function ($field) {
            $field['FIELD_TITLE'] = sprintf('[%s] %s', $field['FIELD_NAME'], $field['TITLE']);
            return $field;
        }, $this->getHelperManager()->UserTypeEntity()->getUserTypeEntities('BLOG_BLOG'));
    }
}
