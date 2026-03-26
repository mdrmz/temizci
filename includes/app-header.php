<?php
$notifCount = isset($user['id']) ? getUnreadNotificationCount($user['id']) : 0;
$initials = isset($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : '?';
$avatarUrl = !empty($user['avatar']) ? (UPLOAD_URL . ltrim($user['avatar'], '/')) : '';
$headerTitle = isset($headerTitle) ? $headerTitle : 'Panel';
?>
<script>
    const APP_URL = "<?= APP_URL ?>";
</script>
<div class="app-header">
    <div class="app-header-left">
        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <div class="app-header-title"><?= e($headerTitle) ?></div>
    </div>
    <div class="header-actions">
        <?php if ($user['role'] === 'homeowner'): ?>
            <a href="<?= APP_URL ?>/listings/create" class="btn btn-primary btn-sm">Yeni İlan</a>
        <?php endif; ?>

        <div class="notif-wrapper">
            <button class="notif-btn" id="notifBtn" aria-label="Bildirimler" onclick="toggleNotifPanel()">
                BD
                <?php if ($notifCount > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?= $notifCount ?></span>
                <?php else: ?>
                    <span class="notif-badge" id="notifBadge" style="display:none;"></span>
                <?php endif; ?>
            </button>

            <div class="notif-panel notif-panel-xl" id="notifDropdown">
                <div class="notif-panel-head">
                    <div class="notif-title">Bildirimler</div>
                    <button onclick="markAllRead()" id="markAllBtn" class="notif-mark-all">Tümünü Okundu Yap</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-loading">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="avatar avatar-sm">
            <?php if ($avatarUrl): ?>
                <img src="<?= e($avatarUrl) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            <?php else: ?>
                <?= e($initials) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    (function() {
        let notifOpen = false;

        window.toggleNotifPanel = function() {
            const panel = document.getElementById('notifDropdown');
            notifOpen = !notifOpen;
            panel.classList.toggle('active', notifOpen);
            if (notifOpen) {
                loadNotifications();
            }
        };

        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.notif-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                const panel = document.getElementById('notifDropdown');
                panel.classList.remove('active');
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
                        list.innerHTML = '<div class="notif-empty">Henüz bildiriminiz yok.</div>';
                        return;
                    }

                    let html = '';
                    data.notifications.forEach(n => {
                        const unreadClass = n.is_read == 0 ? 'notif-item-unread' : '';
                        html += `
                        <a href="${n.link || '#'}" class="notif-item ${unreadClass}">
                            <div class="notif-item-icon">${n.icon}</div>
                            <div class="notif-item-body">
                                <div class="notif-item-text">${n.message}</div>
                                <div class="notif-item-time">${n.time_ago}</div>
                            </div>
                        </a>`;
                    });
                    list.innerHTML = html;

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
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'mark_read'
                    })
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
