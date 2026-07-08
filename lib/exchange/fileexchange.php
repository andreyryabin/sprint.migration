<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Module;

class FileExchange
{
    /**
     * @throws MigrationException
     */
    public function copyFileToExchange(array $file, string $exchangeDir): void
    {
        if (empty($file['SUBDIR']) || empty($file['FILE_NAME'])) {
            return;
        }

        $source = $this->getSourcePath($file);
        if ($source === '' || !is_file($source)) {
            return;
        }

        $target = rtrim($exchangeDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . trim($file['SUBDIR'], DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $file['FILE_NAME'];

        Module::createDir(dirname($target));
        copy($source, $target);
    }

    public function makeFileArray(string $path, array $attributes = []): false|array
    {
        $file = CFile::MakeFileArray($path);
        if (empty($file)) {
            return false;
        }

        if (!empty($attributes['name'])) {
            $file['name'] = $attributes['name'];
        }
        if (!empty($attributes['description'])) {
            $file['description'] = $attributes['description'];
        }
        if (!empty($attributes['content_type'])) {
            $file['type'] = $attributes['content_type'];
        }

        return $file;
    }

    private function getSourcePath(array $file): string
    {
        if (empty($file['ID'])) {
            return '';
        }

        $fileArray = CFile::MakeFileArray((int)$file['ID']);
        $tmpName = (string)($fileArray['tmp_name'] ?? '');

        return ($tmpName !== '' && is_file($tmpName)) ? $tmpName : '';
    }
}
