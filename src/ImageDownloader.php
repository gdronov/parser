<?php

namespace Gdronov\DromParser;

use GuzzleHttp\Client;
use Throwable;

readonly class ImageDownloader
{
    public function __construct(
        private Client $client,
        private string $rootDir
    )
    {}

    public function download(array $links, string $childDir, string $referer): void
    {
        // Скачаем картинки объявления в свой каталог
        $dir = $this->rootDir . $childDir;
        mkdir($dir);
        foreach ($links as $idx => $link) {
            usleep(rand(100000, 1000000));  // Поставим задержку между скачиванием картинок, хотя тут проблем не наблюдал
            $ext = pathinfo($link, PATHINFO_EXTENSION);
            $img = $dir . "/$idx." . ($ext ?: 'jpg');
            try {
                $response = $this->client->get($link, [
                    'headers' => [
                        'Referer' => $referer,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                    ],
                ]);
                if ($response->getStatusCode() !== 200) {
                    continue;
                }
                file_put_contents($img, (string)$response->getBody());
            } catch (Throwable) {
            }
        }
    }
}
