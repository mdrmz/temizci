<?php
// Shared App Header for internal pages
$notifCount = isset($user['id']) ? getUnreadNotificationCount($user['id']) : 0;
$initials = isset($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : '?';
$headerTitle = isset($headerTitle) ? $headerTitle : 'Panel';
?>
<script>const APP_URL = "<?= APP_URL ?>";</script>
<div class="app-header">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" id="hamburger" aria-label="Menü">
            <span></span><span></span><span></span>
        </button>
        <div class="app-header-title"><?= $headerTitle ?></div>
    </div>
    <div class="header-actions">
        <?php if ($user['role'] === 'homeowner'): ?>
            <a href="<?= APP_URL ?>/listings/create" class="btn btn-primary btn-sm">+ Yeni İlan</a>
        <?php endif; ?>
        
        <div class="notif-wrapper" style="position:relative;">
            <button class="notif-btn" id="notifBtn" aria-label="Bildirimler" onclick="toggleNotifPanel()">
                🔔
                <?php if ($notifCount > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?= $notifCount ?></span>
                <?php else: ?>
                    <span class="notif-badge" id="notifBadge" style="display:none;"></span>
                <?php endif; ?>
            </button>
            <div class="notif-panel" id="notifDropdown" style="display:none;position:absolute;right:0;top:50px;width:360px;max-height:480px;background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,0.15);z-index:1000;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--border);">
                    <div style="font-weight:700;font-size:1rem;">🔔 Bildirimler</div>
                    <button onclick="markAllRead()" id="markAllBtn" style="background:none;border:none;color:var(--primary);font-size:0.8rem;font-weight:600;cursor:pointer;">Tümünü Okundu Yap</button>
                </div>
                <div class="notif-list" id="notifList" style="max-height:380px;overflow-y:auto;padding:6px;">
                    <div style="padding:40px; text-align:center;">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="avatar avatar-sm" style="background:var(--gradient);">
            <?= $initials ?>
        </div>
    </div>
</div>

<script>
(function() {
    let notifOpen = false;

    window.toggleNotifPanel = function() {
        const panel = document.getElementById('notifDropdown');
        notifOpen = !notifOpen;
        panel.style.display = notifOpen ? 'block' : 'none';
        if (notifOpen) loadNotifications();
    };

    document.addEventListener('click', function(e) {
        const wrapper = document.querySelector('.notif-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            document.getElementById('notifDropdown').style.display = 'none';
            notifOpen = false;
        }
    });

    window.loadNotifications = function() {
        fetch(APP_URL + '/notifications_ajax')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const list = document.getElementById('notifList');
            
            if (data.notifications.length === 0) {
                list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);"><div style="font-size:2.5rem;margin-bottom:8px;opacity:0.3;">🔔</div>Henüz bildiriminiz yok.</div>';
                return;
            }
            
            let html = '';
            data.notifications.forEach(n => {
                const unreadStyle = n.is_read == 0 ? 'background:rgba(99,102,241,0.04);border-left:3px solid var(--primary);' : 'border-left:3px solid transparent;';
                html += `
                    <a href="${n.link || '#'}" style="display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border-radius:10px;margin-bottom:2px;text-decoration:none;color:var(--text);transition:background 0.2s;${unreadStyle}"
                       onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='${n.is_read == 0 ? 'rgba(99,102,241,0.04)' : 'transparent'}'">
                        <div style="width:36px;height:36px;border-radius:10px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">${n.icon}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.85rem;line-height:1.5;${n.is_read == 0 ? 'font-weight:600;' : ''}">${n.message}</div>
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px;">${n.time_ago}</div>
                        </div>
                    </a>`;
            });
            list.innerHTML = html;

            // Badge güncelle
            const badge = document.getElementById('notifBadge');
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
    };

    window.markAllRead = function() {
        fetch(APP_URL + '/notifications_ajax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('notifBadge').style.display = 'none';
                loadNotifications();
            }
        });
    };
})();
</script>
