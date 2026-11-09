<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Helpers\BlogExchangeHelper;
use Sprint\Migration\Helpers\HlblockExchangeHelper;
use Sprint\Migration\Helpers\IblockExchangeHelper;
use Sprint\Migration\Helpers\MedialibExchangeHelper;
use Sprint\Migration\Interfaces\RestartableInterface;

class ExchangeManager
{
    public function __construct(
        private readonly RestartableInterface $restartable,
        private readonly string               $directory,
    )
    {

    }

    public function IblockElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            new IblockExchangeHelper(),
            $this->directory
        ))->setExchangeResource('iblock_elements.xml');
    }

    public function HlblockElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            new HlblockExchangeHelper(),
            $this->directory
        ))->setExchangeResource('hlblock_elements.xml');
    }

    public function MedialibElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            new MedialibExchangeHelper(),
            $this->directory
        ))->setExchangeResource('medialib_elements.xml');
    }

    public function BlogPostsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            new BlogExchangeHelper(),
            $this->directory
        ))->setExchangeResource('blog_posts.xml');
    }
}
