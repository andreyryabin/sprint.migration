<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Module;

class FileExchange
{
    private ?array $localHosts = null;

    public function exportFileById(int $fileId, string $exchangeDir = '', string $subdir = 'files'): array|false
    {
        if (!$fileId || $exchangeDir === '') {
            return false;
        }

        $file = CFile::GetFileArray($fileId);
        if (empty($file)) {
            return false;
        }

        $relativePath = trim($subdir . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'], '/');
        $target = rtrim($exchangeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        if (!$this->copyFileIdToPath($fileId, $target) && !$this->copyFileArrayToPath($file, $target)) {
            return false;
        }

        return $this->makeFileRef($relativePath, $file);
    }

    public function exportFileByLink(string $link, string $exchangeDir = '', string $subdir = 'files'): array|false
    {
        if ($link === '' || $exchangeDir === '') {
            return false;
        }

        $path = parse_url($link, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return false;
        }

        if ($this->isLocalUploadLink($link)) {
            $fileId = $this->getFileIdByUploadPath($path);
            if ($fileId) {
                $fileRef = $this->exportFileById($fileId, $exchangeDir, $subdir);
                if ($fileRef) {
                    $fileRef['source'] = $link;
                    return $fileRef;
                }
            }

            $source = Module::getDocRoot() . rawurldecode($path);
            if (is_file($source)) {
                return $this->exportLocalFile($source, $exchangeDir, $subdir, $link);
            }
        }

        if ($this->isCloudStorageLink($link)) {
            return $this->exportCloudStorageLink($link, $exchangeDir, $subdir);
        }

        return false;
    }

    public function exportTextFileLinks(array|string $texts, string $exchangeDir = '', string $subdir = 'files'): array
    {
        $result = [];
        foreach ((array)$texts as $text) {
            foreach ($this->extractFileLinks((string)$text) as $link) {
                $fileRef = $this->exportFileByLink($link, $exchangeDir, $subdir);
                if ($fileRef) {
                    $result[$link] = $fileRef;
                }
            }
        }

        return $result;
    }

    public function extractFileLinks(string $text): array
    {
        $links = [];

        $links = array_merge(
            $links,
            $this->extractAttributeFileLinks($text),
            $this->extractSrcsetFileLinks($text),
            $this->extractCssFileLinks($text)
        );

        if (preg_match_all('/\[IMG\]([^\[]+)\[\/IMG\]/iu', $text, $matches)) {
            foreach ($matches[1] as $link) {
                $links[] = trim($link);
            }
        }

        return array_values(array_unique(array_filter(
            $links,
            fn($link) => $this->isSupportedFileLink((string)$link)
        )));
    }

    public function replaceTextFileLinks(string $text, array $linkMap): string
    {
        if (empty($linkMap)) {
            return $text;
        }

        foreach ($linkMap as $oldLink => $newLink) {
            if (!is_string($oldLink) || $oldLink === '' || !is_string($newLink) || $newLink === '') {
                continue;
            }

            $text = str_replace($this->getLinkReplaceVariants($oldLink), $newLink, $text);
        }

        return $text;
    }

    public function makeFileArrayByRef(array $fileRef, string $exchangeDir = ''): array|false
    {
        if (empty($fileRef['path']) || $exchangeDir === '') {
            return false;
        }

        $path = rtrim($exchangeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($fileRef['path'], DIRECTORY_SEPARATOR);
        $file = CFile::MakeFileArray($path);
        if (empty($file)) {
            return false;
        }

        if (!empty($fileRef['name'])) {
            $file['name'] = $fileRef['name'];
        }
        if (!empty($fileRef['description'])) {
            $file['description'] = $fileRef['description'];
        }
        if (!empty($fileRef['content_type'])) {
            $file['type'] = $fileRef['content_type'];
        }

        return $file;
    }

    public function saveFileRefAndGetPath(
        array $fileRef,
        string $exchangeDir = '',
        string $module = 'sprint_migration'
    ): string {
        $file = $this->makeFileArrayByRef($fileRef, $exchangeDir);
        if (!$file) {
            return '';
        }

        Module::prepareLongDatabaseConnection();
        $fileId = CFile::SaveFile($file, $module);
        Module::reconnectDatabase();

        return $fileId ? (string)CFile::GetPath($fileId) : '';
    }

    public function copyFileArrayToPath(array $file, string $target): bool
    {
        $source = $this->getLocalFilePath($file);
        if ($source && is_file($source)) {
            return $this->copyLocalFile($source, $target);
        }

        $url = $this->getFileUrl($file);
        if ($url) {
            return $this->copyRemoteFile($url, $target);
        }

        return false;
    }

    /**
     * @throws MigrationException
     */
    private function copyFileIdToPath(int $fileId, string $target): bool
    {
        $file = CFile::MakeFileArray($fileId);
        if (empty($file['tmp_name'])) {
            return false;
        }

        return $this->copyFileArrayTmpToPath($file, $target);
    }

    /**
     * @throws MigrationException
     */
    private function copyFileArrayTmpToPath(array $file, string $target): bool
    {
        $source = (string)($file['tmp_name'] ?? '');
        if ($source === '' || !is_file($source)) {
            return false;
        }

        return $this->copyLocalFile($source, $target);
    }

    /**
     * @throws MigrationException
     */
    private function copyLocalFile(string $source, string $target): bool
    {
        Module::createDir(dirname($target));
        return copy($source, $target);
    }

    /**
     * @throws MigrationException
     */
    private function copyRemoteFile(string $url, string $target): bool
    {
        $content = $this->getRemoteFileContent($url);
        if ($content === false) {
            return false;
        }

        Module::createDir(dirname($target));
        return file_put_contents($target, $content) !== false;
    }

    private function getRemoteFileContent(string $url): string|false
    {
        if (class_exists('\Bitrix\Main\Web\HttpClient')) {
            $client = new \Bitrix\Main\Web\HttpClient([
                'socketTimeout' => 30,
                'streamTimeout' => 30,
                'redirect' => true,
            ]);

            $content = $client->get($url);
            if ($content !== false && $client->getStatus() >= 200 && $client->getStatus() < 300) {
                return $content;
            }
        }

        if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'follow_location' => 1,
                ],
                'https' => [
                    'timeout' => 30,
                    'follow_location' => 1,
                ],
            ]);

            return @file_get_contents($url, false, $context);
        }

