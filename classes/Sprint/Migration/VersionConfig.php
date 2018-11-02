<?php

namespace Sprint\Migration;

class VersionConfig
{
    private $configCurrent = array();

    private $configList = array();

    private $availablekeys = array(
        'migration_table',
        'migration_extend_class',
        'stop_on_errors',
        'migration_dir',
        'tracker_task_url',
        'version_prefix',
        'version_filter',
        'version_builders',
        'show_admin_interface',
        'console_user',
    );

    public function __construct($configName = '') {
        $this->configList = $this->searchConfigs();

        if (!isset($this->configList['cfg'])) {
            $this->configList['cfg'] = $this->prepare('cfg');
        }

        if (!isset($this->configList['archive'])) {
            $this->configList['archive'] = $this->prepare('archive', array(
                'title' => GetMessage('SPRINT_MIGRATION_CONFIG_archive'),
                'migration_dir' => $this->getArchiveDir('archive'),
                'migration_table' => 'sprint_migration_archive',
            ));
        }

        uasort($this->configList, function ($a, $b) {
            return ($a['sort'] >= $b['sort']);
        });

        if (isset($this->configList[$configName])) {
            $this->configCurrent = $this->configList[$configName];
        } else {
            $this->configCurrent = $this->configList['cfg'];
        }
    }

    public function isExists($configName) {
        return (isset($this->configList[$configName]));
    }

    public function getName() {
        return $this->configCurrent['name'];
    }

    public function getCurrent() {
        return $this->configCurrent;
    }

    public function getList() {
        return $this->configList;
    }

    protected function searchConfigs() {
        $result = array();
        $directory = new \DirectoryIterator(Module::getPhpInterfaceDir());
        foreach ($directory as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $configName = $this->getConfigName($item->getFilename());
            if (!$configName) {
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            $values = include $item->getPathname();
            if (!$this->isValuesValid($values)) {
                continue;
            }

            $result[$configName] = $this->prepare($configName, $values, $item->getPathname());
        }

        return $result;
    }

    protected function getConfigName($fileName) {
        if (preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $fileName, $matches)) {
            return $matches[1];
        }
        return false;
    }

    protected function isValuesValid($values) {
        foreach ($this->availablekeys as $key) {
            if (isset($values[$key])) {
                return true;
            }
        }
        return false;
    }

    protected function prepare($configName, $configValues = array(), $file = false) {
        $configValues = $this->prepareValues($configValues);

        if (!empty($configValues['title'])) {
            $title = sprintf('%s (%s)', $configValues['title'], $configName);
        } else {
            $title = sprintf('%s (%s)', GetMessage('SPRINT_MIGRATION_CONFIG_TITLE'), $configName);
        }

        if (isset($configValues['title'])) {
            unset($configValues['title']);
        }

        return array(
            'name' => $configName,
            'sort' => $this->getSort($configName),
            'title' => $title,
            'file' => $file,
            'values' => $configValues,
        );
    }

    protected function prepareValues($values = array()) {
        if (empty($values['migration_extend_class'])) {
            $values['migration_extend_class'] = 'Version';
        }

        if (empty($values['migration_table'])) {
            $values['migration_table'] = 'sprint_migration_versions';
        }

        if (empty($values['migration_dir'])) {
            $values['migration_dir'] = Module::getPhpInterfaceDir() . '/migrations';
        } else {
            $values['migration_dir'] = Module::getDocRoot() . $values['migration_dir'];
        }

        if (!is_dir($values['migration_dir'])) {
            mkdir($values['migration_dir'], BX_DIR_PERMISSIONS, true);
            $values['migration_dir'] = realpath($values['migration_dir']);
        } else {
            $values['migration_dir'] = realpath($values['migration_dir']);
        }

        if (empty($values['version_prefix'])) {
            $values['version_prefix'] = 'Version';
        }

        if (!isset($values['version_filter']) || !is_array($values['version_filter'])) {
            $values['version_filter'] = [];
        }

        if (isset($values['show_admin_interface']) && !$values['show_admin_interface']) {
            $values['show_admin_interface'] = false;
        } else {
            $values['show_admin_interface'] = true;
        }

        if (isset($values['stop_on_errors']) && $values['stop_on_errors']) {
            $values['stop_on_errors'] = true;
        } else {
            $values['stop_on_errors'] = false;
        }

        if (empty($values['tracker_task_url'])) {
            $values['tracker_task_url'] = '';
        }

        $cond1 = isset($values['console_user']);
        $cond2 = ($cond1 && $values['console_user'] === false);
        $cond3 = ($cond1 && strpos($values['console_user'], 'login:') === 0);

        $values['console_user'] = ($cond2 || $cond3) ? $values['console_user'] : 'admin';

        if (!empty($values['version_builders']) && is_array($values['version_builders'])) {
            $values['version_builders'] = array_merge($this->getDefaultBuilders(), $values['version_builders']);
        } else {
            $values['version_builders'] = $this->getDefaultBuilders();
        }

        ksort($values);
        return $values;
    }

    public function getVal($name, $default = '') {
        if (isset($this->configCurrent['values'][$name])) {
            if (is_bool($this->configCurrent['values'][$name])) {
                return $this->configCurrent['values'][$name];
            } elseif (!empty($this->configCurrent['values'][$name])) {
                return $this->configCurrent['values'][$name];
            }
        }

        return $default;
    }

    public function createConfig($configName) {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->getConfigName($fileName)) {
            return false;
        }

        $configPath = Module::getPhpInterfaceDir() . '/' . $fileName;
        if (is_file($configPath)) {
            return false;
        }

        if (isset($this->configList[$configName])) {
            $curValues = $this->configList[$configName]['values'];
            $configValues = array(
                'migration_dir' => $this->getRelativeDir($curValues['migration_dir']),
                'migration_table' => $curValues['migration_table'],
            );
        } else {
            $configValues = array(
                'migration_dir' => $this->getArchiveDir($configName),
                'migration_table' => 'sprint_migration_' . $configName,
            );
        }

        file_put_contents($configPath, '<?php return ' . var_export($configValues, 1) . ';');
        return is_file($configPath);
    }

    protected function getRelativeDir($dir) {
        $docroot = Module::getDocRoot();

        if (strpos($dir, $docroot) === 0) {
            $dir = substr($dir, strlen($docroot));
        }

        return $dir;
    }

    protected function getArchiveDir($dirname) {
        $def = $this->configList['cfg'];

        return $this->getRelativeDir(
            $def['values']['migration_dir'] . '/' . $dirname
        );
    }

    protected function getSort($configName) {
        if ($configName == 'archive') {
            return 110;
        } elseif ($configName == 'cfg') {
            return 100;
        } else {
            return 500;
        }
    }

    protected function getDefaultBuilders() {
        return array(
            'Version' => '\Sprint\Migration\Builders\Version',
            'IblockExport' => '\Sprint\Migration\Builders\IblockExport',
            'HlblockExport' => '\Sprint\Migration\Builders\HlblockExport',
            'UserTypeEntities' => '\Sprint\Migration\Builders\UserTypeEntities',
            'UserGroupExport' => '\Sprint\Migration\Builders\UserGroupExport',
            'AgentExport' => '\Sprint\Migration\Builders\AgentExport',
            'FormExport' => '\Sprint\Migration\Builders\FormExport',
            'CacheCleaner' => '\Sprint\Migration\Builders\CacheCleaner',
            'Marker' => '\Sprint\Migration\Builders\Marker',
            'Transfer' => '\Sprint\Migration\Builders\Transfer',
            'Configurator' => '\Sprint\Migration\Builders\Configurator',
        );
    }

}



