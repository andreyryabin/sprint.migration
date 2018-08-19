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

    private $fields = array();

    private $templateFile = '';
    private $templateVars = array();

    private $title = '';
    private $description = '';

    protected $params = array();

    private $initRebuild = 0;
    private $initException = 0;
    private $initRestart = 0;

    private $successVersion = '';

    abstract protected function initialize();

    abstract protected function execute();

    public function __construct(VersionConfig $versionConfig, $name, $params = array()) {
        $this->versionConfig = $versionConfig;
        $this->name = $name;
        $this->params = $params;

        $this->setField('builder_name', array(
            'value' => $name,
            'type' => 'hidden',
            'bind' => 1
        ));

        try {
            $this->initialize();

            if (!$this->isBindFields()) {
                $this->rebuild();
            }

        } catch (RebuildException $e) {
            $this->initRebuild = 1;

        } catch (\Exception $e) {
            $this->initException = 1;
            Out::outError('%s: %s', GetMessage('SPRINT_MIGRATION_CREATED_ERROR'), $e->getMessage());
        }
    }

    protected function setField($code, $param = array()) {
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

    protected function requiredField($code, $param = array()) {
        $field = $this->setField($code, $param);

        if (empty($field['value'])) {

            if ($this->isBindField($code)) {
                $this->unbindField($code);
                $this->outError(GetMessage('SPRINT_MIGRATION_SET_REQUIRED_FIELD', array(
                    '#TITLE#' => $field['title']
                )));
            }


            $this->rebuild();
        }

    }

    public function buildAfter(){
        foreach ($this->params as $code => $val) {
            if (!isset($this->fields[$code])) {
                if (is_numeric($val) || is_string($val)) {
                    $this->setField($code, array(
                        'value' => $val,
                        'type' => 'hidden',
                        'bind' => 1
                    ));
                }
            }
        }
    }

    protected function unbindField($code) {
        if (isset($this->fields[$code])){
            $this->fields[$code]['bind'] = 0;
            unset($this->params[$code]);
        }
    }

    protected function isBindField($code) {
        return (isset($this->fields[$code]) && $this->fields[$code]['bind'] == 1);
    }

    protected function isBindFields() {
        foreach ($this->fields as $code => $field) {
            if (!$field['bind']) {
                return false;
            }
        }
        return true;
    }

    public function canShowReset() {
        $foundBind = 0;
        $foundNo = 0;
        foreach ($this->fields as $code => $field) {
            if ($field['bind']) {
                $foundBind ++;
            } else {
                $foundNo ++;
            }
        }

        return ($foundBind >1 && $foundNo > 1 );
    }

    protected function getFieldValue($code, $default = '') {
        return isset($this->fields[$code]) ? $this->fields[$code]['value'] : $default;
    }

    public function bindField($code, $val){
        if (isset($this->fields[$code])){
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

    protected function setTemplateFile($path) {
        $this->templateFile = $path;
    }

    protected function setTemplateVar($code, $value) {
        $this->templateVars[$code] = $value;
    }

    public function render() {
        echo $this->renderFile(Module::getModuleDir() . '/admin/includes/builder_form.php', array(
            'builder' => $this
        ));
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

    public function getVersion(){
        return $this->successVersion;
    }

    public function build() {
        $this->successVersion = '';
        $this->initRestart = 0;

        try {

            if ($this->initRebuild) {
                return false;
            } elseif ($this->initException) {
                return false;
            } else {
                $this->execute();
                if (!$this->isBindFields()) {
                    $this->rebuild();
                }
            }

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

        $description = $this->purifyDescriptionForFile(
            $this->getFieldValue('description')
        );

        $prefix = $this->preparePrefix(
            $this->getFieldValue('prefix')
        );

        $versionName = $prefix . $this->getTimestamp();

        list($extendUse, $extendClass) = explode(' as ', $this->getConfigVal('migration_extend_class'));
        $extendUse = trim($extendUse);
        $extendClass = trim($extendClass);

        if (!empty($extendClass)) {
            $extendUse = 'use ' . $extendUse . ' as ' . $extendClass . ';' . PHP_EOL;
        } else {
            $extendClass = $extendUse;
            $extendUse = '';
        }

        $tplVars = array_merge(array(
            'version' => $versionName,
            'description' => $description,
            'extendUse' => $extendUse,
            'extendClass' => $extendClass,
        ), $this->templateVars);

        if (!is_file($this->templateFile)) {
            $this->templateFile = Module::getModuleDir() . '/templates/version.php';
        }

        $fileName = $this->getVersionFile($versionName);
        $fileContent = $this->renderFile($this->templateFile, $tplVars);

        file_put_contents($fileName, $fileContent);

        if (!is_file($fileName)) {
            Out::outError('%s, error: can\'t create a file "%s"', $versionName, $fileName);
            return false;
        }

        Out::outSuccess(GetMessage('SPRINT_MIGRATION_CREATED_SUCCESS', array(
            '#VERSION#' => $versionName
        )));

        $this->successVersion = $versionName;
        return true;
    }

    protected function preparePrefix($prefix = '') {
        $prefix = trim($prefix);
        if (empty($prefix)) {
            $prefix = $this->getConfigVal('version_prefix');
            $prefix = trim($prefix);
        }

        $default = 'Version';
        if (empty($prefix)) {
            return $default;
        }

        $prefix = preg_replace("/[^a-z0-9_]/i", '', $prefix);
        if (empty($prefix)) {
            return $default;
        }

        if (preg_match('/^\d/', $prefix)) {
            return $default;
        }

        return $prefix;
    }

    protected function purifyDescriptionForFile($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n", "\r"), ' ', $descr);
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

    protected function getVersionFile($versionName) {
        return $this->getConfigVal('migration_dir') . '/' . $versionName . '.php';
    }

    protected function getConfigVal($val, $default = '') {
        return $this->versionConfig->getConfigVal($val, $default);
    }

    protected function getTimestamp() {
        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date('YmdHis');
        date_default_timezone_set($originTz);
        return $ts;
    }

    private function rebuild() {
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
}
