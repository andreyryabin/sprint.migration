<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Output\OutputInterface;

class HelperRegistry
{
    private array $registered = [];

    public function __construct(
        private readonly ?OutputInterface $output = null
    )
    {
        $this->registration();
    }

    /**
     * @template T of Helper
     * @param class-string<T> $class
     * @return T
     * @throws HelperException
     */
    protected function create(string $class): Helper
    {
        if (is_subclass_of($class, Helper::class)) {
            $ob = new $class;
            $ob->setOutput($this->output);
            return $ob;
        }

        throw new HelperException("Class \"$class\" is not helper");
    }

    protected function registration()
    {
        //register classes
    }

    public function registerHelper($name, $class): void
    {
        $this->registered[$name] = $class;
    }

    /**
     * @throws HelperException
     */
    public function __call($name, $arguments): Helper
    {
        if (isset($this->registered[$name])) {
            return $this->create($this->registered[$name]);
        }

        $class = $this->getClassByCalledName($name);
        if ($class && class_exists($class)) {
            $this->registerHelper($name, $class);
            return $this->create($class);
        }

        throw new HelperException("Helper \"$name\" not found");
    }

    protected function getClassByCalledName(string $name): string
    {
        return '\\Sprint\\Migration\\Helpers\\' . $name . 'Helper';
    }


}
