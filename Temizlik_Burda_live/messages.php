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

$conversationStmt = $db->prepare("
    SELECT
        conv.contact_id,
        conv.last_message_date,
        u.name AS contact_name,
        u.avatar AS contact_avatar,
        (
            SELECT COUNT(*)
            FROM tb_chat_messages um
            WHERE um.sender_id = conv.contact_id
              AND um.receiver_id = ?
              AND um.is_read = 0
        ) AS unread_count,
        (
            SELECT m3.message
            FROM tb_chat_messages m3
            WHERE (m3.sender_id = ? AND m3.receiver_id = conv.contact_id)
               OR (m3.sender_id = conv.contact_id AND m3.receiver_id = ?)
            ORDER BY m3.created_at DESC, m3.id DESC
            LIMIT 1
        ) AS last_message,
        (
            SELECT m4.sender_id
            FROM tb_chat_messages m4
            WHERE (m4.sender_id = ? AND m4.receiver_id = conv.contact_id)
               OR (m4.sender_id = conv.contact_id AND m4.receiver_id = ?)
            ORDER BY m4.created_at DESC, m4.id DESC
            LIMIT 1
        ) AS last_sender_id
    FROM (
        SELECT
            CASE
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END AS contact_id,
            MAX(m.created_at) AS last_message_date
        FROM tb_chat_messages m
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY contact_id
    ) conv
    JOIN users u ON u.id = conv.contact_id
    ORDER BY conv.last_message_date DESC
");
$conversationStmt->execute([
    $user['id'],
    $user['id'],
    $user['id'],
    $user['id'],
    $user['id'],
    $user['id'],
    $user['id'],
    $user['id']
]);
$conversations = $conversationStmt->fetchAll();

$activeContactId = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
if (!$activeContactId && count($conversations) > 0) {
    $activeContactId = (int) $conversations[0]['contact_id'];
}

$messages = [];
$activeContact = null;

if ($activeContactId > 0) {
    $contactStmt = $db->prepare("SELECT id, name, avatar FROM users WHERE id = ?");
    $contactStmt->execute([$activeContactId]);
    $activeContact = $contactStmt->fetch();

    if ($activeContact) {
        $msgStmt = $db->prepare("
            SELECT recent.*
            FROM (
                SELECT
                    m.id,
                    m.sender_id,
                    m.receiver_id,
                    m.message,
                    m.created_at,
                    s.name AS sender_name
                FROM tb_chat_messages m
                JOIN users s ON m.sender_id = s.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT 300
            ) recent
            ORDER BY recent.created_at ASC, recent.id ASC
        ");
        $msgStmt->execute([$user['id'], $activeContactId, $activeContactId, $user['id']]);
        $messages = $msgStmt->fetchAll();

        $db->prepare("UPDATE tb_chat_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0")
            ->execute([$user['id'], $activeContactId]);
    } else {
        $activeContactId = 0;
    }
}

$shellClass = $activeContact ? 'chat-has-active' : 'chat-no-active';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MesajlarÄ±m - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'MesajlarÄ±m'; include 'includes/app-header.php'; ?>

            <div class="page-content">
                <div class="chat-shell <?= e($shellClass) ?>">
                    <aside class="chat-sidebar">
                        <div class="chat-sidebar-head">
                            <span>Sohbetler</span>
                            <?php if (!empty($conversations)): ?>
                                <span class="chat-count"><?= (int) count($conversations) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="chat-contact-list">
                            <?php if (empty($conversations)): ?>
                                <div class="chat-empty">
                                    <p>HenÃ¼z mesajÄ±nÄ±z yok.</p>
                                    <a href="<?= APP_URL ?>/listings/browse" class="btn btn-outline btn-sm">Ä°lanlarÄ± GÃ¶r</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $c): ?>
                                    <?php
                                    $isActive = ((int) $c['contact_id'] === $activeContactId);
                                    $previewPrefix = ((int) ($c['last_sender_id'] ?? 0) === (int) $user['id']) ? 'Siz: ' : '';
                                    $previewSource = trim((string) ($c['last_message'] ?? ''));
                                    $preview = $previewSource !== '' ? $previewPrefix . $previewSource : 'HenÃ¼z mesaj yok.';
                                    ?>
                                    <a href="messages.php?uid=<?= (int) $c['contact_id'] ?>" class="chat-contact-item <?= $isActive ? 'active' : '' ?>">
                                        <div class="chat-contact-avatar">
                                            <?php if (!empty($c['contact_avatar'])): ?>
                                                <img src="<?= APP_URL . '/uploads/avatars/' . e($c['contact_avatar']) ?>" alt="<?= e($c['contact_name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                            <?php else: ?>
                                                <?= e(mb_substr((string) $c['contact_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="chat-contact-body">
                                            <div class="chat-contact-row">
                                                <div class="chat-contact-name"><?= e($c['contact_name']) ?></div>
                                                <div class="chat-contact-meta"><?= e(timeAgo((string) $c['last_message_date'])) ?></div>
                                            </div>
                                            <div class="chat-contact-preview"><?= e(mb_strimwidth($preview, 0, 74, '...')) ?></div>
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
                                <a href="messages.php" class="chat-back-link">â† Sohbetler</a>
                                <div class="chat-contact-avatar">
                                    <?php if (!empty($activeContact['avatar'])): ?>
                                        <img src="<?= APP_URL . '/uploads/avatars/' . e($activeContact['avatar']) ?>" alt="<?= e($activeContact['name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <?= e(mb_substr((string) $activeContact['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="chat-contact-name"><?= e($activeContact['name']) ?></div>
                                    <div class="chat-contact-meta">CanlÄ± sohbet</div>
                                </div>
                            </div>

                            <div class="chat-messages" id="msg-list">
                                <?php if (empty($messages)): ?>
                                    <div class="chat-empty" style="margin:auto;">
                                        <p>Bu kiÅŸiyle henÃ¼z mesajlaÅŸmadÄ±nÄ±z.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $m): ?>
                                        <?php $isSent = ((int) $m['sender_id'] === (int) $user['id']); ?>
                                        <div class="chat-bubble <?= $isSent ? 'sent' : 'received' ?>" data-mid="<?= (int) $m['id'] ?>">
                                            <?= nl2br(e((string) $m['message'])) ?>
                                            <div class="chat-time"><?= e(date('H:i', strtotime((string) $m['created_at']))) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form class="chat-input-wrap" id="chat-form" method="POST">
                                <?= csrfField() ?>
                                <textarea name="message_text" id="mText" class="chat-textarea" maxlength="1200" placeholder="MesajÄ±nÄ±zÄ± yazÄ±n..." required></textarea>
                                <button type="submit" class="btn btn-primary" id="sendBtn">GÃ¶nder</button>
                            </form>
                            <div class="chat-main-status" id="liveState">CanlÄ± mesajlaÅŸma aÃ§Ä±k</div>
                            <div class="chat-main-typing" id="typingState"></div>
                        <?php else: ?>
                            <div class="chat-empty" style="margin:auto;">
                                <h3>Sohbet seÃ§in</h3>
                                <p>Soldaki listeden bir kiÅŸi seÃ§erek mesajlaÅŸmaya baÅŸlayabilirsiniz.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="assets/js/app.js?v=5.8"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const activeContactId = <?= (int) $activeContactId ?>;
        const msgList = document.getElementById('msg-list');
        const chatForm = document.getElementById('chat-form');
        const mText = document.getElementById('mText');
        const sendBtn = document.getElementById('sendBtn');
        const liveState = document.getElementById('liveState');
        const typingState = document.getElementById('typingState');
        let lastMessageId = 0;
        let typingTimer = null;
        let lastTypingSentAt = 0;
        let sending = false;

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

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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
            const ids = Array.from(msgList.querySelectorAll('[data-mid]')).map((el) => parseInt(el.dataset.mid, 10) || 0);
            lastMessageId = ids.length ? Math.max(...ids) : 0;
        }

        function appendBubble(m) {
            if (!msgList) return;
            const emptyState = msgList.querySelector('.chat-empty');
            if (emptyState) emptyState.remove();

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + (m.is_mine ? 'sent' : 'received');
            bubble.dataset.mid = String(m.id || 0);

            const safeText = escapeHtml(m.message || '').replace(/\n/g, '<br>');
            bubble.innerHTML = safeText + '<div class="chat-time">' + escapeHtml(m.time || '') + '</div>';
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
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const text = (mText?.value || '').trim();
                if (!text || sending) return;

                sending = true;
                if (sendBtn) sendBtn.disabled = true;

                try {
                    const res = await fetch('<?= APP_URL ?>/api/messages.php?action=send', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ receiver_id: activeContactId, message: text })
                    });
                    const data = await res.json();
                    if (data.success && data.message) {
                        appendBubble(data.message);
                        if (mText) mText.value = '';
                        sendTypingState(false);
                        if (liveState) liveState.textContent = 'Mesaj gÃ¶nderildi';
                    }
                } catch (_) {
                    if (liveState) liveState.textContent = 'Mesaj gÃ¶nderilemedi, tekrar deneyin';
                } finally {
                    sending = false;
                    if (sendBtn) sendBtn.disabled = false;
                }
            });
        }

        async function pollMessages() {
            if (!msgList || activeContactId <= 0) return;
            try {
                const res = await fetch(`<?= APP_URL ?>/api/messages.php?action=poll&uid=${activeContactId}&last_id=${lastMessageId}`, { cache: 'no-store' });
                const data = await res.json();

                if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                    data.messages.forEach((m) => {
                        appendBubble(m);
                        if (!m.is_mine) playIncomingSound();
                    });
                }

                if (typingState) {
                    typingState.textContent = data.typing ? 'KarÅŸÄ± taraf yazÄ±yor...' : '';
                }
                if (liveState) {
                    liveState.textContent = 'CanlÄ± mesajlaÅŸma aÃ§Ä±k';
                }
            } catch (_) {
                if (liveState) {
                    liveState.textContent = 'BaÄŸlantÄ± zayÄ±f, tekrar deneniyor...';
                }
            }
        }

        if (activeContactId > 0) {
            setInterval(pollMessages, 3000);
        }
    </script>
</body>
</html>
