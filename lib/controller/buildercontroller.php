<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Sprint\Migration\Builder;
use Sprint\Migration\BuilderManager;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output\BrowserOutput;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.buildercontroller
 */
class BuilderController extends Controller
{
    protected BuilderManager $builderManager;
    protected BrowserOutput $output;

    /**
     * @throws MigrationException
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $configManager = ConfigManager::getInstance();

        $versionConfig = $configManager->get(
            $this->getRequest()->get('config')
        );

        $this->output = new BrowserOutput();

        $this->builderManager = new BuilderManager(
            $versionConfig,
            $this->output
        );
    }

    public function configureActions(): array
    {
        return [];
    }

    /**
     * @noinspection PhpUnused
     */
    public function refreshBuildersAction(): array
    {
        $builders = array_map(fn(Builder $builder) => [
            'name'  => $builder->getClassName(),
            'title' => $builder->getTitle(),
            'group' => $builder->getGroup(),
        ], $this->builderManager->getBuilders());

        return ['builders' => $builders];
    }

    /**
     * @noinspection PhpUnused
     */
    public function execBuilderAction(string $builder, array $params = []): array
    {
        $result = $this->builderManager->startBuilder($builder, $params);

        return array_merge([
            'restart' => $result->needRestart(),
            'rebuild' => $result->needRebuild(),
            'params'  => $result->getRestartParams(),
            'fields'  => $result->getFields()
        ], $this->output->getLog());

    }
}
