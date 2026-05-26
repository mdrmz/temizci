import type { Metadata } from "next";
import Link from "next/link";

import { getListings } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import { CITY_TARGETS, cityNameFromSlug } from "@/lib/geo";

interface CityLandingPageProps {
  params: Promise<{ city: string }>;
}

export async function generateStaticParams() {
  return CITY_TARGETS.map((city) => ({ city: city.slug }));
}

export async function generateMetadata({
  params,
}: CityLandingPageProps): Promise<Metadata> {
  const { city } = await params;
  const cityName = cityNameFromSlug(city);
  const title = `${cityName} Temizlikçi İlanları | TemizciBurada`;
  const description = `${cityName} için temizlikçi arayanlara özel ilanlar, bütçeler ve hızlı teklif akışı. Temizlik hizmetini güvenle karşılaştır.`;
  const url = `/hizmet/${city}`;

  return {
    title,
    description,
    alternates: { canonical: url },
    openGraph: {
      title,
      description,
      url,
      type: "website",
      locale: "tr_TR",
    },
  };
}

export default async function CityLandingPage({ params }: CityLandingPageProps) {
  const { city } = await params;
  const cityName = cityNameFromSlug(city);
  const listingsRes = await getListings({
    city: cityName,
    status: "open",
    sort: "newest",
    page: 1,
  }).catch(() => null);

  const listings = listingsRes?.data ?? [];
  const listingCount = listingsRes?.pagination.total ?? 0;

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <h1 className="tb-heading">{cityName} Temizlikçi Hizmetleri</h1>
        <p className="tb-subheading">
          {cityName} bölgesindeki güncel temizlik ilanlarını incele, hızlı teklif al
          ve doğru hizmet verenle eşleş.
        </p>
        <div className="mt-3 flex flex-wrap gap-2">
          <span className="tb-chip">Açık ilan: {listingCount}</span>
          <Link href={`/listings?city=${encodeURIComponent(cityName)}`} className="tb-btn-primary">
            {cityName} İlanlarını Aç
          </Link>
          <Link href="/listings/new" className="tb-btn-secondary">
            Yeni İlan Ver
          </Link>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Son Eklenen İlanlar</h2>
        <p className="mt-1 text-sm text-slate-600">
          Bu sayfa şehir bazlı hızlı gezinme için hazırlanmıştır.
        </p>
        <div className="mt-3 grid gap-2">
          {listings.length === 0 ? (
            <p className="tb-empty">
              {cityName} için şu an açık ilan bulunamadı. Yeni ilanları görmek için
              düzenli kontrol et.
            </p>
          ) : (
            listings.slice(0, 12).map((listing) => (
              <article
                key={listing.id}
                className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-3 transition hover:border-emerald-300 hover:bg-emerald-50/30"
              >
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">{listing.title}</h3>
                    <p className="mt-1 text-xs text-slate-500">
                      {listing.city ?? cityName} - {listing.district ?? "-"} -{" "}
                      {formatDate(listing.created_at)}
                    </p>
                  </div>
                  <span className="text-sm font-semibold text-emerald-700">
                    {formatMoney(listing.budget)}
                  </span>
                </div>
                <p className="mt-2 line-clamp-2 text-sm text-slate-700">
                  {listing.description}
                </p>
                <div className="mt-3 flex items-center justify-end">
                  <Link href={`/listings/${listing.id}`} className="tb-btn-secondary">
                    Detayı Aç
                  </Link>
                </div>
              </article>
            ))
          )}
        </div>
      </section>
    </div>
  );
}
