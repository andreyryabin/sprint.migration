<?php

namespace Sprint\Migration;

class VersionConfig
{
    private $configValues = array();
    private $configName = '';

    private $loaded = 0;

    public function __construct($configName) {
        $this->loaded = 0;
        if (!empty($configName) && preg_match("/^[a-z0-9_-]*$/i", $configName)) {
            $configFile = Module::getPhpInterfaceDir() . '/migrations.' . $configName . '.php';
            if (is_file($configFile)) {
                /** @noinspection PhpIncludeInspection */
                $values = include $configFile;
                $this->configName = $configName;
                $this->configValues = $this->prepareConfig($values);
                $this->loaded = 1;
            }
        }

        if (!$this->loaded) {
            $configName = 'cfg';
            $configFile = Module::getPhpInterfaceDir() . '/migrations.' . $configName . '.php';
            if (is_file($configFile)) {
                /** @noinspection PhpIncludeInspection */
                $values = include $configFile;
                $this->configName = $configName;
                $this->configValues = $this->prepareConfig($values);
                $this->loaded = 1;
            }
        }

        if (!$this->loaded) {
            $values = array();
            $this->configName = false;
            $this->configValues = $this->prepareConfig($values);
            $this->loaded = 1;
        }
    }

    public function getConfigInfo() {
        $files = array();

        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator(Module::getPhpInterfaceDir());
        foreach ($directory as $item) {
            if (!$item->isFile()) {
                continue;
            }

            if (!preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $item->getFilename(), $matches)) {
                continue;
            }

            $configName = $matches[1];

            /** @noinspection PhpIncludeInspection */
            $values = include $item->getPathname();
            if (!$this->validConfig($values)) {
                continue;
            }

            if ($configName == $this->configName) {
                continue;
            }

            $values = $this->prepareConfig($values);

            $files[] = array(
                'name' => $configName,
                'values' => $values,
                'current' => 0
            );

        }

        $files[] = array(
            'name' => $this->configName,
            'values' => $this->configValues,
            'current' => 1
        );

        foreach ($files as $key => $file) {
            if (!empty($file['name'])) {
                if (empty($file['values']['title'])) {
                    $file['title'] = sprintf('%s (%s)', GetMessage('SPRINT_MIGRATION_CONFIG_TITLE'), 'migrations.' . $file['name'] . '.php');
                } else {
                    $file['title'] = sprintf('%s (%s)', $file['values']['title'], 'migrations.' . $file['name'] . '.php');
                }
            } else {
                $file['title'] = sprintf('%s (%s)', GetMessage('SPRINT_MIGRATION_CONFIG_TITLE'), GetMessage('SPRINT_MIGRATION_CONFIG_NOFILE'));
            }

            if (isset($file['values']['title'])){
                unset($file['values']['title']);
            }

            $files[$key] = $file;
        }

        return $files;
    }

    protected function validConfig($values) {
        $availablekeys = array(
            'migration_template',
            'migration_table',
            'migration_extend_class',
            'migration_dir',
            'tracker_task_url',
        );

        foreach ($availablekeys as $key) {
            if (!empty($values[$key])) {
                return true;
            }
        }

        return false;
    }

    protected function prepareConfig($values = array()) {
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

        if (!$values['migration_webdir']) {
            if (false === strpos($values['migration_dir'], Module::getDocRoot())) {
                $values['migration_webdir'] = substr($values['migration_dir'], Module::getDocRoot());
            } else {
                $values['migration_webdir'] = '';
            }
        }

        if (!$values['tracker_task_url']) {
            $values['tracker_task_url'] = '';
        }

        return $values;
    }

    public function getConfigVal($val, $default = '') {
        return !empty($this->configValues[$val]) ? $this->configValues[$val] : $default;
    }
}



