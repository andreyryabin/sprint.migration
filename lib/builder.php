<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
use Sprint\Migration\Traits\RestartableTrait;
use Sprint\Migration\Traits\VersionConfigTrait;

class Builder implements RestartableInterface
{
    use HelperManagerTrait;
    use OutTrait;
    use RestartableTrait;
    use VersionConfigTrait;

    private string $name;
    private array $info = [
        'title' => '',
        'description' => '',
        'group' => '',
    ];
    private array $fields = [];
    private string $execStatus = '';

    public function __construct(VersionConfig $versionConfig, string $name, array $params = [])
    {
        $this->name = $name;

        $this->setVersionConfig($versionConfig);
        $this->setRestartParams($params);

        $this->addField('builder_name', [
            'type' => 'hidden',
            'value' => $name
        ]);
    }

    protected function initialize(){
        //your code
    }

    /**
     * @throws RestartException|RebuildException|Exception
     */
    protected function execute(){
        //your code
    }

    protected function isBuilderEnabled()
    {
        return false;
    }

    public function isEnabled()
    {
        try {
            return $this->isBuilderEnabled();
        } catch (Exception) {
            return false;
        }
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
                'bind' => 0,
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

    protected function renderFile($file, $vars = []): string
    {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        if (is_file($file)) {
            include $file;
        }

        return ob_get_clean();
    }

    public function renderHtml(): void
    {
        echo $this->renderFile(
            Module::getModuleDir() . '/admin/includes/builder_form.php', [
                'builder' => $this,
            ]
        );
    }

    public function renderConsole(): void
    {
        foreach ($this->fields as $code => $field) {
            if (empty($field['bind'])) {
                $val = Out::input($field);

                $this->fields[$code]['bind'] = 1;
                $this->fields[$code]['value'] = $val;
                $this->params[$code] = $val;

            }
        }
    }

    public function isRebuild(): bool
    {
        return ($this->execStatus == 'rebuild');
    }

    public function isRestart(): bool
    {
        return ($this->execStatus == 'restart');
    }

    public function buildInitialize(): void
    {
        $this->initialize();
    }

    public function buildExecute(): bool
    {
        $this->execStatus = '';

        try {
            $this->execute();
        } catch (RestartException $e) {
            $this->execStatus = 'restart';
            return false;
        } catch (RebuildException $e) {
            $this->execStatus = 'rebuild';
            return false;
        } catch (Exception $e) {
            $this->execStatus = 'error';
            $this->outException($e);
            $this->params = [];
            return false;
        }

        $this->execStatus = 'success';
        $this->params = [];
        return true;
    }

    public function buildAfter(): void
    {
        foreach ($this->params as $code => $val) {
            if (!isset($this->fields[$code])) {
                if (is_numeric($val) || is_string($val)) {
                    $this->addField($code, ['type' => 'hidden']);
                }
            }
        }
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(): array
    {
        return $this->fields;
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

    public function hasDescription(): bool
    {
        return !empty($this->info['description']);
    }

    public function getGroup(): string
    {
        return $this->info['group'] ?? Locale::getMessage('BUILDER_GROUP_Tools');
    }

    protected function createSelect(
        array  $items,
        string $idKey,
        string $titleKey
    ): array
    {
        $select = [];
        foreach ($items as $item) {
            $itemId = $item[$idKey];
            $select[$itemId] = [
                'title' => $item[$titleKey],
                'value' => $itemId,
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
}
