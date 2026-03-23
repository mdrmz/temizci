<?php
require_once __DIR__ . '/includes/auth.php'; // Protects this file for admins only
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $icon = trim($_POST['icon'] ?? '📋');
        
        if ($name && $slug) {
            try {
                $db->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)")
                   ->execute([$name, $slug, $icon]);
                setFlash('success', 'Kategori başarıyla eklendi.');
            } catch(PDOException $e) {
                setFlash('error', 'Kategori eklenirken hata oluştu.');
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $icon = trim($_POST['icon'] ?? '📋');
        
        try {
            $db->prepare("UPDATE categories SET name=?, slug=?, icon=? WHERE id=?")
               ->execute([$name, $slug, $icon, $id]);
            setFlash('success', 'Kategori güncellendi.');
        } catch(PDOException $e) {
            setFlash('error', 'Güncelleme hatası.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
            setFlash('success', 'Kategori silindi.');
        } catch(PDOException $e) {
            setFlash('error', 'Kategoriye ait ilanlar olduğu için silinemedi.');
        }
    }
    
    redirect('/admin/categories');
}

// Fetch categories
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi — Admin Paneli</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Kategori Yönetimi'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-header" style="margin-bottom: 2rem;">
                    <div class="page-title">Hizmet Kategorileri</div>
                    <div class="page-subtitle">Sistemde kullanılan hizmet başlıklarını ve ikonlarını buradan yönetin.</div>
                </div>

                <div class="grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                    
                    <div class="card" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-light);">
                        <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-light); background: rgba(0,0,0,0.02);">
                            <div class="card-title" style="font-weight: 700; font-size: 1.1rem;">Mevcut Kategoriler</div>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>İkon</th>
                                        <th>Adı</th>
                                        <th>Link (Slug)</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($categories as $c): ?>
                                    <tr>
                                        <td style="font-size:1.6rem;"><?= $c['icon'] ?></td>
                                        <td style="font-weight:700; color: var(--text-primary);"><?= e($c['name']) ?></td>
                                        <td style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted);"><?= e($c['slug']) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinizden emin misiniz?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" style="padding:4px 10px; border-color:#ef4444; color:#ef4444; font-size: 0.75rem;">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card" style="align-self:start; border-radius: 16px; border: 1px solid var(--border-light);">
                        <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-light); background: rgba(0,0,0,0.02);">
                            <div class="card-title" style="font-weight: 700; font-size: 1.1rem;">Yeni Kategori</div>
                        </div>
                        <div class="card-body" style="padding: 20px;">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add">
                                
                                <div class="form-group mb-4">
                                    <label class="form-label">Kategori Adı</label>
                                    <input type="text" name="name" class="form-control" required placeholder="Örn: Ev Temizliği">
                                </div>
                                <div class="form-group mb-4">
                                    <label class="form-label">Link Adresi (Slug)</label>
                                    <input type="text" name="slug" class="form-control" required placeholder="Örn: ev-temizligi">
                                    <div class="form-hint" style="font-size: 0.75rem; margin-top: 4px;">Sadece küçük harf ve tire (-)</div>
                                </div>
                                <div class="form-group mb-4">
                                    <label class="form-label">Simge (Emoji)</label>
                                    <input type="text" name="icon" class="form-control" required placeholder="Örn: 🧹" value="📋">
                                </div>
                                
                                <button class="btn btn-primary btn-block" type="submit" style="padding: 12px; font-weight: 700;">Kategori Ekle</button>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=4.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
