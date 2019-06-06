<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;


abstract class AbstractBuilder
{

    use OutTrait {
        out as protected;
        outIf as protected;
        outProgress as protected;
        outNotice as protected;
        outNoticeIf as protected;
        outInfo as protected;
        outInfoIf as protected;
        outSuccess as protected;
        outSuccessIf as protected;
        outWarning as protected;
        outWarningIf as protected;
        outError as protected;
        outErrorIf as protected;
        outDiff as protected;
        outDiffIf as protected;
    }

    private $name;

    /** @var VersionConfig */
    private $versionConfig = null;

    private $info = [
        'title' => '',
        'description' => '',
        'group' => 'default',
    ];

    private $fields = [];

    protected $params = [];

    private $execStatus = '';

    private $enabled = false;


    private $actions = [];

    protected function initialize()
    {
        //your code
    }

    protected function execute()
    {
        //your code
    }

    protected function isBuilderEnabled()
    {
        //your code

        return false;
    }

    public function __construct(VersionConfig $versionConfig, $name, $params = [])
    {
        $this->versionConfig = $versionConfig;
        $this->name = $name;
        $this->enabled = $this->isBuilderEnabled();
        $this->params = $params;

        $this->addField('builder_name', [
            'value' => $this->getName(),
            'type' => 'hidden',
            'bind' => 1,
        ]);
    }

    public function initializeBuilder()
    {
        $this->initialize();
    }

    public function executeBuilder()
    {
        $this->buildExecute();
        $this->buildAfter();
    }

    public function getVersionConfig()
    {
        return $this->versionConfig;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    protected function addField($code, $param = [])
    {
        if (isset($param['multiple']) && $param['multiple']) {
            $value = [];
        } else {
            $value = '';
        }

        $param = array_merge([
            'title' => '',
            'value' => $value,
            'bind' => 0,
        ], $param);

        if (empty($param['title'])) {
            $param['title'] = $code;
        }

        if (isset($this->params[$code])) {
            $param['value'] = $this->params[$code];
            $param['bind'] = 1;
        }

        $this->fields[$code] = $param;
        return $param;
    }

    protected function getFieldValue($code, $default = '')
    {
        if (isset($this->fields[$code]) && $this->fields[$code]['bind'] == 1) {
            return $this->fields[$code]['value'];
        } else {
            return $default;
        }
    }

    public function bindField($code, $val)
    {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 1;
            $this->fields[$code]['value'] = $val;
            $this->params[$code] = $val;
        }
    }

    public function canShowReset()
    {
        return 0;
    }

    protected function renderFile($file, $vars = [])
    {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
        }

        $html = ob_get_clean();

        return $html;
    }

    public function renderHtml()
    {
        echo $this->renderFile(Module::getModuleDir() . '/admin/includes/builder_form.php', [
            'builder' => $this,
        ]);
    }

    public function renderConsole()
    {
        $fields = $this->getFields();
        foreach ($fields as $code => $field) {
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

    public function getRestartParams()
    {
        return $this->params;
    }

    private function buildExecute()
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
            $this->outError('%s: %s', GetMessage('SPRINT_MIGRATION_BUILDER_ERROR'), $e->getMessage());
            return false;
        }

        $this->execStatus = 'success';
        return true;
    }

    private function buildAfter()
    {
        foreach ($this->params as $code => $val) {
            if (!isset($this->fields[$code])) {
                if (is_numeric($val) || is_string($val)) {
                    $this->addField($code, [
                        'value' => $val,
                        'type' => 'hidden',
                        'bind' => 1,
                    ]);
                }
            }
        }
    }

    protected function unbindField($code)
    {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 0;
            unset($this->params[$code]);
        }
    }

    protected function rebuildField($code)
    {
        $this->unbindField($code);
        Throw new RebuildException('rebuild form');
    }

    protected function restart()
    {
        Throw new RestartException('restart form');
    }

    protected function exitWithMessage($msg)
    {
        Throw new BuilderException($msg);
    }

    protected function exitIf($cond, $msg)
    {
        if ($cond) {
            Throw new BuilderException($msg);
        }
    }

    protected function exitIfEmpty($var, $msg)
    {
        if (empty($var)) {
            Throw new BuilderException($msg);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFields()
    {
        return $this->fields;
    }

    protected function redirect($url)
    {
        $this->actions[] = [
            'type' => 'redirect',
            'url' => $url,
        ];
    }

    public function hasActions()
    {
        return !empty($this->actions);
    }

    public function getActions()
    {
        return $this->actions;
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

    public function getGroup()
    {
        return $this->info['group'];
    }

    /** @deprecated */
    protected function requiredField($code, $param = [])
    {
        $this->addField($code, $param);
    }

    /** @deprecated */
    protected function setField($code, $param = [])
    {
        $this->addField($code, $param);
    }

}
