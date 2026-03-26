<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard');
}

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
    <title>Temizci Burada - Eviniz Icin Guvenilir Temizlik Hizmeti</title>
    <meta name="description" content="Temizci Burada ile eviniz icin guvenilir ev temizligi, cam temizligi, utu ve daha fazlasinda hizmet ilani olusturun.">
    <meta name="keywords" content="temizlikci bul, ev temizligi, temizlik ilani, gunubirlik temizlikci, temizlik hizmeti, Turkiye temizlik platformu">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.temizciburada.com/">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#3f7d58">
    <link rel="apple-touch-icon" href="logo.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.temizciburada.com/">
    <meta property="og:title" content="Temizci Burada - Guvenilir Temizlik Hizmeti Platformu">
    <meta property="og:description" content="Eviniz icin guvenilir temizlik hizmeti bulmanin en duzenli yolu. Ilan verin, teklifler alin ve size en uygun kisiyi secin.">
    <meta property="og:image" content="https://temizciburada.com/logo.png">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Temizci Burada">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Temizci Burada - Temizlik Hizmetleri Platformu">
    <meta name="twitter:description" content="Turkiye'nin yeni temizlik hizmetleri pazaryeri. Ilan ver, teklif al, temiz bir eve kavus.">
    <meta name="twitter:image" content="https://temizciburada.com/logo.png">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "name": "Temizci Burada",
          "alternateName": "temizciburada.com",
          "url": "https://www.temizciburada.com",
          "description": "Ev temizligi, cam temizligi ve gunubirlik hizmetler icin Turkiye'nin guvenilir platformu.",
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
            "name": "Turkiye"
          }
        },
        {
          "@type": "Service",
          "serviceType": "Ev Temizligi Pazaryeri",
          "provider": { "@type": "Organization", "name": "Temizci Burada" },
          "areaServed": { "@type": "Country", "name": "Turkiye" }
        }
      ]
    }
    </script>
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
            "text": "Temizci Burada, ev sahiplerini guvenilir temizlik hizmeti verenlerle bulusturan Turkiye merkezli bir cevrimici pazar yeridir."
          }
        },
        {
          "@type": "Question",
          "name": "Kayit ve ilan acmak ucretsiz mi?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Evet. Temizci Burada'da hem ev sahipleri hem de hizmet verenler icin kayit ve ilan olusturma ucretsizdir."
          }
        },
        {
          "@type": "Question",
          "name": "Teklif almak ne kadar surer?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Ilan verdikten sonra genellikle birkac saat icinde birden fazla teklif gelmeye baslar."
          }
        }
      ]
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HowTo",
      "name": "Temizci Burada ile temizlikci nasil bulunur?",
      "description": "Temizci Burada uzerinden temizlik hizmeti icin nasil ilan olusturulur ve uzman secilir?",
      "totalTime": "PT5M",
      "step": [
        {
          "@type": "HowToStep",
          "name": "Ucretsiz kayit ol",
          "text": "Kayit olun ve ev sahibi veya hizmet veren olarak profilinizi olusturun.",
          "url": "https://www.temizciburada.com/register.php"
        },
        {
          "@type": "HowToStep",
          "name": "Ev bilgilerini ekle",
          "text": "Fotograf, oda sayisi, metrekare ve sehir bilgilerini girin.",
          "url": "https://www.temizciburada.com/homes/add.php"
        },
        {
          "@type": "HowToStep",
          "name": "Ilan olustur",
          "text": "Hizmet turunu, tercih edilen tarihi ve butceyi belirleyin.",
          "url": "https://www.temizciburada.com/listings/create.php"
        },
        {
          "@type": "HowToStep",
          "name": "Teklifleri sec",
          "text": "Gelen teklifleri profil, fiyat ve uygunluk bilgileriyle karsilastirin.",
          "url": "https://www.temizciburada.com/listings/browse.php"
        }
      ]
    }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <style>
        body { padding-top: 0; }
        .navbar { background: transparent; border-bottom: 0; }
        .navbar.scrolled { background: transparent; border-bottom: 0; box-shadow: none; }
    </style>
