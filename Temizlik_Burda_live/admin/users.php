<?php
require_once __DIR__ . '/includes/auth.php'; // Protects this file for admins only
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Handle ban/unban or verify/unverify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    // Safety checks: do not alter current admin, and do not alter other admins from here.
    if ($userId !== $user['id'] && $userId > 0) {
        $targetStmt = $db->prepare("SELECT id, role FROM users WHERE id = ?");
        $targetStmt->execute([$userId]);
        $targetUser = $targetStmt->fetch();

        if (!$targetUser) {
            setFlash('error', 'Kullanıcı bulunamadı.');
        } elseif (($targetUser['role'] ?? '') === 'admin') {
            setFlash('error', 'Admin kullanıcı bu ekrandan değiştirilemez.');
        } elseif ($action === 'verify') {
            try {
                $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$userId]);
                setFlash('success', 'Kullanıcı doğrulandı.');
            } catch (PDOException $e) {
                setFlash('error', 'İşlem başarısız.');
            }
        } elseif ($action === 'unverify') {
            try {
                $db->prepare("UPDATE users SET is_verified = 0 WHERE id = ?")->execute([$userId]);
                setFlash('success', 'Doğrulama iptal edildi.');
            } catch (PDOException $e) {
                setFlash('error', 'İşlem başarısız.');
            }
        } elseif ($action === 'ban') {
            $db->prepare("UPDATE users SET is_active = 0, session_version = COALESCE(session_version, 0) + 1 WHERE id = ?")
                ->execute([$userId]);
            setFlash('success', 'Kullanıcı yasaklandı.');
        } elseif ($action === 'unban') {
            $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?")
                ->execute([$userId]);
            setFlash('success', 'Kullanıcının yasağı kaldırıldı.');
        }
    }
    redirect('/admin/users');
}

// Fetch users
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi — Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Kullanıcı Yönetimi'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-header" style="margin-bottom: 2rem;">
                    <div class="page-title">Sistemdeki Tüm Kullanıcılar</div>
                    <div class="page-subtitle">Platforma kayıtlı tüm kullanıcıları listeleyip, yetkilendirebilirsiniz.</div>
                </div>

                <div class="card" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-light);">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Rol</th>
                                    <th>Doğrulama</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?= e($u['name']) ?></div>
                                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= (int)($u['is_active'] ?? 1) === 0 ? 'badge-cancelled' : ($u['role'] === 'worker' ? 'badge-progress' : 'badge-open') ?>" style="font-size: 0.75rem; padding: 4px 10px;">
                                            <?php
                                            if ((int)($u['is_active'] ?? 1) === 0) {
                                                echo 'Yasaklı';
                                            } elseif ($u['role'] === 'worker') {
                                                echo 'Hizmet Veren';
                                            } elseif ($u['role'] === 'admin') {
                                                echo 'Admin';
                                            } else {
                                                echo 'Ev Sahibi';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($u['is_verified']) && $u['is_verified']): ?>
                                            <span style="color:#10b981;font-weight:700;font-size:0.8rem;">✅ Onaylı</span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:0.8rem;">Standart</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <div style="display:flex; gap:6px;">
                                                <form method="POST" data-confirm="Bu kullanıcı işlemini onaylıyor musunuz?">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                                                    <?php if (!isset($u['is_verified']) || !$u['is_verified']): ?>
                                                        <button type="submit" name="action" value="verify" class="btn btn-sm btn-outline" style="padding:4px 8px; border-color:#10b981; color:#10b981; font-size: 0.75rem;">Onayla</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action" value="unverify" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size: 0.75rem;">Geri Al</button>
                                                    <?php endif; ?>

                                                    <?php if ((int)($u['is_active'] ?? 1) === 1): ?>
                                                        <button type="submit" name="action" value="ban" class="btn btn-sm btn-outline" style="padding:4px 8px; border-color:#ef4444; color:#ef4444; font-size: 0.75rem;">Yasakla</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action" value="unban" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size: 0.75rem;">Kaldır</button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=5.8"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
