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

    abstract protected function isBuilderEnabled();

    abstract protected function initialize();

    /**
     * @throws RebuildException
     * @throws RestartException
     */
    abstract protected function execute();

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

    /** @return  BuilderField[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @throws RebuildException
     */
    public function resetBuilder(): void
    {
        $this->fields = [];
        $this->params = [];
        $this->initialize();
        throw new RebuildException();
    }

    /**
     * @throws RebuildException
     * @throws RestartException
     */
    public function start(): void
    {
        $this->execute();
    }

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

        //пока нет необходимости делать метод checkRequiredValue
        if (!empty($value)) {
            return $value;
        }

        $this->resetField($code);
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
}
