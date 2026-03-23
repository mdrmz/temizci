<?php
// includes/sidebar.php — Reusable sidebar component
// Kullanım: Bu dosya, giriş gerektiren tüm sayfalara include edilir.
// Config, auth, functions zaten include edilmiş olmalı.

$user = currentUser();
$initials = strtoupper(substr($user['name'], 0, 2));
$isWorker = $user['role'] === 'worker';

// Aktif sayfa tespiti
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
function isActive(string ...$files): string
{
    global $currentFile, $currentDir;
    foreach ($files as $f) {
        if (str_ends_with($f, '.php')) {
            if (basename($f) === $currentFile)
                return 'active';
        } else {
            if ($currentDir === $f)
                return 'active';
        }
    }
    return '';
}
$base = APP_URL;
?>
<div class="sidebar" id="appSidebar">
    <!-- Logo -->
    <div class="sidebar-logo" style="justify-content:space-between;">
        <a href="<?= $base ?>/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <div class="logo-icon"
                style="width:34px;height:34px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                <img src="<?= $base ?>/logo.png" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div class="sidebar-logo-text"><span>Temizci Burada</span></div>
        </a>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <div class="sidebar-section-title">Genel</div>

        <a href="<?= $base ?>/dashboard" class="sidebar-link <?= isActive('dashboard.php') ?>">
            <span class="icon">🏠</span> Dashboard
        </a>

        <a href="<?= $base ?>/listings/browse" class="sidebar-link <?= isActive('browse.php') ?>">
            <span class="icon">🔍</span> İlanları Gez
        </a>

        <div class="sidebar-section-title">Evlerim</div>

        <a href="<?= $base ?>/homes/list" class="sidebar-link <?= isActive('homes') ?>">
            <span class="icon">🏘️</span> Evlerim
        </a>
        <a href="<?= $base ?>/homes/add" class="sidebar-link <?= isActive('add.php') ?>">
            <span class="icon">➕</span> Ev Ekle
        </a>

        <div class="sidebar-section-title">İlanlarım</div>

        <a href="<?= $base ?>/listings/my_listings" class="sidebar-link <?= isActive('my_listings.php') ?>">
            <span class="icon">📋</span> İlanlarım
        </a>
        <a href="<?= $base ?>/listings/create" class="sidebar-link <?= isActive('create.php') ?>">
            <span class="icon">✏️</span> İlan Oluştur
        </a>

        <a href="<?= $base ?>/offers/my_offers" class="sidebar-link <?= isActive('my_offers.php') ?>">
            <span class="icon">💬</span> Tekliflerim
        </a>

        <a href="<?= $base ?>/favorites" class="sidebar-link <?= isActive('favorites.php') ?>">
            <span class="icon">❤️</span> Favorilerim
        </a>

        <a href="<?= $base ?>/messages" class="sidebar-link <?= isActive('messages.php') ?>">
            <span class="icon">✉️</span> Mesajlarım
        </a>

        <a href="<?= $base ?>/destek" class="sidebar-link <?= isActive('destek.php', 'ticket_detail.php') ?>">
            <span class="icon">🎧</span> Destek Merkezi
        </a>

        <div class="sidebar-section-title">Hesap</div>

        <a href="<?= $base ?>/profile" class="sidebar-link <?= isActive('profile.php') ?>">
            <span class="icon">👤</span> Profilim
        </a>

        <a href="<?= $base ?>/logout" class="sidebar-link" data-confirm="Çıkış yapmak istediğinizden emin misiniz?">
            <span class="icon">🚪</span> Çıkış Yap
        </a>

    </nav>

    <!-- User info -->
    <div class="sidebar-user">
        <div class="avatar avatar-md" style="background:var(--gradient);">
            <?= e($initials) ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name">
                <?= e($user['name']) ?>
            </div>
            <div class="sidebar-user-role">
                <?= $isWorker ? '🧹 Hizmet Veren' : '🏠 Ev Sahibi' ?>
            </div>
        </div>
        <button class="theme-toggle-btn" id="themeToggle" title="Tema Değiştir" style="width:32px;height:32px;font-size:0.9rem;border-radius:8px;">🌙</button>
    </div>
</div>