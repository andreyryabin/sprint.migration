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
        private VersionConfig    $versionConfig,
        private ?OutputInterface $output = null,
    )
    {
        $this->output ??= OutputFactory::create($this->versionConfig);
    }

    /**
     * @return Builder[]
     */
    public function getBuilders(): array
    {
        $collection = [];
        foreach ($this->versionConfig->getBuilders() as $builderClass) {
            try {
                $collection[] = $this->createBuilder($builderClass);
            } catch (BuilderException) {
            }
        }
        return $collection;
    }

    /**
     * @throws BuilderException
     */
    public function createBuilder(string $class, array $params = []): Builder
    {
        if ($class && class_exists($class) && is_subclass_of($class, Builder::class)) {
            $builder = new $class($this->versionConfig, $params);
            if ($builder->isEnabled()) {
                return $builder;
            }
        }
        throw new BuilderException(Locale::getMessage('ERR_BUILDER_NOT_FOUND', ['#NAME#' => $class]));
    }


    public function startBuilder(string $builder, array $params): BuilderResult
    {
        $builderResult = new BuilderResult();

        try {
            $builderOj = $this->createBuilder($builder, $params);
            $builderOj->setOutput($this->output);

            $builderOj->start();

        } catch (RebuildException) {
            $builderResult->setRebuild(isset($builderOj) ? $builderOj->getRestartParams() : []);
        } catch (RestartException) {
            $builderResult->setRestart(isset($builderOj) ? $builderOj->getRestartParams() : []);
        } catch (Throwable $e) {
            $builderResult->setException($e);
            $this->output->outException($e);
        }

        $builderResult->setFields(isset($builderOj) ? $builderOj->getFields() : []);
        return $builderResult;
    }
}