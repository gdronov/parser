<?php

namespace Gdronov\DromParser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use LogicException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Класс реализует парсинг отдельной страницы объявления
 */
class PageParser
{
    private Crawler $crawler;
    public function __construct(private readonly Client $client)
    {
        $this->crawler = new Crawler();
    }

    /**
     * @throws GuzzleException
     */
    public function parsePage(string $url): array
    {
        $response = $this->client->get($url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Host' => 'auto.drom.ru',
                'Connection' => 'keep-alive',
            ],
        ]);
        $content = $response->getBody()->getContents();
        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $this->crawler->clear();
        $this->crawler->addHtmlContent($content, 'CP1251');

        $data = [
            'dealMark' =>  $this->getDealMark(),
            'generation' => $this->findCarSpec('Поколение'),
            'equipment' => $this->findCarSpec('Комплектация'),
            'color' => $this->findCarSpec('Цвет'),
            'enginePower' => $this->getEnginePower(),
            'images' => $this->getImages(),
        ];
        $data += $this->getMileAgeInfo(); // Информация по пробегу
        $data += $this->getEngineInfo();  // Информация по двигателю

        return $data;
    }

    /**
     * Парсинг конкретной характеристики
     * Эти характеристики перечислены построчно в единственной на странице <table>
     */
    private function findCarSpec(string $name): ?string
    {
        try {
            $th = $this->crawler->filter('table > tbody > tr > th:contains("' . $name . '")');

            return $th->siblings()
                ->filter('td')
                ->first()
                ->text();
        } catch (LogicException) {
            return null;
        }
    }

    /**
     *  Отметка цены Дромом (Хорошая, Нормальная, Высокая)
     */
    private function getDealMark(): ?string
    {
        try {
            $div = $this->crawler->filter('div[data-ga-stats-name="good_deal_mark"]');
            return $div->count() ? $div->first()->innerText() : null;
        } catch (LogicException) {
            return null;
        }
    }

    /**
     * Пробег и отметка "без пробега по РФ"
     */
    private function getMileAgeInfo(): ?array
    {
        $info = [
            'mileage' => null,
            'noRussiaMileage' => null,
        ];

        $value = $this->findCarSpec('Пробег');
        if ($value) {
            $info['mileage'] = preg_replace('/\D/', '', $value); // Оставляем только цифры

            $no_rf = mb_stripos($this->removeSpaces($value), 'безпробегапорф') !== false;
            $info['noRussiaMileage'] = $no_rf ? 'без пробега по РФ' : null; // по ТЗ надо вывести этот признак текстом
        }

        return $info;
    }

    private function getEnginePower(): ?string
    {
        $value = $this->findCarSpec('Мощность');
        if ($value && preg_match('/(\d+)л\.с\./iU', $this->removeSpaces($value), $m)) {
            return $m[1];   // Возвращаем только цифры
        }

        return null;
    }

    /**
     * Топливо и объём двигателя
     */
    private function getEngineInfo(): array
    {
        $info = [
            'fuel' => null,
            'engineVolume' => null,
        ];
        $value = $this->findCarSpec('Двигатель');
        if (!$value) {
            return $info;
        }

        $parts = mb_split(',', $value);
        if ($parts !== false) {
            $info['fuel'] = $parts[0];
            $info['engineVolume'] = $parts[1] ? preg_replace('/[^.0-9]/', '', $parts[1]) : null;
        }

        return $info;
    }

    /**
     * Ссылки на самые большие изображения из галереи объявления
     */
    private function getImages(): array
    {
        try {
            $gallery = $this->crawler->filter('div[data-ftid="bull-page_bull-gallery_thumbnails"]');

            // Заметил, что иногда в галерее возникает дополнительный div
            return $gallery->filter('a')->count()
                ? $gallery->filter('a')->extract(['href'])
                : $gallery->filter('div > a')->extract(['href']);
        } catch (LogicException) {
            return [];
        }
    }

    /**
     * Удаляем неразрывный пробел другие whitespace символы
     */
    private function removeSpaces(string $text): ?string
    {
        return preg_replace('/(\s| )/', '', $text);
    }
}
