<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Przetwarzanie awatarów: walidacja uploadu, resize, kompresja WebP.
 */
final class AvatarService
{
    private const PUBLIC_PREFIX = 'assets/uploads/avatars/';

    private string $uploadDir;
    private string $publicPath;
    private int $maxUpload;
    private int $outputSize;
    private int $webpQuality;
    private int $maxDimension;

    /** @var array<int, string> */
    private array $allowedMime;

    public function __construct()
    {
        $cfg = (array)Config::get('app.avatars', []);
        $uploadCfg = (array)Config::get('app.uploads', []);

        $this->uploadDir = (string)Config::get('app.paths.uploads') . '/avatars';
        $this->publicPath = (string)Config::get('app.paths.public');
        $this->maxUpload = (int)($cfg['max_upload'] ?? 2 * 1024 * 1024);
        $this->outputSize = (int)($cfg['output_size'] ?? 256);
        $this->webpQuality = (int)($cfg['webp_quality'] ?? 82);
        $this->maxDimension = (int)($cfg['max_dimension'] ?? 2048);
        $this->allowedMime = $uploadCfg['allowed_mime'] ?? ['image/jpeg', 'image/png', 'image/webp'];
    }

    public function canProcess(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * @param array<string, mixed> $file element $_FILES['avatar']
     * @return array{ok:bool, path?:string, url?:string, error?:string}
     */
    public function storeFromUpload(array $file, int $userId, ?string $oldPath = null): array
    {
        if (!$this->canProcess()) {
            return ['ok' => false, 'error' => 'Serwer nie obsługuje przetwarzania obrazów (brak GD/WebP).'];
        }

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $mb = (int)round($this->maxUpload / (1024 * 1024));
            $msg = match ((int)($file['error'] ?? 0)) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Plik jest zbyt duży (max {$mb} MB).",
                UPLOAD_ERR_PARTIAL   => 'Plik został przesłany tylko częściowo — spróbuj ponownie.',
                UPLOAD_ERR_NO_FILE   => 'Nie wybrano pliku.',
                default              => 'Błąd przesyłania pliku.',
            };
            return ['ok' => false, 'error' => $msg];
        }

        if (($file['size'] ?? 0) > $this->maxUpload) {
            $mb = (int)round($this->maxUpload / (1024 * 1024));
            return ['ok' => false, 'error' => "Plik jest zbyt duży (max {$mb} MB)."];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Nieprawidłowy plik.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!in_array($mime, $this->allowedMime, true)) {
            return ['ok' => false, 'error' => 'Dozwolone formaty: JPG, PNG, WebP.'];
        }

        $imageInfo = @getimagesize($tmp);
        if ($imageInfo === false) {
            return ['ok' => false, 'error' => 'Nie udało się odczytać obrazu.'];
        }

        $width = (int)$imageInfo[0];
        $height = (int)$imageInfo[1];
        if ($width < 64 || $height < 64) {
            return ['ok' => false, 'error' => 'Obraz jest zbyt mały (min. 64×64 px).'];
        }
        if ($width > $this->maxDimension || $height > $this->maxDimension) {
            return ['ok' => false, 'error' => 'Obraz jest zbyt duży (max ' . $this->maxDimension . ' px).'];
        }

        $source = $this->loadImage($tmp, $mime);
        if ($source === null) {
            return ['ok' => false, 'error' => 'Nie udało się wczytać obrazu.'];
        }

        $size = min($width, $height);
        $cropX = (int)max(0, ($width - $size) / 2);
        $cropY = (int)max(0, ($height - $size) / 2);

        $square = imagecreatetruecolor($size, $size);
        if ($square === false) {
            return ['ok' => false, 'error' => 'Błąd przetwarzania obrazu.'];
        }

        imagealphablending($square, false);
        imagesavealpha($square, true);
        $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
        imagefill($square, 0, 0, $transparent);

        if (!imagecopyresampled($square, $source, 0, 0, $cropX, $cropY, $size, $size, $size, $size)) {
            return ['ok' => false, 'error' => 'Błąd kadrowania obrazu.'];
        }

        $output = imagecreatetruecolor($this->outputSize, $this->outputSize);
        if ($output === false) {
            return ['ok' => false, 'error' => 'Błąd skalowania obrazu.'];
        }

        imagealphablending($output, true);
        imagesavealpha($output, true);
        if (!imagecopyresampled($output, $square, 0, 0, 0, 0, $this->outputSize, $this->outputSize, $size, $size)) {
            return ['ok' => false, 'error' => 'Błąd skalowania obrazu.'];
        }

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0775, true);
        }

        $filename = 'u' . $userId . '_' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.webp';
        $absolute = $this->uploadDir . '/' . $filename;

        if (!imagewebp($output, $absolute, $this->webpQuality)) {
            return ['ok' => false, 'error' => 'Nie udało się zapisać awatara.'];
        }

        @chmod($absolute, 0644);

        $relative = self::PUBLIC_PREFIX . $filename;
        $this->deleteFile($oldPath);

        return [
            'ok'   => true,
            'path' => $relative,
            'url'  => user_avatar_url($relative),
        ];
    }

    public function deleteFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        if (!str_starts_with($relativePath, self::PUBLIC_PREFIX)) {
            return;
        }

        $absolute = $this->publicPath . '/' . ltrim($relativePath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * @return \GdImage|null
     */
    private function loadImage(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path) ?: null,
            'image/png'  => @imagecreatefrompng($path) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            'image/gif'  => @imagecreatefromgif($path) ?: null,
            default      => null,
        };
    }
}
