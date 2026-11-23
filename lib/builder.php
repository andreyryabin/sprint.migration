<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\ExchangeHelpers;
use Sprint\Migration\Fields\BuilderField;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Output\OutputTrait;
use Sprint\Migration\Traits\LegacyBuilderTrait;
use Sprint\Migration\Traits\RestartableTrait;
use Sprint\Migration\Traits\VersionConfigTrait;


abstract class Builder implements RestartableInterface
{
    use OutputTrait;
    use RestartableTrait;
    use VersionConfigTrait;
    use LegacyBuilderTrait;

    private array $info = [
        'title'       => '',
        'description' => '',
        'group'       => '',
    ];

    /** @var BuilderField[] */
    private array $fields = [];

    private HelperManager $helperManager;
    private ExchangeHelpers $exchangeHelpers;

    public function __construct(
        VersionConfig $versionConfig,
        private readonly string $name,
        array $params = [],
    ) {
        $this->setVersionConfig($versionConfig);
        $this->setRestartParams($params);

        $this->initialize();
    }


    abstract protected function initialize();

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        try {
            return (bool) $this->isBuilderEnabled();
        } catch (Exception) {
            return false;
        }
    }

    abstract protected function isBuilderEnabled();

    /** @return  BuilderField[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    /** @return  BuilderField[] */
    public function getUnbindFields(): array
    {
        return array_filter(
            $this->fields,
            fn(string $code) => !isset($this->params[$code]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @throws RebuildException
     */
    private function checkUnbindFields(): void
    {
        foreach ($this->fields as $code => $field) {
            if (!isset($this->params[$code])) {
                throw new RebuildException();
            }
        }
    }

    public function cleanParamsSkipFields(): void
    {
        $cleaned = [];
        foreach ($this->getFields() as $code => $field) {
            $cleaned[$code] = $this->params[$code] ?? $field->getDefaultValue();
        }
        $this->setRestartParams($cleaned);
    }

    public function mergeParamsWithFields(): void
    {
        foreach ($this->getFields() as $code => $field) {
            $this->params[$code] ??= $field->getDefaultValue();
        }
    }

    /**
     * @throws RebuildException
     * @throws RestartException
     */
    public function start(): void
    {
        $this->checkUnbindFields();

        $this->execute();
    }


    /**
     * @throws RebuildException
     * @throws RestartException
     */
    abstract protected function execute();

    public function getTitle(): string
    {
        return $this->info['title'];
    }

    public function getDescription(): string
    {
        return $this->info['description'];
    }

    public function getGroup(): string
    {
        return $this->info['group'] ?? Locale::getMessage('BUILDER_GROUP_Tools');
    }

    protected function getHelperManager(): HelperManager
    {
        return $this->helperManager ??= new HelperManager($this->output);
    }

    protected function getExchangeHelpers(): ExchangeHelpers
    {
        return $this->exchangeHelpers ??= new ExchangeHelpers($this->output);
    }

    /**
     * @throws MigrationException
     */
    protected function getVersionManager(): VersionManager
    {
        return new VersionManager($this->getVersionConfig(), $this->output);
    }

    /**
     * Показать поле в форме (без возврата значения).
     * Используйте, когда нужно добавить несколько несвязанных полей.
     */
    protected function showField(string $code, BuilderField $field): void
    {
        $this->fields[$code] = $field;
    }

    /**
     * Показать поле и вернуть его значение (спросить пользователя).
     * Форма не пропустит этот шаг, пока поле не будет заполнено
     * @throws RebuildException
     */
    protected function askField(string $code, BuilderField $field): mixed
    {
        $field->setRequired(true);

        $this->showField($code, $field);

        $value = $this->getFieldValue($code);

        if (empty($value)) {
            $this->resetField($code);
        }

        return $value;
    }

    /**
     * Сбрасывает значение поля и переспрашивает пользователя.
     * Вызывает перестройку форму в админке или повтор ввода в консоли
     * @throws RebuildException
     */
    protected function resetField(string $code)
    {
        if (isset($this->params[$code])) {
            unset($this->params[$code]);
        }

        throw new RebuildException();
    }


    protected function getField(string $code): ?BuilderField
    {
        return $this->fields[$code] ?? null;
    }

    protected function getFieldValue(string $code, $default = '')
    {
        return $this->params[$code] ?? $default;
    }

    public function setFieldValue(string $code, mixed $value): void
    {
        $this->params[$code] = $value;
    }


    protected function setTitle(string $title = ''): void
    {
        $this->info['title'] = $title;
    }

    protected function setDescription(string $description = ''): void
    {
        $this->info['description'] = $description;
    }

    protected function setGroup(string $group = ''): void
    {
        $this->info['group'] = $group;
    }

    protected function createSelect(
        array $items,
        string $idKey,
        string $titleKey,
        bool $withIdInTitle = false,
    ): array {
        $select = [];
        foreach ($items as $item) {
            $value = $item[$idKey];
            $title = $withIdInTitle ? "[$value] $item[$titleKey]" : $item[$titleKey];

            $select[$value] = [
                'title' => $title,
                'value' => $value,
            ];
        }
        return $select;
    }

    protected function createStructure(
        array $items,
        string $idKey,
        string $titleKey,
        string $groupKey = '-',
    ): array {
        $select = [];
        foreach ($items as $item) {
            $groupId = $item[$groupKey] ?? 'Group';

            $value = $item[$idKey];
            $title = $item[$titleKey];

            if (!isset($select[$groupId])) {
                $select[$groupId] = [
                    'title' => $groupId,
                    'items' => [],
                ];
            }

            $select[$groupId]['items'][] = [
                'title' => $title,
                'value' => $value,
            ];
        }

        return $select;
    }

    protected function explodeString(string $string, string $delimiter = ' '): array
    {
        return array_filter(
            explode($delimiter, trim($string)),
        );
    }
}
