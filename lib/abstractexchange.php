<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\HelperManagerTrait;

abstract class AbstractExchange
{

    use HelperManagerTrait;
    use OutTrait;

    protected $exchangeEntity;
    protected $file;
    protected $limit = 10;

    /**
     * abstractexchange constructor.
     *
     * @param ExchangeEntity $exchangeEntity
     *
     * @throws MigrationException
     */
    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;

        if (!class_exists('XMLReader') || !class_exists('XMLWriter')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }

        if (!$this->isEnabled()) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED'
                )
            );
        }
    }

    protected function isEnabled()
    {
        return true;
    }

    public function setExchangeFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    protected function purifyValue($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->purifyValue($value);
            }
        } else {
            $data = htmlspecialchars_decode($data);
        }
        return $data;
    }

    protected function getExchangeDir()
    {
        return dirname($this->file);
    }

    protected function getExchangeFile()
    {
        return $this->file;
    }
}
