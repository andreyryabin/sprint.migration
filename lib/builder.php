<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\ExchangeHelpers;
use Sprint\Migration\Fields\AbstractField;
use Sprint\Migration\Fields\FieldFactory;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Output\OutputTrait;
use Sprint\Migration\Traits\RestartableTrait;
use Sprint\Migration\Traits\VersionConfigTrait;


abstract class Builder implements RestartableInterface
{
    use RestartableTrait;
    use VersionConfigTrait;
    use OutputTrait;

    private array $info = [
        'title'       => '',
        'description' => '',
        'group'       => '',
    ];

    /** @var AbstractField[] */
    private array $fields = [];

    private HelperManager $helperManager;
    private ExchangeHelpers $exchangeHelpers;

    abstract protected function initialize();

    /**
     * @throws RebuildException
     * @throws RestartException
     */
    abstract protected function execute();

    abstract protected function isBuilderEnabled();

    public function __construct(
        VersionConfig           $versionConfig,
        private readonly string $name,
        array                   $params = []
    )
    {
        $this->setVersionConfig($versionConfig);
        $this->setRestartParams($params);

        $this->initialize();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        try {
            return (bool)$this->isBuilderEnabled();
        } catch (Exception) {
            return false;
        }
    }

    /** @return  AbstractField[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    protected function getHelperManager(): HelperManager
    {
        return $this->helperManager ??= new HelperManager($this->output);
    }

    protected function getExchangeHelpers(): ExchangeHelpers
    {
        return $this->exchangeHelpers ??= new ExchangeHelpers($this->output);
    }

    protected function addField(string $code, array $field): void
    {
        $this->fields[] = FieldFactory::createFromArray($code, $field);
    }

    /**
     * @throws RebuildException
     */
    protected function addFieldAndReturn(string $code, array $field)
    {
        $this->addField($code, $field);

        $value = $this->getFieldValue($code);

        if (empty($value)) {
            $this->rebuildField($code);
        }

        return $value;
    }

    protected function getFieldValue(string $code, $default = '')
    {
        return $this->params[$code] ?? $default;
    }


    /**
     * @throws RebuildException
     * @throws RestartException
     */
    public function start(): void
    {
        $this->checkBindedFields();

        $this->execute();
    }


    /**
     * @throws RebuildException
     */
    private function checkBindedFields(): void
    {
        foreach ($this->fields as $field) {
            if (!isset($this->params[$field->getCode()])) {
                throw new RebuildException();
            }
        }
    }

    /**
     * @throws RebuildException
     */
    protected function rebuildField(string $code)
    {
        if (isset($this->params[$code])) {
            unset($this->params[$code]);
        }
        throw new RebuildException();
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

    protected function createSelect(
        array  $items,
        string $idKey,
        string $titleKey,
        bool   $withIdInTitle = false,
    ): array
    {
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
        array  $items,
        string $idKey,
        string $titleKey,
        string $groupKey = '-'
    ): array
    {
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
            explode($delimiter, trim($string))
        );
    }
}
