<?php
/**
 * @var string $app_name
 */
?>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>
            <div class="site-footer__brand">przypierdolka<span>.pl</span></div>
        </div>
        <nav class="site-footer__links" aria-label="Stopka">
            <a href="<?= e(url('/kategorie')) ?>">Kategorie</a>
            <a href="<?= e(url('/dodaj')) ?>">Dodaj historię</a>
            <a href="<?= e(url('/sitemap.xml')) ?>">Sitemap</a>
        </nav>
    </div>
    <div class="site-footer__copy container">
        <small>&copy; <?= date('Y') ?> <?= e($app_name) ?></small>
    </div>
</footer>
