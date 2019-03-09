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
    public function __construct() {
        $this->checkModules(array('sale'));
    }

    /**
     * @param array $fields
     *
     * @return Base|null
     */
    public static function createObject(array $fields) {
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
     * @return int
     * @throws Exception
     * @throws HelperException
     * @throws SystemException
     */
    public function add(array $fields) {
        $fields = self::createObject($fields)->prepareFieldsForSaving($fields);

        $addResult = Manager::add($fields);
        if (!$addResult->isSuccess()) {
            $this->throwException(
                __METHOD__,
                'Error adding delivery service: %s',
                implode('; ', $addResult->getErrorMessages())
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
                $this->throwException(
                    __METHOD__,
                    'Error setting CODE while adding delivery service: %s',
                    implode('; ', $updateResult->getErrorMessages())
                );
            }
        }

        $this->initEmptyExtraServices($addResult->getId());

        return $addResult->getId();
    }

    /**
     * @param string $code
     *
     * @return Base
     * @throws HelperException
     */
    public function get($code) {
        $fields = Table::query()->setSelect(['*'])
            ->setFilter(['CODE' => trim($code)])
            ->exec()
            ->fetch();

        if (false === $fields) {
            $this->throwException(
                __METHOD__,
                'Delivery service [%s] not found.',
                $code
            );
        }

        return self::createObject($fields);
    }

    /**
     * @param $code
     * @param array $fields
     *
     * @return int
     * @throws Exception
     * @throws HelperException
     * @throws SystemException
     */
    public function update($code, array $fields) {
        $service = self::get($code);

        $updateResult = Manager::update($service->getId(), $fields);

        if (!$updateResult->isSuccess()) {
            $this->throwException(
                __METHOD__,
                'Error updating delivery service [%s]: %s',
                $code,
                implode('; ', $updateResult->getErrorMessages())
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
    public function delete($code) {
        $service = self::get($code);

        $deleteResult = Manager::delete($service->getId());
        if (!$deleteResult->isSuccess()) {
            $this->throwException(
                __METHOD__,
                'Error deleting delivery service [%s]: %s',
                $code,
                implode('; ', $deleteResult->getErrorMessages())
            );
        }
    }

    /**
     * @param $deliveryId
     *
     * @return void
     * @throws HelperException
     * @throws Exception
     */
    private function initEmptyExtraServices($deliveryId) {
        /**
         * Вероятно, инициализация пустой записи в таблице 'b_sale_delivery_es'
         * для дополнительных услуг службы доставки.
         * (взято из исходника ~/bitrix/modules/sale/admin/delivery_service_edit.php )
         */
        $unActiveResult = ExtraServicesManager::setStoresUnActive($deliveryId);
        if (!$unActiveResult->isSuccess()) {
            $this->throwException(
                __METHOD__,
                'Error initializing empty extra services: %s',
                implode('; ', $unActiveResult->getErrorMessages())
            );
        }
    }
}
