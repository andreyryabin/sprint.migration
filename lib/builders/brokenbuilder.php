<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builder;

class BrokenBuilder extends Builder
{
    public function setOriginClass(string $originClass, string $message): static
    {
        $this->setTitle("[label:red]{$this->getName()}[/] $originClass");
        $this->setDescription($message);
        $this->setEnabled(false);

        return $this;
    }

    protected function initialize(): void
    {
        $this->setTitle($this->getName());
    }

    protected function execute(): void {}

}
