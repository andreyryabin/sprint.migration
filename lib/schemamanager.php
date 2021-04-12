<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Traits\HelperManagerTrait;

class SchemaManager extends ExchangeEntity
{
    use HelperManagerTrait;

    /** @var VersionConfig */
    protected $versionConfig = null;

    private $progress = [];

    protected $testMode = 0;

    /**
     * SchemaManager constructor.
     * @param string $configName
     * @param array $params
     * @throws Exception
     */
    public function __construct($configName = '', $params = [])
    {
        if ($configName instanceof VersionConfig) {
            $this->versionConfig = $configName;
        } else {
            $this->versionConfig = new VersionConfig(
                $configName
            );
        }
        $this->params = $params;
    }

    public function setTestMode($testMode = 1)
    {
        $this->testMode = $testMode;
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
     * @param array $filter
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
     * @throws RestartException
     */
    public function import($filter = [])
    {
        $this->progress = [];

        $schemas = $this->getVersionSchemas($filter);
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

        $files = $schema->getSchemaFiles();
        if (!empty($files)) {
            $this->outNotice(
                Locale::getMessage(
                    'ERR_SCHEMA_CREATED',
                    [
                        '#NAME#' => $schema->getTitle(),

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
                        '#NAME#' => $schema->getTitle(),

                    ]
                )
            );
        }

        if (!$this->testMode) {
            $schema->setModified();
        }

        return true;
    }

    /**
     * @param $name
     * @throws RestartException
     * @throws Exception
     * @return bool
     */
    protected function importSchema($name)
    {
        $schema = $this->createSchema($name);
        if (!$schema->isEnabled()) {
            return false;
        }

        $schema->setTestMode($this->testMode);

        $title = $this->testMode ? 'diff' : 'import';

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

        if (!$this->testMode) {
            $schema->setModified();
        }

        $this->removeQueue($schema);
        $this->out('%s (%s) success', $schema->getTitle(), $title);

        return true;
    }

    protected function getVersionConfig()
    {
        return $this->versionConfig;
    }

    /**
     * @param $name
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
            /** @noinspection PhpIncludeInspection */
            $items = include $file;
            if (
                $items &&
                isset($items['items']) &&
                is_array($items['items'])
            ) {
                return $items['items'];
            }
        }

        return [];
    }

    /**
     * @param AbstractSchema $schema
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
        return Module::getDocRoot() . '/bitrix/tmp/sprint.migration/' . $name . '.php';
    }

    /**
     * @return ExchangeManager
     */
    protected function getExchangeManager()
    {
        return new ExchangeManager($this);
    }
}
