<?php

namespace Sprint\Migration;

use Exception;
use ReflectionClass;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Output\ConsoleOutput;
use Sprint\Migration\Output\OutputTrait;
use Sprint\Migration\Traits\RestartableTrait;
use Sprint\Migration\Traits\VersionConfigTrait;


abstract class Builder implements RestartableInterface
{
    use RestartableTrait;
    use VersionConfigTrait;
    use OutputTrait;

    private string $name;
    private array $info = [
        'title'       => '',
        'description' => '',
        'group'       => '',
    ];
    private array $fields = [];

    private HelperManager $helperManager;

    abstract protected function initialize();

    /**
     * @throws RebuildException
     * @throws RestartException
     */
    abstract protected function execute();

    abstract protected function isBuilderEnabled();

    public function __construct(VersionConfig $versionConfig)
    {
        $this->setVersionConfig($versionConfig);

        $this->initialize();
    }

    /**
     * @deprecated
     */
    public function getName(): string
    {
        return $this->name ??= (new ReflectionClass($this))->getShortName();
    }

    public function getClassName(): string
    {
        return static::class;
    }

    public function isEnabled(): bool
    {
        try {
            return (bool)$this->isBuilderEnabled();
        } catch (Exception) {
            return false;
        }
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    protected function getHelperManager(): HelperManager
    {
        return $this->helperManager ??= new HelperManager($this->getOutput());
    }


    protected function addField($code, $param = []): void
    {
        if (isset($param['multiple']) && $param['multiple']) {
            $value = [];
        } else {
            $value = '';
        }

        $param = array_merge(
            [
                'title' => '',
                'value' => $value,
                'bind'  => 0,
            ], $param
        );

        if (empty($param['title'])) {
            $param['title'] = $code;
        }

        if (isset($this->params[$code])) {
            $param['value'] = $this->params[$code];
            $param['bind'] = 1;
        }

        $this->fields[$code] = $param;
    }

    /**
     * @throws RebuildException
     */
    protected function addFieldAndReturn(string $code, array $param = [])
    {
        $this->addField($code, $param);

        $value = $this->getFieldValue($code);
        if (empty($value)) {
            $this->rebuildField($code);
        }

        if (isset($param['multiple']) && $param['multiple']) {
            $value = is_array($value) ? $value : [$value];
        }

        return $value;
    }

    protected function getFieldValue(string $code, $default = '')
    {
        if (isset($this->fields[$code]) && $this->fields[$code]['bind'] == 1) {
            return $this->fields[$code]['value'];
        } else {
            return $default;
        }
    }


    /**
     * @throws RebuildException
     * @throws RestartException
     */
    public function start()
    {
        $this->execute();
    }

    /**
     * @throws RebuildException
     */
    protected function rebuildField(string $code)
    {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 0;
        }

        if (isset($this->params[$code])) {
            unset($this->params[$code]);
        }

        throw new RebuildException('rebuild form');
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
            $itemValue = $item[$idKey];
            $itemTitle = $withIdInTitle ? "[$itemValue] $item[$titleKey]" : $item[$titleKey];

            $select[$itemValue] = [
                'title' => $itemTitle,
                'value' => $itemValue,
            ];
        }
        return $select;
    }

    protected function createSelectWithGroups(
        array  $items,
        string $idKey,
        string $titleKey,
        string $groupKey = '-'
    ): array
    {
        $select = [];
        foreach ($items as $item) {
            $groupId = $item[$groupKey] ?? 'Group';
            $itemId = $item[$idKey];

            if (!isset($select[$groupId])) {
                $select[$groupId] = [
                    'title' => $groupId,
                    'items' => [],
                ];
            }

            $select[$groupId]['items'][] = [
                'title' => $item[$titleKey],
                'value' => $itemId,
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
