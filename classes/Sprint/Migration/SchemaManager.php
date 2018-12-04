<?php

namespace Sprint\Migration;

class SchemaManager
{
    /** @var VersionConfig */
    private $versionConfig = null;

    protected $params = array();

    public function __construct() {
        $this->versionConfig = new VersionConfig('cfg');
    }


    public function export() {
        $schemas = $this->getVersionConfig()->getVal('version_schemas');
        $schemas = array_keys($schemas);

        foreach ($schemas as $name) {
            $this->createSchema($name)->export();
        }
    }

    protected function getVersionConfig() {
        return $this->versionConfig;
    }

    /** @return AbstractSchema */
    protected function createSchema($name) {
        $schemas = $this->getVersionConfig()->getVal('version_schemas');
        $class = $schemas[$name];

        return new $class($this->getVersionConfig(), $name);
    }

    public function import() {
        $schemas = $this->getVersionConfig()->getVal('version_schemas');
        $schemas = array_keys($schemas);

        if (!isset($this->params['schema'])) {
            $this->params['schema'] = 0;
        }

        if (isset($schemas[$this->params['schema']])) {

            $schema = $this->createSchema(
                $schemas[$this->params['schema']]
            );

            $this->executeSchema($schema);
            $this->params['schema']++;
            $this->restart();
        }

        unset($this->params['schema']);
    }

    protected function executeSchema(AbstractSchema $schema) {
        if (!isset($this->params['index'])) {
            $this->params['index'] = 0;
            $schema->import();
            $this->saveQueue(
                $schema->getName(),
                $schema->getQueue()
            );
        }

        $queue = $this->loadQueue(
            $schema->getName()
        );

        $queueCount = count($queue);

        if (isset($queue[$this->params['index']])) {
            $item = $queue[$this->params['index']];
            $schema->executeQueue($item);

            $this->outProgress('progress', $this->params['index'], $queueCount);
            $this->params['index']++;
            $this->restart();
        }

        $this->removeQueue(
            $schema->getName()
        );

        unset($this->params['index']);
    }

    protected function removeQueue($name) {
        $file = $this->getQueueFile($name);
        if (is_file($file)) {
            unlink($file);
        }
    }

    protected function loadQueue($name) {
        $file = $this->getQueueFile($name);
        if (is_file($file)) {
            $items = include $file;
            if (
                $items &&
                isset($items['items']) &&
                is_array($items['items'])
            ) {
                return $items['items'];
            }
        }

        return array();
    }


    protected function saveQueue($name, $data) {
        $file = $this->getQueueFile($name);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($file, '<?php return ' . var_export(array('items' => $data), 1) . ';');
    }

    protected function getQueueFile($name) {
        $name = 'compiled__' . strtolower($name);
        return $this->getVersionConfig()->getVal('migration_dir') . '/schema/' . $name . '.php';
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
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccess'), $args);
    }

    protected function outError($msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outError'), $args);
    }
}