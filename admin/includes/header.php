<?php
$headerTitle = isset($headerTitle) ? $headerTitle : 'Admin Panel';
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<div class="app-header">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <div class="app-header-title"><?= e($headerTitle) ?></div>
    </div>
    <div class="header-actions">
        <a href="/admin/index" class="btn btn-outline btn-sm" style="padding:6px 12px;font-size:0.8rem;border-radius:8px;">
            Ozet
        </a>
        <div class="avatar avatar-sm" style="background:var(--gradient);">
            <?= e($initials) ?>
        </div>
    </div>
</div>
