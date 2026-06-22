<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Config;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Models\Story;
use App\Models\StoryImage;
use App\Services\ImageStoryService;
use App\Services\ShareImageService;
use App\Services\StoryService;

/**
 * Udostępnianie historii: przekierowanie kanoniczne oraz serwowanie obrazka social.
 */
final class ShareController extends Controller
{
    private StoryService $storyService;
    private ImageStoryService $imageService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $storyModel = new Story($this->db);
        $shareImage = new ShareImageService($storyModel);
        $this->storyService = new StoryService($storyModel, $this->cache, 12, $shareImage);
        $this->imageService = new ImageStoryService(new StoryImage($this->db), $shareImage);
    }

    /**
     * Krótki link udostępniania -> przekierowanie na kanoniczny adres historii.
     */
    public function go(string $slug): Response
    {
        $story = $this->storyService->getBySlug($slug);
        if ($story === null) {
            throw HttpException::notFound();
        }
        return Response::redirect($this->url('/historia/' . $story['slug']), 301);
    }

    /**
     * Serwuje wygenerowany obrazek historii (og:image). Generuje w razie potrzeby.
     */
    public function image(string $slug): Response
    {
        return $this->serveImage($slug);
    }

    /** Legacy PNG URL — przekierowanie na WebP. */
    public function imageLegacy(string $slug): Response
    {
        $story = $this->storyService->getBySlug($slug);
        if ($story === null) {
            throw HttpException::notFound();
        }

        $publicPath = $this->imageService->shareImagePath($story);

        return Response::redirect($this->url($publicPath), 301);
    }

    private function serveImage(string $slug): Response
    {
        $story = $this->storyService->getBySlug($slug);
        if ($story === null) {
            throw HttpException::notFound();
        }

        $publicPath = $this->imageService->shareImagePath($story);
        $absolute = (string)Config::get('app.paths.public') . '/' . ltrim($publicPath, '/');

        if (!is_file($absolute)) {
            throw HttpException::notFound('Brak obrazka.');
        }

        $body = (string)file_get_contents($absolute);
        $mime = str_ends_with(strtolower($publicPath), '.webp') ? 'image/webp' : 'image/png';

        return (new Response())
            ->setStatus(200)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', 'public, max-age=86400')
            ->setBody($body);
    }
}
