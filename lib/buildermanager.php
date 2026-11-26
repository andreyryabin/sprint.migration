<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Output\OutputFactory;
use Sprint\Migration\Output\OutputInterface;
use Sprint\Migration\Results\BuilderResult;
use Throwable;

class BuilderManager
{
    public function __construct(
        private readonly VersionConfig $versionConfig,
        private ?OutputInterface $output = null,
    ) {
        $this->output ??= OutputFactory::create($this->versionConfig);
    }

    /**
     * @throws BuilderException
     */
    public function createBuilder(string $builderName, array $params = []): Builder
    {
        $builders = $this->versionConfig->getBuilders();
        $builderClass = $builders[$builderName] ?? '';

        return $this->createBuilderByClass($builderClass, $builderName, $params);
    }

    /**
     * @return Builder[]
     */
    public function getBuilders(): array
    {
        $collection = [];
        foreach ($this->versionConfig->getBuilders() as $builderName => $builderClass) {
            try {
                $collection[] = $this->createBuilderByClass($builderClass, $builderName);
            } catch (BuilderException) {
            }
        }
        return $collection;
    }

    /**
     * @throws BuilderException
     */
    private function createBuilderByClass(string $builderClass, string $builderName, array $params = []): Builder
    {
        if ($builderClass && class_exists($builderClass) && is_subclass_of($builderClass, Builder::class)) {
            $builder = new $builderClass($this->versionConfig, $builderName, $params);
            if ($builder->isEnabled()) {
                return $builder;
            }
        }
        throw new BuilderException(Locale::getMessage('ERR_BUILDER_NOT_FOUND', ['#NAME#' => $builderName]));
    }

    /**
     */
    public function startBuilder(Builder $builder): BuilderResult
    {
        $builderResult = new BuilderResult();
        $builder->setOutput($this->output);

        try {
            $builder->start();
        } catch (RebuildException) {
            $builderResult->setRebuild();
        } catch (RestartException) {
            $builderResult->setRestart();
        } catch (Throwable $e) {
            $builderResult->setException($e);
            $this->output->outException($e);
        }

        $this->encodeFieldValues($builder);

        if ($builderResult->isSuccess()) {
            $this->cleanRestartParams($builder);
        }

        return $builderResult;
    }

    private function encodeFieldValues(Builder $builder): void
    {
        $params = $builder->getRestartParams();
        foreach ($builder->getFields() as $code => $field) {
            if (isset($params[$code])) {
                $params[$code] = $field->encodeValue($params[$code]);
            }
        }
        $builder->setRestartParams($params);
    }

    private function cleanRestartParams(Builder $builder): void
    {
        $keep = [];
        $fields = $builder->getFields();
        foreach ($builder->getRestartParams() as $code => $param) {
            if (isset($fields[$code])) {
                $keep[$code] = $param;
            }
        }
        $builder->setRestartParams($keep);
    }
}