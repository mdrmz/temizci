<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? currentUser() : null;
$initials = $isLoggedIn ? strtoupper(substr($user['name'], 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nasıl Çalışır & S.S.S — Temizci Burada</title>
    
    <!-- SEO -->
    <meta name="description" content="Temizci Burada platformu nasıl çalışır? İlan vermek, temizlikçi bulmak ve teklif almak hakkında merak ettiğiniz tüm sıkça sorulan sorulara (S.S.S) buradan ulaşın.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.temizciburada.com/nasil-calisir">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    
    <link rel="icon" href="/logo.png" type="image/png">
    
    <style>
        .faq-hero {
            padding: 80px 20px 60px;
            background: linear-gradient(135deg, rgba(79,70,229,0.05), rgba(6,182,212,0.05));
            border-bottom: 1px solid var(--border);
            text-align: center;
        }
        .faq-hero h1 { font-weight: 800; font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 16px; color: var(--text-primary); }
        .faq-hero p { max-width: 600px; margin: 0 auto; color: var(--text-secondary); font-size: 1.1rem; line-height: 1.6; }

        .faq-container { max-width: 800px; margin: 0 auto; padding: 60px 20px; }
        
        .faq-category { margin-bottom: 40px; }
        .faq-category-title { 
            font-size: 1.4rem; font-weight: 700; color: var(--primary); 
            margin-bottom: 20px; display:flex; align-items:center; gap:10px;
        }

        .accordion-item {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-bottom: 12px;
            overflow: hidden;
            transition: var(--transition);
        }
        .accordion-item:hover { border-color: var(--primary); }
        
        .accordion-header {
            width: 100%; text-align: left; background: transparent; border: none;
            padding: 18px 20px; font-weight: 600; font-size: 1.05rem; color: var(--text-primary);
            display: flex; justify-content: space-between; align-items: center;
            cursor: pointer;
        }
        .accordion-header .icon {
            font-size: 1.2rem; color: var(--text-muted); transition: transform 0.3s ease;
        }
        .accordion-content {
            padding: 0 20px; max-height: 0; overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
            background: var(--bg); color: var(--text-secondary); line-height: 1.7;
        }
        
        .accordion-item.active .accordion-header .icon { transform: rotate(180deg); color: var(--primary); }
        .accordion-item.active .accordion-content { padding: 0 20px 20px; max-height: 1000px; }
    </style>
</head>
<body class="bg-light">

    <!-- ======== NAV ======== -->
    <nav class="navbar scrolled" style="background:rgba(255,255,255,0.95);">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon">🧹</div><span><span>Temizci Burada</span></span>
            </a>
            <div class="navbar-nav">
                <a href="listings/browse">İlanlar</a>
                <a href="nasil-calisir" style="color:var(--primary);">Nasıl Çalışır?</a>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle" title="Tema Değiştir" style="margin-right:10px;">🌙</button>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard" class="btn btn-outline btn-sm">Panelime Git</a>
                <?php else: ?>
                    <a href="login" class="btn btn-outline btn-sm">Giriş Yap</a>
                    <a href="register" class="btn btn-primary btn-sm">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- ======== HERO ======== -->
    <div style="padding-top: var(--header-h);">
        <div class="faq-hero">
            <h1>Nasıl Çalışır & S.S.S</h1>
            <p>Aklınızdaki tüm soruların cevapları burada. Sistemimizin nasıl işlediğini ve güvenliğinizi nasıl sağladığımızı öğrenin.</p>
        </div>
    </div>

    <!-- ======== SSS ICERIK ======== -->
    <div class="faq-container">
        
        <div class="faq-category">
            <div class="faq-category-title"><span>🏠</span> Ev Sahipleri İçin</div>
            
            <div class="accordion-item">
                <button class="accordion-header">
                    Platformu kullanmak ücretli mi?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Hayır, ev sahipleri için <strong>kayıt olmak ve ilan açmak tamamen ücretsizdir.</strong> Sadece anlaştığınız temizlik çalışanına hizmet sonrasında belirlediğiniz ücreti ödersiniz. Platformumuz hiçbir komisyon kesintisi yapmaz.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    Nasıl ilan verebilirim?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    İlk olarak sisteme kayıt olup paneldeki "Evlerim" sekmesinden temizlenecek evinizin fotoğraflarını ve (ör. 3+1, 120m2 gibi) temel detaylarını eklersiniz. Ardından "İlan Oluştur" kısmına geçerek istediğiniz tarihi, hizmet türünü (Genel temizlik, ütü vd.) ve tahmini bütçenizi seçip ilanınızı yayınlayabilirsiniz.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    Gelen teklifleri nasıl değerlendirmeliyim?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    İlanı açtıktan sonra bölgenizdeki hizmet verenler size teklif sunarlar. İlan detay sayfanızdan teklifleri görebilirsiniz. Seçim yaparken kişinin teklif fiyatına, profildeki <strong>Yıldız Puanlamasına</strong>, yaptığı iş sayısına ve "✅ Doğrulanmış Profil" rozeti olup olmadığına dikkat edebilirsiniz.
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-category-title"><span>🧹</span> Hizmet Verenler İçin</div>
            
            <div class="accordion-item">
                <button class="accordion-header">
                    Nasıl iş bulabilirim?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Uygulamamıza "Hizmet Veren" rolüyle kayıt olduktan sonra "İlanları Gez" sayfasından kendi bölgenizdeki açık temizlik ilanlarını görebilir, ilanlara "Teklif Ver" diyerek istediğiniz fiyatı ve mesajınızı ev sahibine iletebilirsiniz.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    "Doğrulanmış Profil (Verified)" rozeti nedir nasıl alırım?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Doğrulanmış Profil rozeti (profildeki yeşil ✅ işareti), sistem yöneticilerimiz tarafından kimlik onayından geçmiş güvenilir temizlik profesyonellerine verilir. Bu rozet ev sahiplerinin size olan güvenini arttırır. Doğrulama talebi için profil sayfanızdaki Destek sisteminden bize ulaşabilirsiniz.
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-category-title"><span>🔒</span> Güvenlik & Ödeme</div>
            
            <div class="accordion-item">
                <button class="accordion-header">
                    Ödemeler nasıl yapılıyor?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Şu anda platformumuz online ödeme <strong>almamaktadır.</strong> Teklif üzerinden anlaştığınız tutarı iş bitiminde doğrudan hizmet verene nakit veya havale sistemi ile elden ödersiniz. Param güvende gibi altyapılar yakın zamanda sisteme dahil olacaktır.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    Bir sorun yaşarsam ne yapmalıyım?
                    <span class="icon">▼</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Kullanıcı panelinizdeki "Destek Talebi" (Ticket) sistemi üzerinden sorununuzu bilet numarası ile doğrudan merkez ofisimize e-posta formatında iletebilirsiniz. Ekibimiz en kısa sürede müdahale ederek iki tarafın mağduriyetini çözecektir.
                </div>
            </div>
        </div>

        <!-- Yasal -->
        <div style="margin-top:60px; text-align:center;">
            <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:14px;">Diğer Yasal Sözleşmeler</p>
            <div style="display:flex; justify-content:center; gap:20px; flex-wrap:wrap;">
                <a href="#" class="btn btn-ghost btn-sm">Kullanım Koşulları</a>
                <a href="#" class="btn btn-ghost btn-sm">KVKK Aydınlatma Metni</a>
                <a href="#" class="btn btn-ghost btn-sm">Uzak Mesafeli E-Sözleşme</a>
            </div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        // Accordion functionality
        document.querySelectorAll('.accordion-header').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.parentElement;
                const isActive = item.classList.contains('active');
                
                // Close all others
                document.querySelectorAll('.accordion-item').forEach(acc => acc.classList.remove('active'));
                
                // Toggle current
                if (!isActive) item.classList.add('active');
            });
        });

        // Açılışta ilk itemi açık yapalım
        const firstItem = document.querySelector('.accordion-item');
        if(firstItem) firstItem.classList.add('active');
    </script>
</body>
</html>
