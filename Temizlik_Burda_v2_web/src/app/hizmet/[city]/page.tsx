import type { Metadata } from "next";
import Link from "next/link";

import { getListings } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import { CITY_TARGETS, cityTargetFromSlug } from "@/lib/geo";

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
  const cityTarget = cityTargetFromSlug(city);
  const title = `${cityTarget.displayName} Temizlikçi İlanları | TemizciBurada`;
  const description = `${cityTarget.displayName} için temizlikçi arayanlara özel ilanlar, bütçeler ve hızlı teklif akışı. Ev temizliği ve günlük temizlik hizmetlerini güvenle karşılaştır.`;
  const url = `/hizmet/${city}`;

  return {
    title,
    description,
    keywords: [
      `${cityTarget.displayName} temizlikçi`,
      `${cityTarget.displayName} ev temizliği`,
      `${cityTarget.displayName} temizlik hizmeti`,
      `${cityTarget.displayName} gündelikçi`,
      "temizlikçi bul",
    ],
    alternates: { canonical: url },
    openGraph: {
      title,
      description,
      url,
      type: "website",
      locale: "tr_TR",
    },
    other: {
      "geo.region": cityTarget.region,
      "geo.placename": cityTarget.displayName,
      "geo.position": `${cityTarget.latitude};${cityTarget.longitude}`,
      ICBM: `${cityTarget.latitude}, ${cityTarget.longitude}`,
    },
  };
}

export default async function CityLandingPage({ params }: CityLandingPageProps) {
  const { city } = await params;
  const cityTarget = cityTargetFromSlug(city);
  const cityName = cityTarget.name;
  const displayCityName = cityTarget.displayName;
  const listingsRes = await getListings({
    city: cityName,
    status: "open",
    sort: "newest",
    page: 1,
  }).catch(() => null);

  const listings = listingsRes?.data ?? [];
  const listingCount = listingsRes?.pagination.total ?? 0;
  const canonicalUrl = `https://temizciburada.com/hizmet/${city}`;
  const listItems = listings.slice(0, 8).map((listing, index) => ({
    "@type": "ListItem",
    position: index + 1,
    url: `https://temizciburada.com/listings/${listing.id}`,
    name: listing.title,
  }));
  const schemaMarkup = [
    {
      "@context": "https://schema.org",
      "@type": "Service",
      name: `${displayCityName} temizlikçi ve ev temizliği hizmetleri`,
      serviceType: "Ev temizliği ve temizlikçi bulma",
      provider: {
        "@type": "Organization",
        name: "TemizciBurada",
        url: "https://temizciburada.com",
      },
      areaServed: {
        "@type": "City",
        name: displayCityName,
        containedInPlace: {
          "@type": "Country",
          name: "Türkiye",
        },
        geo: {
          "@type": "GeoCoordinates",
          latitude: cityTarget.latitude,
          longitude: cityTarget.longitude,
        },
      },
      url: canonicalUrl,
      description: `${displayCityName} bölgesinde ev temizliği, gündelik temizlik ve benzeri hizmetler için ilanları inceleyin ve teklif alın.`,
    },
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      itemListElement: [
        {
          "@type": "ListItem",
          position: 1,
          name: "Ana Sayfa",
          item: "https://temizciburada.com",
        },
        {
          "@type": "ListItem",
          position: 2,
          name: `${displayCityName} Temizlikçi Hizmetleri`,
          item: canonicalUrl,
        },
      ],
    },
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      mainEntity: [
        {
          "@type": "Question",
          name: `${displayCityName} için temizlikçi nasıl bulunur?`,
          acceptedAnswer: {
            "@type": "Answer",
            text: "TemizciBurada'da şehir ve kategori filtresiyle açık ilanları görebilir veya yeni ilan oluşturarak hizmet verenlerden teklif alabilirsiniz.",
          },
        },
        {
          "@type": "Question",
          name: `${displayCityName} temizlik ilanlarında fiyat nasıl karşılaştırılır?`,
          acceptedAnswer: {
            "@type": "Answer",
            text: "İlan detayında bütçe, teklif sayısı, hizmet veren profili ve mesajlaşma akışı birlikte değerlendirilerek doğru seçim yapılabilir.",
          },
        },
      ],
    },
    listItems.length > 0
      ? {
          "@context": "https://schema.org",
          "@type": "ItemList",
          name: `${displayCityName} güncel temizlik ilanları`,
          itemListElement: listItems,
        }
      : null,
  ].filter(Boolean);

  return (
    <div className="tb-page">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaMarkup) }}
      />
      <section className="tb-surface tb-surface-pad">
        <h1 className="tb-heading">{displayCityName} Temizlikçi Hizmetleri</h1>
        <p className="tb-subheading">
          {displayCityName} bölgesindeki güncel temizlik ilanlarını incele, hızlı teklif al
          ve doğru hizmet verenle eşleş.
        </p>
        <div className="mt-3 flex flex-wrap gap-2">
          <span className="tb-chip">Açık ilan: {listingCount}</span>
          <Link href={`/listings?city=${encodeURIComponent(cityName)}`} className="tb-btn-primary">
            {displayCityName} İlanlarını Aç
          </Link>
          <Link href="/listings/new" className="tb-btn-secondary">
            Yeni İlan Ver
          </Link>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Son Eklenen İlanlar</h2>
        <p className="mt-1 text-sm text-slate-600">
          {displayCityName} için şehir bazlı hızlı gezinme ve yerel temizlik ilanı keşfi.
        </p>
        <div className="mt-3 grid gap-2">
          {listings.length === 0 ? (
            <p className="tb-empty">
              {displayCityName} için şu an açık ilan bulunamadı. Yeni ilanları görmek için
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
