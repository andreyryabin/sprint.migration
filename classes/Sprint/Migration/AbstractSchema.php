<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\SchemaException;

abstract class AbstractSchema
{

    /** @var VersionConfig */
    private $versionConfig = null;

    protected $params = array();

    public function __construct(VersionConfig $versionConfig, $params = array()) {
        $this->versionConfig = $versionConfig;
        $this->params = $params;
    }

    abstract protected function import($execute);

    abstract protected function export($execute);

    public function testImport() {
        $this->import(0);
    }

    public function testExport() {
        $this->export(0);
    }

    public function runImport() {
        $this->import(1);
    }

    public function runExport() {
        $this->export(1);
    }

    protected function getSchemaDir($relative = false) {
        $dir = $this->getVersionConfig()->getVal('migration_dir') . '/schema/';
        return ($relative) ? Module::getRelativeDir($dir) : $dir;
    }



    protected function saveSchema($name, $data) {
        $file = $this->getSchemaDir() . $name . '.json';

        $dir = pathinfo($file, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($file,
            json_encode($data, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT)
        );
    }

    protected function loadSchema($name, $merge = array()) {
        $file = $this->getSchemaDir() . $name . '.json';
        if (is_file($file)) {
            $json = file_get_contents($file);
            $json = json_decode($json, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                return array_replace_recursive($merge, $json);
            }
        }
        return $merge;
    }

    protected function deleteSchema($name) {
        $file = $this->getSchemaDir() . $name . '.json';
        if (is_file($file)) {
            unlink($file);
        }
    }

    protected function deleteSchemas($path) {
        $names = $this->getSchemas($path);

        foreach ($names as $name) {
            $this->deleteSchema($name);
        }
    }

    protected function getSchemas($path) {
        $path = trim($path, '/') . '/';

        $dir = $this->getSchemaDir() . $path;

        $result = array();

        if (is_dir($dir)) {
            /* @var $item \SplFileInfo */
            $items = new \DirectoryIterator($dir);
            foreach ($items as $item) {
                if ($item->isFile() && $item->getExtension() == 'json') {
                    $name = $item->getBasename('.json');
                    $result[] = $path . $name;
                }
            }
        }

        return $result;
    }

    protected function loadSchemas($path, $merge = array()) {
        $names = $this->getSchemas($path);

        $schemas = array();
        foreach ($names as $name) {
            $schemas[$name] = $this->loadSchema($name, $merge);
        }

        return $schemas;
    }

    protected function exitWithMessage($msg) {
        Throw new SchemaException($msg);
    }

    protected function exitIf($cond, $msg) {
        if ($cond) {
            Throw new SchemaException($msg);
        }
    }

    protected function exitIfEmpty($var, $msg) {
        if (empty($var)) {
            Throw new SchemaException($msg);
        }
    }

    protected function out($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'out'), $args);
    }

    protected function outProgress($msg, $val, $total) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outProgress'), $args);
    }

    protected function outSuccess($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccessText'), $args);
    }

    protected function outError($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outErrorText'), $args);
    }

    protected function getVersionConfig() {
        return $this->versionConfig;
    }

    public function getParams() {
        return $this->params;
    }
}