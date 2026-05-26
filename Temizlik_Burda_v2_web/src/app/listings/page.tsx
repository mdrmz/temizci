import Link from "next/link";
import type { Metadata } from "next";

import { AdSlot } from "@/components/ad-slot";
import { getCategories, getListings } from "@/lib/api";
import {
  formatDate,
  formatListingStatus,
  formatMoney,
  listingStatusClasses,
} from "@/lib/format";

function normalizeParam(value: string | string[] | undefined): string {
  if (Array.isArray(value)) return value[0] ?? "";
  return value ?? "";
}

interface ListingsPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}

export async function generateMetadata({
  searchParams,
}: ListingsPageProps): Promise<Metadata> {
  const params = await searchParams;
  const city = normalizeParam(params.city).trim();
  const cat = normalizeParam(params.cat).trim();
  const q = normalizeParam(params.q).trim();

  const titleParts = ["Temizlikçi İlanları"];
  if (city) titleParts.unshift(city);
  if (cat) titleParts.push(cat);

  const title = `${titleParts.join(" ")} | TemizciBurada`;
  const description = city
    ? `${city} bölgesinde temizlikçi ilanlarını filtrele, bütçe ve tarih karşılaştırmasıyla doğru hizmeti seç.`
    : "Temizlikçi ilanlarını şehir, kategori ve bütçeye göre filtreleyerek hızlıca karşılaştır.";

  const url = new URL("https://temizciburada.com/listings");
  if (city) url.searchParams.set("city", city);
  if (cat) url.searchParams.set("cat", cat);
  if (q) url.searchParams.set("q", q);

  return {
    title,
    description,
    alternates: {
      canonical: `${url.pathname}${url.search}`,
    },
    openGraph: {
      title,
      description,
      url: `${url.pathname}${url.search}`,
      type: "website",
      locale: "tr_TR",
    },
  };
}

