<?php

namespace Gdronov\DromParser;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс реализует получение ID объявлений, ссылки на них и некоторые характеристики авто
 * из запроса на поиск по определённым критериям. Поисковый запрос возвращает json
 */
class SearchCollector
{
    private ?int $totalCount;   // Общее количество объявлений в результатах поиска (указано в пагинации json ответа)
    private ?int $itemsPerPage; // Количество объявлений на странице поиска (указано в пагинации json ответа)

    public function __construct(
        private readonly Client $client,
        private readonly string $base_uri,
        private readonly string $query
    ) {}

    /**
     * @throws GuzzleException
     */
    public function getData(): Generator
    {
        $this->totalCount = null;
        $this->itemsPerPage = null;
        $page = 1;

        do {
            $list = $this->getDataByPage($page); // Получаем ссылки на объявления из запроса на поиск
            if (!$list) { // Не удалось определить список объявлений
                return;
            }
            foreach ($list as $info) {
                yield $info;
            }
            $page++;
        } while ($this->needRequestPage($page));
    }

    private function needRequestPage(int $page): bool
    {
        return $this->itemsPerPage &&
               $this->totalCount &&
               ceil($this->totalCount / $this->itemsPerPage) >= $page;
    }

    /**
     * @param int $page
     *
     * @return array|null
     * @throws GuzzleException
     */
    private function getDataByPage(int $page): ?array
    {
        $result = [];
        $response = $this->client->get(
            $this->base_uri . ($page > 1 ? "page$page/" : ''),  // Добавляем номер страницы в путь урла запроса
            [
                // Эти заголовки необходимы, без них сервер не вернет ответ
                'headers' => [
                    'Accept' => 'application/json',
                    'Host' => 'auto.drom.ru',
                ],
                'query' => $this->query,
                // 'proxy' => ''

            ]
        );

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $content = $response->getBody()->getContents();
        $data = json_decode($content);
        if (!is_object($data) || !$data->bullList) {
            return null;
        }

        $bullsData = $data->bullList->bullsData;
        $bulls = is_array($bullsData) && isset($bullsData[0])
            ? $bullsData[0]->bulls
            : null;

        if (!is_array($bulls)) {  // Не удалось определить в json список с объявлениями
            return null;
        }

        // Из json ответа получаем информацию о пагинации
        // При первом запросе запомним общее количество найденных объявлений и их количество на одной странице
        if ($this->totalCount === null) {
            $this->totalCount = (int)$bullsData[0]?->pagination?->total;
        }
        if ($this->itemsPerPage === null) {
            $this->itemsPerPage = (int)$bullsData[0]?->pagination?->itemsPerPage;
        }

        foreach ($bulls as $item) {
            if (
                is_numeric($item?->bullId) &&                   // Числовой ID объявления
                filter_var($item?->url, FILTER_VALIDATE_URL)    // Есть корректный урл
            ) {
                $result[$item->bullId] = [
                    'bullId' => $item->bullId,
                    'url' => $item->url,

                    // Некоторую нужную информацию по объявлению можно получить уже тут
                    'frameType' => FrameType::name($item?->frameType), // Тип кузова по его идентификатору
                    'price' => $item?->price,                          // Цена продажи
                    'mark' => $this->getMark($item?->title),           // Марка авто
                    'model' => $this->getModel($item?->title),         // Модель
                ];
            }
        }

        return $result;
    }
    private function getMark(?string $title): ?string
    {
        if (!$title) {
            return null;
        }
        $parts = mb_split('\s+', $title);

        return is_array($parts) && $parts[0]
            ? $parts[0]
            : null;
    }

    private function getModel(?string $title): ?string
    {
        if (!$title) {
            return null;
        }
        $parts = mb_split('\s+', $title);

        return is_array($parts) && $parts[1]
            ? mb_ereg_replace(',', '', $parts[1])
            : null;
    }
}