        return false;
    }

    private function exportLocalFile(string $source, string $exchangeDir, string $subdir, string $sourceLink): array|false
    {
        $fileName = basename($source);
        $relativePath = trim($subdir . '/' . md5($sourceLink) . '_' . $fileName, '/');
        $target = rtrim($exchangeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        if (!$this->copyLocalFile($source, $target)) {
            return false;
        }

        return [
            'path' => $relativePath,
            'name' => $fileName,
            'source' => $sourceLink,
        ];
    }

    private function exportCloudStorageLink(string $url, string $exchangeDir, string $subdir): array|false
    {
        $file = CFile::MakeFileArray($url);
        if (empty($file)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $fileName = ($file['name'] ?? basename((string)$path)) ?: md5($url);
        $relativePath = trim($subdir . '/' . md5($url) . '_' . $fileName, '/');
        $target = rtrim($exchangeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        if (!$this->copyFileArrayTmpToPath($file, $target)) {
            return false;
        }

        return array_filter([
            'path' => $relativePath,
            'name' => $fileName,
            'description' => $file['description'] ?? '',
            'content_type' => $file['type'] ?? '',
            'source' => $url,
        ], fn($value) => $value !== '');
    }

    private function makeFileRef(string $relativePath, array $file): array
    {
        return array_filter([
            'path' => $relativePath,
            'name' => $file['ORIGINAL_NAME'] ?? $file['FILE_NAME'] ?? basename($relativePath),
            'description' => $file['DESCRIPTION'] ?? '',
            'content_type' => $file['CONTENT_TYPE'] ?? '',
        ], fn($value) => $value !== '');
    }

    private function extractAttributeFileLinks(string $text): array
    {
        $links = [];
        $attributes = [
            'src',
            'href',
            'data-src',
            'data-lazy-src',
            'data-original',
        ];

        if (preg_match_all('/\b(' . implode('|', $attributes) . ')\s*=\s*([\'"])(.*?)\2/iu', $text, $matches)) {
            foreach ($matches[3] as $link) {
                $links[] = $this->normalizeExtractedLink($link);
            }
        }

        return $links;
    }

    private function extractSrcsetFileLinks(string $text): array
    {
        $links = [];
        $attributes = [
            'srcset',
            'data-srcset',
        ];

        if (preg_match_all('/\b(' . implode('|', $attributes) . ')\s*=\s*([\'"])(.*?)\2/iu', $text, $matches)) {
            foreach ($matches[3] as $srcset) {
                foreach (explode(',', html_entity_decode($srcset, ENT_QUOTES | ENT_HTML5)) as $candidate) {
                    $parts = preg_split('/\s+/', trim($candidate));
                    if (!empty($parts[0])) {
                        $links[] = $parts[0];
                    }
                }
            }
        }

        return $links;
    }

    private function extractCssFileLinks(string $text): array
    {
        $links = [];

        if (preg_match_all('/url\(\s*(?:([\'"])(.*?)\1|([^)]*?))\s*\)/iu', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $links[] = $this->normalizeExtractedLink(($match[2] ?? '') !== '' ? $match[2] : ($match[3] ?? ''));
            }
        }

        return $links;
    }

    private function normalizeExtractedLink(string $link): string
    {
        return trim(html_entity_decode($link, ENT_QUOTES | ENT_HTML5));
    }

    private function getLinkReplaceVariants(string $link): array
    {
        return array_values(array_unique(array_filter([
            $link,
            htmlspecialchars($link, ENT_QUOTES | ENT_HTML5),
            htmlspecialchars($link, ENT_COMPAT | ENT_HTML5),
        ])));
    }

    private function getLocalFilePath(array $file): string
    {
        if (empty($file['SRC'])) {
            return '';
        }

        $src = (string)$file['SRC'];
        if ($this->isUrl($src)) {
            return '';
        }

        return Module::getDocRoot() . rawurldecode($src);
    }

    private function getFileUrl(array $file): string
    {
        if (empty($file['SRC'])) {
            return '';
        }

        $src = (string)$file['SRC'];
        return $this->isUrl($src) ? $src : '';
    }

    private function getFileIdByUploadPath(string $path): int
    {
        $path = rawurldecode(parse_url($path, PHP_URL_PATH) ?: '');
        if (!str_starts_with($path, '/upload/')) {
            return 0;
        }

        $relativePath = trim(substr($path, strlen('/upload/')), '/');
        $subdir = trim(dirname($relativePath), '.');
        $fileName = basename($relativePath);

        if ($subdir === '' || $fileName === '') {
            return 0;
        }

        $file = CFile::GetList(
            ['ID' => 'DESC'],
            [
                'SUBDIR' => $subdir,
                'FILE_NAME' => $fileName,
            ]
        )->Fetch();

        return (int)($file['ID'] ?? 0);
    }

    private function isSupportedFileLink(string $link): bool
    {
        if ($this->isLocalUploadLink($link)) {
            return true;
        }

        return $this->isCloudStorageLink($link);
    }

    private function isLocalUploadLink(string $link): bool
    {
        $path = parse_url($link, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/upload/')) {
            return false;
        }

        $host = parse_url($link, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return true;
        }

        return $this->isLocalHost($host);
    }

    private function isLocalHost(string $host): bool
    {
        $host = $this->normalizeHost($host);
        if ($host === '') {
            return false;
        }

        foreach ($this->getLocalHosts() as $localHost) {
            if ($host === $localHost) {
                return true;
            }

            if (str_starts_with($localHost, '*.')) {
                $suffix = substr($localHost, 2);
                if ($host === $suffix || str_ends_with($host, '.' . $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getLocalHosts(): array
    {
        if ($this->localHosts !== null) {
            return $this->localHosts;
        }

        $hosts = [
            $_SERVER['HTTP_HOST'] ?? '',
            $_SERVER['SERVER_NAME'] ?? '',
        ];

        if (class_exists('\COption')) {
            $hosts[] = \COption::GetOptionString('main', 'server_name', '');
        }

        if (class_exists('\CSite')) {
            $by = 'sort';
            $order = 'asc';
            $dbres = \CSite::GetList($by, $order, []);
            while ($site = $dbres->Fetch()) {
                $hosts[] = $site['SERVER_NAME'] ?? '';
                foreach (preg_split('/[\s,;]+/', (string)($site['DOMAINS'] ?? '')) as $domain) {
                    $hosts[] = $domain;
                }
            }
        }

        $hosts = array_map(fn($host) => $this->normalizeHost((string)$host), $hosts);

        $this->localHosts = array_values(array_unique(array_filter($hosts)));

        return $this->localHosts;
    }

    private function normalizeHost(string $host): string
    {
        $host = trim(strtolower($host));
        if ($host === '') {
            return '';
        }

        if (str_contains($host, '://')) {
            $host = (string)parse_url($host, PHP_URL_HOST);
        }

        return trim((string)preg_replace('/:\d+$/', '', $host), '.');
    }

    private function isCloudStorageLink(string $link): bool
    {
        if (!$this->isUrl($link)) {
            return false;
        }

        if (!class_exists('\CCloudStorage') && class_exists('\CModule')) {
            \CModule::IncludeModule('clouds');
        }

        if (!class_exists('\CCloudStorage') || !method_exists('\CCloudStorage', 'FindBucketByFile')) {
            return false;
        }

        return is_object(\CCloudStorage::FindBucketByFile($link));
    }

    private function isUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
