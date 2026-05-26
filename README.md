# Temizci Projesi

Bu depo Temizci projesinin dosyalarını içerir.

## Depo İçeriği

- `Temizlik_Burda_live/`: Canlı durumda kullanılan PHP uygulaması.
- `Temizlik_Burda_v2_web/`: Yeni web sürümünün kaynak kodları.
- `temizci_burada/`: Flutter/Dart tabanlı mobil uygulama projesi.
- `deploy_artifacts/`: Sunucu servis dosyaları ve yapılandırmaları.

## Nasıl Kullanılır

1. Depoyu klonla:
   ```bash
   git clone https://github.com/mdrmz/temizci.git
   ```

2. `Temizlik_Burda_live` dizini, PHP tabanlı web uygulamasını içerir.
   - Bu klasörü bir PHP sunucusuna veya yerel geliştirme ortamına taşı.
   - `includes/config.php` içindeki ayarları kendi veritabanına göre güncelle.

3. `Temizlik_Burda_v2_web` dizini, muhtemelen Next.js tabanlı web uygulamasıdır.
   ```bash
   cd Temizlik_Burda_v2_web
   npm install
   npm run dev
   ```

4. `temizci_burada` dizini, Flutter mobil uygulama projesidir.
   - Flutter kuruluysa bu dizinde projenin derlemesini ve çalıştırmasını yapabilirsin.

## Önemli Notlar

- `README.md` dosyası kök dizine eklendi.
- Proje daha önce `https://github.com/mdrmz/temizci.git` adresine gönderildi.
