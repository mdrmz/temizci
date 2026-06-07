import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";

import { getCategories, getListings } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import { CITY_TARGETS, cityTargetFromSlug } from "@/lib/geo";
import type { Category } from "@/lib/types";

interface ServiceLandingPageProps {
  params: Promise<{ city: string; service: string }>;
}

const SITE_URL = "https://temizciburada.com";

async function getCategoryBySlug(slug: string): Promise<Category | null> {
  const categories = await getCategories().catch(() => null);
  return categories?.data.find((category) => category.slug === slug) ?? null;
}

export async function generateStaticParams() {
  const categories = await getCategories().catch(() => null);
  const categorySlugs = (categories?.data ?? [])
    .map((category) => category.slug)
    .filter((slug) => slug !== "")
    .slice(0, 12);

  return CITY_TARGETS.flatMap((city) =>
    categorySlugs.map((service) => ({
      city: city.slug,
      service,
    })),
  );
}

export async function generateMetadata({
  params,
}: ServiceLandingPageProps): Promise<Metadata> {
  const { city, service } = await params;
  const cityTarget = cityTargetFromSlug(city);
  const category = await getCategoryBySlug(service);
  const serviceName = category?.name ?? service.split("-").join(" ");
  const title = `${cityTarget.displayName} ${serviceName} | TemizciBurada`;
  const description = `${cityTarget.displayName} ${serviceName} ilanlarını incele, bütçeleri karşılaştır ve uygun temizlik hizmeti için hızlı teklif akışına katıl.`;
  const canonical = `/hizmet/${city}/${service}`;

  return {
    title,
    description,
    keywords: [
      `${cityTarget.displayName} ${serviceName}`,
      `${cityTarget.displayName} temizlikçi`,
      `${serviceName} hizmeti`,
      `${cityTarget.displayName} ev temizliği`,
      "temizlik hizmeti ilanları",
    ],
    alternates: { canonical },
    openGraph: {
      title,
      description,
      url: canonical,
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

export default async function ServiceLandingPage({
  params,
}: ServiceLandingPageProps) {
  const { city, service } = await params;
  const cityTarget = cityTargetFromSlug(city);
  const category = await getCategoryBySlug(service);

  if (!category) {
    notFound();
  }

  const listingsRes = await getListings({
    city: cityTarget.name,
    cat: category.slug,
    status: "open",
    sort: "newest",
    page: 1,
  }).catch(() => null);

  const listings = listingsRes?.data ?? [];
  const listingCount = listingsRes?.pagination.total ?? 0;
  const canonicalUrl = `${SITE_URL}/hizmet/${city}/${category.slug}`;
  const listItems = listings.slice(0, 8).map((listing, index) => ({
    "@type": "ListItem",
    position: index + 1,
    url: `${SITE_URL}/listings/${listing.id}`,
    name: listing.title,
  }));
  const schemaMarkup = [
    {
      "@context": "https://schema.org",
      "@type": "Service",
      name: `${cityTarget.displayName} ${category.name}`,
      serviceType: category.name,
      provider: {
        "@type": "Organization",
        name: "TemizciBurada",
        url: SITE_URL,
      },
      areaServed: {
        "@type": "City",
        name: cityTarget.displayName,
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
      description: `${cityTarget.displayName} bölgesinde ${category.name.toLocaleLowerCase("tr-TR")} ilanlarını inceleyin ve hizmet verenlerden teklif alın.`,
    },
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      itemListElement: [
        {
          "@type": "ListItem",
          position: 1,
          name: "Ana Sayfa",
          item: SITE_URL,
        },
        {
          "@type": "ListItem",
          position: 2,
          name: `${cityTarget.displayName} Temizlikçi Hizmetleri`,
          item: `${SITE_URL}/hizmet/${city}`,
        },
        {
          "@type": "ListItem",
          position: 3,
          name: category.name,
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
          name: `${cityTarget.displayName} ${category.name} ilanları nasıl bulunur?`,
          acceptedAnswer: {
            "@type": "Answer",
            text: "Bu sayfada şehir ve hizmet kategorisine göre filtrelenmiş açık ilanları görebilir, uygun ilan detayından teklif ve iletişim akışına geçebilirsiniz.",
          },
        },
        {
          "@type": "Question",
          name: `${category.name} için ilan verirken nelere dikkat edilmeli?`,
          acceptedAnswer: {
            "@type": "Answer",
            text: "Net başlık, doğru kategori, şehir/ilçe bilgisi, tarih tercihi ve bütçe aralığı daha hızlı ve kaliteli teklif almanıza yardımcı olur.",
          },
        },
      ],
    },
    listItems.length > 0
      ? {
          "@context": "https://schema.org",
          "@type": "ItemList",
          name: `${cityTarget.displayName} ${category.name} ilanları`,
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
        <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">
          {cityTarget.displayName} / {category.name}
        </p>
        <h1 className="tb-heading">
          {cityTarget.displayName} {category.name} İlanları
        </h1>
        <p className="tb-subheading">
          {cityTarget.displayName} bölgesinde {category.name.toLocaleLowerCase("tr-TR")} için
          açık ilanları incele, bütçeleri karşılaştır ve doğru hizmet verenle hızlıca eşleş.
        </p>
        <div className="mt-3 flex flex-wrap gap-2">
          <span className="tb-chip">Açık ilan: {listingCount}</span>
          <Link
            href={`/listings?city=${encodeURIComponent(cityTarget.name)}&cat=${encodeURIComponent(category.slug)}`}
            className="tb-btn-primary"
          >
            Filtreli İlanları Aç
          </Link>
          <Link href={`/hizmet/${city}`} className="tb-btn-secondary">
            {cityTarget.displayName} Tüm Hizmetler
          </Link>
          <Link href="/listings/new" className="tb-btn-secondary">
            Yeni İlan Ver
          </Link>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">
          Güncel {category.name} İlanları
        </h2>
        <p className="mt-1 text-sm text-slate-600">
          {cityTarget.displayName} içinde {category.name.toLocaleLowerCase("tr-TR")} arayanlar
          için şehir ve kategori bazlı güncel akış.
        </p>
        <div className="mt-3 grid gap-2">
          {listings.length === 0 ? (
            <p className="tb-empty">
              {cityTarget.displayName} için bu kategoride açık ilan bulunamadı.
              Yeni ilanlar için sayfayı daha sonra tekrar kontrol edebilirsin.
            </p>
          ) : (
            listings.slice(0, 12).map((listing) => (
              <article
                key={listing.id}
                className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-3 transition hover:border-emerald-300 hover:bg-emerald-50/30"
              >
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <h3 className="text-sm font-bold text-slate-900">
                      {listing.title}
                    </h3>
                    <p className="mt-1 text-xs text-slate-500">
                      {listing.city ?? cityTarget.displayName} - {listing.district ?? "-"} -{" "}
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
