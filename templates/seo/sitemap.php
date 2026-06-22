<?php
/**
 * @var string $base
 * @var array<int, array<string, mixed>> $stories
 * @var array<int, array<string, mixed>> $categories
 */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= e($base) ?>/</loc>
        <changefreq>hourly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= e($base) ?>/kategorie</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= e($base) ?>/kategoria/<?= e($cat['slug']) ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($stories as $story): ?>
    <url>
        <loc><?= e($base) ?>/historia/<?= e($story['slug']) ?></loc>
        <?php if (!empty($story['lastmod'])): ?>
        <lastmod><?= e(date('Y-m-d', strtotime((string)$story['lastmod']))) ?></lastmod>
        <?php endif; ?>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>
