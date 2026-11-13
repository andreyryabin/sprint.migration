<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Output\OutputInterface;

class ExchangeManager
{
    private ExchangeHelpers $exhelpers;

    public function __construct(
        private readonly RestartableInterface $restartable,
        private readonly string               $directory,
        private readonly ?OutputInterface     $output = null,
    )
    {
        $this->exhelpers = new ExchangeHelpers($this->output);
    }

    public function IblockElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->exhelpers->IblockExchange(),
            $this->directory
        ))
            ->setExchangeResource('iblock_elements.xml')
            ->setProgressFn($this->getProgressFn());
    }

    public function HlblockElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->exhelpers->HlblockExchange(),
            $this->directory
        ))
            ->setExchangeResource('hlblock_elements.xml')
            ->setProgressFn($this->getProgressFn());
    }

    public function MedialibElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->exhelpers->MedialibExchange(),
            $this->directory
        ))
            ->setExchangeResource('medialib_elements.xml')
            ->setProgressFn($this->getProgressFn());
    }

    public function BlogPostsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->exhelpers->BlogExchange(),
            $this->directory
        ))
            ->setExchangeResource('blog_posts.xml')
            ->setProgressFn($this->getProgressFn());
    }

    private function getProgressFn(): Closure
    {
        return fn($value, $totalCount) => $this->output?->outProgress(
            'Progress', $value, $totalCount
        );
    }
}
