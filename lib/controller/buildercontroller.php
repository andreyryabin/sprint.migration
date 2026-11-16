<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Sprint\Migration\Builder;
use Sprint\Migration\BuilderManager;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Fields\AbstractField;
use Sprint\Migration\Output\RestOutput;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.buildercontroller
 */
class BuilderController extends Controller
{
    protected BuilderManager $builderManager;
    protected RestOutput $output;

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

        $this->output = new RestOutput();

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
        $builders = array_map(
            fn(Builder $builder) => $this->getBuilderForm($builder, true),
            $this->builderManager->getBuilders()
        );

        return ['builders' => $builders];
    }

    /**
     * @noinspection PhpUnused
     * @throws BuilderException
     */
    public function execBuilderAction(string $name, array $params = []): array
    {
        $builder = $this->builderManager->createBuilder($name, $params);

        $result = $this->builderManager->startBuilder($builder);

        return array_merge(
            [
                'restart' => $result->needRestart(),
                'success' => $result->isSuccess(),
            ],
            $this->getBuilderForm($builder, $result->isSuccess()),
            $this->output->getLog()
        );

    }

    private function getBuilderForm(Builder $builder, bool $newform): array
    {

        if ($newform) {
            $this->cleanParams($builder);
        } else {
            $this->mergeParams($builder);
        }

        return [
            'name'   => $builder->getName(),
            'title'  => $builder->getTitle(),
            'group'  => $builder->getGroup(),
            'note'   => $builder->getDescription(),
            'params' => $builder->getRestartParams(),
            'fields' => array_map(fn(AbstractField $field) => [
                'code'      => $field->getCode(),
                'title'     => $field->getTitle(),
                'note'      => $field->getNote(),
                'component' => $field->getComponentName(),
                'props'     => $field->getProps(),
            ], $builder->getFields())
        ];
    }

    private function cleanParams(Builder $builder): void
    {
        $params = $builder->getRestartParams();
        $cleaned = [];
        foreach ($builder->getFields() as $field) {
            $cleaned[$field->getCode()] = $params[$field->getCode()] ?? $field->getDefaultValue();
        }
        $builder->setRestartParams($cleaned);
    }

    private function mergeParams(Builder $builder): void
    {
        $params = $builder->getRestartParams();
        foreach ($builder->getFields() as $field) {
            $params[$field->getCode()] ??= $field->getDefaultValue();
        }
        $builder->setRestartParams($params);
    }
}
