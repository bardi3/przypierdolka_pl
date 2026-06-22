<?php
/**
 * Otwarcie strony konta — pełny układ z tabami i bocznym panelem.
 *
 * @var string $accountNav
 */
?>
<div class="page-layout account-page">
    <div class="page-main">
        <?= $view->renderPartial('account/_nav', ['active' => $accountNav]) ?>
        <div class="account-panel">
