<?php

namespace Sprint\Migration;

use DirectoryIterator;
use Exception;
use SplFileInfo;
use Sprint\Migration\Traits\HelperManagerTrait;

abstract class AbstractSchema extends ExchangeEntity
{
    use HelperManagerTrait;
    use OutTrait;

    private $name;
    /** @var VersionConfig */
    private   $versionConfig;
    private   $queue     = [];
    protected $testMode  = 0;
    protected $info      = [
        'title' => '',
    ];
    private   $filecache = [];

    abstract public function export();

    abstract public function import();

    abstract protected function initialize();

    abstract public function outDescription();

    abstract public function getMap();

    public function __construct(VersionConfig $versionConfig, $name, $params = [])
    {
        $this->name = $name;

        $this->setVersionConfig($versionConfig);
        $this->setRestartParams($params);

        $this->initialize();
    }

    protected function isBuilderEnabled()
    {
        //your code

        return false;
    }

    public function setTestMode($testMode = 1)
    {
        $this->testMode = ($testMode) ? 1 : 0;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isEnabled()
    {
        try {
            return $this->isBuilderEnabled();
        } catch (Exception $e) {
            return false;
        }
    }

    public function isModified()
    {
        $algo = $this->getVersionConfig()->getVal('migration_hash_algo');

        $opt = strtolower('schema_' . $this->getName());
        $oldhash = Module::getDbOption($opt);

        $data = $this->loadSchemas($this->getMap());
        $newhash = hash($algo, serialize($data));

        return ($newhash != $oldhash);
    }

    public function setModified()
    {
        $algo = $this->getVersionConfig()->getVal('migration_hash_algo');

        $data = $this->loadSchemas($this->getMap());
        $newhash = hash($algo, serialize($data));

        $opt = strtolower('schema_' . $this->getName());
        Module::setDbOption($opt, $newhash);
    }

    protected function setTitle($title = '')
    {
        $this->info['title'] = $title;
    }

    public function getTitle()
    {
        return $this->info['title'];
    }

    public function outTitle($fullname = true)
    {
        $title = ($fullname) ? $this->getName() . ' (' . $this->getTitle() . ')' : $this->getTitle();
        if ($this->isModified()) {
            $this->out('[new]' . $title . '[/]');
        } else {
            $this->out('[installed]' . $title . '[/]');
        }
    }

    protected function getSchemaDir()
    {
        $dir = $this->getVersionConfig()->getVal('migration_dir');

        return $dir . '.schema';
    }

    protected function getSchemaSubDir($name)
    {
        return $this->getSchemaDir() . DIRECTORY_SEPARATOR . $name;
    }

    protected function getSchemaFile($name, $absolute = true)
    {
        $root = $absolute ? $this->getSchemaDir() . DIRECTORY_SEPARATOR : '';

        return $root . $name . '.json';
    }

    /**
     * @param $name
     * @param $data
     *
     * @throws Exception
     */
    protected function saveSchema($name, $data)
    {
        $file = $this->getSchemaFile($name);
        Module::createDir(dirname($file));

        file_put_contents(
            $file,
            json_encode($data, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT)
        );
    }

    public function deleteSchemaFiles()
    {
        $names = $this->getSchemas($this->getMap());
        foreach ($names as $name) {
            $file = $this->getSchemaFile($name);
            unlink($file);
        }
    }

    public function outSchemaFiles()
    {
        $files = [];
        $names = $this->getSchemas($this->getMap());
        foreach ($names as $name) {
            $files[] = $this->getSchemaFile($name, false);
        }

        if (!empty($files)) {
            $this->outNotice(
                Locale::getMessage(
                    'ERR_SCHEMA_CREATED',
                    [
                        '#NAME#' => $this->getTitle(),

                    ]
                )
            );
            foreach ($files as $file) {
                $this->out($file);
            }
        } else {
            $this->outWarning(
                Locale::getMessage(
                    'ERR_SCHEMA_EMPTY',
                    [
                        '#NAME#' => $this->getTitle(),

                    ]
                )
            );
        }
    }

    protected function getSchemas($map)
    {
        $map = is_array($map) ? $map : [$map];
        $result = [];

        foreach ($map as $path) {
            $dir = $this->getSchemaSubDir($path);
            $file = $this->getSchemaFile($path);

            if (is_dir($dir)) {
                /* @var $item SplFileInfo */
                $items = new DirectoryIterator($dir);
                foreach ($items as $item) {
                    if ($item->isFile() && $item->getExtension() == 'json') {
                        $result[] = $path . $item->getBasename('.json');
                    }
                }
            }

            if (is_file($file)) {
                $result[] = $path;
            }
        }

        return $result;
    }

    protected function loadSchema($name, $merge = [])
    {
        if (!isset($this->filecache[$name])) {
            $this->filecache[$name] = $this->loadSchemaFile($name);
        }

        return array_merge($merge, $this->filecache[$name]);
    }

    private function loadSchemaFile($name)
    {
        $file = $this->getSchemaFile($name);

        if (!is_file($file)) {
            return [];
        }

        $json = file_get_contents($file);
        $json = json_decode($json, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            return [];
        }

        if (!is_array($json)) {
            return [];
        }

        return $json;
    }

    protected function loadSchemas($map, $merge = [])
    {
        $names = $this->getSchemas($map);
        $schemas = [];
        foreach ($names as $name) {
            $schemas[$name] = $this->loadSchema($name, $merge);
        }
        return $schemas;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    protected function addToQueue($method, ...$vars)
    {
        $args = func_get_args();
        $method = array_shift($args);
        $this->queue[] = [$method, $args];
    }

    public function executeQueue($item)
    {
        if (method_exists($this, $item[0])) {
            call_user_func_array([$this, $item[0]], $item[1]);
        } else {
            $this->outError(
                Locale::getMessage(
                    'ERR_METHOD_NOT_FOUND', [
                        '#NAME#' => $item[0],
                    ]
                )
            );
        }
    }
}
