<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class MediaFormats
{
    public const IMAGES = [
        'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png',
        'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif',
        'image/heic' => 'heic', 'image/heif' => 'heif',
        'image/heic-sequence' => 'heic', 'image/heif-sequence' => 'heif',
        'image/bmp' => 'bmp', 'image/x-ms-bmp' => 'bmp', 'image/tiff' => 'tiff',
    ];
    public const DOCUMENTS = [
        'application/pdf' => 'pdf', 'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt', 'text/csv' => 'csv', 'application/csv' => 'csv',
        'application/rtf' => 'rtf', 'text/rtf' => 'rtf',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/vnd.oasis.opendocument.presentation' => 'odp',
    ];

    public static function mimeTypes(bool $documents = false): array
    {
        return array_keys($documents ? self::IMAGES + self::DOCUMENTS : self::IMAGES);
    }

    public static function fail(string $message, string $field = 'file'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /** Inspect server-detected content, never trust the client's Content-Type. */
    public static function inspect(UploadedFile $file, bool $documents, string $field = 'file'): array
    {
        if (!$file->isValid() || !is_readable($file->getPathname())) {
            self::fail('The uploaded file could not be read. Please select it again.', $field);
        }
        $limit = (int) config($documents ? 'dura-media.max_document_size_kb' : 'dura-media.max_image_size_kb', $documents ? 51200 : 25600);
        if ($file->getSize() <= 0 || $file->getSize() > $limit * 1024) {
            self::fail('File is empty or exceeds the upload limit of ' . round($limit / 1024) . ' MB.', $field);
        }
        // Inspect bytes directly. UploadedFile test doubles may infer MIME from the filename.
        if (!class_exists(\finfo::class)) {
            self::fail('Enable the PHP fileinfo extension to validate uploads.', $field);
        }
        $mime = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname()));
        $ext = strtolower($file->getClientOriginalExtension());
        // Validate ISO-BMFF brand when libmagic cannot identify HEIC/AVIF.
        if (in_array($mime, ['application/octet-stream', 'application/x-empty'], true)) {
            $stream = fopen($file->getPathname(), 'rb');
            $header = $stream ? fread($stream, 128) : '';
            if ($stream) { fclose($stream); }
            if (substr($header, 4, 4) === 'ftyp') {
                $brands = substr($header, 8, 4) . substr($header, 16);
                if (str_contains($brands, 'avif') || str_contains($brands, 'avis')) { $mime = 'image/avif'; }
                elseif (str_contains($brands, 'heic') || str_contains($brands, 'heix') || str_contains($brands, 'hevc') || str_contains($brands, 'hevx')) { $mime = 'image/heic'; }
                elseif (str_contains($brands, 'mif1') || str_contains($brands, 'msf1')) { $mime = 'image/heif'; }
            }
        }

        // Some libmagic builds identify an Office package as a ZIP container.
        if ($documents && in_array($mime, ['application/zip', 'application/x-zip-compressed'], true)
            && in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
            if (!class_exists(\ZipArchive::class)) {
                self::fail('Office document inspection needs the PHP ZIP extension on the server.', $field);
            }
            $zip = new \ZipArchive();
            if ($zip->open($file->getPathname()) !== true) {
                self::fail('This Office document is damaged.', $field);
            }
            try {
                $entry = ['docx' => 'word/document.xml', 'xlsx' => 'xl/workbook.xml', 'pptx' => 'ppt/presentation.xml'][$ext];
                if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName($entry) === false) {
                    self::fail('The document contents do not match its file format.', $field);
                }
                $mime = array_search($ext, self::DOCUMENTS, true);
            } finally {
                $zip->close();
            }
        }
        $allowed = $documents ? self::IMAGES + self::DOCUMENTS : self::IMAGES;
        if (!isset($allowed[$mime])) {
            self::fail('This file format is not supported. Select a supported photo or document.', $field);
        }
        $extension = $allowed[$mime];
        if ($documents && $mime === 'text/plain' && $ext === 'csv') {
            $extension = 'csv';
        }
        return ['mime' => $mime, 'extension' => $extension, 'image' => isset(self::IMAGES[$mime])];
    }

    public static function browserImage(?string $mime): bool
    {
        return in_array(strtolower((string) $mime), ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/bmp'], true);
    }
}
