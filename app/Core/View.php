<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * System szablonów PHP. Renderuje pliki z templates/ z layoutem.
 * Template'y wyświetlają wyłącznie dane (escape przez helper e()).
 */
final class View
{
    private string $templatePath;

    /** @var array<string, mixed> Dane współdzielone dla wszystkich widoków */
    private array $shared = [];

    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/');
    }

    /**
     * Dane dostępne w każdym widoku (np. auth, csrf, flash).
     * @param array<string, mixed> $data
     */
    public function share(array $data): void
    {
        $this->shared = array_merge($this->shared, $data);
    }

    /**
     * Renderuje widok wewnątrz layoutu i zwraca HTML.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], string $layout = 'layout/main'): string
    {
        $content = $this->renderPartial($template, $data);

        if ($layout === '') {
            return $content;
        }

        return $this->renderPartial($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Renderuje pojedynczy plik szablonu bez layoutu.
     *
     * @param array<string, mixed> $data
     */
    public function renderPartial(string $template, array $data = []): string
    {
        $file = $this->templatePath . '/' . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Szablon nie istnieje: {$template} ({$file})");
        }

        $vars = array_merge($this->shared, $data);

        // 'view' pozwala na zagnieżdżone partiale i includowanie z poziomu szablonu
        $view = $this;

        ob_start();
        (static function () use ($file, $vars, $view): void {
            extract($vars, EXTR_SKIP);
            require $file;
        })();

        return (string)ob_get_clean();
    }
}
