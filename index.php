<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Giriş yaptıysa dashboard'a yönlendir
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard');
}

// Son ilanları çek
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT l.*, c.name AS cat_name, c.icon AS cat_icon,
               u.name AS owner_name,
               h.city, h.room_config, h.photo AS home_photo
        FROM listings l
        JOIN categories c ON l.category_id = c.id
        JOIN users u ON l.user_id = u.id
        JOIN homes h ON l.home_id = h.id
        WHERE l.status = 'open'
        ORDER BY l.created_at DESC
        LIMIT 6
    ");
    $listings = $stmt->fetchAll();
    $categories = getCategories();
} catch (Exception $e) {
    $listings = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ===== Temel SEO ===== -->
    <title>Temizci Burada — Eviniz İçin Güvenilir Temizlik Hizmeti</title>
    <meta name="description"
        content="Temizci Burada ile eviniz için güvenilir ev temizliği, cam temizliği, ütü ve daha fazlasında hizmet ilanı oluşturun. Binlerce temizlikçi arasından size en uygununu bulun.">
    <meta name="keywords"
        content="temizlikçi bul, ev temizliği, temizlik ilanı, günübirlik temizlikçi, ev temizleme hizmeti, temizlikçi ara, Türkiye temizlik platformu">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.temizciburada.com/">
    
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#10b981">
    <link rel="apple-touch-icon" href="logo.png">

    <!-- ===== Open Graph (Facebook, WhatsApp, LinkedIn) ===== -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.temizciburada.com/">
    <meta property="og:title" content="Temizci Burada — Güvenilir Temizlik Hizmeti Platformu">
    <meta property="og:description"
        content="Eviniz için güvenilir temizlik hizmeti bulmanın en kolay yolu. İlan verin, teklifler alın, seçin ve temiz bir eve kavuşun!">
    <meta property="og:image" content="https://temizciburada.com/logo.png">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Temizci Burada">

    <!-- ===== Twitter / X Card ===== -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Temizci Burada — Temizlik Hizmetleri Platformu">
    <meta name="twitter:description"
        content="Türkiye'nin yeni temizlik hizmetleri pazaryeri. İlan ver, teklif al, temiz bir eve kavuş!">
    <meta name="twitter:image" content="https://temizciburada.com/logo.png">

    <!-- ===== Schema.org — WebSite + Organization + Service ===== -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "name": "Temizci Burada",
          "alternateName": "temizciburada.com",
          "url": "https://www.temizciburada.com",
          "description": "Ev temizliği, cam temizliği ve günübirlik hizmetler için Türkiye'nin güvenilir platformu.",
          "inLanguage": "tr-TR",
          "potentialAction": {
            "@type": "SearchAction",
            "target": "https://www.temizciburada.com/listings/browse.php?q={search_term_string}",
            "query-input": "required name=search_term_string"
          }
        },
        {
          "@type": "Organization",
          "name": "Temizci Burada",
          "url": "https://www.temizciburada.com",
          "logo": {
            "@type": "ImageObject",
            "url": "https://temizciburada.com/logo.png"
          },
          "contactPoint": {
            "@type": "ContactPoint",
            "email": "info@temizciburada.com",
            "contactType": "customer support",
            "availableLanguage": "Turkish"
          },
          "areaServed": {
            "@type": "Country",
            "name": "Türkiye"
          },
          "description": "Türkiye'nin ev temizliği ve günübirlik hizmet ilanları platformu. Ev sahiplerini güvenilir temizlikçilerle buluşturur.",
          "knowsAbout": ["Ev Temizliği", "Cam Temizliği", "Ütü Hizmeti", "Koltuk Yıkama", "Halı Yıkama", "Günübirlik Temizlik"]
        },
        {
          "@type": "Service",
          "serviceType": "Ev Temizliği Pazaryeri",
          "provider": { "@type": "Organization", "name": "Temizci Burada" },
          "areaServed": { "@type": "Country", "name": "Türkiye" },
          "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Temizlik Hizmetleri",
            "itemListElement": [
              { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Ev Temizliği" } },
              { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Cam ve Pencere Temizliği" } },
              { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Ütü ve Çamaşır" } },
              { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Koltuk Yıkama" } },
              { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Bahçe Düzenleme" } }
            ]
          }
        }
      ]
    }
    </script>

    <!-- ===== Schema.org — FAQPage (AI & Google için) ===== -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "Temizci Burada nedir?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Temizci Burada, ev sahiplerini güvenilir temizlik hizmeti verenlerle buluşturan Türkiye merkezli bir çevrimiçi pazar yeridir. Ev temizliği, cam temizliği, ütü, çamaşır ve daha fazlası için ilan açılabilir."
          }
        },
        {
          "@type": "Question",
          "name": "Temizci Burada'da kayıt ve ilan açmak ücretsiz mi?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Evet, Temizci Burada'da hem ev sahipleri hem de hizmet verenler için kayıt ve ilan oluşturma tamamen ücretsizdir."
          }
        },
        {
          "@type": "Question",
          "name": "Temizci Burada hangi şehirlerde hizmet veriyor?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Temizci Burada Türkiye genelinde tüm şehirlere hizmet vermektedir; İstanbul, Ankara, İzmir, Bursa, Antalya başta olmak üzere tüm illerden ilan açılıp teklif alınabilir."
          }
        },
        {
          "@type": "Question",
          "name": "Temizci Burada'da nasıl temizlikçi bulurum?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "temizciburada.com adresine ücretsiz kayıt olun, evinizin bilgilerini girin, hangi hizmeti istediğinizi seçin ve ilan oluşturun. Hizmet verenler tekliflerini gönderir, siz de en uygununu seçersiniz."
          }
        },
        {
          "@type": "Question",
          "name": "Temizci Burada güvenli mi?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Evet. Platform güvenli veritabanı sorguları, şifreli kullanıcı verileri ve CSRF koruması kullanmaktadır. Kullanıcı profilleri ve değerlendirmeler sistemi şeffaflık sağlar."
          }
        },
        {
          "@type": "Question",
          "name": "Ev temizliği için teklif almak ne kadar sürer?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Temizci Burada'da ilan verdikten sonra genellikle birkaç saat içinde birden fazla teklif gelir. Hizmet verenler ilana doğrudan teklif göndererek fiyatlarını belirtir."
          }
        }
      ]
    }
    </script>

    <!-- ===== Schema.org — HowTo (Nasıl Çalışır) ===== -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HowTo",
      "name": "Temizci Burada ile Temizlikçi Nasıl Bulunur?",
      "description": "Temizci Burada üzerinden ev temizliği, cam temizliği veya günübirlik hizmet için nasıl ilan oluşturulur ve temizlikçi bulunur?",
      "totalTime": "PT5M",
      "step": [
        {
          "@type": "HowToStep",
          "name": "Ücretsiz Kayıt Ol",
          "text": "temizciburada.com adresine gidin ve 'Ücretsiz Başla' butonuna tıklayarak Ev Sahibi rolüyle kayıt olun.",
          "url": "https://www.temizciburada.com/register.php"
        },
        {
          "@type": "HowToStep",
          "name": "Evini Ekle",
          "text": "Evinizin fotoğrafını yükleyin, oda sayısı (örn. 3+1), metrekare ve şehir bilgilerini girin.",
          "url": "https://www.temizciburada.com/homes/add.php"
        },
        {
          "@type": "HowToStep",
          "name": "İlan Oluştur",
          "text": "İstediğiniz hizmet türünü (temizlik, cam, ütü vb.), tercih ettiğiniz tarihi ve bütçeyi belirleyin, ilanı yayınlayın.",
          "url": "https://www.temizciburada.com/listings/create.php"
        },
        {
          "@type": "HowToStep",
          "name": "Tekliflerden Seçin",
          "text": "Hizmet verenlerden gelen teklifleri inceleyin, profilleri ve fiyatları karşılaştırın, en uygun teklifi kabul edin.",
          "url": "https://www.temizciburada.com/listings/browse.php"
        }
      ]
    }
    </script>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">

    <style>
        body {
            padding-top: 0;
        }

        .navbar {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            border-bottom-color: var(--border);
            box-shadow: var(--shadow-sm);
        }

        .navbar.scrolled .navbar-logo span {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar:not(.scrolled) .navbar-nav a {
            color: rgba(255, 255, 255, 0.85);
        }

        .navbar:not(.scrolled) .navbar-nav a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .navbar:not(.scrolled) .btn-outline {
            border-color: rgba(255, 255, 255, 0.5);
            color: #fff;
        }

        .navbar:not(.scrolled) .btn-outline:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .navbar:not(.scrolled) .navbar-logo-text span {
            background: linear-gradient(135deg, #a78bfa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 400px;
            position: relative;
        }

        .hero-mockup {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 24px;
            width: 320px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.4);
        }

        .mockup-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .mockup-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
        }

        .mockup-title {
            font-weight: 700;
            color: #fff;
            font-size: 0.9rem;
        }

        .mockup-sub {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .mockup-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mockup-item-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mockup-item-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(108, 99, 255, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .mockup-item-name {
            font-weight: 600;
            color: #fff;
            font-size: 0.82rem;
        }

        .mockup-item-loc {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .mockup-price {
            font-weight: 700;
            color: #34d399;
            font-size: 0.85rem;
        }

        .stats-row {
            display: flex;
            gap: 16px;
            margin-top: 16px;
        }

        .stat-mini {
            flex: 1;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .stat-mini-val {
            font-weight: 800;
            color: #fff;
            font-size: 1.1rem;
        }

        .stat-mini-lbl {
            font-size: 0.68rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .testimonial-section {
            padding: 80px 0;
            background: var(--bg);
        }

        .testimonial-card {
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 28px;
            transition: var(--transition);
        }

        .testimonial-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-3px);
        }

        .testimonial-text {
            font-size: 0.92rem;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 20px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tes-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
        }

        .tes-name {
            font-weight: 700;
            font-size: 0.88rem;
        }

        .tes-role {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #0f0c29, #302b63);
            text-align: center;
            color: #fff;
        }

        .cta-section h2 {
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 900;
            margin-bottom: 14px;
        }

        .cta-section p {
            opacity: 0.8;
            margin-bottom: 32px;
            font-size: 1rem;
        }

        .cta-btns {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="icon" href="/logo.png" type="image/png">
</head>

<body>

    <!-- ======== NAV ======== -->
    <nav class="navbar" id="mainNav">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon"
                    style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    <img src="logo.png" alt="Temizci Burada Logo" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
            <div class="navbar-nav">
                <a href="listings/browse">İlanlar</a>
                <a href="#nasil-calisir">Nasıl Çalışır?</a>
                <a href="#kategoriler">Kategoriler</a>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle" title="Tema Değiştir" style="margin-right:10px;">🌙</button>
                <a href="login" class="btn btn-outline btn-sm">Giriş Yap</a>
                <a href="register" class="btn btn-primary btn-sm">Ücretsiz Başla</a>
            </div>
        </div>
    </nav>

    <!-- ======== HERO ======== -->
    <section class="hero">
        <div class="container"
            style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;padding-top:40px;padding-bottom:60px;">
            <div class="hero-content" data-aos="fade-right">
                <div class="hero-badge">
                    ✨ Türkiye'nin Yeni Temizlik Platformu
                </div>
                <h1>
                    Evinizi Temiz Tutmanın<br>
                    <span class="gradient-text">En Kolay Yolu</span>
                </h1>
                <p class="hero-desc">
                    İlan verin, teklifler alın — güvenilir temizlik hizmetlerine dakikalar içinde ulaşın. Ev
                    bilgilerinizi kaydedin, tarih seçin, bütçenizi belirleyin.
                </p>
                <div class="hero-actions">
                    <a href="register" class="btn btn-primary btn-lg">
                        🚀 Hemen Başla
                    </a>
                    <a href="listings/browse" class="btn btn-white btn-lg">
                        📋 İlanları Gör
                    </a>
                </div>
                <div style="display:flex;gap:28px;margin-top:36px;flex-wrap:wrap;">
                    <div>
                        <div style="font-weight:800;font-size:1.4rem;color:#fff;">2.500+</div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);">Tamamlanan İş</div>
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:1.4rem;color:#fff;">1.200+</div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);">Hizmet Veren</div>
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:1.4rem;color:#fff;">4.8 ⭐</div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);">Ortalama Puan</div>
                    </div>
                </div>
            </div>
            <div class="hero-right" data-aos="fade-left" data-aos-delay="200">
                <div class="hero-visual">
                    <div class="hero-mockup">
                        <div class="mockup-header">
                            <div class="mockup-avatar">👩</div>
                            <div>
                                <div class="mockup-title">Ayşe T. — Ev Temizliği</div>
                                <div class="mockup-sub">3+1 • Kadıköy, İstanbul</div>
                            </div>
                        </div>
                        <div class="mockup-item">
                            <div class="mockup-item-left">
                                <div class="mockup-item-icon">🧹</div>
                                <div>
                                    <div class="mockup-item-name">Genel Temizlik</div>
                                    <div class="mockup-item-loc">📅 Yarın Sabah</div>
                                </div>
                            </div>
                            <div class="mockup-price">450 ₺</div>
                        </div>
                        <div class="mockup-item">
                            <div class="mockup-item-left">
                                <div class="mockup-item-icon">🪟</div>
                                <div>
                                    <div class="mockup-item-name">Cam Temizliği</div>
                                    <div class="mockup-item-loc">📅 Bu Hafta</div>
                                </div>
                            </div>
                            <div class="mockup-price">280 ₺</div>
                        </div>
                        <div class="mockup-item">
                            <div class="mockup-item-left">
                                <div class="mockup-item-icon">👕</div>
                                <div>
                                    <div class="mockup-item-name">Ütü & Çamaşır</div>
                                    <div class="mockup-item-loc">📅 Esnek</div>
                                </div>
                            </div>
                            <div class="mockup-price">180 ₺</div>
                        </div>
                        <div class="stats-row">
                            <div class="stat-mini">
                                <div class="stat-mini-val">12</div>
                                <div class="stat-mini-lbl">Teklif Geldi</div>
                            </div>
                            <div class="stat-mini">
                                <div class="stat-mini-val">3</div>
                                <div class="stat-mini-lbl">Aktif İlan</div>
                            </div>
                            <div class="stat-mini">
                                <div class="stat-mini-val">4.9★</div>
                                <div class="stat-mini-lbl">Puanım</div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating cards -->
                    <div class="hero-floating hero-floating-1">
                        <span style="font-size:1.3rem">✅</span>
                        <div>
                            <div style="font-size:0.75rem;opacity:0.8;">Yeni Teklif</div>
                            <div style="font-weight:700">350 ₺ — Fatma H.</div>
                        </div>
                    </div>
                    <div class="hero-floating hero-floating-3">
                        <span style="font-size:1.3rem">⭐</span>
                        <div>
                            <div style="font-size:0.75rem;opacity:0.8;">Değerlendirme</div>
                            <div style="font-weight:700">5/5 — Harika iş!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== CATEGORIES ======== -->
    <section id="kategoriler" style="padding:80px 0;background:#fff;">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Kategoriler</div>
                <h2>Hangi Hizmete İhtiyacınız Var?</h2>
                <p>İhtiyacınıza uygun kategoriyi seçin, ilgili ilanları keşfedin</p>
            </div>
            <div class="cat-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="listings/browse?cat=<?= e($cat['slug']) ?>" class="cat-item">
                        <div class="cat-icon">
                            <?= $cat['icon'] ?>
                        </div>
                        <div class="cat-name">
                            <?= e($cat['name']) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <a href="listings/browse?cat=ev-temizligi" class="cat-item">
                        <div class="cat-icon">🧹</div>
                        <div class="cat-name">Ev Temizliği</div>
                    </a>
                    <a href="listings/browse?cat=cam-pencere" class="cat-item">
                        <div class="cat-icon">🪟</div>
                        <div class="cat-name">Cam & Pencere</div>
                    </a>
                    <a href="listings/browse?cat=utu-camasir" class="cat-item">
                        <div class="cat-icon">👕</div>
                        <div class="cat-name">Ütü & Çamaşır</div>
                    </a>
                    <a href="listings/browse?cat=bulasik" class="cat-item">
                        <div class="cat-icon">🍽️</div>
                        <div class="cat-name">Bulaşık</div>
                    </a>
                    <a href="listings/browse?cat=bahce" class="cat-item">
                        <div class="cat-icon">🌿</div>
                        <div class="cat-name">Bahçe</div>
                    </a>
                    <a href="listings/browse?cat=koltuk-yikama" class="cat-item">
                        <div class="cat-icon">🛋️</div>
                        <div class="cat-name">Koltuk Yıkama</div>
                    </a>
                    <a href="listings/browse?cat=genel-temizlik" class="cat-item">
                        <div class="cat-icon">✨</div>
                        <div class="cat-name">Genel Temizlik</div>
                    </a>
                    <a href="listings/browse" class="cat-item">
                        <div class="cat-icon">📋</div>
                        <div class="cat-name">Diğer</div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ======== HOW IT WORKS ======== -->
    <section id="nasil-calisir" class="how-section" style="background:var(--bg);">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Nasıl Çalışır?</div>
                <h2>3 Adımda Temizlik Hizmeti</h2>
                <p>Kayıt olmaktan işi tamamlamaya kadar her şey çok kolay</p>
            </div>
            <div class="grid-3">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-num">1</div>
                    <div class="step-icon">🏠</div>
                    <h3>Evini Ekle</h3>
                    <p>Evinizin fotoğrafını yükleyin, oda sayısını, metrekaresini ve diğer bilgileri girin.</p>
                </div>
                <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-num">2</div>
                    <div class="step-icon">📋</div>
                    <h3>İlan Ver</h3>
                    <p>İstediğiniz hizmet türünü, tarihi ve bütçeyi belirleyerek dakikalar içinde ilan oluşturun.</p>
                </div>
                <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="step-num">3</div>
                    <div class="step-icon">🤝</div>
                    <h3>Teklif Al & Seç</h3>
                    <p>Hizmet verenlerden teklifler gelir, en uygununu seçer, işi tamamlarsınız.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== SON İLANLAR ======== -->
    <?php if (!empty($listings)): ?>
        <section style="padding:80px 0;background:#fff;">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">Son İlanlar</div>
                    <h2>Güncel Temizlik İlanları</h2>
                    <p>En son eklenen ilanları inceleyin, teklif verin</p>
                </div>
                <div class="grid-3">
                    <?php foreach ($listings as $listing): ?>
                        <div class="card listing-card">
                            <?php if (!empty($listing['home_photo'])): ?>
                                <div class="card-img-placeholder" style="background: url('<?= APP_URL ?>/uploads/homes/<?= $listing['home_photo'] ?>') center/cover no-repeat; position: relative; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                                    <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.8)); border-radius: var(--radius-lg) var(--radius-lg) 0 0;"></div>
                                    <span style="position: absolute; bottom: 15px; left: 15px; background: var(--gradient); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display:flex; gap: 8px; align-items:center;">
                                        <span><?= $listing['cat_icon'] ?></span> <?= e($listing['cat_name']) ?>
                                    </span>
                                </div>
                                <div class="card-content">
                            <?php else: ?>
                                <div class="card-img-placeholder">
                                    <?= $listing['cat_icon'] ?>
                                </div>
                                <div class="card-content">
                                    <div class="listing-cat">
                                        <?= $listing['cat_icon'] ?>
                                        <?= e($listing['cat_name']) ?>
                                    </div>
                            <?php endif; ?>
                                <div class="listing-title">
                                    <?= e($listing['title']) ?>
                                </div>
                                <div class="listing-meta">
                                    <span>📍
                                        <?= e($listing['city']) ?>
                                    </span>
                                    <span>🏠
                                        <?= e($listing['room_config']) ?>
                                    </span>
                                    <span>📅
                                        <?= date('d M', strtotime($listing['preferred_date'])) ?>
                                    </span>
                                </div>
                                <div class="listing-footer">
                                    <?php if ($listing['budget']): ?>
                                        <span class="listing-budget">
                                            <?= formatMoney($listing['budget']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);font-size:0.85rem;">Bütçe belirsiz</span>
                                    <?php endif; ?>
                                    <a href="listings/detail?id=<?= $listing['id'] ?>" class="btn btn-primary btn-sm">Teklif
                                        Ver</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-5">
                    <a href="listings/browse" class="btn btn-outline btn-lg">Tüm İlanları Gör →</a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ======== TESTİMONYALS ======== -->
    <section class="testimonial-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Yorumlar</div>
                <h2>Kullanıcılarımız Ne Diyor?</h2>
            </div>
            <div class="grid-3">
                <div class="testimonial-card">
                    <div class="testimonial-text">"Artık temizlikçi bulmak çok kolay! İlan verdim, 2 saat içinde 8
                        teklif geldi. Fiyatlar da çok uygundu."</div>
                    <div class="testimonial-author">
                        <div class="tes-avatar">A</div>
                        <div>
                            <div class="tes-name">Ayşe Kaplan</div>
                            <div class="tes-role">Ev Sahibi · İstanbul</div>
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <?= starRating(5) ?>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">"Güvenilir bir platform. Hem teklif verenler değerlendiriliyor hem de
                        müşteriler. İşimi büyüttüm bu sayede."</div>
                    <div class="testimonial-author">
                        <div class="tes-avatar">F</div>
                        <div>
                            <div class="tes-name">Fatma Yıldız</div>
                            <div class="tes-role">Temizlik Uzmanı · Ankara</div>
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <?= starRating(5) ?>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">"Ev bilgilerimi bir kez girdim, her seferinde tekrar tekrar
                        yazmıyorum. Çok pratik bir sistem!"</div>
                    <div class="testimonial-author">
                        <div class="tes-avatar">M</div>
                        <div>
                            <div class="tes-name">Merve Demir</div>
                            <div class="tes-role">Ev Sahibi · İzmir</div>
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <?= starRating(4) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== CTA ======== -->
    <section class="cta-section">
        <div class="container">
            <h2>Hemen Başlamaya Hazır mısınız?</h2>
            <p>Ücretsiz kayıt olun, evinizi ekleyin, ilk ilanınızı verin!</p>
            <div class="cta-btns">
                <a href="register" class="btn btn-primary btn-lg">🏠 Ev Sahibiyim</a>
                <a href="register?role=worker" class="btn"
                    style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);padding:14px 30px;border-radius:var(--radius);font-size:1rem;font-weight:600;">🧹
                    Hizmet Vermek İstiyorum</a>
            </div>
        </div>
    </section>

    <!-- ======== FOOTER ======== -->
    <footer class="footer">
        <div class="container">
            <div class="grid-4" style="gap:40px;">
                <div style="grid-column:span 2;">
                    <div class="footer-logo">🧹 Temizci Burada</div>
                    <p class="footer-desc">Ev hanımları ve günübirlik hizmet arayanlar için güvenilir temizlik ilanları
                        platformu.</p>
                </div>
                <div>
                    <div class="footer-title">Platform</div>
                    <div class="footer-links">
                        <a href="listings/browse">İlanlar</a>
                        <a href="register">Kayıt Ol</a>
                        <a href="login">Giriş Yap</a>
                    </div>
                </div>
                <div>
                    <div class="footer-title">İletişim</div>
                    <div class="footer-links">
                        <a href="mailto:info@temizciburada.com">info@temizciburada.com</a>
                        <a href="kvkk">KVKK</a>
                        <a href="cerez-politikasi">Çerez Politikası</a>
                        <a href="#">Destek</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>©
                    <?= date('Y') ?> Temizci Burada · Tüm hakları saklıdır. ·
                    <a href="kvkk" style="color:var(--text-muted);">KVKK</a> ·
                    <a href="cerez-politikasi" style="color:var(--text-muted);">Çerez Politikası</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- ===== Cookie Banner ===== -->
    <div id="cookieBanner" style="
        display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;
        background:rgba(15,12,41,0.97);color:#fff;padding:16px 24px;
        display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
        box-shadow:0 -4px 24px rgba(0,0,0,0.3);backdrop-filter:blur(10px);
    ">
        <p style="margin:0;font-size:0.88rem;opacity:0.9;max-width:700px;line-height:1.6;">
            🍪 Bu site yalnızca oturum yönetimi için zorunlu çerezler kullanır.
            <a href="cerez-politikasi" style="color:#a78bfa;text-decoration:underline;">Çerez Politikası</a> ve
            <a href="kvkk" style="color:#a78bfa;text-decoration:underline;">KVKK Aydınlatma Metni</a>
        </p>
        <button id="cookieAccept" onclick="acceptCookies()" style="
            background:#6C63FF;color:#fff;border:none;padding:10px 24px;
            border-radius:8px;font-weight:600;cursor:pointer;font-size:0.88rem;
            white-space:nowrap;flex-shrink:0;
        ">Anladım, Kabul Et</button>
    </div>
    <script>
        function acceptCookies() {
            localStorage.setItem('cookie_consent', '1');
            document.getElementById('cookieBanner').style.display = 'none';
        }
        if (!localStorage.getItem('cookie_consent')) {
            document.getElementById('cookieBanner').style.display = 'flex';
        }
    </script>


    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 50, duration: 600 });

        // Navbar scroll effect
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        });

        // PWA Service Worker Kaydı
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('SW Registered: ', registration.scope);
                    })
                    .catch((error) => {
                        console.log('SW Registration Failed: ', error);
                    });
            });
        }
    </script>
</body>

</html>