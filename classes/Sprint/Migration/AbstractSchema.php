<?php

namespace Sprint\Migration;

abstract class AbstractSchema
{
    private $name;

    /** @var VersionConfig */
    private $versionConfig = null;

    private $queue = array();

    protected $params = array();

    protected $testMode = 0;

    protected $info = array(
        'title' => '',
    );

    abstract public function export();

    abstract public function import();

    abstract protected function initialize();

    abstract public function outDescription();

    public function __construct(VersionConfig $versionConfig, $name, $params = array()) {
        $this->versionConfig = $versionConfig;
        $this->name = $name;
        $this->params = $params;

        $this->initialize();
    }

    public function setTestMode($testMode = 1) {
        $this->testMode = $testMode;
    }

    public function getName() {
        return $this->name;
    }

    protected function setTitle($title = '') {
        $this->info['title'] = $title;
    }

    public function getTitle() {
        return $this->info['title'];
    }

    protected function getSchemaDir($relative = false) {
        $dir = Module::getPhpInterfaceDir() . '/schema/';
        return ($relative) ? Module::getRelativeDir($dir) : $dir;
    }

    protected function getSchemaDirname($name, $relative = false) {
        $dir = $this->getSchemaDir() . $name;
        return ($relative) ? Module::getRelativeDir($dir) : $dir;
    }

    protected function getSchemaFile($name, $relative = false) {
        $file = $this->getSchemaDir() . $name . '.json';
        return ($relative) ? Module::getRelativeDir($file) : $file;
    }

    protected function saveSchema($name, $data) {
        $file = $this->getSchemaFile($name);

        $dir = pathinfo($file, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($file,
            json_encode($data, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT)
        );
    }

    protected function loadSchema($name, $merge = array()) {
        $file = $this->getSchemaFile($name);

        if (is_file($file)) {
            $json = file_get_contents($file);
            $json = json_decode($json, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                return array_merge($merge, $json);
            }
        }
        return $merge;
    }

    protected function deleteSchemas($paths) {
        $names = $this->getSchemas($paths);
        foreach ($names as $name) {
            $file = $this->getSchemaFile($name);
            unlink($file);
        }
    }

    protected function getSchemas($paths) {
        $paths = is_array($paths) ? $paths : array($paths);

        $result = array();

        foreach ($paths as $path){
            $dir = $this->getSchemaDirname($path);
            $file = $this->getSchemaFile($path);

            if (is_dir($dir)){
                /* @var $item \SplFileInfo */
                $items = new \DirectoryIterator($dir);
                foreach ($items as $item) {
                    if ($item->isFile() && $item->getExtension() == 'json') {
                        $result[] = $path . $item->getBasename('.json');
                    }
                }
            }

            if (is_file($file)){
                $result[] = $path;
            }
        }

        return $result;
    }

    protected function outSchemas($paths){
        $this->outSuccess('%s сохранена', $this->getTitle());
        $names = $this->getSchemas($paths);
        foreach ($names as $name) {
            $this->out($this->getSchemaFile($name, true));
        }
    }

    protected function loadSchemas($path, $merge = array()) {
        $names = $this->getSchemas($path);

        $schemas = array();
        foreach ($names as $name) {
            $schemas[$name] = $this->loadSchema($name, $merge);
        }

        return $schemas;
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

    protected function outError($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outErrorText'), $args);
    }

    protected function outSuccess($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccessText'), $args);
    }



    protected function outIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array('Sprint\Migration\Out', 'out'), $args);
        }

    }

    protected function outErrorIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array('Sprint\Migration\Out', 'outErrorText'), $args);
        }
    }

    protected function outSuccessIf($cond, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $cond = array_shift($args);
        if ($cond) {
            call_user_func_array(array('Sprint\Migration\Out', 'outSuccessText'), $args);
        }
    }

    protected function getVersionConfig() {
        return $this->versionConfig;
    }

}