<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\ExtraServices\Manager as ExtraServicesManager;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\Services\Table;
use Exception;
use InvalidArgumentException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

class DeliveryServiceHelper extends Helper
{
    /**
     * DeliveryServiceHelper constructor.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->checkModules(['sale']);
    }

    /**
     * @param array $fields
     *
     * @return Base|null
     */
    public static function createObject(array $fields)
    {
        $service = Manager::createObject($fields);
        if (!($service instanceof Base)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Error creating delivery service object.'
                )
            );
        }

        return $service;
    }

    /**
     * Добавляет службу доставки. Позвоялет указывать символьный код в 'CODE' и он будет сохранён дополнительным
     * запросом, чего Битрикс делать не умеет.
     *
     * @param array $fields
     *
     *    [
     *        'CODE'                => 'pickup_central',
     *        'SORT'                => 100,
     *        'NAME'                => 'ТЦ Капитолий',
     *        'CONFIG'              =>
     *            [
     *                'MAIN' =>
     *                    [
     *                        'CURRENCY' => 'RUB',
     *                        'PRICE'    => '0',
     *                        'PERIOD'   =>
     *                            [
     *                                'FROM' => '0',
     *                                'TO'   => '2',
     *                                'TYPE' => 'D',
     *                            ],
     *                    ],
     *            ],
     *        'CURRENCY'            => 'RUB',
     *        'PARENT_ID'           => 123,
     *        'CLASS_NAME'          => '\\Bitrix\\Sale\\Delivery\\Services\\Configurable',
     *        'DESCRIPTION'         => 'description',
     *        'TRACKING_PARAMS'     => [],
     *        'ACTIVE'              => 'Y',
     *        'ALLOW_EDIT_SHIPMENT' => 'Y',
     *    ]
     *
     * @throws Exception
     * @throws HelperException
     * @throws SystemException
     * @return int
     */
    public function add(array $fields)
    {
        $fields = self::createObject($fields)->prepareFieldsForSaving($fields);

        $addResult = Manager::add($fields);
        if (!$addResult->isSuccess()) {
            throw new HelperException(
                sprintf(
                    'Error adding delivery service: %s',
                    implode('; ', $addResult->getErrorMessages())
                )
            );
        }

        /**
         * Вероятно, какая-то инициализация объекта службы доставки.
         * (взято из исходника ~/bitrix/modules/sale/admin/delivery_service_edit.php:176 )
         */
        /** @noinspection PhpUndefinedMethodInspection */
        if (!$fields["CLASS_NAME"]::isInstalled()) {
            /** @noinspection PhpUndefinedMethodInspection */
            $fields["CLASS_NAME"]::install();
        }

        /**
         * Установка символьного кода, т.к. при первом добавлении этого не происходит.
         */
        if (array_key_exists('CODE', $fields)) {
            $updateResult = Table::update(
                $addResult->getId(),
                ['CODE' => trim($fields['CODE'])]
            );
            if (!$updateResult->isSuccess()) {
                throw new HelperException(
                    sprintf(
                        'Error setting CODE while adding delivery service: %s',
                        implode('; ', $updateResult->getErrorMessages())
                    )
                );
            }
        }

        $this->initEmptyExtraServices($addResult->getId());

        return $addResult->getId();
    }

    /**
     * @param string $code
     *
     * @throws HelperException
     * @return Base
     */
    public function get($code)
    {
        $fields = Table::query()->setSelect(['*'])
                       ->setFilter(['CODE' => trim($code)])
                       ->exec()
                       ->fetch();

        if (false === $fields) {
            throw new HelperException(
                sprintf(
                    'Delivery service [%s] not found.',
                    $code
                )
            );
        }

        return self::createObject($fields);
    }

    /**
     * @param       $code
     * @param array $fields
     *
     * @throws Exception
     * @throws HelperException
     * @throws SystemException
     * @return int
     */
    public function update($code, array $fields)
    {
        $service = self::get($code);

        $updateResult = Manager::update($service->getId(), $fields);

        if (!$updateResult->isSuccess()) {
            throw new HelperException(
                sprintf(
                    'Error updating delivery service [%s]: %s',
                    $code,
                    implode('; ', $updateResult->getErrorMessages())
                )
            );
        }

        $this->initEmptyExtraServices($service->getId());

        return $updateResult->getId();
    }

    /**
     * @param string $code
     *
     * @throws Exception
     * @throws HelperException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ArgumentNullException
     */
    public function delete($code)
    {
        $service = self::get($code);

        $deleteResult = Manager::delete($service->getId());
        if (!$deleteResult->isSuccess()) {
            throw new HelperException(
                sprintf(
                    'Error deleting delivery service [%s]: %s',
                    $code,
                    implode('; ', $deleteResult->getErrorMessages())
                )
            );
        }
    }

    /**
     * @param $deliveryId
     *
     * @throws HelperException
     * @throws Exception
     * @return void
     */
    private function initEmptyExtraServices($deliveryId)
    {
        /**
         * Вероятно, инициализация пустой записи в таблице 'b_sale_delivery_es'
         * для дополнительных услуг службы доставки.
         * (взято из исходника ~/bitrix/modules/sale/admin/delivery_service_edit.php )
         */
        $unActiveResult = ExtraServicesManager::setStoresUnActive($deliveryId);
        if (!$unActiveResult->isSuccess()) {
            throw new HelperException(
                sprintf(
                    'Error initializing empty extra services: %s',
                    implode('; ', $unActiveResult->getErrorMessages())
                )
            );
        }
    }
}
