<?php
// includes/footer.php
?>
<footer class="site-footer" style="background:#0F172A;color:#fff;padding:60px 0 30px;margin-top:auto;">
    <div class="container">
        <div class="grid-4" style="gap:40px;margin-bottom:40px;">
            <div>
                <a href="<?= APP_URL ?>/" class="navbar-logo" style="margin-bottom:20px;display:inline-flex;">
                    <div class="logo-icon" style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fff;">
                        <img src="<?= APP_URL ?>/logo.png" alt="Temizci Burada" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                </a>
                <p style="color:rgba(255,255,255,0.7);font-size:0.9rem;line-height:1.6;">
                    Temizci Burada, ev temizliği, cam temizliği, ütü ve diğer ev hizmetlerinde güvenilir kişiler bulabileceğiniz Türkiye'nin pazar yeridir. Profesyonel ve pratik çözümler burada.
                </p>
            </div>
            <div>
                <h4 style="color:#fff;margin-bottom:20px;font-size:1.1rem;">Hızlı Bağlantılar</h4>
                <ul style="list-style:none;padding:0;margin:0;">
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/listings/browse" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">İlanlar</a></li>
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/login" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">Giriş Yap</a></li>
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/register" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">Kayıt Ol</a></li>
                </ul>
            </div>
            <div>
                <h4 style="color:#fff;margin-bottom:20px;font-size:1.1rem;">Kurumsal</h4>
                <ul style="list-style:none;padding:0;margin:0;">
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/#hakkimizda" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">Hakkımızda</a></li>
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/#iletisim" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">İletişim</a></li>
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/sitemap" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">Site Haritası</a></li>
                </ul>
            </div>
            <div>
                <h4 style="color:#fff;margin-bottom:20px;font-size:1.1rem;">Yasal</h4>
                <ul style="list-style:none;padding:0;margin:0;">
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/#" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">E-Sözleşme</a></li>
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/#" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">Kullanım Koşulları</a></li>
                    <li style="margin-bottom:10px;"><a href="<?= APP_URL ?>/#" style="color:rgba(148,163,184,1);text-decoration:none;transition:all 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(148,163,184,1)'">KVKK & Gizlilik</a></li>
                </ul>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.1);padding-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;color:rgba(148,163,184,1);font-size:0.85rem;">
            <div>&copy; <?= date('Y') ?> Temizci Burada. Tüm Hakları Saklıdır.</div>
            <div style="display:flex;gap:15px;">
                <span>Güvenli Hizmet Platformu</span>
            </div>
        </div>
    </div>
</footer>
