<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\SchemaException;

abstract class AbstractSchema
{
    private $name;

    /** @var VersionConfig */
    private $versionConfig = null;

    private $queue = array();

    public function __construct(VersionConfig $versionConfig, $name) {
        $this->versionConfig = $versionConfig;
        $this->name = $name;
        $this->initialize();
    }

    abstract public function export();

    abstract public function import();

    abstract protected function initialize();

    protected function getSchemaDir($relative = false) {
        $dir = $this->getVersionConfig()->getVal('migration_dir') . '/schema/';
        return ($relative) ? Module::getRelativeDir($dir) : $dir;
    }

    public function getName() {
        return $this->name;
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

    public function getQueue() {
        return $this->queue;
    }

    protected function addToQueue($method, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $method = array_shift($args);
        $this->queue[] = array($method, $args);
    }

    public function executeQueue($item) {
        if (method_exists($this, $item[0])) {
            call_user_func_array(array($this, $item[0]), $item[1]);
        } else {
            $this->outError('method %s not found', $item[0]);
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
}