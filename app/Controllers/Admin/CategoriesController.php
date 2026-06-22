<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\HttpException;
use App\Core\Response;
use App\Core\Slugger;
use App\Core\Validator;
use App\Models\Category;

/**
 * Zarządzanie kategoriami w panelu admina.
 */
final class CategoriesController extends AdminController
{
    private Category $categories;

    public function __construct(\App\Core\App $app)
    {
        parent::__construct($app);
        $this->categories = new Category($this->db);
    }

    public function index(): Response
    {
        return $this->view('admin/categories/index', [
            'seo'   => $this->adminSeo('Kategorie'),
            'items' => $this->categories->allWithCounts(),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin/categories/edit', [
            'seo'      => $this->adminSeo('Nowa kategoria'),
            'category' => null,
            'errors'   => [],
        ]);
    }

    public function store(): Response
    {
        $this->verifyCsrf();

        $data = $this->collect();
        $validator = $this->validate($data);

        if ($validator->passes() && $this->categories->slugExists($data['slug'])) {
            $validator->addError('slug', 'Taki slug już istnieje.');
        }

        if ($validator->fails()) {
            return $this->view('admin/categories/edit', [
                'seo'      => $this->adminSeo('Nowa kategoria'),
                'category' => $data,
                'errors'   => $validator->errors(),
            ], 422);
        }

        $maxOrder = (int)$this->db->fetchColumn("SELECT COALESCE(MAX(sort_order), 0) FROM `categories`");
        $this->categories->insert([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'],
            'sort_order'  => $maxOrder + 1,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->clearCategoryCaches();
        $this->session->flash('success', 'Kategoria utworzona.');
        return $this->redirect($this->adminUrl('categories'));
    }

    public function edit(string $id): Response
    {
        $category = $this->categories->find((int)$id);
        if ($category === null) {
            throw HttpException::notFound('Kategoria nie istnieje.');
        }
        return $this->view('admin/categories/edit', [
            'seo'      => $this->adminSeo('Edycja kategorii'),
            'category' => $category,
            'errors'   => [],
        ]);
    }

    public function update(string $id): Response
    {
        $this->verifyCsrf();
        $catId = (int)$id;
        $category = $this->categories->find($catId);
        if ($category === null) {
            throw HttpException::notFound('Kategoria nie istnieje.');
        }

        $data = $this->collect();
        $validator = $this->validate($data);

        if ($validator->passes() && $this->categories->slugExists($data['slug'], $catId)) {
            $validator->addError('slug', 'Taki slug już istnieje.');
        }

        if ($validator->fails()) {
            return $this->view('admin/categories/edit', [
                'seo'      => $this->adminSeo('Edycja kategorii'),
                'category' => array_merge($category, $data),
                'errors'   => $validator->errors(),
            ], 422);
        }

        $this->categories->update($catId, [
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'],
        ]);
        $this->clearCategoryCaches();
        $this->session->flash('success', 'Kategoria zaktualizowana.');
        return $this->redirect($this->adminUrl('categories'));
    }

    public function delete(string $id): Response
    {
        $this->verifyCsrf();
        $catId = (int)$id;

        $count = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM `stories` WHERE category_id = ?", [$catId]);
        if ($count > 0) {
            $this->session->flash('error', 'Nie można usunąć kategorii z przypisanymi historiami.');
            return $this->back();
        }

        $this->categories->delete($catId);
        $this->clearCategoryCaches();
        $this->session->flash('success', 'Kategoria usunięta.');
        return $this->back();
    }

    public function sort(): Response
    {
        $this->verifyCsrf();

        /** @var array<string, mixed> $orders */
        $orders = $_POST['sort_order'] ?? [];
        if (!is_array($orders)) {
            $this->session->flash('error', 'Nieprawidłowe dane sortowania.');
            return $this->back();
        }

        $map = [];
        foreach ($orders as $id => $order) {
            $map[(int)$id] = (int)$order;
        }

        if ($map !== []) {
            $this->categories->updateSortOrders($map);
            $this->clearCategoryCaches();
        }

        $this->session->flash('success', 'Kolejność kategorii zapisana.');
        return $this->redirect($this->adminUrl('categories'));
    }

    /**
     * @return array{name:string, slug:string, description:string}
     */
    private function collect(): array
    {
        $name = trim((string)$this->input('name', ''));
        $slug = trim((string)$this->input('slug', ''));
        if ($slug === '' && $name !== '') {
            $slug = Slugger::slugify($name);
        } else {
            $slug = Slugger::slugify($slug);
        }
        return [
            'name'        => $name,
            'slug'        => $slug,
            'description' => trim((string)$this->input('description', '')),
        ];
    }

    /**
     * @param array<string, string> $data
     */
    private function validate(array $data): Validator
    {
        $validator = new Validator($data, ['name' => 'nazwa', 'slug' => 'slug']);
        $validator->validate([
            'name' => 'required|min:2|max:60',
            'slug' => 'required|slug|max:80',
        ]);
        return $validator;
    }

    private function clearCategoryCaches(): void
    {
        foreach (['home', 'categories', 'stories', 'rankings'] as $prefix) {
            $this->cache->clearByPrefix($prefix);
        }
    }
}
