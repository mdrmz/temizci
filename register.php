<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn())
  redirect(APP_URL . '/dashboard');

$errors = [];
$role = $_GET['role'] ?? 'homeowner';
$refCode = trim($_GET['ref'] ?? $_POST['ref_code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCsrf()) {
    $errors[] = 'Güvenlik hatası.';
  } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    $role_val = in_array($_POST['role'] ?? '', ['homeowner', 'worker']) ? $_POST['role'] : 'homeowner';
    $city = trim($_POST['city'] ?? '');

    if (strlen($name) < 2)
      $errors[] = 'Ad en az 2 karakter olmalı.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      $errors[] = 'Geçerli bir e-posta girin.';
    if (strlen($password) < 6)
      $errors[] = 'Şifre en az 6 karakter olmalı.';
    if ($password !== $confirm)
      $errors[] = 'Şifreler eşleşmiyor.';
    if (empty($_POST['kvkk_accept']))
      $errors[] = 'KVKK Aydınlatma Metni’ni kabul etmelisiniz.';

    if (empty($errors)) {
      $db = getDB();
      $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Referans kodu ile davet eden kullanıcıyı bul
        $referredBy = null;
        if ($refCode) {
            $refStmt = $db->prepare("SELECT id FROM users WHERE referral_code = ?");
            $refStmt->execute([$refCode]);
            $referrer = $refStmt->fetch();
            if ($referrer) $referredBy = $referrer['id'];
        }
        
        // Yeni kullanıcı için referans kodu oluştur
        $newRefCode = strtoupper(substr(md5(uniqid() . $email), 0, 8));
        
        try {
            $db->prepare("INSERT INTO users (name, email, phone, password, role, city, referral_code, referred_by) VALUES (?,?,?,?,?,?,?,?)")
              ->execute([$name, $email, $phone, $hash, $role_val, $city, $newRefCode, $referredBy]);
        } catch (Exception $e) {
            // referred_by veya referral_code kolonları yoksa eski yöntemle kaydet
            $db->prepare("INSERT INTO users (name, email, phone, password, role, city) VALUES (?,?,?,?,?,?)")
              ->execute([$name, $email, $phone, $hash, $role_val, $city]);
        }
        
        $userId = $db->lastInsertId();
        loginUser($userId);
        setFlash('success', 'Hoş geldiniz! Hesabınız oluşturuldu.' . ($referredBy ? ' 🎁 Davet ile katıldınız!' : ''));
        redirect(APP_URL . '/dashboard');
      }
    }
  }
}
$cities = getCities();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kayıt Ol — Temizci Burada</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
  <div class="auth-page">
    <div class="auth-card" style="max-width:520px;">
      <div class="auth-logo">
        <div class="logo-icon">🧹</div>
        <h1>Ücretsiz Hesap Oluştur</h1>
        <p>Birkaç adımda kayıt olun</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="flash flash-error">❌ <?= e(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <form method="POST" action="" data-validate>
        <?= csrfField() ?>
        <?php if ($refCode): ?>
            <input type="hidden" name="ref_code" value="<?= e($refCode) ?>">
            <div style="background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(139,92,246,0.08));border:1px solid rgba(99,102,241,0.2);border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:1.3rem;">🎁</span>
                <div>
                    <div style="font-weight:700;font-size:0.88rem;color:var(--primary);">Davet ile Katılıyorsunuz!</div>
                    <div style="font-size:0.78rem;color:var(--text-muted);">Davet kodu: <?= e($refCode) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Rol Seçimi -->
        <div class="form-group">
          <label class="form-label">Ben bir...</label>
          <div class="role-selector">
            <div class="role-option">
              <input type="radio" name="role" id="role_homeowner" value="homeowner" <?= (($_POST['role'] ?? $role) === 'homeowner') ? 'checked' : '' ?>>
              <label for="role_homeowner">
                <span class="role-icon">🏠</span>
                <span class="role-name">Ev Sahibiyim</span>
                <span class="role-desc">Temizlik hizmeti arıyorum</span>
              </label>
            </div>
            <div class="role-option">
              <input type="radio" name="role" id="role_worker" value="worker" <?= (($_POST['role'] ?? $role) === 'worker') ? 'checked' : '' ?>>
              <label for="role_worker">
                <span class="role-icon">🧹</span>
                <span class="role-name">Hizmet Vereceğim</span>
                <span class="role-desc">İş fırsatları arıyorum</span>
              </label>
            </div>
          </div>
        </div>

        <div class="grid-2" style="gap:14px;">
          <div class="form-group">
            <label class="form-label" for="name">Ad Soyad</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="Ayşe Kara"
              value="<?= e($_POST['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="phone">Telefon</label>
            <input type="tel" id="phone" name="phone" class="form-control" placeholder="05xx xxx xx xx"
              value="<?= e($_POST['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">E-posta Adresi</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="ornek@mail.com"
            value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="city">Şehir</label>
          <select id="city" name="city" class="form-control">
            <option value="">Şehir seçin</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= e($c) ?>" <?= (($_POST['city'] ?? '') === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid-2" style="gap:14px;">
          <div class="form-group">
            <label class="form-label" for="password">Şifre</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 karakter"
              required>
          </div>
          <div class="form-group">
            <label class="form-label" for="password_confirm">Şifre Tekrar</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control"
              placeholder="Şifrenizi tekrar girin" required>
          </div>
        </div>

        <!-- KVKK Onay -->
        <div class="form-group" style="margin-top:4px;">
          <label
            style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:0.88rem;color:var(--text-secondary);line-height:1.5;">
            <input type="checkbox" name="kvkk_accept" id="kvkk_accept" value="1"
              style="margin-top:3px;accent-color:var(--primary);width:16px;height:16px;flex-shrink:0;"
              <?= !empty($_POST['kvkk_accept']) ? 'checked' : '' ?> required>
            <span>
              <a href="kvkk" target="_blank" style="color:var(--primary);font-weight:600;">KVKK Aydınlatma
                Metni</a>’ni ve
              <a href="cerez-politikasi" target="_blank" style="color:var(--primary);font-weight:600;">Çerez
                Politikası</a>’nı
              okudum, kişisel verilerimin işlenmesine açık rıza gösteriyorum. <span style="color:#e11d48;">*</span>
            </span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
          🚀 Hesabımı Oluştur
        </button>
      </form>

      <div class="auth-footer mt-3">
        Zaten hesabınız var mı? <a href="login">Giriş Yap</a>
      </div>
      <div class="text-center mt-2">
        <a href="index" style="font-size:0.82rem;color:var(--text-muted);">← Ana Sayfaya Dön</a>
      </div>
    </div>
  </div>
  <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>