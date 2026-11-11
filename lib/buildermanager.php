<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Output\OutputFactory;
use Sprint\Migration\Output\OutputInterface;
use Throwable;

class BuilderManager
{
    private bool $isRestart = false;
    private bool $isRebuild = false;
    private array $lastRestartParams = [];
    private ?Throwable $lastException;

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
    public function createBuilder(string $class): Builder
    {
        if ($class && class_exists($class) && is_subclass_of($class, Builder::class)) {
            $builder = new $class($this->versionConfig);
            if ($builder->isEnabled()) {
                return $builder;
            }
        }
        throw new BuilderException(Locale::getMessage('ERR_BUILDER_NOT_FOUND', ['#NAME#' => $class]));
    }


    public function startBuilder(string $builder, array $params): void
    {
        $this->isRestart = false;
        $this->isRebuild = false;
        $this->lastRestartParams = [];
        $this->lastException = null;

        try {
            $builderOj = $this->createBuilder($builder);
            $builderOj->setRestartParams($params);
            $builderOj->setOutput($this->output);

            $builderOj->start();

        } catch (RebuildException) {
            $this->isRebuild = true;
            $this->lastRestartParams = isset($builderOj) ? $builderOj->getRestartParams() : [];
        } catch (RestartException) {
            $this->isRestart = true;
            $this->lastRestartParams = isset($builderOj) ? $builderOj->getRestartParams() : [];
        } catch (Throwable $e) {
            $this->lastException = $e;
        }

    }

    public function getLastException(): ?Throwable
    {
        return $this->lastException;
    }

    public function needRestart(): bool
    {
        return $this->isRestart;
    }

    public function needRebuild(): bool
    {
        return $this->isRebuild;
    }

    public function getRestartParams(): array
    {
        return $this->lastRestartParams;
    }

}