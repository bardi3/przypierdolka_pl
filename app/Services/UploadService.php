<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Obsługa bezpiecznego uploadu obrazków (walidacja MIME, rozmiaru, rozszerzenia).
 */
final class UploadService
{
    /** @var array<int, string> */
    private array $allowedMime;
    /** @var array<int, string> */
    private array $allowedExt;
    private int $maxSize;
    private string $uploadDir;

    public function __construct()
    {
        $cfg = (array)Config::get('app.uploads', []);
        $this->allowedMime = $cfg['allowed_mime'] ?? ['image/jpeg', 'image/png', 'image/webp'];
        $this->allowedExt  = $cfg['allowed_ext'] ?? ['jpg', 'jpeg', 'png', 'webp'];
        $this->maxSize     = (int)($cfg['max_size'] ?? 4 * 1024 * 1024);
        $this->uploadDir   = (string)Config::get('app.paths.uploads') . '/stories';
    }

    /**
     * @param array<string, mixed> $file element z $_FILES
     * @return array{ok:bool, path?:string, error?:string}
     */
    public function handle(array $file): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Błąd przesyłania pliku.'];
        }

        if (($file['size'] ?? 0) > $this->maxSize) {
            return ['ok' => false, 'error' => 'Plik jest zbyt duży.'];
        }

        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Nieprawidłowy plik.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!in_array($mime, $this->allowedMime, true)) {
            return ['ok' => false, 'error' => 'Niedozwolony typ pliku.'];
        }

        $ext = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExt, true)) {
            return ['ok' => false, 'error' => 'Niedozwolone rozszerzenie pliku.'];
        }

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0775, true);
        }

        $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $this->uploadDir . '/' . $name;

        if (!move_uploaded_file($tmp, $dest)) {
            return ['ok' => false, 'error' => 'Nie udało się zapisać pliku.'];
        }

        // Ścieżka publiczna (relatywna do public/)
        return ['ok' => true, 'path' => 'assets/uploads/stories/' . $name];
    }
}
