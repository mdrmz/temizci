<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$user = currentUser();
$db = getDB();

// Kullanıcının mesajlaştığı kişileri (sohbetleri) getir
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
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY contact_id
    ORDER BY last_message_date DESC
");
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $stmt->fetchAll();

// Aktif sohbet ID
$active_contact_id = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
// Eğer aktif sohbet yoksa ve sohbet listesi doluysa, ilkini seç
if (!$active_contact_id && count($conversations) > 0) {
    $active_contact_id = $conversations[0]['contact_id'];
}

$messages = [];
$active_contact = null;

if ($active_contact_id > 0) {
    // Mesajları getir
    $stmt = $db->prepare("
        SELECT m.*, s.name as sender_name 
        FROM messages m
        JOIN users s ON m.sender_id = s.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user['id'], $active_contact_id, $active_contact_id, $user['id']]);
    $messages = $stmt->fetchAll();

    // Aktif kişiyi getir
    $stmt = $db->prepare("SELECT id, name, avatar FROM users WHERE id = ?");
    $stmt->execute([$active_contact_id]);
    $active_contact = $stmt->fetch();

    // Okunmadı işaretlemesini kaldır
    $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0")->execute([$user['id'], $active_contact_id]);
}

// Mesaj Gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text']) && $active_contact_id > 0) {
    if (!verifyCsrf()) {
        die(json_encode(['status' => 'error', 'message' => 'Geçersiz CSRF']));
    }
    $message = trim($_POST['message_text']);
    if (strlen($message) > 0) {
        $db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)")->execute([$user['id'], $active_contact_id, $message]);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['status' => 'success', 'timestamp' => date('H:i')]);
            exit;
        }
        redirect(APP_URL . '/messages.php?uid=' . $active_contact_id);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlarım — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .chat-container { display: flex; height: 100%; background: #fff; overflow: hidden; margin: 0; border: none; border-radius: 0;}
        .chat-sidebar { width: 320px; border-right: 1px solid var(--border); background: #fdfdfd; display:flex; flex-direction:column; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 1.1rem; }
        .contact-list { overflow-y: auto; flex:1; }
        .contact-item { padding: 15px 20px; display: flex; align-items: center; gap: 12px; cursor: pointer; border-bottom: 1px solid var(--border); transition: background 0.2s; text-decoration: none; color: inherit; }
        .contact-item:hover, .contact-item.active { background: #f0f0ff; }
        .contact-avatar { width: 40px; height: 40px; background: var(--gradient); border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .contact-info { flex: 1; overflow: hidden; }
        .contact-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .unread-badge { background: #e11d48; color: #fff; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 700; }
        
        .chat-area { flex: 1; display: flex; flex-direction: column; background: #fafafa; }
        .chat-header { padding: 15px 24px; border-bottom: 1px solid var(--border); background: #fff; display:flex; align-items:center; gap:12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
        .messages-list { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 12px; }
        .message-bubble { max-width: 70%; padding: 12px 16px; border-radius: 18px; line-height: 1.5; font-size: 0.95rem; position: relative; }
        .message-sent { align-self: flex-end; background: var(--primary); color: #fff; border-bottom-right-radius: 4px; }
        .message-received { align-self: flex-start; background: #fff; border: 1px solid var(--border); color: var(--text); border-bottom-left-radius: 4px; }
        .message-time { font-size: 0.7rem; margin-top: 4px; opacity: 0.7; text-align: right; }
        .message-sent .message-time { color: rgba(255,255,255,0.8); }
        .message-received .message-time { color: var(--text-muted); }
        
        .chat-input { padding: 20px; background: #fff; border-top: 1px solid var(--border); display:flex; gap: 12px; align-items: flex-end;}
        .chat-input textarea { flex: 1; border: 1px solid var(--border); border-radius: 24px; padding: 12px 20px; font-family: inherit; resize: none; min-height: 48px; max-height: 120px; outline: none; }
        .chat-input textarea:focus { border-color: var(--primary); }
        .chat-input button { border-radius: 24px; padding: 0 24px; height: 48px; }

        @media (max-width: 768px) {
            .chat-container { flex-direction: column; height: 100%; }
            .chat-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); height: 35vh; }
            .chat-area { height: 65vh; }
        }
    </style>

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>
<body class="bg-light">

    <div class="app-layout">
        <!-- ======== SIDEBAR ======== -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- ======== ANA İÇERİK ======== -->
        <div class="main-content">
            <!-- Header -->
            <div class="app-header">
                <div style="display:flex;align-items:center;gap:14px;">
                    <button class="hamburger" id="hamburger" aria-label="Menü">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="app-header-title">Mesajlarım</div>
                </div>
            </div>

            <!-- Content -->
            <div class="page-content" style="padding:0; height:calc(100vh - 72px); display:flex; flex-direction:column;">
                <div class="chat-container">
            <!-- Kişiler Listesi -->
            <div class="chat-sidebar">
                <div class="sidebar-header">Mesajlarım</div>
                <div class="contact-list">
                    <?php if (empty($conversations)): ?>
                        <div class="text-center p-4 text-muted">Henüz hiç mesajınız yok.</div>
                    <?php else: ?>
                        <?php foreach($conversations as $c): ?>
                            <a href="messages.php?uid=<?= $c['contact_id'] ?>" class="contact-item <?= ($c['contact_id'] == $active_contact_id) ? 'active' : '' ?>">
                                <div class="contact-avatar"><?= mb_substr($c['contact_name'], 0, 1) ?></div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= e($c['contact_name']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);"><?= timeAgo($c['last_message_date']) ?></div>
                                </div>
                                <?php if($c['unread_count'] > 0): ?>
                                    <div class="unread-badge"><?= $c['unread_count'] ?></div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mesajlaşma Alanı -->
            <div class="chat-area">
                <?php if ($active_contact): ?>
                    <div class="chat-header">
                        <div class="contact-avatar" style="width:36px;height:36px;font-size:0.9rem;"><?= mb_substr($active_contact['name'], 0, 1) ?></div>
                        <div style="font-weight:700;"><?= e($active_contact['name']) ?></div>
                    </div>
                    
                    <div class="messages-list" id="msg-list">
                        <?php foreach($messages as $m): ?>
                            <?php $isSent = ($m['sender_id'] == $user['id']); ?>
                            <div class="message-bubble <?= $isSent ? 'message-sent' : 'message-received' ?>">
                                <?= nl2br(e($m['message'])) ?>
                                <div class="message-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted" style="margin:auto;">Henüz bu kişiyle mesajlaşmadınız. <br>İlk mesajı gönderin!</div>
                        <?php endif; ?>
                    </div>

                    <form class="chat-input" id="chat-form" method="POST">
                        <?= csrfField() ?>
                        <textarea name="message_text" id="mText" placeholder="Mesajınızı yazın..." required></textarea>
                        <button type="submit" class="btn btn-primary">Gönder</button>
                    </form>
                <?php else: ?>
                    <div style="margin:auto;text-align:center;color:var(--text-muted);">
                        <div style="font-size:3rem;opacity:0.2;margin-bottom:10px;">💬</div>
                        Sohbete başlamak için soldan bir kişi seçin.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        // Mesaj listesinde en alta scroll yap
        const msgList = document.getElementById('msg-list');
        if(msgList) {
            msgList.scrollTop = msgList.scrollHeight;
        }

        // Enter tuşuyla mesaj gönderimi
        const mText = document.getElementById('mText');
        if(mText){
            mText.addEventListener('keydown', function(e){
                if(e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('chat-form').submit();
                }
            });
        }
    </script>
</body>
</html>
