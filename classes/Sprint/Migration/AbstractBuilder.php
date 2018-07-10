<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\RebuildException;

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

    private $postvars = array();
    private $initRebuild = 0;
    private $initException = 0;


    abstract protected function initialize();

    abstract protected function execute();

    public function __construct(VersionConfig $versionConfig, $name, $postvars = array()) {
        $this->versionConfig = $versionConfig;
        $this->name = $name;

        $this->bind($postvars);

        try {
            $this->initialize();

        } catch (RebuildException $e) {
            $this->initRebuild = 1;

        } catch (\Exception $e) {
            Out::outError('%s: %s', GetMessage('SPRINT_MIGRATION_CREATED_ERROR'), $e->getMessage());
            $this->initException = 1;
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

        if (isset($this->postvars[$code])) {
            $param['value'] = $this->postvars[$code];
            $param['bind'] = 1;
        }

        $this->fields[$code] = $param;
        return $param;
    }

    protected function requiredField($code, $param = array()) {
        $field = $this->setField($code, $param);

        if (empty($field['value'])) {
            $this->rebuild();
        }

    }


    protected function getFieldValue($code, $default = '') {
        return isset($this->fields[$code]) ? $this->fields[$code]['value'] : $default;
    }

    public function bind($postvars = array()) {
        $this->postvars = array_merge($this->postvars, $postvars);
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

    public function build() {
        try {

            if ($this->initRebuild) {
                return -1;
            } elseif ($this->initException) {
                return false;
            } else {
                $this->execute();
            }


        } catch (RebuildException $e) {
            return -1;

        } catch (\Exception $e) {
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
        return $versionName;
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

    protected function rebuild() {
        Throw new RebuildException('rebuild form');
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
}
