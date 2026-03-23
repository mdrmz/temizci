<?php
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <!-- Ana Sayfa -->
    <url>
        <loc>https://www.temizciburada.com/</loc>
        <lastmod>
            <?php echo date('Y-m-d'); ?>
        </lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- İlanlar -->
    <url>
        <loc>https://www.temizciburada.com/listings/browse.php</loc>
        <lastmod>
            <?php echo date('Y-m-d'); ?>
        </lastmod>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Kayıt / Giriş -->
    <url>
        <loc>https://www.temizciburada.com/register.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>https://www.temizciburada.com/login.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <?php
    // Dinamik: mevcut ilanlar
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/db.php';
    try {
        $db = getDB();
        $stmt = $db->query("SELECT id, updated_at FROM listings WHERE status = 'open' ORDER BY created_at DESC LIMIT 200");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $lastmod = date('Y-m-d', strtotime($row['updated_at']));
            echo "    <url>\n";
            echo "        <loc>https://www.temizciburada.com/listings/detail.php?id=" . (int) $row['id'] . "</loc>\n";
            echo "        <lastmod>{$lastmod}</lastmod>\n";
            echo "        <changefreq>weekly</changefreq>\n";
            echo "        <priority>0.6</priority>\n";
            echo "    </url>\n";
        }
    } catch (Exception $e) { /* sessizce geç */
    }
    ?>
</urlset>