</head>
<body class="minimal-home">
    <nav class="navbar" id="mainNav">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon" style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    <img src="logo.png" alt="Temizci Burada Logo" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
            <div class="navbar-nav">
                <a href="listings/browse"><span class="nav-mini-icon">◧</span>Ilanlar</a>
                <a href="#nasil-calisir"><span class="nav-mini-icon">◴</span>Nasil Calisir?</a>
                <a href="#kategoriler"><span class="nav-mini-icon">◫</span>Kategoriler</a>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle" title="Tema Degistir" style="margin-right:10px;">TM</button>
                <a href="login" class="btn btn-outline btn-sm"><span class="nav-mini-icon">↪</span>Giris Yap</a>
                <a href="register" class="btn btn-primary btn-sm"><span class="btn-mini-icon">✦</span>Ucretsiz Basla</a>
            </div>
        </div>
    </nav>
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="hero-badge">Sade ve guvenilir platform</div>
                <h1>Eviniz icin temizlik uzmanini <span class="gradient-text">kolayca bulun</span></h1>
                <p class="hero-desc">Ilaninizi olusturun, gelen teklifleri tek ekranda karsilastirin ve size uygun kisiyle hizlica anlasin.</p>
                <div class="hero-actions">
                    <a href="register" class="btn btn-primary btn-lg">Hemen Basla</a>
                    <a href="listings/browse" class="btn btn-white btn-lg">Ilanlari Incele</a>
                </div>
                <div class="trust-points">
                    <span class="trust-chip">Onayli profiller</span>
                    <span class="trust-chip">Sade karsilastirma</span>
                    <span class="trust-chip">Hizli teklif</span>
                </div>
                <div class="hero-metrics mt-5">
                    <div class="hero-metric">
                        <div class="hero-metric-value">2.500+</div>
                        <div class="hero-metric-label">tamamlanan is</div>
                    </div>
                    <div class="hero-metric">
                        <div class="hero-metric-value">1.200+</div>
                        <div class="hero-metric-label">aktif hizmet veren</div>
                    </div>
                    <div class="hero-metric">
                        <div class="hero-metric-value">4.8/5</div>
                        <div class="hero-metric-label">ortalama memnuniyet</div>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-panel">
                    <div class="hero-panel-top">
                        <div>
                            <div class="eyebrow">Bugunun ilani</div>
                            <div class="hero-panel-title">Ayse T. icin haftalik ev temizligi</div>
                            <div class="hero-panel-subtitle">3+1 daire, Kadikoy, sali sabahi</div>
                        </div>
                        <div class="hero-panel-score">
                            <strong>4.9</strong>
                            <span>puan ort.</span>
                        </div>
                    </div>
                    <div class="job-list">
                        <div class="job-item">
                            <div class="job-item-main">
                                <div class="job-item-icon">GT</div>
                                <div>
                                    <div class="job-item-title">Genel temizlik</div>
                                    <div class="job-item-meta">Yarin, 09:00 - 13:00</div>
                                </div>
                            </div>
                            <div class="job-item-price">450 TL</div>
                        </div>
                        <div class="job-item">
                            <div class="job-item-main">
                                <div class="job-item-icon">CT</div>
                                <div>
                                    <div class="job-item-title">Cam ve yuzeyler</div>
                                    <div class="job-item-meta">Ayni ziyaret icinde dahil</div>
                                </div>
                            </div>
                            <div class="job-item-price">280 TL</div>
                        </div>
                        <div class="job-item">
                            <div class="job-item-main">
                                <div class="job-item-icon">UT</div>
                                <div>
                                    <div class="job-item-title">Utu ve camasir duzeni</div>
                                    <div class="job-item-meta">Esnek, ekstra hizmet secenegi</div>
                                </div>
                            </div>
                            <div class="job-item-price">180 TL</div>
                        </div>
                    </div>
                    <div class="hero-panel-footer">
                        <div class="mini-stat"><strong>12</strong><span>gelen teklif</span></div>
                        <div class="mini-stat"><strong>3</strong><span>aktif ilan</span></div>
                        <div class="mini-stat"><strong>98%</strong><span>zamaninda cevap</span></div>
                    </div>
                    <div class="hero-floating hero-floating-1">
                        <span style="font-size:1.3rem">+</span>
                        <div>
                            <div style="font-size:0.75rem;opacity:0.8;">Yeni teklif</div>
                            <div style="font-weight:700">350 TL - Fatma H.</div>
                        </div>
                    </div>
                    <div class="hero-floating hero-floating-3">
                        <span style="font-size:1.3rem">*</span>
                        <div>
                            <div style="font-size:0.75rem;opacity:0.8;">Degerlendirme</div>
                            <div style="font-weight:700">5/5 - Harika is</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="editorial-section">
        <div class="container editorial-grid">
            <div class="editorial-card" data-aos="fade-up">
                <div class="section-badge">Neden farkli?</div>
                <h3>Platform hissi yerine, ozenli bir hizmet deneyimi</h3>
                <p>Temizci Burada; ilan vermeyi, karsilastirmayi ve dogru kisiyi secmeyi daha sakin ve daha profesyonel bir akisa donusturur. Karmasa yok, gereksiz bagirti yok.</p>
                <div class="editorial-points">
                    <div class="editorial-point">
                        <div class="editorial-point-icon">01</div>
                        <div><strong>Net beklenti kurulur</strong><span>Ev tipi, tarih, butce ve hizmet kapsami daha bastan dogru tarif edilir.</span></div>
                    </div>
                    <div class="editorial-point">
                        <div class="editorial-point-icon">02</div>
                        <div><strong>Teklifler anlamli gorunur</strong><span>Fiyat, profil ve uygunluk ayni ritimde karsiniza gelir.</span></div>
                    </div>
                    <div class="editorial-point">
                        <div class="editorial-point-icon">03</div>
                        <div><strong>Guven duygusu kaybolmaz</strong><span>Degerlendirmeler ve profil detaylari secim aninda destek olur.</span></div>
                    </div>
                </div>
            </div>
            <div class="editorial-side" data-aos="fade-up" data-aos-delay="150">
                <div class="editorial-note"><strong>Ev sahipleri icin</strong><p>Bir kez ev bilgisini tanimlayin, sonrasinda her ilanda sifirdan baslamak yerine daha hizli ilerleyin.</p></div>
                <div class="editorial-note"><strong>Hizmet verenler icin</strong><p>Karar vermeyi kolaylastiran daha temiz kartlar ve daha okunur ilan detaylariyla dogru islere daha rahat teklif verin.</p></div>
                <div class="editorial-note"><strong>Marka hissi icin</strong><p>Sicak tonlar, daha iyi bosluk kullanimi ve editoryal tipografi ile guven veren bir ilk izlenim olusur.</p></div>
            </div>
        </div>
    </section>
    <section id="kategoriler" class="listing-showcase">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Kategoriler</div>
                <h2>Hangi hizmete ihtiyaciniz var?</h2>
                <p>Ihtiyaciniza uygun kategoriyi secin ve size en yakin hizmet akisini kesfedin.</p>
            </div>
            <div class="cat-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="listings/browse?cat=<?= e($cat['slug']) ?>" class="cat-item">
                        <div class="cat-icon"><?= $cat['icon'] ?></div>
                        <div class="cat-name"><?= e($cat['name']) ?></div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <a href="listings/browse?cat=ev-temizligi" class="cat-item"><div class="cat-icon">ET</div><div class="cat-name">Ev Temizligi</div></a>
                    <a href="listings/browse?cat=cam-pencere" class="cat-item"><div class="cat-icon">CP</div><div class="cat-name">Cam ve Pencere</div></a>
                    <a href="listings/browse?cat=utu-camasir" class="cat-item"><div class="cat-icon">UC</div><div class="cat-name">Utu ve Camasir</div></a>
                    <a href="listings/browse?cat=bulasik" class="cat-item"><div class="cat-icon">BL</div><div class="cat-name">Bulasik</div></a>
                    <a href="listings/browse?cat=bahce" class="cat-item"><div class="cat-icon">BH</div><div class="cat-name">Bahce</div></a>
                    <a href="listings/browse?cat=koltuk-yikama" class="cat-item"><div class="cat-icon">KY</div><div class="cat-name">Koltuk Yikama</div></a>
                    <a href="listings/browse?cat=genel-temizlik" class="cat-item"><div class="cat-icon">GT</div><div class="cat-name">Genel Temizlik</div></a>
                    <a href="listings/browse" class="cat-item"><div class="cat-icon">DG</div><div class="cat-name">Diger</div></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section id="nasil-calisir" class="how-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Nasil calisir?</div>
                <h2>Uc adimda duzenli bir deneyim</h2>
                <p>Kayit olmaktan dogru teklifi secmeye kadar akisin her asamasi sade ve anlasilir.</p>
            </div>
            <div class="grid-3">
                <div class="step-card">
                    <div class="step-num">1</div>
                    <div class="step-icon">EV</div>
                    <h3>Evinizi tanimlayin</h3>
                    <p>Evinizin tipi, oda sayisi, fotografi ve temel ihtiyaclarini net sekilde girin.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">2</div>
                    <div class="step-icon">IL</div>
                    <h3>Ilaninizi acin</h3>
                    <p>Tarih, butce ve hizmet kapsamini belirleyin; uzmanlar ne istediginizi hemen anlasin.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">3</div>
                    <div class="step-icon">TK</div>
                    <h3>Teklifleri secin</h3>
                    <p>Gelen teklifleri profil, fiyat ve uygunluk bilgileriyle karsilastirin ve seciminizi yapin.</p>
                </div>
            </div>
            <div class="process-band">
                <div class="process-band-item"><strong>Sakin arayuz</strong><span>Fazla gorsel gurultu olmadan karar verin.</span></div>
                <div class="process-band-item"><strong>Daha iyi hiyerarsi</strong><span>Onemli bilgi once, detaylar sonra gelsin.</span></div>
                <div class="process-band-item"><strong>Guven hissi</strong><span>Profil ve degerlendirmeler secimi desteklesin.</span></div>
            </div>
        </div>
    </section>
    <?php if (!empty($listings)): ?>
        <section class="listing-showcase">
            <div class="container">
                <div class="section-topline">
                    <div class="section-header">
                        <div class="section-badge">Son ilanlar</div>
                        <h2>Guncel temizlik ilanlari</h2>
                        <p>En son eklenen ilanlari inceleyin ve uygun gorduklerinize hemen teklif verin.</p>
                    </div>
                    <a href="listings/browse" class="btn btn-outline">Tum ilanlari gor</a>
                </div>
                <div class="grid-3">
                    <?php foreach ($listings as $listing): ?>
                        <div class="card listing-card">
                            <?php if (!empty($listing['home_photo'])): ?>
                                <div class="card-img-placeholder" style="background:url('<?= APP_URL ?>/uploads/homes/<?= $listing['home_photo'] ?>') center/cover no-repeat; position:relative; border-radius:var(--radius-lg) var(--radius-lg) 0 0;">
                                    <div style="position:absolute; inset:0; background:linear-gradient(to bottom, transparent, rgba(17,39,51,0.82)); border-radius:var(--radius-lg) var(--radius-lg) 0 0;"></div>
                                    <span style="position:absolute; bottom:15px; left:15px; background:rgba(255,255,255,0.9); color:var(--primary); padding:6px 14px; border-radius:20px; font-size:0.85rem; font-weight:700; display:flex; gap:8px; align-items:center;">
                                        <span><?= $listing['cat_icon'] ?></span> <?= e($listing['cat_name']) ?>
                                    </span>
                                </div>
                                <div class="card-content">
                            <?php else: ?>
                                <div class="card-img-placeholder"><?= $listing['cat_icon'] ?></div>
                                <div class="card-content">
                                    <div class="listing-cat"><?= $listing['cat_icon'] ?> <?= e($listing['cat_name']) ?></div>
                            <?php endif; ?>
                                <div class="listing-title"><?= e($listing['title']) ?></div>
                                <div class="listing-meta">
                                    <span>SEHIR <?= e($listing['city']) ?></span>
                                    <span>EV <?= e($listing['room_config']) ?></span>
                                    <span>TARIH <?= date('d M', strtotime($listing['preferred_date'])) ?></span>
                                </div>
                                <div class="listing-footer">
                                    <?php if ($listing['budget']): ?>
                                        <span class="listing-budget"><?= formatMoney($listing['budget']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);font-size:0.85rem;">Butce belirsiz</span>
                                    <?php endif; ?>
                                    <a href="listings/detail?id=<?= $listing['id'] ?>" class="btn btn-primary btn-sm">Teklif Ver</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <section class="testimonial-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Yorumlar</div>
                <h2>Kullanicilarimiz ne diyor?</h2>
            </div>
            <div class="grid-3">
                <div class="testimonial-card">
                    <div class="testimonial-kicker">Ev sahibi</div>
                    <div class="testimonial-text">"Ilan verdikten sonra teklifler daginik gelmedi; hepsini rahatca karsilastirdim. Karar verme kismi ilk kez bu kadar duzenli hissettirdi."</div>
                    <div class="testimonial-author">
                        <div class="tes-avatar">A</div>
                        <div><div class="tes-name">Ayse Kaplan</div><div class="tes-role">Ev Sahibi - Istanbul</div></div>
                    </div>
                    <div style="margin-top:10px;"><?= starRating(5) ?></div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-kicker">Hizmet veren</div>
                    <div class="testimonial-text">"Ilan detaylari daha net oldugu icin bos teklif vermiyorum. Hangi eve, hangi beklentiyle gidecegimi bastan anlamak isi kolaylastiriyor."</div>
                    <div class="testimonial-author">
                        <div class="tes-avatar">F</div>
                        <div><div class="tes-name">Fatma Yildiz</div><div class="tes-role">Temizlik Uzm. - Ankara</div></div>
                    </div>
                    <div style="margin-top:10px;"><?= starRating(5) ?></div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-kicker">Duzenli kullanim</div>
                    <div class="testimonial-text">"Ev bilgilerimi bir kez ekleyip daha sonra sadece tarih ve kapsam secmek ciddi zaman kazandiriyor. Platform daha olgun hissettiriyor."</div>
                    <div class="testimonial-author">
                        <div class="tes-avatar">M</div>
                        <div><div class="tes-name">Merve Demir</div><div class="tes-role">Ev Sahibi - Izmir</div></div>
                    </div>
                    <div style="margin-top:10px;"><?= starRating(4) ?></div>
                </div>
            </div>
        </div>
    </section>
    <section class="cta-section">
        <div class="container">
            <div class="cta-shell">
                <div class="cta-copy">
                    <div class="section-badge" style="background:rgba(255,255,255,0.12);color:#fff;">Hazirsiniz</div>
                    <h2>Eviniz icin daha iyi bir baslangic yapin</h2>
                    <p>Ucretsiz kayit olun, ilk ilaninizi acin ve size uygun temizlik uzmanlariyla daha ozenli bir deneyim yasayin.</p>
                </div>
                <div class="cta-actions">
                    <a href="register" class="btn btn-primary btn-lg">Ev Sahibiyim</a>
                    <a href="register?role=worker" class="btn btn-outline btn-lg">Hizmet Vermek Istiyorum</a>
                </div>
            </div>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="grid-4" style="gap:40px;">
                <div style="grid-column:span 2;">
                    <div class="footer-logo">Temizci Burada</div>
                    <p class="footer-desc">Evinin duzenine onem verenlerle, isini ozenle yapan hizmet verenleri ayni cizgide bulusturan yeni nesil temizlik platformu.</p>
                </div>
                <div>
                    <div class="footer-title">Platform</div>
                    <div class="footer-links">
                        <a href="listings/browse">Ilanlar</a>
                        <a href="register">Kayit Ol</a>
                        <a href="login">Giris Yap</a>
                    </div>
                </div>
                <div>
                    <div class="footer-title">Iletisim</div>
                    <div class="footer-links">
                        <a href="mailto:info@temizciburada.com">info@temizciburada.com</a>
                        <a href="kvkk">KVKK</a>
                        <a href="cerez-politikasi">Cerez Politikasi</a>
                        <a href="destek">Destek</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Temizci Burada · Tum haklari saklidir. · <a href="kvkk" style="color:rgba(255,255,255,0.65);">KVKK</a> · <a href="cerez-politikasi" style="color:rgba(255,255,255,0.65);">Cerez Politikasi</a></p>
            </div>
        </div>
    </footer>
    <div id="cookieBanner" class="cookie-banner">
        <p>Bu site yalnizca oturum yonetimi icin zorunlu cerezler kullanir. <a href="cerez-politikasi">Cerez Politikasi</a> ve <a href="kvkk">KVKK Aydinlatma Metni</a></p>
        <button id="cookieAccept" onclick="acceptCookies()" class="btn btn-primary btn-sm">Anladim, Kabul Et</button>
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
    <script src="assets/js/app.js?v=5.0"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        });
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

