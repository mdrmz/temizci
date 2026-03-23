<?php
// Shared Admin Header
$headerTitle = isset($headerTitle) ? $headerTitle : 'Admin Panel';
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<div class="app-header">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburger" aria-label="Menü">
            <span></span><span></span><span></span>
        </button>
        <div class="app-header-title"><?= $headerTitle ?></div>
    </div>
    <div class="header-actions">
        <!-- Notification Button (Request by User) -->
        <div class="notifications" style="position: relative;">
            <button class="notification-btn" id="notifBtn" style="background:none; border:none; font-size:1.4rem; cursor:pointer; padding: 5px; opacity: 0.8; transition: all 0.2s;">
                🔔
                <span class="notif-badge" style="position: absolute; top: 0; right: 0; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.65rem; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-card); font-weight: 800;">2</span>
            </button>
        </div>

        <!-- Dashboard Link Shortcut -->
        <a href="/admin/index" class="btn btn-outline btn-sm" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">
            <span style="font-size: 1rem; margin-right: 4px;">📊</span> Özet
        </a>
        
        <div class="avatar avatar-sm" style="background:var(--gradient);">
            <?= $initials ?>
        </div>
    </div>
</div>
