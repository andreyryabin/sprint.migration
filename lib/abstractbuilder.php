<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Traits\ExitMessageTrait;
use Sprint\Migration\Traits\HelperManagerTrait;

abstract class AbstractBuilder extends ExchangeEntity
{
    use HelperManagerTrait;
    use ExitMessageTrait;
    use OutTrait;

    private $name;
    private $info       = [
        'title'       => '',
        'description' => '',
        'group'       => '',
    ];
    private $fields     = [];
    private $execStatus = '';

    public function __construct(VersionConfig $versionConfig, $name, $params = [])
    {
        $this->name = $name;

        $this->setVersionConfig($versionConfig);
        $this->setRestartParams($params);

        $this->addFieldHidden('builder_name', $this->getName());
    }

    abstract protected function initialize();

    /**
     * @throws RestartException|RebuildException|Exception
     * @return mixed
     */
    abstract protected function execute();

    protected function isBuilderEnabled()
    {
        return false;
    }

    public function initializeBuilder()
    {
        $this->initialize();
    }

    public function isEnabled()
    {
        try {
            return $this->isBuilderEnabled();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return ExchangeManager
     */
    protected function getExchangeManager()
    {
        return new ExchangeManager($this);
    }

    protected function addField($code, $param = [])
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
     * @param string $code
     * @param array  $param
     *
     * @throws RebuildException
     * @return mixed
     */
    protected function addFieldAndReturn($code, $param = [])
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

    protected function addFieldHidden($code, $val)
    {
        $this->params[$code] = $val;
        $this->addField($code, ['type' => 'hidden']);
    }

    protected function getFieldValue($code, $default = '')
    {
        if (isset($this->fields[$code]) && $this->fields[$code]['bind'] == 1) {
            return $this->fields[$code]['value'];
        } else {
            return $default;
        }
    }

    protected function bindField($code, $val)
    {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 1;
            $this->fields[$code]['value'] = $val;
            $this->params[$code] = $val;
        }
    }

    protected function renderFile($file, $vars = [])
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

    public function renderHtml()
    {
        echo $this->renderFile(
            Module::getModuleDir() . '/admin/includes/builder_form.php', [
                'builder' => $this,
            ]
        );
    }

    public function renderConsole()
    {
        foreach ($this->getFields() as $code => $field) {
            if (empty($field['bind'])) {
                $val = Out::input($field);
                $this->bindField($code, $val);
            }
        }
    }

    public function isRebuild()
    {
        return ($this->execStatus == 'rebuild');
    }

    public function isRestart()
    {
        return ($this->execStatus == 'restart');
    }

    public function buildExecute()
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

    public function buildAfter()
    {
        foreach ($this->params as $code => $val) {
            if (!isset($this->fields[$code])) {
                if (is_numeric($val) || is_string($val)) {
                    $this->addFieldHidden($code, $val);
                }
            }
        }
    }

    protected function unbindField($code)
    {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 0;
        }

        if (isset($this->params[$code])) {
            unset($this->params[$code]);
        }
    }

    protected function removeField($code)
    {
        if (isset($this->params[$code])) {
            unset($this->params[$code]);
        }

        if (isset($this->fields[$code])) {
            unset($this->fields[$code]);
        }
    }

    /**
     * @param $code
     *
     * @throws RebuildException
     */
    protected function rebuildField($code)
    {
        $this->unbindField($code);
        throw new RebuildException('rebuild form');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFields()
    {
        return $this->fields;
    }

    protected function setTitle($title = '')
    {
        $this->info['title'] = $title;
    }

    protected function setDescription($description = '')
    {
        $this->info['description'] = $description;
    }

    protected function setGroup($group = '')
    {
        $this->info['group'] = $group;
    }

    public function getTitle()
    {
        return $this->info['title'];
    }

    public function getDescription()
    {
        return $this->info['description'];
    }

    public function hasDescription()
    {
        return !empty($this->info['description']);
    }

    public function getGroup()
    {
        return $this->info['group'] ?? Locale::getMessage('BUILDER_GROUP_Tools');
    }

    /** @param $code
     * @param array $param
     *
     * @deprecated
     */
    protected function requiredField($code, $param = [])
    {
        $this->addField($code, $param);
    }

    /** @param $code
     * @param array $param
     *
     * @deprecated
     */
    protected function setField($code, $param = [])
    {
        $this->addField($code, $param);
    }

    protected function createSelect(
        array $items,
        string $idKey,
        string $titleKey
    ): array {
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
        array $items,
        string $idKey,
        string $titleKey,
        string $groupKey = '-'
    ): array {
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
