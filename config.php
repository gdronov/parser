<?php

return (object)[
    'searchBaseUri' => 'https://auto.drom.ru/all/',
    // GET-параметры запроса на поиск авто по критериям:
    //  - Toyota Crown 15-го и 16-го поколений;
    //  - в городах Владивосток и Уссурийск;
    //  - только авто в наличии;
    //  - с документами в порядке;
    //  - не требуется ремонт;
    'searchQuery' => 'cid[]=23&cid[]=170&multiselect[]=9_4_15_all&multiselect[]=9_4_16_all&pts=2&damaged=2&whereabouts[]=0&order=price',
    'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',

    'dumpDir' => __DIR__ . DIRECTORY_SEPARATOR . 'dump' . DIRECTORY_SEPARATOR,
    'dumpZip' => 'Result_Crown.zip',
    'dumpCsv' => 'Data.csv',
    'dumpFields' => [
        'bullId' => 'ID объявления',
        'url' => 'URL объявления',
        'mark' => 'Марка авто',
        'model' => 'Модель авто',
        'price' => 'Цена продажи',
        'dealMark' => 'Отметка цены',
        'generation' => 'Поколение авто',
        'equipment' => 'Комплектация авто',
        'mileage' => 'Пробег',
        'noRussiaMileage' => 'Пробег по РФ',
        'color' => 'Цвет',
        'frameType' => 'Тип кузова',
        'enginePower' => 'Мощность двигателя',
        'fuel' => 'Тип топлива',
        'engineVolume' => 'Объем двигателя',
    ],
];
