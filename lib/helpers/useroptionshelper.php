<?php

namespace Sprint\Migration\helpers;

use CIBlockProperty;
use CUserOptions;
use InvalidArgumentException;
use Sprint\Migration\Helper;
use Sprint\Migration\HelperManager;

/**
 * Class UserOptionsHelper
 *
 * Индивидуальные настройки пользовательского интерфейса.
 *
 * @package Sprint\Migration\helpers
 */
class UserOptionsHelper extends Helper
{
    protected $iblockHelper;

    public function __construct()
    {
        $this->checkModules(['iblock']);
        /**
         * Разрешение менять чужие настройки, чтобы нормально отработал \CUserOptions::SetOptionsFromArray
         */
        $_SESSION["SESS_OPERATIONS"]["edit_other_settings"] = true;
    }

    /**
     * Настраивает вид формы редактирования элемента ИБ по умолчанию для всех пользователей.
     *
     * @param string $iblockType
     * @param string $iblockCode
     * @param array $data массив вида:
     *
     * 'edit1' => [
     *      'NAME' => 'Название вкладки',
     *       'ITEMS' => [
     *           'ACTIVE' => 'Активность',
     *           'SORT' => '*Сортировка',
     *           'NAME' => '*Название',
     *           'PREVIEW_PICTURE' => '*Иконка',
     *           'PROPERTY_OPTIONS' => 'PROPERTY_OPTIONS'
     *       ],
     *   ],
     *
     * @return void
     */
    public function setIblockEditFormView($iblockType, $iblockCode, array $data)
    {
        $iblockId = $this->getIblockId($iblockType, $iblockCode);
        $tabs = [];
        $properties = self::getProperties($iblockId);

        $fieldSeparator = "--#--";
        $fieldStartEnd = "--";
        $endTab = ";--";
        $defaultTabCode = "";

        foreach ($data as $tabIndex => $tabData) {
            $tabElements = [];

            $tabCode = $defaultTabCode . $tabIndex;
            $tabName = $tabData['NAME'];
            $tabElements[] = $tabCode . $fieldSeparator . $tabName . $fieldStartEnd;

            foreach ($tabData['ITEMS'] as $fieldCode => $fieldName) {
                if (strpos($fieldCode, 'PROPERTY_') === 0) {
                    $fieldCode = 'PROPERTY_' . $properties[substr($fieldCode, 9)]['ID'];
                }

                if (strpos($fieldName, 'PROPERTY_') === 0) {
                    $fieldName = $properties[substr($fieldName, 9)]['NAME'];
                }

                $tabElements[] = $fieldStartEnd . $fieldCode . $fieldSeparator . $fieldName . $fieldStartEnd;
            }

            $tabs[] = join(',', $tabElements) . $endTab;
        }

        CUserOptions::SetOptionsFromArray(
            [
                [
                    'c' => 'form',
                    'n' => 'form_element_' . $iblockId,
                    'd' => 'Y',
                    'v' => ['tabs' => join('', $tabs)],
                ],
            ]
        );
    }

    /**
     * Настраивает вид формы редактирования секции ИБ по умолчанию для всех пользователей.
     *
     * @param string $iblockType
     * @param string $iblockCode
     * @param array $data массив вида:
     *
     * 'edit1' => [
     *      'NAME' => 'Название вкладки',
     *       'ITEMS' => [
     *           'ACTIVE' => 'Активность',
     *           'SORT' => '*Сортировка',
     *           'NAME' => '*Название',
     *       ],
     *   ],
     *
     * @return void
     */
    public function setIblockSectionEditFormView($iblockType, $iblockCode, array $data)
    {
        $iblockId = $this->getIblockId($iblockType, $iblockCode);
        $tabs = [];

        $fieldSeparator = "--#--";
        $fieldStartEnd = "--";
        $endTab = ";--";
        $defaultTabCode = "";

        foreach ($data as $tabIndex => $tabData) {
            $tabElements = [];

            $tabCode = $defaultTabCode . $tabIndex;
            $tabName = $tabData['NAME'];
            $tabElements[] = $tabCode . $fieldSeparator . $tabName . $fieldStartEnd;

            foreach ($tabData['ITEMS'] as $fieldCode => $fieldName) {
                $tabElements[] = $fieldStartEnd . $fieldCode . $fieldSeparator . $fieldName . $fieldStartEnd;
            }

            $tabs[] = join(',', $tabElements) . $endTab;
        }

        CUserOptions::SetOptionsFromArray(
            [
                [
                    'c' => 'form',
                    'n' => 'form_section_' . $iblockId,
                    'd' => 'Y',
                    'v' => ['tabs' => join('', $tabs)],
                ],
            ]
        );
    }

    /**
     * Настраивает вид списка разделов ИБ по умолчанию для всех пользователей.
     *
     * @param string $iblockType
     *
     * @param string $iblockCode
     * @param array $data массив вида:
     *
     * [
     *     [columns] => NAME,ID
     *     [by] => timestamp_x
     *     [order] => desc
     *     [page_size] => 20
     *]
     *
     * @return void
     */
    public function setIblockSectionListView($iblockType, $iblockCode, array $data)
    {
        $iblockId = $this->getIblockId($iblockType, $iblockCode);

        $hash = md5($iblockType . "." . $iblockId);

        CUserOptions::SetOptionsFromArray(
            [
                [
                    'c' => 'list',
                    'n' => 'tbl_iblock_list_' . $hash,
                    'v' => $data,
                ],
            ]
        );
    }

    /**
     * @return IblockHelper
     */
    protected function getIblockHelper()
    {
        if (is_null($this->iblockHelper)) {
            $this->iblockHelper = HelperManager::getInstance()->Iblock();
        }

        return $this->iblockHelper;
    }

    /**
     * @param string $type
     * @param string $code
     *
     * @return int
     */
    protected function getIblockId($type, $code)
    {
        $iblockId = $this->getIblockHelper()->getIblockId($code, $type);

        if ($iblockId <= 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'Iblock of type "%s" and code "%s" is not found.',
                    $type,
                    $code
                )
            );
        }

        return $iblockId;
    }

    /**
     * @param int $iblockId
     *
     * @return array
     */
    protected static function getProperties($iblockId)
    {
        $properties = [];

        $db = CIBlockProperty::GetList(['SORT' => 'ASC'], ['ACTIVE' => 'Y', 'IBLOCK_ID' => $iblockId]);
        while ($property = $db->Fetch()) {
            $properties[$property['CODE']] = [
                'ID'   => $property['ID'],
                'NAME' => ($property['IS_REQUIRED'] == 'Y' ? '*' : '') . $property['NAME'],
            ];
        }

        return $properties;
    }
}
