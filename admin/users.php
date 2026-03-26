<?php
require_once __DIR__ . '/includes/auth.php'; // Protects this file for admins only
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Handle ban/unban or verify/unverify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    
    // Safety check: don't alter the admin
    if ($userId !== $user['id'] && $userId > 0) {
        if ($action === 'verify') {
            try {
                $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$userId]);
                setFlash('success', 'KullanÄ±cÄ± doÄŸrulandÄ±.');
            } catch(PDOException $e) {
                setFlash('error', 'Ä°ÅŸlem baÅŸarÄ±sÄ±z.');
            }
        } elseif ($action === 'unverify') {
             try {
                $db->prepare("UPDATE users SET is_verified = 0 WHERE id = ?")->execute([$userId]);
                setFlash('success', 'DoÄŸrulama iptal edildi.');
            } catch(PDOException $e) {}
        } elseif ($action === 'ban') {
            $db->prepare("UPDATE users SET role = 'banned' WHERE id = ?")->execute([$userId]);
            setFlash('success', 'KullanÄ±cÄ± yasaklandÄ±.');
        } elseif ($action === 'unban') {
            $db->prepare("UPDATE users SET role = 'homeowner' WHERE id = ? AND role = 'banned'")->execute([$userId]);
            setFlash('success', 'KullanÄ±cÄ± banÄ± kaldÄ±rÄ±ldÄ±.');
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
    <title>KullanÄ±cÄ± YÃ¶netimi â€” Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'KullanÄ±cÄ± YÃ¶netimi'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-header" style="margin-bottom: 2rem;">
                    <div class="page-title">Sistemdeki TÃ¼m KullanÄ±cÄ±lar</div>
                    <div class="page-subtitle">Platforma kayÄ±tlÄ± tÃ¼m kullanÄ±cÄ±larÄ± listeleyip, yetkilendirebilirsiniz.</div>
                </div>

                <div class="card" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-light);">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>KullanÄ±cÄ±</th>
                                    <th>Rol</th>
                                    <th>DoÄŸrulama</th>
                                    <th>KayÄ±t Tarihi</th>
                                    <th>Ä°ÅŸlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?= e($u['name']) ?></div>
                                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $u['role'] === 'worker' ? 'badge-progress' : ($u['role'] === 'banned' ? 'badge-cancelled' : 'badge-open') ?>" style="font-size: 0.75rem; padding: 4px 10px;">
                                            <?php 
                                            if($u['role'] === 'worker') echo 'Hizmet Veren';
                                            elseif($u['role'] === 'admin') echo 'Admin';
                                            elseif($u['role'] === 'banned') echo 'YasaklÄ±';
                                            else echo 'Ev Sahibi';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(isset($u['is_verified']) && $u['is_verified']): ?>
                                            <span style="color:#10b981;font-weight:700;font-size:0.8rem;">âœ… OnaylÄ±</span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:0.8rem;">Standart</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <?php if($u['id'] !== $user['id']): ?>
                                            <div style="display:flex; gap:6px;">
                                                <form method="POST" onsubmit="return confirm('Emin misiniz?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    
                                                    <?php if(!isset($u['is_verified']) || !$u['is_verified']): ?>
                                                        <button type="submit" name="action" value="verify" class="btn btn-sm btn-outline" style="padding:4px 8px; border-color:#10b981; color:#10b981; font-size: 0.75rem;">Onayla</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action" value="unverify" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size: 0.75rem;">Geri Al</button>
                                                    <?php endif; ?>

                                                    <?php if($u['role'] !== 'banned'): ?>
                                                        <button type="submit" name="action" value="ban" class="btn btn-sm btn-outline" style="padding:4px 8px; border-color:#ef4444; color:#ef4444; font-size: 0.75rem;">Yasakla</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action" value="unban" class="btn btn-sm btn-outline" style="padding:4px 8px; font-size: 0.75rem;">KaldÄ±r</button>
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
    <script src="../assets/js/app.js?v=5.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>


