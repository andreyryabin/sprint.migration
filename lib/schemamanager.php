<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;

class SchemaManager extends ExchangeEntity
{
    use HelperManagerTrait;
    use OutTrait;

    private $progress = [];

    /**
     * @param VersionConfig $versionConfig
     * @param array         $params
     *
     * @throws Exception
     */
    public function __construct(VersionConfig $versionConfig, $params = [])
    {
        $this->setVersionConfig($versionConfig);
        $this->setRestartParams($params);
    }

    /**
     * @return AbstractSchema[]
     */
    public function getEnabledSchemas()
    {
        $result = [];
        $schemas = $this->getVersionSchemas();
        $schemas = array_keys($schemas);
        foreach ($schemas as $name) {
            $schema = $this->createSchema($name);
            if ($schema->isEnabled()) {
                $result[] = $schema;
            }
        }
        return $result;
    }

    protected function getVersionSchemas($filter = [])
    {
        $schemas = $this->getVersionConfig()->getVal('version_schemas');
        $schemas = is_array($schemas) ? $schemas : [];

        if (!isset($filter['name'])) {
            return $schemas;
        }

        if (!is_array($filter['name'])) {
            $filter['name'] = [$filter['name']];
        }

        $filtered = [];
        foreach ($schemas as $name => $class) {
            if (in_array($name, $filter['name'])) {
                $filtered[$name] = $class;
            }
        }

        return $filtered;
    }

    /**
     * @throws RestartException
     */
    public function export($filter = [])
    {
        $schemas = $this->getVersionSchemas($filter);
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

        unset($this->params['schema']);
    }

    /**
     * @param array $filter
     *
     * @throws RestartException
     */
    public function import($filter = [], $testMode = 0)
    {
        $this->progress = [];

        $schemas = $this->getVersionSchemas($filter);
        $schemas = array_keys($schemas);

        if (!isset($this->params['schema'])) {
            $this->params['schema'] = 0;
        }

        if (isset($schemas[$this->params['schema']])) {
            $name = $schemas[$this->params['schema']];
            $this->importSchema($name, $testMode);

            $this->setProgress('full', $this->params['schema'] + 1, count($schemas));
            $this->params['schema']++;
            $this->restart();
        }

        unset($this->params['schema']);
    }

    public function getProgress($type = false)
    {
        return ($type) ? $this->progress[$type] : $this->progress;
    }

    protected function setProgress($type, $index, $cnt)
    {
        if ($cnt > 0) {
            $this->progress[$type] = round($index / $cnt * 100);
        } else {
            $this->progress[$type] = 0;
        }
    }

    protected function exportSchema($name)
    {
        $schema = $this->createSchema($name);
        if (!$schema->isEnabled()) {
            return false;
        }

        $schema->deleteSchemaFiles();

        $schema->export();

        $schema->outSchemaFiles();

        $schema->setModified();

        return true;
    }

    /**
     * @throws RestartException
     * @throws Exception
     */
    protected function importSchema($name, $testMode)
    {
        $schema = $this->createSchema($name);
        if (!$schema->isEnabled()) {
            return false;
        }

        $schema->setTestMode($testMode);

        $title = $testMode ? 'diff' : 'import';

        if (!isset($this->params['index'])) {
            $this->outInfo('%s (%s) start', $schema->getTitle(), $title);

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

        if (!$testMode) {
            $schema->setModified();
        }

        $this->removeQueue($schema);
        $this->out('%s (%s) success', $schema->getTitle(), $title);

        return true;
    }

    /**
     * @param $name
     *
     * @return AbstractSchema
     */
    protected function createSchema($name)
    {
        $schemas = $this->getVersionSchemas();
        $class = $schemas[$name];

        return new $class($this->getVersionConfig(), $name);
    }

    protected function removeQueue(AbstractSchema $schema)
    {
        $file = $this->getQueueFile($schema->getName());
        if (is_file($file)) {
            unlink($file);
        }
    }

    protected function loadQueue(AbstractSchema $schema)
    {
        $file = $this->getQueueFile($schema->getName());
        if (is_file($file)) {
            $items = include $file;
            if (
                $items
                && isset($items['items'])
                && is_array($items['items'])
            ) {
                return $items['items'];
            }
        }

        return [];
    }

    /**
     * @param AbstractSchema $schema
     *
     * @throws Exception
     */
    protected function saveQueue(AbstractSchema $schema)
    {
        $file = $this->getQueueFile($schema->getName());
        $data = $schema->getQueue();

        Module::createDir(dirname($file));
        file_put_contents($file, '<?php return ' . var_export(['items' => $data], 1) . ';');
    }

    protected function getQueueFile($name)
    {
        $name = 'queue__' . strtolower($name);
        return Module::getDocRoot() . '/bitrix/tmp/' . Module::ID . '/' . $name . '.php';
    }
}
