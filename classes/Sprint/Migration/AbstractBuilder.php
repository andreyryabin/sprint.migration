<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;

abstract class AbstractBuilder
{

    private $name;

    /** @var VersionConfig */
    private $versionConfig = null;

    private $title = '';
    private $description = '';

    private $fields = array();

    protected $params = array();

    private $initRebuild = 0;
    private $initException = 0;
    private $initRestart = 0;

    private $enabled = false;

    protected function initialize() {
        //your code
    }

    protected function execute() {
        //your code
    }

    protected function isBuilderEnabled() {
        //your code

        return false;
    }

    public function __construct(VersionConfig $versionConfig, $name, $params = array(), $initialize = true) {
        $this->versionConfig = $versionConfig;
        $this->name = $name;
        $this->params = $params;
        $this->enabled = $this->isBuilderEnabled();

        if ($this->enabled && $initialize) {
            $this->addField('builder_name', array(
                'value' => $this->getName(),
                'type' => 'hidden',
                'bind' => 1
            ));

            $this->initialize();
        }
    }

    public function isEnabled() {
        return $this->enabled;
    }

    protected function addField($code, $param = array()) {
        $param = array_merge(array(
            'title' => '',
            'value' => '',
            'bind' => 0
        ), $param);

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

    public function canShowReset() {
        $foundBind = 0;
        $foundNo = 0;
        foreach ($this->fields as $code => $field) {
            if ($field['bind']) {
                $foundBind++;
            } else {
                $foundNo++;
            }
        }

        return ($foundBind > 1 && $foundNo > 1);
    }

    protected function getFieldValue($code, $default = '') {
        if (isset($this->fields[$code]) && $this->fields[$code]['bind'] == 1) {
            return $this->fields[$code]['value'];
        } else {
            return $default;
        }
    }

    public function bindField($code, $val) {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 1;
            $this->fields[$code]['value'] = $val;
            $this->params[$code] = $val;
        }
    }

    protected function renderFile($file, $vars = array()) {
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

    public function renderHtml() {
        echo $this->renderFile(Module::getModuleDir() . '/admin/includes/builder_form.php', array(
            'builder' => $this
        ));
    }

    public function renderConsole() {
        $fields = $this->getFields();
        foreach ($fields as $code => $field) {
            if (empty($field['bind'])) {
                $this->renderConsoleField($field);
                $val = fgets(STDIN);
                $val = trim($val);
                $this->bindField($code, $val);
            }
        }
    }

    protected function renderConsoleField($field) {
        if (!empty($field['items'])) {
            fwrite(STDOUT, $field['title'] . PHP_EOL);

            foreach ($field['items'] as $group) {
                fwrite(STDOUT, '---' . $group['title'] . PHP_EOL);

                foreach ($group['items'] as $item) {
                    fwrite(STDOUT, ' > ' . $item['value'] . ' (' . $item['title'] . ')' . PHP_EOL);
                }
            }

            fwrite(STDOUT, 'input value' . ':');
        } else {
            fwrite(STDOUT, $field['title'] . ':');
        }
    }

    public function isRebuild() {
        return $this->initRebuild;
    }

    public function isRestart() {
        return $this->initRestart;
    }

    public function getRestartParams() {
        return $this->params;
    }

    public function build() {
        $this->buildExecute();
        $this->buildAfter();
    }

    private function buildExecute() {
        $this->initRestart = 0;
        $this->initRebuild = 0;
        $this->initException = 0;

        try {

            $this->execute();


        } catch (RestartException $e) {
            $this->initRestart = 1;
            return false;

        } catch (RebuildException $e) {
            $this->initRebuild = 1;
            return false;

        } catch (\Exception $e) {
            $this->initException = 1;
            Out::outError('%s: %s', GetMessage('SPRINT_MIGRATION_CREATED_ERROR'), $e->getMessage());
            return false;
        }

        return true;
    }

    private function buildAfter() {
        foreach ($this->params as $code => $val) {
            if (!isset($this->fields[$code])) {
                if (is_numeric($val) || is_string($val)) {
                    $this->addField($code, array(
                        'value' => $val,
                        'type' => 'hidden',
                        'bind' => 1
                    ));
                }
            }
        }
    }

    protected function getConfigVal($val, $default = '') {
        return $this->versionConfig->getConfigVal($val, $default);
    }

    protected function unbindField($code) {
        if (isset($this->fields[$code])) {
            $this->fields[$code]['bind'] = 0;
            unset($this->params[$code]);
        }
    }

    protected function rebuildField($code) {
        $this->unbindField($code);
        Throw new RebuildException('rebuild form');
    }

    protected function restart() {
        Throw new RestartException('restart form');
    }

    protected function exitWithMessage($msg) {
        Throw new BuilderException($msg);
    }

    protected function exitIf($cond, $msg) {
        if ($cond) {
            Throw new BuilderException($msg);
        }
    }

    protected function exitIfEmpty($var, $msg) {
        if (empty($var)) {
            Throw new BuilderException($msg);
        }
    }

    protected function setTitle($title = '') {
        $this->title = $title;
    }

    protected function setDescription($description = '') {
        $this->description = $description;
    }

    public function getName() {
        return $this->name;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getFields() {
        return $this->fields;
    }

    public function out($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'out'), $args);
    }

    public function outProgress($msg, $val, $total) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outProgress'), $args);
    }

    public function outSuccess($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccess'), $args);
    }

    public function outError($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outError'), $args);
    }


    /** @deprecated */
    protected function requiredField($code, $param = array()) {
        $this->addField($code, $param);
    }

    /** @deprecated */
    protected function setField($code, $param = array()) {
        $this->addField($code, $param);
    }
}
