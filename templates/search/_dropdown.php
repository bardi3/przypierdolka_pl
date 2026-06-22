<?php
/**
 * Wyniki AJAX w dropdownie wyszukiwarki.
 *
 * @var array{query:string, stories:array, users:array, friends:array, total:int} $results
 */
$query = $results['query'] ?? '';
$stories = $results['stories'] ?? [];
$users = $results['users'] ?? [];
$friends = $results['friends'] ?? [];
$total = (int)($results['total'] ?? 0);

if ($total === 0): ?>
    <p class="site-search__empty">Brak wyników dla „<?= e($query) ?>”.</p>
    <a class="site-search__more" href="<?= e(url('/szukaj?q=' . rawurlencode($query))) ?>">Szukaj na pełnej stronie</a>
<?php return; endif; ?>

<?php if (!empty($stories)): ?>
    <p class="site-search__group-title">Historie</p>
    <ul class="site-search__list">
        <?php foreach ($stories as $story): ?>
            <li>
                <a href="<?= e(url('/historia/' . $story['slug'])) ?>" class="site-search__item">
                    <span class="site-search__item-title"><?= e($story['title']) ?></span>
                    <?php if (!empty($story['category_name'])): ?>
                        <span class="site-search__item-meta"><?= e($story['category_name']) ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($users)): ?>
    <p class="site-search__group-title">Użytkownicy</p>
    <ul class="site-search__list">
        <?php foreach ($users as $user): ?>
            <li>
                <a href="<?= e(url('/profil/' . $user['username'])) ?>" class="site-search__item">
                    <span class="site-search__item-title">@<?= e($user['username']) ?></span>
                    <?php if (!empty($user['bio'])): ?>
                        <span class="site-search__item-meta"><?= e(mb_strimwidth((string)$user['bio'], 0, 60, '…')) ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($friends)): ?>
    <p class="site-search__group-title">Znajomi</p>
    <ul class="site-search__list">
        <?php foreach ($friends as $friend): ?>
            <li>
                <a href="<?= e(url('/profil/' . $friend['username'])) ?>" class="site-search__item">
                    <span class="site-search__item-title">@<?= e($friend['username']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<a class="site-search__more" href="<?= e(url('/szukaj?q=' . rawurlencode($query))) ?>">Wszystkie wyniki →</a>
