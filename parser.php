<?php

require 'vendor/autoload.php';

use Gdronov\DromParser\FileHelper;
use Gdronov\DromParser\ImageDownloader;
use Gdronov\DromParser\PageParser;
use GuzzleHttp\Client;
use Gdronov\DromParser\SearchCollector;

$cfg = require('config.php');

@mkdir($cfg->dumpDir);
FileHelper::clearDir($cfg->dumpDir); // Очищаем от прошлого содержимого

$csv = fopen($cfg->dumpDir . $cfg->dumpCsv, 'w');
fputcsv($csv, array_values($cfg->dumpFields));

// Отдельный клиент для скачивания картинок
$imgClient = new Client(['http_errors' => false, 'timeout' => 30]);
$imgDownloader = new ImageDownloader($imgClient, $cfg->dumpDir);

// Сервис для поискового запроса по критериям
$client = new Client([
    'cookies' => true,
    'headers' => ['User-Agent' => $cfg->userAgent],
]);
$searchCollector = new SearchCollector($client, $cfg->searchBaseUri, $cfg->searchQuery);

// Сервис для парсинга страницы конкретного объявления
// Используем тот-же клиент, иначе наблюдается бан 429 Too Many Requests
// Вероятно сверяются ip и cookie от запроса к запросу
$pageParser = new PageParser($client);

// Цикл по объявлениям из поиска (его получаем из json ответа)
foreach ($searchCollector->getData() as $data) {
    // Соберем и добавим остальную информацию уже со страницы объявления
    $data += $pageParser->parsePage($data['url']);

    fputcsv($csv, array_map(
        // Сохраним в csv только необходимые поля в соответствии со списком и по порядку из конфига
        function($field) use ($data) {
            array_walk($data, function (&$v) { $v ??= 'null'; }); // по ТЗ в csv надо вывести 'null' строкой
            return array_key_exists($field, $data) ? $data[$field] : 'null';
        },
        array_keys($cfg->dumpFields)
    ));

    // Скачаем картинки из объявления
    $imgDownloader->download($data['images'], $data['bullId'], $data['url']);
}

FileHelper::createZip($cfg->dumpDir, $cfg->dumpZip);
FileHelper::clearDir($cfg->dumpDir, $cfg->dumpDir . $cfg->dumpZip);
