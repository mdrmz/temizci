<?php
// includes/footer.php
?>
<footer class="site-footer">
    <div class="container">
        <div class="grid-4" style="gap:40px;margin-bottom:40px;">
            <div>
                <a href="<?= APP_URL ?>/" class="navbar-logo" style="margin-bottom:20px;display:inline-flex;">
                    <div class="logo-icon" style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fff;">
                        <img src="<?= APP_URL ?>/logo.png" alt="Temizci Burada" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                </a>
                <p class="site-footer-copy">
                    Temizci Burada; ev temizliği, cam temizliği, ütü ve diğer ev hizmetlerinde güvenilir kişileri
                    bulabileceğiniz daha düzenli ve daha sakin bir pazaryeridir.
                </p>
            </div>
            <div>
                <h4>Hızlı Bağlantılar</h4>
                <div class="site-footer-links">
                    <a href="<?= APP_URL ?>/listings/browse">İlanlar</a>
                    <a href="<?= APP_URL ?>/quick-request">Hızlı Talep</a>
                    <a href="<?= APP_URL ?>/login">Giriş Yap</a>
                    <a href="<?= APP_URL ?>/register">Kayıt Ol</a>
                </div>
            </div>
            <div>
                <h4>Kurumsal</h4>
                <div class="site-footer-links">
                    <a href="<?= APP_URL ?>/#hakkimizda">Hakkımızda</a>
                    <a href="<?= APP_URL ?>/#iletisim">İletişim</a>
                    <a href="<?= APP_URL ?>/sitemap">Site Haritası</a>
                </div>
            </div>
            <div>
                <h4>Yasal</h4>
                <div class="site-footer-links">
                    <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>
                    <a href="<?= APP_URL ?>/gizlilik-politikasi">Gizlilik Politikası</a>
                    <a href="<?= APP_URL ?>/kvkk-aydinlatma">KVKK Aydınlatma Metni</a>
                    <a href="<?= APP_URL ?>/cerez-politikasi">Çerez Politikası</a>
                    <a href="<?= APP_URL ?>/destek" data-track="footer_support_click">Destek</a>
                </div>
            </div>
        </div>
        <div class="site-footer-bottom">
            <div>&copy; <?= date('Y') ?> Temizci Burada. Tüm hakları saklıdır.</div>
            <div>Güvenli hizmet platformu</div>
        </div>
    </div>
</footer>
