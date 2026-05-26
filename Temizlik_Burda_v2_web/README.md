# TemizciBurada Web (Next.js)

Bu proje TemizciBurada'nin web arayuzudur.

## Lokal gelistirme

```bash
npm install
npm run dev
```

## Uretim ortami env dosyasi

1. `.env.production.example` dosyasini kopyala:
```bash
cp .env.production.example .env.production
```
2. AdSense `client` ve `slot` degerlerini doldur.
3. Build ve servis restart yap.

## Reklam ayarlari

- Tum desktop sayfalarda yan reklam raylari:
  - `NEXT_PUBLIC_ADSENSE_SLOT_SIDE_LEFT`
  - `NEXT_PUBLIC_ADSENSE_SLOT_SIDE_RIGHT`
- Ana ve sayfa ici sponsor alanlari:
  - `NEXT_PUBLIC_ADSENSE_SLOT_HOME_TOP`
  - `NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID`
  - `NEXT_PUBLIC_ADSENSE_SLOT_DASHBOARD`
  - `NEXT_PUBLIC_ADSENSE_SLOT_MESSAGES`
  - `NEXT_PUBLIC_ADSENSE_SLOT_LISTINGS_INLINE`
  - `NEXT_PUBLIC_ADSENSE_SLOT_LISTING_DETAIL`

Slot bos birakilirsa reklam kutusunda otomatik fallback metni gorunur:
"Buraya reklam verebilirsiniz".

Fallback karttaki buton/link:
- `NEXT_PUBLIC_AD_CONTACT_URL`
- `NEXT_PUBLIC_AD_CONTACT_LABEL`

Reklam basvuru sayfasi (`/reklam-ver`) iletisim ayarlari:
- `NEXT_PUBLIC_AD_SALES_WHATSAPP`
- `NEXT_PUBLIC_AD_SALES_EMAIL`
- `NEXT_PUBLIC_AD_SALES_PHONE`

## Sunucuda deploy (systemd)

Servis adi:
- `temizci-web-v2.service`

Komutlar:
```bash
npm run build
sudo systemctl restart temizci-web-v2.service
sudo systemctl status --no-pager temizci-web-v2.service
```
