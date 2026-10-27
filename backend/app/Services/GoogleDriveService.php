<?php

namespace App\Services;

class GoogleDriveService
{
    public static function extractFolderId(string $urlOrId): ?string
    {
        $trimmed = trim($urlOrId);
        if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $trimmed, $m)) {
            return $m[1];
        }
        if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $trimmed, $m)) {
            return $m[1];
        }
        if (preg_match('/^[a-zA-Z0-9_-]{20,55}$/', $trimmed)) {
            return $trimmed;
        }

        return null;
    }

    public static function extractFileId(string $urlOrId): ?string
    {
        $trimmed = trim($urlOrId);
        if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $trimmed, $m)) {
            return $m[1];
        }
        if (preg_match('#/d/([a-zA-Z0-9_-]+)#', $trimmed, $m)) {
            return $m[1];
        }
        if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $trimmed, $m)) {
            return $m[1];
        }
        if (preg_match('/^[a-zA-Z0-9_-]{25,45}$/', $trimmed)) {
            return $trimmed;
        }

        return null;
    }

    public static function fetchPhotosFromFolder(string $folderUrlOrId): array
    {
        $folderId = self::extractFolderId($folderUrlOrId);
        if (! $folderId) {
            return [];
        }

        $url = "https://drive.google.com/drive/folders/{$folderId}";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n" .
                            "Accept-Language: en-US,en;q=0.5\r\n",
                'timeout' => 15,
            ]
        ]);

        $html = @file_get_contents($url, false, $context);
        if (! $html) {
            return [];
        }

        $photos = [];

        // 1. Tooltip pattern
        if (preg_match_all('/data-id="([a-zA-Z0-9_-]{25,45})"[^>]*data-tooltip="([^"]+)\s+Image"/i', $html, $m1, PREG_SET_ORDER)) {
            foreach ($m1 as $row) {
                $id = $row[1];
                $name = trim($row[2]);
                $photos[$id] = $name;
            }
        }

        // 2. Aria-label pattern
        if (preg_match_all('/aria-label="([^"]+)\s+Image Shared".*?data-id="([a-zA-Z0-9_-]{25,45})"/s', $html, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $row) {
                $name = trim($row[1]);
                $id = $row[2];
                $photos[$id] = $name;
            }
        }

        // 3. JSON metadata pattern in Drive scripts
        if (preg_match_all('/"([a-zA-Z0-9_-]{28,40})",\["([^"]+\.(?:jpg|jpeg|png|webp|JPG|PNG))"/i', $html, $m3, PREG_SET_ORDER)) {
            foreach ($m3 as $row) {
                $id = $row[1];
                $name = trim($row[2]);
                $photos[$id] = $name;
            }
        }

        $result = [];
        foreach ($photos as $id => $name) {
            $result[] = [
                'id' => $id,
                'name' => $name,
                'path' => "https://lh3.googleusercontent.com/d/{$id}",
                'thumb' => "https://lh3.googleusercontent.com/d/{$id}=w800",
            ];
        }

        return $result;
    }
}
