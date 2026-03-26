<?php
$current_page = basename($_SERVER['PHP_SELF']);
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<div class="sidebar" id="appSidebar">
    <div class="sidebar-logo">
        <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <div class="logo-icon" style="width:34px;height:34px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--gradient);">
                <img src="/logo.png" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div class="sidebar-logo-text"><span>Temizci Burada</span></div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Genel</div>
        <a href="index.php" class="sidebar-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <span class="icon">DB</span> Dashboard
        </a>

        <div class="sidebar-section-title">Yonetim</div>
        <a href="users.php" class="sidebar-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <span class="icon">US</span> Kullanicilar
        </a>
        <a href="categories.php" class="sidebar-link <?= $current_page === 'categories.php' ? 'active' : '' ?>">
            <span class="icon">KT</span> Kategoriler
        </a>
        <a href="tickets.php" class="sidebar-link <?= in_array($current_page, ['tickets.php', 'ticket_view.php']) ? 'active' : '' ?>">
            <span class="icon">DK</span> Destek Talepleri
        </a>
        <a href="backups.php" class="sidebar-link <?= $current_page === 'backups.php' ? 'active' : '' ?>">
            <span class="icon">BK</span> Yedekleme
        </a>

        <div class="sidebar-section-title">Geri Donus</div>
        <a href="/dashboard" class="sidebar-link">
            <span class="icon">AN</span> Siteye Don
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="avatar avatar-md" style="background:var(--gradient);">
            <?= e($initials) ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($user['name']) ?></div>
            <div class="sidebar-user-role">Sistem Yoneticisi</div>
        </div>
        <button class="theme-toggle-btn" id="themeToggle" title="Tema Degistir" style="width:32px;height:32px;font-size:0.74rem;border-radius:8px;letter-spacing:0.08em;">TM</button>
    </div>
</div>
