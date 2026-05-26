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
$baseUrl = canonicalBaseUrl();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NasÄ±l Ã‡alÄ±ÅŸÄ±r & S.S.S  -  Temizci Burada</title>
    
    <!-- SEO -->
    <meta name="description" content="Temizci Burada platformu nasÄ±l Ã§alÄ±ÅŸÄ±r? Ä°lan vermek, temizlikÃ§i bulmak ve teklif almak hakkÄ±nda merak ettiÄŸiniz tÃ¼m sÄ±kÃ§a sorulan sorulara (S.S.S) buradan ulaÅŸÄ±n.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($baseUrl . '/nasil-calisir') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
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
                <div class="logo-icon"></div><span><span>Temizci Burada</span></span>
            </a>
            <div class="navbar-nav">
                <a href="listings/browse">Ä°lanlar</a>
                <a href="nasil-calisir" style="color:var(--primary);">NasÄ±l Ã‡alÄ±ÅŸÄ±r?</a>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle" title="Tema DeÄŸiÅŸtir" style="margin-right:10px;"></button>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard" class="btn btn-outline btn-sm">Panelime Git</a>
                <?php else: ?>
                    <a href="login" class="btn btn-outline btn-sm">GiriÅŸ Yap</a>
                    <a href="register" class="btn btn-primary btn-sm">KayÄ±t Ol</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- ======== HERO ======== -->
    <div style="padding-top: var(--header-h);">
        <div class="faq-hero">
            <h1>NasÄ±l Ã‡alÄ±ÅŸÄ±r & S.S.S</h1>
            <p>AklÄ±nÄ±zdaki tÃ¼m sorularÄ±n cevaplarÄ± burada. Sistemimizin nasÄ±l iÅŸlediÄŸini ve gÃ¼venliÄŸinizi nasÄ±l saÄŸladÄ±ÄŸÄ±mÄ±zÄ± Ã¶ÄŸrenin.</p>
        </div>
    </div>

    <!-- ======== SSS ICERIK ======== -->
    <div class="faq-container">
        
        <div class="faq-category">
            <div class="faq-category-title"><span>Â </span> Ev Sahipleri Ä°Ã§in</div>
            
            <div class="accordion-item">
                <button class="accordion-header">
                    Platformu kullanmak Ã¼cretli mi?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    HayÄ±r, ev sahipleri iÃ§in <strong>kayÄ±t olmak ve ilan aÃ§mak tamamen Ã¼cretsizdir.</strong> Sadece anlaÅŸtÄ±ÄŸÄ±nÄ±z temizlik Ã§alÄ±ÅŸanÄ±na hizmet sonrasÄ±nda belirlediÄŸiniz Ã¼creti Ã¶dersiniz. Platformumuz hiÃ§bir komisyon kesintisi yapmaz.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    NasÄ±l ilan verebilirim?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Ä°lk olarak sisteme kayÄ±t olup paneldeki "Evlerim" sekmesinden temizlenecek evinizin fotoÄŸraflarÄ±nÄ± ve (Ã¶r. 3+1, 120m2 gibi) temel detaylarÄ±nÄ± eklersiniz. ArdÄ±ndan "Ä°lan OluÅŸtur" kÄ±smÄ±na geÃ§erek istediÄŸiniz tarihi, hizmet tÃ¼rÃ¼nÃ¼ (Genel temizlik, Ã¼tÃ¼ vd.) ve tahmini bÃ¼tÃ§enizi seÃ§ip ilanÄ±nÄ±zÄ± yayÄ±nlayabilirsiniz.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    Gelen teklifleri nasÄ±l deÄŸerlendirmeliyim?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Ä°lanÄ± aÃ§tÄ±ktan sonra bÃ¶lgenizdeki hizmet verenler size teklif sunarlar. Ä°lan detay sayfanÄ±zdan teklifleri gÃ¶rebilirsiniz. SeÃ§im yaparken kiÅŸinin teklif fiyatÄ±na, profildeki <strong>YÄ±ldÄ±z PuanlamasÄ±na</strong>, yaptÄ±ÄŸÄ± iÅŸ sayÄ±sÄ±na ve " DoÄŸrulanmÄ±ÅŸ Profil" rozeti olup olmadÄ±ÄŸÄ±na dikkat edebilirsiniz.
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-category-title"><span></span> Hizmet Verenler Ä°Ã§in</div>
            
            <div class="accordion-item">
                <button class="accordion-header">
                    NasÄ±l iÅŸ bulabilirim?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    UygulamamÄ±za "Hizmet Veren" rolÃ¼yle kayÄ±t olduktan sonra "Ä°lanlarÄ± Gez" sayfasÄ±ndan kendi bÃ¶lgenizdeki aÃ§Ä±k temizlik ilanlarÄ±nÄ± gÃ¶rebilir, ilanlara "Teklif Ver" diyerek istediÄŸiniz fiyatÄ± ve mesajÄ±nÄ±zÄ± ev sahibine iletebilirsiniz.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    "DoÄŸrulanmÄ±ÅŸ Profil (Verified)" rozeti nedir nasÄ±l alÄ±rÄ±m?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    DoÄŸrulanmÄ±ÅŸ Profil rozeti (profildeki yeÅŸil  iÅŸareti), sistem yÃ¶neticilerimiz tarafÄ±ndan kimlik onayÄ±ndan geÃ§miÅŸ gÃ¼venilir temizlik profesyonellerine verilir. Bu rozet ev sahiplerinin size olan gÃ¼venini arttÄ±rÄ±r. DoÄŸrulama talebi iÃ§in profil sayfanÄ±zdaki Destek sisteminden bize ulaÅŸabilirsiniz.
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-category-title"><span></span> GÃ¼venlik & Ã–deme</div>
            
            <div class="accordion-item">
                <button class="accordion-header">
                    Ã–demeler nasÄ±l yapÄ±lÄ±yor?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    Åu anda platformumuz online Ã¶deme <strong>almamaktadÄ±r.</strong> Teklif Ã¼zerinden anlaÅŸtÄ±ÄŸÄ±nÄ±z tutarÄ± iÅŸ bitiminde doÄŸrudan hizmet verene nakit veya havale sistemi ile elden Ã¶dersiniz. Param gÃ¼vende gibi altyapÄ±lar yakÄ±n zamanda sisteme dahil olacaktÄ±r.
                </div>
            </div>

            <div class="accordion-item">
                <button class="accordion-header">
                    Bir sorun yaÅŸarsam ne yapmalÄ±yÄ±m?
                    <span class="icon">&#9660;</span>
                </button>
                <div class="accordion-content">
                    <br>
                    KullanÄ±cÄ± panelinizdeki "Destek Talebi" (Ticket) sistemi Ã¼zerinden sorununuzu bilet numarasÄ± ile doÄŸrudan merkez ofisimize e-posta formatÄ±nda iletebilirsiniz. Ekibimiz en kÄ±sa sÃ¼rede mÃ¼dahale ederek iki tarafÄ±n maÄŸduriyetini Ã§Ã¶zecektir.
                </div>
            </div>
        </div>

        <!-- Yasal -->
        <div style="margin-top:60px; text-align:center;">
            <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:14px;">DiÄŸer Yasal SÃ¶zleÅŸmeler</p>
            <div style="display:flex; justify-content:center; gap:20px; flex-wrap:wrap;">
                <a href="#" class="btn btn-ghost btn-sm">KullanÄ±m KoÅŸullarÄ±</a>
                <a href="gizlilik-politikasi" class="btn btn-ghost btn-sm">Gizlilik PolitikasÄ±</a>
                <a href="kvkk" class="btn btn-ghost btn-sm">KVKK AydÄ±nlatma Metni</a>
                <a href="#" class="btn btn-ghost btn-sm">Uzak Mesafeli E-SÃ¶zleÅŸme</a>
            </div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/app.js?v=5.8"></script>
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

        // AÃ§Ä±lÄ±ÅŸta ilk itemi aÃ§Ä±k yapalÄ±m
        const firstItem = document.querySelector('.accordion-item');
        if(firstItem) firstItem.classList.add('active');
    </script>
</body>
</html>



