<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\FieldFactory;

trait LegacyBuilderTrait
{
    /**
     * @throws RebuildException
     * @deprecated, меняем на askField(string $code, Field $field)
     */
    protected function addFieldAndReturn(string $code, array $field)
    {
        return $this->askField($code, FieldFactory::createFromArray($field));
    }

    /**
     * @deprecated, меняем на showField(string $code, Field $field)
     */
    protected function addField(string $code, array $field): void
    {
        $this->showField($code, FieldFactory::createFromArray($field));
    }

    /**
     * @throws RebuildException
     * @deprecated, меняем на ResetField(string $code)
     */
    protected function rebuildField(string $code): void
    {
        $this->resetField($code);
    }
}