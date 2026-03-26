<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM homes WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$homes = $stmt->fetchAll();
$notifCount = getUnreadNotificationCount($user['id']);
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evlerim  -  Temizci Burada</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Evlerim'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-title"> Kayıtlı Evlerim</div>
                <div class="page-subtitle">Kayıtlı evlerinizi yönetin, yeni ilanlar için kullanın</div>

                <?php if (empty($homes)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <div class="empty-state-icon"> </div>
                            <h3>Henüz ev eklemediniz</h3>
                            <p>Temizlik ilanı oluşturmak için önce bir ev eklemeniz gerekiyor.</p>
                            <a href="add" class="btn btn-primary">  İlk Evimi Ekle</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid-3">
                        <?php foreach ($homes as $home): ?>
                            <div class="card">
                                <?php if ($home['photo']): ?>
                                    <img src="<?= UPLOAD_URL . e($home['photo']) ?>" alt="Ev Fotoğrafı"
                                        style="width:100%;height:180px;object-fit:cover;">
                                <?php else: ?>
                                    <div
                                        style="width:100%;height:160px;background:var(--gradient-soft);display:flex;align-items:center;justify-content:center;font-size:3rem;">
                                         </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div style="font-weight:700;font-size:1rem;margin-bottom:8px;">
                                        <?= e($home['title']) ?>
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:14px;">
                                        <div style="font-size:0.82rem;color:var(--text-muted);">
                                            
                                            <?= e($home['district'] ?: $home['city']) ?>,
                                            <?= e($home['city']) ?>
                                        </div>
                                        <div style="display:flex;gap:12px;font-size:0.82rem;color:var(--text-muted);">
                                            <span>
                                                <?= e($home['room_config']) ?>
                                            </span>
                                            <span>
                                                <?= $home['bathroom_count'] ?> Banyo
                                            </span>
                                            <?php if ($home['sqm']): ?><span>
                                                    <?= $home['sqm'] ?>m²
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($home['has_elevator']): ?>
                                            <div style="font-size:0.78rem;color:var(--secondary);"> Asansörlü</div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <a href="../listings/create?home_id=<?= $home['id'] ?>"
                                            class="btn btn-primary btn-sm"> İlan Oluştur</a>
                                        <a href="edit?id=<?= $home['id'] ?>" class="btn btn-ghost btn-sm"> Düzenle</a>
                                        <a href="delete?id=<?= $home['id'] ?>" class="btn btn-danger btn-sm"
                                            data-confirm="Bu evi silmek istediğinizden emin misiniz?"></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Yeni Ev Ekle Kartı -->
                        <a href="add" class="card"
                            style="display:flex;align-items:center;justify-content:center;min-height:240px;border:2px dashed var(--border);border-radius:var(--radius-lg);text-decoration:none;flex-direction:column;gap:12px;color:var(--text-muted);transition:var(--transition);"
                            onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
                            onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'">
                            <div style="font-size:2.5rem;">â•</div>
                            <div style="font-weight:600;">Yeni Ev Ekle</div>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=5.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>


