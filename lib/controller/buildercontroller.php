<?php

namespace Sprint\Migration\Controller;


use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use CMain;
use Sprint\Migration\Builder;
use Sprint\Migration\BuilderManager;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Output\RestOutput;

/**
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

        Loc::setCurrentLang(
            $this->getRequest()->get('lang') ?? Locale::getDefaultLang(),
        );

        $versionConfig = ConfigManager::getInstance()->get(
            $this->getRequest()->get('config'),
        );

        $this->output = new RestOutput(
            $versionConfig->getLogger(),
        );

        $this->builderManager = new BuilderManager(
            $versionConfig,
            $this->output,
        );
    }

    public function configureActions(): array
    {
        return [];
    }

    /**
     * @publicApi
     */
    public function refreshBuildersAction(): array
    {
        $builders = array_map(
            fn(Builder $builder) => $this->getBuilderForm($builder, true),
            $this->builderManager->getBuilders(),
        );

        return ['builders' => $builders];
    }

    /**
     * @publicApi
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
            $this->output->getLog(),
        );
    }

    protected function processBeforeAction(Action $action): bool
    {
        if (CMain::GetUserRight(Module::ID) == 'D') {
            $this->addError(new Error('ACCESS_DENIED'));
            return false;
        }

        return parent::processBeforeAction($action);
    }

    private function getBuilderForm(Builder $builder, bool $newform): array
    {
        if ($newform) {
            $builder->cleanParamsSkipFields();
        } else {
            $builder->mergeParamsWithFields();
        }

        return $this->builderToArray($builder);
    }

    private function builderToArray(Builder $builder): array
    {
        $fields = [];
        foreach ($builder->getFields() as $code => $field) {
            $fields[] = [
                'code'      => $code,
                'title'     => $field->getTitle(),
                'note'      => $field->getNote(),
                'required'  => $field->isRequired(),
                'component' => $field->getComponentName(),
                'props'     => $field->getProps(),
            ];
        }

        return [
            'name'   => $builder->getName(),
            'title'  => $builder->getTitle(),
            'group'  => $builder->getGroup(),
            'note'   => $builder->getDescription(),
            'params' => $builder->getRestartParams(),
            'fields' => $fields,
        ];
    }


}
