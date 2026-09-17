<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class SafeUpload
{
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/vnd.rar' => 'rar',
        'application/x-rar-compressed' => 'rar',
        'text/plain' => 'txt',
    ];

    public static function extensionFromMime(?string $mime, array $allowed): ?string
    {
        if (! $mime) {
            return null;
        }

        $mime = strtolower(trim(explode(';', $mime)[0]));
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;

        return $extension !== null && in_array($extension, $allowed, true) ? $extension : null;
    }

    public static function extensionFromUploadedFile(UploadedFile $file, array $allowed): ?string
    {
        return self::extensionFromMime($file->getMimeType(), $allowed);
    }

    public static function extensionFromPath(string $path, array $allowed): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        return self::extensionFromMime(MimeTypes::getDefault()->guessMimeType($path), $allowed);
    }

    public static function filenameForUploadedFile(UploadedFile $file, array $allowed): ?string
    {
        $extension = self::extensionFromUploadedFile($file, $allowed);

        return $extension ? Str::uuid()->toString().'.'.$extension : null;
    }

    public static function filenameForPath(string $path, array $allowed): ?string
    {
        $extension = self::extensionFromPath($path, $allowed);

        return $extension ? Str::uuid()->toString().'.'.$extension : null;
    }
}