export default async function ListingsPage({ searchParams }: ListingsPageProps) {
  const listingsInlineAdSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_LISTINGS_INLINE ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    "";

  const params = await searchParams;
  const q = normalizeParam(params.q).trim();
  const cat = normalizeParam(params.cat).trim();
  const city = normalizeParam(params.city).trim();
  const statusParam = normalizeParam(params.status).trim().toLowerCase();
  const sortParam = normalizeParam(params.sort).trim().toLowerCase();
  const page = Math.max(1, Number.parseInt(normalizeParam(params.page), 10) || 1);
  const status =
    statusParam === "all" ||
    statusParam === "open" ||
    statusParam === "in_progress" ||
    statusParam === "closed" ||
    statusParam === "cancelled"
      ? statusParam
      : "open";
  const sort =
    sortParam === "newest" ||
    sortParam === "oldest" ||
    sortParam === "budget_high" ||
    sortParam === "budget_low" ||
    sortParam === "offers_high" ||
    sortParam === "views_high"
      ? sortParam
      : "newest";

  const [listingsRes, categoriesRes] = await Promise.all([
    getListings({
      q,
      cat,
      city,
      status: status as "all" | "open" | "in_progress" | "closed" | "cancelled",
      sort: sort as
        | "newest"
        | "oldest"
        | "budget_high"
        | "budget_low"
        | "offers_high"
        | "views_high",
      page,
    }),
    getCategories(),
  ]);

  const pagination = listingsRes.pagination;
  const hasFilter =
    q !== "" || cat !== "" || city !== "" || status !== "open" || sort !== "newest";
  const activeCategory =
    categoriesRes.data.find((category) => category.slug === cat)?.name ?? cat;
  const statusLabelMap: Record<string, string> = {
    all: "Tumu",
    open: "Acik",
    in_progress: "Devam Eden",
    closed: "Tamamlanan",
    cancelled: "Iptal",
  };
  const sortLabelMap: Record<string, string> = {
    newest: "En Yeni",
    oldest: "En Eski",
    budget_high: "Butce Yuksek",
    budget_low: "Butce Dusuk",
    offers_high: "Teklif Sayisi Yuksek",
    views_high: "Goruntulenme Yuksek",
  };

  const categoryCounter = new Map<string, number>();
  listingsRes.data.forEach((listing) => {
    const slug = (listing.cat_slug ?? "").trim();
    if (!slug) return;
    categoryCounter.set(slug, (categoryCounter.get(slug) ?? 0) + 1);
  });

  function pageHref(nextPage: number) {
    const url = new URLSearchParams();
    if (q) url.set("q", q);
    if (cat) url.set("cat", cat);
    if (city) url.set("city", city);
    if (status !== "open") url.set("status", status);
    if (sort !== "newest") url.set("sort", sort);
    if (nextPage > 1) url.set("page", String(nextPage));
    const query = url.toString();
    return query ? `/listings?${query}` : "/listings";
  }

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h1 className="tb-heading">İlanlar</h1>
          <Link
            href="/listings/new"
            className="tb-btn-primary"
          >
            İlan Ver
          </Link>
        </div>
        <p className="tb-subheading">
          Şehir, kategori ve metin filtresiyle uygun işleri hızlıca bul.
        </p>

        <form className="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3" method="get">
          <input
            name="q"
            defaultValue={q}
            placeholder="İlan ara..."
            className="tb-input"
          />
          <select
            name="cat"
            defaultValue={cat}
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
            defaultValue={city}
            placeholder="Şehir"
            className="tb-input"
          />
          <select
            name="status"
            defaultValue={status}
            className="tb-select"
          >
            <option value="open">Acik</option>
            <option value="in_progress">Devam Eden</option>
            <option value="closed">Tamamlanan</option>
            <option value="cancelled">Iptal</option>
            <option value="all">Tumu</option>
          </select>
          <select
            name="sort"
            defaultValue={sort}
            className="tb-select"
          >
            <option value="newest">En Yeni</option>
            <option value="oldest">En Eski</option>
            <option value="budget_high">Butce Yuksek</option>
            <option value="budget_low">Butce Dusuk</option>
            <option value="offers_high">Teklif Sayisi Yuksek</option>
            <option value="views_high">Goruntulenme Yuksek</option>
          </select>
          <button
            type="submit"
            className="tb-btn-primary xl:col-span-1"
          >
            Filtrele
          </button>
        </form>

        <div className="mt-3 flex flex-wrap items-center gap-2">
          <span className="tb-chip">
            Toplam: {pagination.total}
          </span>
          {q ? (
            <span className="tb-chip">
              Arama: {q}
            </span>
          ) : null}
          {cat ? (
            <span className="tb-chip">
              Kategori: {activeCategory}
            </span>
          ) : null}
          {city ? (
            <span className="tb-chip">
              Şehir: {city}
            </span>
          ) : null}
          <span className="tb-chip">
            Durum: {statusLabelMap[status] ?? status}
          </span>
          <span className="tb-chip">
            Siralama: {sortLabelMap[sort] ?? sort}
          </span>
          {hasFilter ? (
            <Link
              href="/listings"
              className="tb-btn-secondary !min-h-[30px] !px-2 !text-xs"
            >
              Filtreyi Temizle
            </Link>
          ) : null}
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-sm font-bold uppercase tracking-wide text-slate-700">
          Kategori Hızlı Geçiş
        </h2>
        <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          {categoriesRes.data.map((category) => (
            <Link
              key={category.id}
              href={`/listings?cat=${encodeURIComponent(category.slug)}`}
              className={`rounded-[8px] border px-3 py-2 text-sm transition ${
                cat === category.slug
                  ? "border-emerald-300 bg-emerald-50 font-semibold text-emerald-700"
                  : "border-[#dbe6df] hover:border-emerald-300 hover:bg-emerald-50/35"
              }`}
            >
              <div className="flex items-center justify-between gap-2">
                <span>{category.name}</span>
                <span className="text-xs text-slate-500">
                  {categoryCounter.get(category.slug) ?? 0}
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={listingsInlineAdSlot}
          label="Sponsorlu Sonuclar"
          minHeight={140}
        />
      </section>

      <section className="space-y-3">
        {listingsRes.data.length === 0 ? (
          <div className="tb-surface tb-surface-pad">
            <p className="tb-empty">Bu filtreyle ilan bulunamadı.</p>
          </div>
        ) : (
          listingsRes.data.map((listing) => (
            <article
              key={listing.id}
              className="tb-surface tb-surface-pad transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-[0_12px_26px_rgba(15,43,35,0.08)]"
            >
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 className="text-lg font-bold text-slate-900">{listing.title}</h2>
                  <p className="mt-1 text-sm text-slate-600">
                    {listing.city ?? "Şehir yok"} - {listing.district ?? "-"} -{" "}
                    {listing.cat_name ?? "Kategori yok"}
                  </p>
                </div>
                <span className="rounded-[8px] border border-emerald-100 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                  {formatMoney(listing.budget)}
                </span>
              </div>
              <p className="mt-3 text-sm text-slate-700">{listing.description}</p>
              <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span
                  className={`rounded-md border px-2 py-1 font-semibold ${listingStatusClasses(listing.status)}`}
                >
                  {formatListingStatus(listing.status)}
                </span>
                <span>Oluşturulma: {formatDate(listing.created_at)}</span>
              </div>
              <div className="mt-4 flex items-center justify-between">
                <p className="text-xs text-slate-500">
                  Teklif: {listing.offer_count ?? 0} - Görüntülenme:{" "}
                  {listing.view_count ?? 0}
                </p>
                <Link
                  href={`/listings/${listing.id}`}
                  className="tb-btn-secondary"
                >
                  Detayı Aç
                </Link>
              </div>
            </article>
          ))
        )}
      </section>

      {pagination.pages > 1 ? (
        <nav className="flex items-center justify-center gap-2">
          <Link
            href={pageHref(Math.max(1, page - 1))}
            className={`rounded-[8px] border px-3 py-2 text-sm font-semibold ${
              page === 1
                ? "pointer-events-none border-slate-200 text-slate-400"
                : "border-[#cdd9d2] bg-white text-slate-700 hover:border-[#b9c9c0]"
            }`}
          >
            Önceki
          </Link>
          <span className="text-sm text-slate-600">
            Sayfa {page} / {pagination.pages}
          </span>
          <Link
            href={pageHref(Math.min(pagination.pages, page + 1))}
            className={`rounded-[8px] border px-3 py-2 text-sm font-semibold ${
              page >= pagination.pages
                ? "pointer-events-none border-slate-200 text-slate-400"
                : "border-[#cdd9d2] bg-white text-slate-700 hover:border-[#b9c9c0]"
            }`}
          >
            Sonraki
          </Link>
        </nav>
      ) : null}
    </div>
  );
}
