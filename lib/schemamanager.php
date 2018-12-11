<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;

class SchemaManager
{
    /** @var VersionConfig */
    private $versionConfig = null;

    protected $params = array();

    private $progress = array();

    protected $testMode = 0;

    public function __construct($params = array()) {
        $this->versionConfig = new VersionConfig('cfg');

        $this->params = $params;
    }

    public function setTestMode($testMode = 1) {
        $this->testMode = $testMode;
    }

    public function outDescriptions() {
        $schemas = $this->getVersionSchemas();
        $schemas = array_keys($schemas);

        foreach ($schemas as $name) {
            $schema = $this->createSchema($name);
            if ($schema->isEnabled()) {
                $this->outSuccess($schema->getTitle());
                $schema->outDescription();
            }
        }
    }

    protected function getVersionSchemas() {
        return $this->getVersionConfig()->getVal('version_schemas');
    }

    public function export() {
        $schemas = $this->getVersionSchemas();
        $schemas = array_keys($schemas);

        if (!isset($this->params['schema'])) {
            $this->params['schema'] = 0;
        }

        if (isset($schemas[$this->params['schema']])) {
            $name = $schemas[$this->params['schema']];
            $this->exportSchema($name);

            $this->setProgress('full', $this->params['schema'] + 1, count($schemas));
            $this->params['schema']++;
            $this->restart();
        }
    }

    public function import() {
        $this->progress = array();

        $schemas = $this->getVersionSchemas();
        $schemas = array_keys($schemas);

        if (!isset($this->params['schema'])) {
            $this->params['schema'] = 0;
        }

        if (isset($schemas[$this->params['schema']])) {
            $name = $schemas[$this->params['schema']];
            $this->importSchema($name);

            $this->setProgress('full', $this->params['schema'] + 1, count($schemas));
            $this->params['schema']++;
            $this->restart();
        }

        unset($this->params['schema']);
    }

    public function getProgress() {
        return $this->progress;
    }

    protected function setProgress($type, $index, $cnt) {
        if ($cnt > 0) {
            $this->progress[$type] = round($index / $cnt * 100);
        } else {
            $this->progress[$type] = 0;
        }
    }

    protected function exportSchema($name) {
        $schema = $this->createSchema($name);
        if ($schema->isEnabled()) {
            $schema->export();
        }

    }

    protected function importSchema($name) {
        $schema = $this->createSchema($name);
        if (!$schema->isEnabled()) {
            return false;
        }

        $schema->setTestMode($this->testMode);

        if (!isset($this->params['index'])) {
            $this->outSuccess('%s (test import) start', $schema->getTitle());

            $this->params['index'] = 0;
            $schema->import();
            $this->saveQueue($schema);
        }

        $queue = $this->loadQueue($schema);

        if (isset($queue[$this->params['index']])) {
            $this->setProgress('current', $this->params['index'] + 1, count($queue));

            $item = $queue[$this->params['index']];
            $schema->executeQueue($item);

            $this->params['index']++;
            $this->restart();
        }

        unset($this->params['index']);

        $this->removeQueue($schema);
        $this->out('%s (test import) success', $schema->getTitle());
    }

    protected function getVersionConfig() {
        return $this->versionConfig;
    }

    /** @return AbstractSchema */
    protected function createSchema($name) {
        $schemas = $this->getVersionSchemas();
        $class = $schemas[$name];

        return new $class($this->getVersionConfig(), $name);
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

    protected function removeQueue(AbstractSchema $schema) {
        $file = $this->getQueueFile($schema->getName());
        if (is_file($file)) {
            unlink($file);
        }
    }

    protected function loadQueue(AbstractSchema $schema) {
        $file = $this->getQueueFile($schema->getName());
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


    protected function saveQueue(AbstractSchema $schema) {
        $file = $this->getQueueFile($schema->getName());
        $data = $schema->getQueue();

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($file, '<?php return ' . var_export(array('items' => $data), 1) . ';');
    }

    protected function getQueueFile($name) {
        $name = 'queue__' . strtolower($name);
        return Module::getDocRoot() . '/bitrix/tmp/sprint.migration/' . $name . '.php';
    }

    protected function restart() {
        Throw new RestartException('restart');
    }

    public function getRestartParams() {
        return $this->params;
    }
}