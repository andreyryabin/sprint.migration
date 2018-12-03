<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\AbstractSchema;

class SchemaImport extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_SchemaImport'));
        $this->setGroup('schema');
    }


    protected function execute() {

        $schemas = $this->getVersionConfig()->getVal('version_schemas');
        $schemas = array_values($schemas);


        if (!isset($this->params['schema'])) {
            $this->params['schema'] = 0;
        }


        if (isset($schemas[$this->params['schema']])) {

            $this->executeSchema($schemas[$this->params['schema']]);

            $this->params['schema']++;

            $this->restart();
        }


        unset($this->params['schema']);
    }

    protected function executeSchema($class) {
        /** @var $schema AbstractSchema */

        $schema = new $class($this->getVersionConfig());

        if (!isset($this->params['index'])) {
            $this->params['index'] = 0;
            $schema->import();
            $this->saveQueue($schema->getQueue());
        }

        $queue = $this->loadQueue();
        $queueCount = count($queue);

        if (isset($queue[$this->params['index']])) {
            $item = $queue[$this->params['index']];
            $schema->executeQueue($item);
            $this->outProgress('progress', $this->params['index'], $queueCount);
            $this->params['index']++;
            $this->restart();
        }

        //$this->removeQueue();
        unset($this->params['index']);
    }

    protected function removeQueue() {
        $file = $this->getQueueFile();
        if (is_file($file)) {
            unlink($file);
        }
    }

    protected function loadQueue() {
        $file = $this->getQueueFile();
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


    protected function saveQueue($data) {
        $file = $this->getQueueFile();

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($file, '<?php return ' . var_export(array('items' => $data), 1) . ';');
    }

    protected function getQueueFile() {
        $name = $this->params['schema'] . '-compiled';
        return $this->getVersionConfig()->getVal('migration_dir') . '/schema/' . $name . '.php';
    }
}