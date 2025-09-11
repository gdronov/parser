<?php

namespace Gdronov\DromParser;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class FileHelper
{
    /**
     * Рекурсивно очищаем содержимое каталога
     */
    public static function clearDir(string $dir, ?string $skip = null): void
    {
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var SplFileInfo $info */
            foreach ($it as $info) {
                if ($skip === $info->getPathname()) {
                    continue;
                }
                if ($info->isDir()) {
                    @rmdir($info->getPathname());
                } else {
                    @unlink($info->getPathname());
                }
            }
        }
    }

    public static function createZip(string $dir, string $zipFileName): void
    {
        $zip = new ZipArchive();
        $zip->open($dir . $zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::CHILD_FIRST | RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $info */
        foreach ($it as $info) {
            if ($info->isFile()) {
                $fn = substr($info->getPathname(), strlen($dir)); // Путь к файлу внутри архива
                $zip->addFile($info->getPathname(), $fn);
            }
        }
        $zip->close();
    }
}
