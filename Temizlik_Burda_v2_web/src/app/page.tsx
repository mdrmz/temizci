import Link from "next/link";

import { AdSlot } from "@/components/ad-slot";
import { getCategories, getListings } from "@/lib/api";
import {
  formatDate,
  formatListingStatus,
  formatMoney,
  listingStatusClasses,
} from "@/lib/format";
import { toGeoSlug } from "@/lib/geo";

export default async function HomePage() {
  const homeTopAdSlot = process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_TOP ?? "";
  const homeMidAdSlot = process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ?? "";

  const [listingsRes, categoriesRes] = await Promise.all([
    getListings({ page: 1, status: "all", sort: "newest" }),
    getCategories(),
  ]);

  const listings = listingsRes.data.slice(0, 10);
  const categories = categoriesRes.data.slice(0, 12);
  const listingCount = listingsRes.pagination.total;

  const cityCounter = new Map<string, number>();
  listingsRes.data.forEach((listing) => {
    const city = (listing.city ?? "").trim();
    if (!city) return;
    cityCounter.set(city, (cityCounter.get(city) ?? 0) + 1);
  });

  const topCities = [...cityCounter.entries()]
    .sort((left, right) => right[1] - left[1])
    .slice(0, 8);

  const categoryCounter = new Map<string, number>();
  listingsRes.data.forEach((listing) => {
    const slug = (listing.cat_slug ?? "").trim();
    if (!slug) return;
    categoryCounter.set(slug, (categoryCounter.get(slug) ?? 0) + 1);
  });

  const avgBudget =
    listings.length > 0
      ? Math.round(
          listings.reduce((sum, listing) => sum + (listing.budget ?? 0), 0) /
            listings.length,
        )
      : 0;

  const totalOffers = listings.reduce(
    (sum, listing) => sum + (listing.offer_count ?? 0),
    0,
  );

  const totalViews = listings.reduce(
    (sum, listing) => sum + (listing.view_count ?? 0),
    0,
  );

  const featuredListings = listings
    .filter((listing) => (listing.status ?? "").toLowerCase() !== "closed")
    .slice(0, 8);
  const spotlightListings = listings.slice(0, 4);
  const openCount = listingsRes.data.filter(
    (listing) => (listing.status ?? "").toLowerCase() === "open",
  ).length;
  const inProgressCount = listingsRes.data.filter(
    (listing) => (listing.status ?? "").toLowerCase() === "in_progress",
  ).length;
  const closedCount = listingsRes.data.filter(
    (listing) => (listing.status ?? "").toLowerCase() === "closed",
  ).length;
  const cancelledCount = listingsRes.data.filter(
    (listing) => (listing.status ?? "").toLowerCase() === "cancelled",
  ).length;
  const recentListings = listingsRes.data.slice(0, 6);
  const areaServed = topCities.map(([city]) => city).filter((city) => city !== "");
  const schemaMarkup = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: "TemizciBurada",
    url: "https://temizciburada.com",
    image: "https://temizciburada.com/hero-cleaner-v2.jpg",
    description:
      "Temizlikçi arayanlarla hizmet verenleri şehir bazlı ilan sistemiyle buluşturan platform.",
    areaServed,
    knowsAbout: categories.slice(0, 10).map((category) => category.name),
  };

  return (
    <div className="tb-page">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaMarkup) }}
      />
      <section className="tb-surface tb-surface-pad">
        <h1 className="tb-heading">
          TemizciBurada İlan Pazarı
        </h1>
        <p className="tb-subheading max-w-2xl">
          İlan aç, filtrele, teklifleri karşılaştır ve işi tek panelden yönet.
        </p>
        <div className="mt-4 grid gap-4 lg:grid-cols-[1.25fr_0.95fr]">
          <div className="space-y-4">
            <form
              action="/listings"
              method="get"
              className="grid gap-2 md:grid-cols-2 xl:grid-cols-[1.2fr_1fr_1fr_1fr_auto]"
            >
              <input
                name="q"
                placeholder="Hizmet, başlık veya kelime ara"
                className="tb-input"
              />
              <select
                name="cat"
                defaultValue=""
                className="tb-select"
              >
                <option value="">Tüm kategoriler</option>
                {categoriesRes.data.map((category) => (
                  <option key={category.id} value={category.slug}>
                    {category.name}
                  </option>
                ))}
              </select>
              <input
                name="city"
                placeholder="Şehir"
                className="tb-input"
              />
              <select name="status" defaultValue="open" className="tb-select">
                <option value="open">Açık İlanlar</option>
                <option value="in_progress">Devam Eden</option>
                <option value="closed">Tamamlanan</option>
                <option value="cancelled">İptal</option>
                <option value="all">Tümü</option>
              </select>
              <button
                type="submit"
                className="tb-btn-primary"
              >
                İlan Ara
              </button>
            </form>

            <div className="flex flex-wrap gap-2">
              <Link href="/listings?status=open" className="tb-chip">
                Açık: {openCount}
              </Link>
              <Link
                href="/listings?status=in_progress"
                className="tb-chip border-sky-100 bg-sky-50 text-sky-700"
              >
                Devam Eden: {inProgressCount}
              </Link>
              <Link
                href="/listings?status=closed"
                className="tb-chip border-slate-200 bg-slate-100 text-slate-700"
              >
                Tamamlanan: {closedCount}
              </Link>
              <Link
                href="/listings?status=cancelled"
                className="tb-chip border-rose-100 bg-rose-50 text-rose-700"
              >
                İptal: {cancelledCount}
              </Link>
            </div>

            <div className="grid gap-2 sm:grid-cols-4">
              <div className="tb-stat">
                <p className="tb-stat-title">Toplam ilan</p>
                <p className="tb-stat-value">{listingCount}</p>
              </div>
              <div className="tb-stat">
                <p className="tb-stat-title">Ortalama bütçe</p>
                <p className="tb-stat-value text-emerald-700">
                  {formatMoney(avgBudget)}
                </p>
              </div>
              <div className="tb-stat">
                <p className="tb-stat-title">Toplam teklif</p>
                <p className="tb-stat-value">{totalOffers}</p>
              </div>
              <div className="tb-stat">
                <p className="tb-stat-title">Toplam görüntülenme</p>
                <p className="tb-stat-value">{totalViews}</p>
              </div>
            </div>

            <div className="flex flex-wrap gap-2">
              <Link
                href="/listings/new"
                className="tb-btn-primary"
              >
                İlan Ver
              </Link>
              <Link
                href="/listings"
                className="tb-btn-secondary"
              >
                Tüm İlanlar
              </Link>
              <Link
                href="/dashboard"
                className="tb-btn-secondary"
              >
                Hesabım
              </Link>
              <Link
                href="/messages"
                className="tb-btn-secondary"
              >
                Mesajlar
              </Link>
            </div>
          </div>

          <aside className="overflow-hidden rounded-[8px] border border-[#dbe6df] bg-white shadow-[0_12px_28px_rgba(15,43,35,0.08)]">
            <div className="border-b border-[#dbe6df] bg-gradient-to-r from-emerald-50 via-white to-sky-50 px-4 py-3">
              <p className="text-xs font-semibold tracking-wide text-emerald-700">
                CANLI PAZAR ÖZETİ
              </p>
              <p className="mt-1 text-sm font-semibold text-slate-900">
                Bugünün öne çıkan hareketi
              </p>
            </div>
            <div className="space-y-3 p-4">
              <div className="grid gap-2 sm:grid-cols-3 lg:grid-cols-1">
                <div className="rounded-[8px] border border-[#dbe6df] bg-emerald-50/45 px-3 py-2">
                  <p className="text-[11px] text-emerald-700">Aktif şehir</p>
                  <p className="text-sm font-bold text-slate-900">{topCities.length}</p>
                </div>
                <div className="rounded-[8px] border border-[#dbe6df] bg-slate-50 px-3 py-2">
                  <p className="text-[11px] text-slate-600">Açık ilan</p>
                  <p className="text-sm font-bold text-slate-900">{featuredListings.length}</p>
                </div>
                <div className="rounded-[8px] border border-[#dbe6df] bg-sky-50/45 px-3 py-2">
                  <p className="text-[11px] text-sky-700">Toplam kategori</p>
                  <p className="text-sm font-bold text-slate-900">{categories.length}</p>
                </div>
              </div>

              <div className="space-y-2">
                {spotlightListings.length === 0 ? (
                  <p className="rounded-[8px] border border-[#dbe6df] bg-slate-50 px-3 py-2 text-sm text-slate-600">
                    Şu an gösterilecek ilan bulunmuyor.
                  </p>
                ) : (
                  spotlightListings.map((listing) => (
                    <Link
                      key={listing.id}
                      href={`/listings/${listing.id}`}
                      className="block rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 transition hover:border-emerald-300 hover:bg-emerald-50/35"
                    >
                      <div className="flex items-center gap-2">
                        {listing.home_photo_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img
                            src={listing.home_photo_url}
                            alt={listing.title}
                            className="h-11 w-11 rounded-[8px] border border-[#dbe6df] object-cover"
                          />
                        ) : (
                          <span className="inline-flex h-11 w-11 items-center justify-center rounded-[8px] border border-[#dbe6df] bg-emerald-50 text-xs font-bold text-emerald-700">
                            İlan
                          </span>
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="line-clamp-1 text-sm font-semibold text-slate-900">
                            {listing.title}
                          </p>
                          <div className="mt-1 flex items-center justify-between gap-2 text-xs text-slate-500">
                            <span className="truncate">
                              {listing.city ?? "Şehir yok"} - {formatDate(listing.created_at)}
                            </span>
                            <span className="font-semibold text-emerald-700">
                              {formatMoney(listing.budget)}
                            </span>
                          </div>
                        </div>
                      </div>
                    </Link>
                  ))
                )}
              </div>

              <Link href="/listings" className="tb-btn-secondary w-full">
                Tüm ilan akışını aç
              </Link>
            </div>
          </aside>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={homeTopAdSlot}
          label="Sponsorlu Alan"
          minHeight={138}
        />
      </section>

      <section className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-xl font-bold text-slate-900">Guvenli Is Akisi</h2>
          <Link className="tb-btn-secondary" href="/messages">
            Mesaj Merkezini Ac
          </Link>
        </div>
        <p className="mt-1 text-sm text-slate-600">
          Ilan, teklif ve mesajlar tek kayit altinda tutulur; tum akis hesap
          baglantisiyla ilerler.
        </p>
        <div className="mt-3 grid gap-2 md:grid-cols-3">
          <article className="tb-soft-card">
            <p className="text-xs text-slate-500">Mesaj Güvenliği</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              Sadece ilan sahibi ve ilgili teklif veren kisiler mesajlasir.
            </p>
          </article>
          <article className="tb-soft-card">
            <p className="text-xs text-slate-500">Canli Is Durumu</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              Acik: {openCount} • Devam: {inProgressCount} • Tamamlanan: {closedCount}
            </p>
          </article>
          <article className="tb-soft-card">
            <p className="text-xs text-slate-500">Profil Kalitesi</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              Profil fotografi, aciklama ve dogru iletisim bilgisi eslesme
              kalitesini artirir.
            </p>
          </article>
          <article className="tb-soft-card">
            <p className="text-xs text-slate-500">Liste Kalitesi</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              Net baslik, dogru kategori ve net butce daha hizli teklif getirir.
            </p>
          </article>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-xl font-bold text-slate-900">Vitrin İlanlar</h2>
          <Link className="text-sm font-semibold text-emerald-700 hover:text-emerald-800" href="/listings">
            Tüm ilanları aç
          </Link>
        </div>
        <div className="mt-4 grid gap-3 md:grid-cols-2">
          {featuredListings.length === 0 ? (
            <p className="text-sm text-slate-600">
              Şu an vitrine alınmış ilan bulunamadı.
            </p>
          ) : (
            featuredListings.map((listing) => (
              <article
                key={listing.id}
                className="rounded-[8px] border border-[#dbe6df] bg-white p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-[0_12px_26px_rgba(15,43,35,0.08)]"
              >
                {listing.home_photo_url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={listing.home_photo_url}
                    alt={listing.title}
                    className="mb-3 h-36 w-full rounded-[8px] border border-[#dbe6df] object-cover"
                  />
                ) : null}
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <h3 className="text-base font-bold text-slate-900">
                      {listing.title}
                    </h3>
                    <p className="mt-1 text-sm text-slate-600">
                      {listing.city ?? "Şehir yok"} - {listing.district ?? "-"}
                    </p>
                  </div>
                  <span className="rounded-[8px] border border-emerald-100 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                    {formatMoney(listing.budget)}
                  </span>
                </div>
                <p className="mt-2 line-clamp-2 text-sm text-slate-700">
                  {listing.description}
                </p>
                <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                  <span className="rounded-md border border-slate-200 px-2 py-1">
                    {listing.cat_name ?? "Kategori yok"}
                  </span>
                  <span
                    className={`rounded-md border px-2 py-1 font-semibold ${listingStatusClasses(listing.status)}`}
                  >
                    {formatListingStatus(listing.status)}
                  </span>
                  <span>{formatDate(listing.created_at)}</span>
                </div>
                <div className="mt-3 flex items-center justify-between">
                  <p className="text-xs text-slate-500">
                    Teklif: {listing.offer_count ?? 0} - Görüntülenme:{" "}
                    {listing.view_count ?? 0}
                  </p>
                  <Link
                    href={`/listings/${listing.id}`}
                    className="tb-btn-secondary"
                  >
                    Detay
                  </Link>
                </div>
              </article>
            ))
          )}
        </div>
      </section>

      <section className="grid gap-3 lg:grid-cols-[1.1fr_0.9fr]">
        <article className="tb-surface tb-surface-pad">
          <h2 className="text-lg font-bold text-slate-900">Hizli Baslangic</h2>
          <p className="mt-1 text-sm text-slate-600">
            Ev sahibi ve hizmet veren icin net iki akisla daha hizli sonuc al.
          </p>
          <div className="mt-3 grid gap-2 md:grid-cols-2">
            <article className="tb-soft-card">
              <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                Ev Sahibi
              </p>
              <ol className="mt-2 grid gap-2 text-sm text-slate-700">
                <li>1. Ev bilgini kaydet ve kategori sec.</li>
                <li>2. Net baslik, tarih ve butce ile ilan ac.</li>
                <li>3. Teklifleri karsilastir, uygun kisiyi sec.</li>
              </ol>
              <Link href="/listings/new" className="tb-btn-primary mt-3">
                Hemen Ilan Ver
              </Link>
            </article>
            <article className="tb-soft-card">
              <p className="text-xs font-semibold uppercase tracking-wide text-sky-700">
                Hizmet Veren
              </p>
              <ol className="mt-2 grid gap-2 text-sm text-slate-700">
                <li>1. Sehir ve kategori filtrelerini sec.</li>
                <li>2. Uygun ilanlara net teklif gonder.</li>
                <li>3. Mesajlasma ile sureci hizla netlestir.</li>
              </ol>
              <Link href="/listings?status=open" className="tb-btn-secondary mt-3">
                Acik Isleri Gor
              </Link>
            </article>
          </div>
        </article>

        <article className="tb-surface tb-surface-pad">
          <h2 className="text-lg font-bold text-slate-900">Son Hareketler</h2>
          <p className="mt-1 text-sm text-slate-600">
            En son olusan ilanlar ve durumlar.
          </p>
          <div className="mt-3 grid gap-2">
            {recentListings.length === 0 ? (
              <p className="tb-empty">Henuz yeni ilan akisi olusmadi.</p>
            ) : (
              recentListings.map((listing) => (
                <Link
                  key={listing.id}
                  href={`/listings/${listing.id}`}
                  className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 transition hover:border-emerald-300 hover:bg-emerald-50/30"
                >
                  <div className="flex items-center justify-between gap-2">
                    <p className="line-clamp-1 text-sm font-semibold text-slate-900">
                      {listing.title}
                    </p>
                    <span
                      className={`rounded-md border px-2 py-0.5 text-[11px] font-semibold ${listingStatusClasses(listing.status)}`}
                    >
                      {formatListingStatus(listing.status)}
                    </span>
                  </div>
                  <p className="mt-1 text-xs text-slate-500">
                    {listing.city ?? "Şehir yok"} • {formatDate(listing.created_at)}
                  </p>
                </Link>
              ))
            )}
          </div>
        </article>
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={homeMidAdSlot}
          label="Sponsorlu Is Ortaklari"
          minHeight={160}
        />
      </section>

      <section className="grid gap-3 lg:grid-cols-[2fr_1fr]">
        <article className="tb-surface tb-surface-pad">
          <h2 className="text-lg font-bold text-slate-900">Popüler Kategoriler</h2>
          <p className="mt-1 text-sm text-slate-600">
            Kategori bazlı gez ve sana uygun işleri daha hızlı yakala.
          </p>
          <div className="mt-3 grid gap-2 sm:grid-cols-2">
            {categories.map((category) => (
              <Link
                key={category.id}
                href={`/listings?cat=${encodeURIComponent(category.slug)}`}
                className="flex items-center justify-between rounded-[8px] border border-[#dbe6df] px-3 py-2 text-sm transition hover:border-emerald-300 hover:bg-emerald-50/40"
              >
                <span className="font-semibold text-slate-800">{category.name}</span>
                <span className="text-xs text-slate-500">
                  {categoryCounter.get(category.slug) ?? 0} ilan
                </span>
              </Link>
            ))}
          </div>
        </article>

        <article className="tb-surface tb-surface-pad">
          <h2 className="text-lg font-bold text-slate-900">Aktif Şehirler</h2>
          <p className="mt-1 text-sm text-slate-600">
            Şehir bazında hızlı geçiş bağlantıları.
          </p>
          <div className="mt-3 grid gap-2">
            {topCities.length === 0 ? (
              <p className="text-sm text-slate-500">Şehir verisi henüz oluşmadı.</p>
            ) : (
              topCities.map(([city, count]) => (
                <Link
                  key={city}
                  href={`/hizmet/${encodeURIComponent(toGeoSlug(city))}`}
                  className="flex items-center justify-between rounded-[8px] border border-[#dbe6df] px-3 py-2 text-sm transition hover:border-emerald-300 hover:bg-emerald-50/40"
                >
                  <span className="font-semibold text-slate-800">{city}</span>
                  <span className="text-xs text-slate-500">{count} ilan</span>
                </Link>
              ))
            )}
          </div>
        </article>
      </section>
    </div>
  );
}
