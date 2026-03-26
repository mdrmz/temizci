<?php
$user = currentUser();
$initials = strtoupper(substr($user['name'], 0, 2));
$isWorker = $user['role'] === 'worker';
$avatarUrl = !empty($user['avatar']) ? (UPLOAD_URL . ltrim($user['avatar'], '/')) : '';

$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function isActive(string ...$files): string
{
    global $currentFile, $currentDir;
    foreach ($files as $f) {
        if (str_ends_with($f, '.php')) {
            if (basename($f) === $currentFile) {
                return 'active';
            }
        } else {
            if ($currentDir === $f) {
                return 'active';
            }
        }
    }
    return '';
}

$base = APP_URL;
?>
<div class="sidebar" id="appSidebar">
    <div class="sidebar-logo">
        <a href="<?= $base ?>/" class="sidebar-logo-link">
            <div class="logo-icon sidebar-brand-icon">
                <img src="<?= $base ?>/logo.png" alt="Logo" class="sidebar-brand-image">
            </div>
            <div class="sidebar-logo-text"><span>Temizci Burada</span></div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Genel</div>
        <a href="<?= $base ?>/dashboard" class="sidebar-link <?= isActive('dashboard.php') ?>">
            <span class="icon" aria-hidden="true">&#128200;</span> Dashboard
        </a>
        <a href="<?= $base ?>/listings/browse" class="sidebar-link <?= isActive('browse.php') ?>">
            <span class="icon" aria-hidden="true">&#128269;</span> Ilanlari Gez
        </a>

        <div class="sidebar-section-title">Evlerim</div>
        <a href="<?= $base ?>/homes/list" class="sidebar-link <?= isActive('homes') ?>">
            <span class="icon" aria-hidden="true">&#127968;</span> Evlerim
        </a>
        <a href="<?= $base ?>/homes/add" class="sidebar-link <?= isActive('add.php') ?>">
            <span class="icon" aria-hidden="true">&#10133;</span> Ev Ekle
        </a>

        <div class="sidebar-section-title">Ilanlarim</div>
        <a href="<?= $base ?>/listings/my_listings" class="sidebar-link <?= isActive('my_listings.php') ?>">
            <span class="icon" aria-hidden="true">&#128221;</span> Ilanlarim
        </a>
        <a href="<?= $base ?>/listings/create" class="sidebar-link <?= isActive('create.php') ?>">
            <span class="icon" aria-hidden="true">&#9998;</span> Ilan Olustur
        </a>

        <a href="<?= $base ?>/offers/my_offers" class="sidebar-link <?= isActive('my_offers.php') ?>">
            <span class="icon" aria-hidden="true">&#128188;</span> Tekliflerim
        </a>
        <a href="<?= $base ?>/favorites" class="sidebar-link <?= isActive('favorites.php') ?>">
            <span class="icon" aria-hidden="true">&#10084;</span> Favorilerim
        </a>
        <a href="<?= $base ?>/messages.php" class="sidebar-link <?= isActive('messages.php') ?>">
            <span class="icon" aria-hidden="true">&#9993;</span> Mesajlarim
        </a>
        <a href="<?= $base ?>/destek" class="sidebar-link <?= isActive('destek.php', 'ticket_detail.php') ?>">
            <span class="icon" aria-hidden="true">&#128172;</span> Destek
        </a>

        <div class="sidebar-section-title">Hesap</div>
        <a href="<?= $base ?>/profile" class="sidebar-link <?= isActive('profile.php') ?>">
            <span class="icon" aria-hidden="true">&#128100;</span> Profilim
        </a>
        <a href="<?= $base ?>/logout" class="sidebar-link" data-confirm="Cikis yapmak istediginize emin misiniz?">
            <span class="icon" aria-hidden="true">&#10162;</span> Cikis Yap
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="avatar avatar-md sidebar-user-avatar">
            <?php if ($avatarUrl): ?>
                <img src="<?= e($avatarUrl) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            <?php else: ?>
                <?= e($initials) ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($user['name']) ?></div>
            <div class="sidebar-user-role"><?= $isWorker ? 'Hizmet Veren' : 'Ev Sahibi' ?></div>
        </div>
        <button class="theme-toggle-btn sidebar-theme-toggle" id="themeToggle" title="Tema Degistir">TM</button>
    </div>
</div>

