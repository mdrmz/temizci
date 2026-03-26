<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$user = currentUser();
$db = getDB();

$db->exec("
    CREATE TABLE IF NOT EXISTS tb_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        listing_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sender (sender_id),
        INDEX idx_receiver (receiver_id),
        INDEX idx_listing (listing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $db->prepare("
    SELECT
        CASE
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END AS contact_id,
        MAX(m.created_at) AS last_message_date,
        u.name AS contact_name,
        u.avatar AS contact_avatar,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM tb_chat_messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY contact_id
    ORDER BY last_message_date DESC
");
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $stmt->fetchAll();

$activeContactId = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
if (!$activeContactId && count($conversations) > 0) {
    $activeContactId = (int) $conversations[0]['contact_id'];
}

$messages = [];
$activeContact = null;

if ($activeContactId > 0) {
    $stmt = $db->prepare("
        SELECT m.*, s.name as sender_name
        FROM tb_chat_messages m
        JOIN users s ON m.sender_id = s.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user['id'], $activeContactId, $activeContactId, $user['id']]);
    $messages = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT id, name, avatar FROM users WHERE id = ?");
    $stmt->execute([$activeContactId]);
    $activeContact = $stmt->fetch();

    $db->prepare("UPDATE tb_chat_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0")
        ->execute([$user['id'], $activeContactId]);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlarım - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Mesajlarım'; include 'includes/app-header.php'; ?>

            <div class="page-content">
                <div class="chat-shell">
                    <aside class="chat-sidebar">
                        <div class="chat-sidebar-head">Sohbetler</div>
                        <div class="chat-contact-list">
                            <?php if (empty($conversations)): ?>
                                <div class="empty-state" style="padding:28px 14px;">
                                    <p>Henüz mesajınız yok.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $c): ?>
                                    <a href="messages.php?uid=<?= (int) $c['contact_id'] ?>"
                                        class="chat-contact-item <?= ((int) $c['contact_id'] === $activeContactId) ? 'active' : '' ?>">
                                        <div class="chat-contact-avatar"><?= mb_substr($c['contact_name'], 0, 1) ?></div>
                                        <div>
                                            <div class="chat-contact-name"><?= e($c['contact_name']) ?></div>
                                            <div class="chat-contact-meta"><?= timeAgo($c['last_message_date']) ?></div>
                                        </div>
                                        <?php if ((int) $c['unread_count'] > 0): ?>
                                            <div class="chat-unread"><?= (int) $c['unread_count'] ?></div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </aside>

                    <section class="chat-main">
                        <?php if ($activeContact): ?>
                            <div class="chat-main-head">
                                <div class="chat-contact-avatar"><?= mb_substr($activeContact['name'], 0, 1) ?></div>
                                <div class="chat-contact-name"><?= e($activeContact['name']) ?></div>
                            </div>

                            <div class="chat-messages" id="msg-list">
                                <?php if (empty($messages)): ?>
                                    <div class="empty-state" style="margin:auto;">
                                        <p>Bu kişiyle henüz mesajlaşmadınız.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $m): ?>
                                        <?php $isSent = ((int) $m['sender_id'] === (int) $user['id']); ?>
                                        <div class="chat-bubble <?= $isSent ? 'sent' : 'received' ?>" data-mid="<?= (int) $m['id'] ?>">
                                            <?= nl2br(e($m['message'])) ?>
                                            <div class="chat-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form class="chat-input-wrap" id="chat-form" method="POST">
                                <?= csrfField() ?>
                                <textarea name="message_text" id="mText" class="chat-textarea" placeholder="Mesajınızı yazın..." required></textarea>
                                <button type="submit" class="btn btn-primary">Gönder</button>
                            </form>
                            <div style="font-size:0.74rem;color:var(--text-muted);padding:0 8px 6px;" id="liveState">Canlı mesajlaşma açık</div>
                            <div style="font-size:0.74rem;color:var(--primary);padding:0 8px 10px;min-height:18px;" id="typingState"></div>
                        <?php else: ?>
                            <div class="empty-state" style="margin:auto;">
                                <h3>Sohbet seçin</h3>
                                <p>Soldan bir kişi seçerek mesajlaşmaya başlayabilirsiniz.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="assets/js/app.js?v=5.0"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const activeContactId = <?= (int) $activeContactId ?>;
        const msgList = document.getElementById('msg-list');
        const chatForm = document.getElementById('chat-form');
        const mText = document.getElementById('mText');
        const liveState = document.getElementById('liveState');
        const typingState = document.getElementById('typingState');
        let lastMessageId = 0;
        let typingTimer = null;
        let lastTypingSentAt = 0;

        function playIncomingSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.value = 0.03;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                setTimeout(() => {
                    osc.stop();
                    ctx.close();
                }, 120);
            } catch (_) {}
        }

        async function sendTypingState(isTyping) {
            if (activeContactId <= 0) return;
            const now = Date.now();
            if (isTyping && now - lastTypingSentAt < 1000) return;
            lastTypingSentAt = now;
            try {
                await fetch('<?= APP_URL ?>/api/messages.php?action=typing_set', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ contact_id: activeContactId, is_typing: isTyping ? 1 : 0 })
                });
            } catch (_) {}
        }

        function refreshLastMessageId() {
            if (!msgList) return;
            const ids = Array.from(msgList.querySelectorAll('[data-mid]')).map(el => parseInt(el.dataset.mid, 10) || 0);
            lastMessageId = ids.length ? Math.max(...ids) : 0;
        }

        function appendBubble(m) {
            if (!msgList) return;
            const emptyState = msgList.querySelector('.empty-state');
            if (emptyState) emptyState.remove();

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + (m.is_mine ? 'sent' : 'received');
            bubble.dataset.mid = String(m.id || 0);

            const safeText = (m.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
            bubble.innerHTML = safeText + '<div class="chat-time">' + (m.time || '') + '</div>';
            msgList.appendChild(bubble);
            msgList.scrollTop = msgList.scrollHeight;

            if (m.id && m.id > lastMessageId) {
                lastMessageId = m.id;
            }
        }

        if (msgList) {
            msgList.scrollTop = msgList.scrollHeight;
            refreshLastMessageId();
        }

        if (mText) {
            mText.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    chatForm?.dispatchEvent(new Event('submit', { cancelable: true }));
                }
            });
            mText.addEventListener('input', function() {
                if (!mText.value.trim()) {
                    sendTypingState(false);
                    return;
                }
                sendTypingState(true);
                if (typingTimer) clearTimeout(typingTimer);
                typingTimer = setTimeout(() => sendTypingState(false), 1800);
            });
            mText.addEventListener('blur', function() {
                sendTypingState(false);
            });
        }

        if (chatForm && activeContactId > 0) {
            chatForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const text = (mText?.value || '').trim();
                if (!text) return;

                try {
                    const res = await fetch('<?= APP_URL ?>/api/messages.php?action=send', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ receiver_id: activeContactId, message: text })
                    });
                    const data = await res.json();
                    if (data.success && data.message) {
                        appendBubble(data.message);
                        mText.value = '';
                        sendTypingState(false);
                    }
                } catch (_) {}
            });
        }

        async function pollMessages() {
            if (!msgList || activeContactId <= 0) return;
            try {
                const res = await fetch(`<?= APP_URL ?>/api/messages.php?action=poll&uid=${activeContactId}&last_id=${lastMessageId}`, { cache: 'no-store' });
                const data = await res.json();
                if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                    data.messages.forEach(m => {
                        appendBubble(m);
                        if (!m.is_mine) {
                            playIncomingSound();
                        }
                    });
                }
                if (typingState) {
                    typingState.textContent = data.typing ? 'Karsi taraf yaziyor...' : '';
                }
                if (liveState) liveState.textContent = 'Canlı mesajlaşma açık';
            } catch (_) {
                if (liveState) liveState.textContent = 'Bağlantı zayıf, tekrar deneniyor...';
            }
        }

        if (activeContactId > 0) {
            setInterval(pollMessages, 4000);
        }
    </script>
</body>
</html>
