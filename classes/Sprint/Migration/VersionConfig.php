<?php

namespace Sprint\Migration;

class VersionConfig
{
    private $configCurrent = array();

    private $configList = array();


    private $availablekeys = array(
        'title',
        'migration_template',
        'migration_table',
        'migration_extend_class',
        'migration_dir',
        'tracker_task_url',
        'version_prefix',
    );

    public function __construct($configName) {
        $configName = empty($configName) ? 'cfg' : $configName;

        $this->configList = array();
        $this->configList['cfg'] = $this->prepareConfig('cfg', array());

        $directory = new \DirectoryIterator(Module::getPhpInterfaceDir());
        foreach ($directory as $item) {
            if (!$item->isFile()) {
                continue;
            }

            if (!preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $item->getFilename(), $matches)) {
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            $values = include $item->getPathname();
            if (!$this->validConfig($values)) {
                continue;
            }

            $cname = $matches[1];
            $this->configList[$cname] = $this->prepareConfig($cname, $values);
        }

        if (isset($this->configList[$configName])){
            $this->configCurrent = $this->configList[$configName];
        } else {
            $this->configCurrent = $this->configList['cfg'];
        }
    }

    public function getConfigName(){
        return $this->configCurrent['name'];
    }

    public function getConfigList() {
        return $this->configList;
    }

    public function getConfigCurrent() {
        return $this->configCurrent;
    }

    protected function validConfig($values) {
        foreach ($this->availablekeys as $key) {
            if (!empty($values[$key])) {
                return true;
            }
        }
        return false;
    }

    protected function prepareConfig($configName, $configValues = array()){
        $configValues = $this->prepareConfigValues($configValues);
        if (!empty($configValues['title'])) {
            $title = $configValues['title'];
            unset($configValues['title']);
        } else {
            $title = GetMessage('SPRINT_MIGRATION_CONFIG_TITLE');
        }
        $title = sprintf('%s (%s)', $title,'migrations.' . $configName . '.php');
        return array(
            'name' => $configName,
            'title' => $title,
            'values' => $configValues
        );
    }

    protected function prepareConfigValues($values = array()) {
        if (!$values['migration_extend_class']) {
            $values['migration_extend_class'] = 'Version';
        }

        if (!$values['migration_table']) {
            $values['migration_table'] = 'sprint_migration_versions';
        }

        if ($values['migration_template'] && is_file(Module::getDocRoot() . $values['migration_template'])) {
            $values['migration_template'] = Module::getDocRoot() . $values['migration_template'];
        } else {
            $values['migration_template'] = Module::getModuleDir() . '/templates/version.php';
        }

        if ($values['migration_dir']) {
            $values['migration_dir'] = Module::getDocRoot() . $values['migration_dir'];
        } else {
            $values['migration_dir'] = Module::getPhpInterfaceDir() . '/migrations';
        }

        if (!is_dir($values['migration_dir'])) {
            mkdir($values['migration_dir'], BX_DIR_PERMISSIONS, true);
            $values['migration_dir'] = realpath($values['migration_dir']);
        } else {
            $values['migration_dir'] = realpath($values['migration_dir']);
        }

        if (!$values['version_prefix']) {
            $values['version_prefix'] = 'Version';
        }

        if (!$values['tracker_task_url']) {
            $values['tracker_task_url'] = '';
        }

        ksort($values);
        return $values;
    }

    public function getConfigVal($val, $default = '') {
        if ($val == 'migration_webdir'){
            return $this->getWebdir($default);
        } else {
            return !empty($this->configCurrent['values'][$val]) ? $this->configCurrent['values'][$val] : $default;
        }
    }

    protected function getWebdir($default = ''){
        $dir = $this->configCurrent['values']['migration_dir'];
        if (0 === strpos($dir, Module::getDocRoot())) {
            return substr($dir, strlen(Module::getDocRoot()));
        } else {
            return $default;
        }
    }

}



