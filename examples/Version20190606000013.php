<?php

namespace Sprint\Migration;

class Version20190606000013 extends Version
{
    protected $description = "Пример создания категорий инфоблока из древовидной структуры";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_news');

        $helper->Iblock()->addSectionsFromTree($iblockId, [
            [
                'NAME' => 'Корневая категория 1',
            ],
            [
                'NAME' => 'Корневая категория 2',
                'CHILDS' => [
                    [
                        'NAME' => 'Подкатегория 2/1',
                        'CHILDS' => [
                            [
                                'NAME' => 'Подкатегория 2/1/1',
                            ],
                            [
                                'NAME' => 'Подкатегория 2/1/2',
                            ],
                        ],
                    ],
                    [
                        'NAME' => 'Подкатегория 2/2',
                    ],
                ],
            ],
        ]);
    }

    public function down()
    {
        //your code ...
    }
}
