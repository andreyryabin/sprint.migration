<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\SchemaException;

abstract class AbstractSchema
{

    /** @var VersionConfig */
    private $versionConfig = null;

    public function __construct(VersionConfig $versionConfig) {
        $this->versionConfig = $versionConfig;
    }

    abstract public function import();

    abstract public function export();

    protected function saveSchema($name, $data) {
        $file = $this->getVersionConfig()->getVal('migration_dir') . '/schema/' . $name . '.json';

        $dir = pathinfo($file, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($file,
            json_encode($data, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT)
        );
    }

    protected function loadSchema($name) {
        $file = $this->getVersionConfig()->getVal('migration_dir') . '/schema/' . $name . '.json';
        if (is_file($file)) {
            $json = file_get_contents($file);
            $json = json_decode($json, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                return $json;
            }
        }
        return array();
    }

    protected function getSchemas($path) {
        $path = trim($path, '/') . '/';

        $dir = $this->getVersionConfig()->getVal('migration_dir') . '/schema/' . $path;

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

    protected function loadSchemas($path) {
        $names = $this->getSchemas($path);

        $schemas = array();
        foreach ($names as $name) {
            $schemas[$name] = $this->loadSchema($name);
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

    protected function getVersionConfig() {
        return $this->versionConfig;
    }
}