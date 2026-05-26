import Link from "next/link";
import type { Metadata } from "next";
import { notFound } from "next/navigation";

import { AdSlot } from "@/components/ad-slot";
import { OfferForm } from "@/components/offer-form";
import { getListingDetail } from "@/lib/api";
import {
  formatDate,
  formatListingStatus,
  formatMoney,
  formatOfferStatus,
  listingStatusClasses,
  offerStatusClasses,
} from "@/lib/format";

interface ListingDetailPageProps {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({
  params,
}: ListingDetailPageProps): Promise<Metadata> {
  const { id } = await params;
  const listingId = Number.parseInt(id, 10);
  if (!Number.isFinite(listingId) || listingId <= 0) {
    return {
      title: "Ilan Detayi | TemizciBurada",
      description: "TemizciBurada ilan detaylari.",
    };
  }

  const payload = await getListingDetail(listingId).catch(() => null);
  if (!payload?.listing) {
    return {
      title: "Ilan Detayi | TemizciBurada",
      description: "TemizciBurada ilan detaylari.",
    };
  }

  const listing = payload.listing;
  const city = (listing.city ?? "").trim();
  const category = (listing.cat_name ?? "Temizlik Hizmeti").trim();
  const title = city
    ? `${city} ${category} | ${listing.title}`
    : `${category} | ${listing.title}`;
  const description =
    listing.description?.slice(0, 155) ||
    "Temizlik hizmet ilan detaylarini inceleyin ve teklifleri karsilastirin.";
  const canonical = `/listings/${listing.id}`;

  return {
    title,
    description,
    alternates: {
      canonical,
    },
    openGraph: {
      title,
      description,
      type: "article",
      url: canonical,
      locale: "tr_TR",
    },
  };
}

function buildInitials(name: string): string {
  const normalized = name.trim();
  if (!normalized) return "?";
  return (
    normalized
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase() ?? "")
      .join("") || "?"
  );
}

export default async function ListingDetailPage({
  params,
}: ListingDetailPageProps) {
  const listingDetailAdSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_LISTING_DETAIL ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    "";

  const { id } = await params;
  const listingId = Number.parseInt(id, 10);
  if (!Number.isFinite(listingId) || listingId <= 0) {
    notFound();
  }

  const payload = await getListingDetail(listingId).catch(() => null);
  if (!payload) {
    notFound();
  }

  const { listing, offers } = payload;
  const sortedOffers = [...offers].sort((left, right) => {
    const rank = (value?: string) => {
      const normalized = (value ?? "").toLowerCase();
      if (normalized === "accepted") return 0;
      if (normalized === "pending" || normalized === "") return 1;
      if (normalized === "rejected") return 2;
      return 3;
    };

    const rankDiff = rank(left.status) - rank(right.status);
    if (rankDiff !== 0) return rankDiff;
    return (right.price ?? 0) - (left.price ?? 0);
  });
  const acceptedOffers = offers.filter(
    (offer) => (offer.status ?? "").toLowerCase() === "accepted",
  ).length;
  const rejectedOffers = offers.filter(
    (offer) => (offer.status ?? "").toLowerCase() === "rejected",
  ).length;
  const pendingOffers = offers.length - acceptedOffers - rejectedOffers;
  const offerScope = listing.offers_scope ?? "none";
  const offerTitle =
    offerScope === "all"
      ? "Gelen Teklifler"
      : offerScope === "mine"
        ? "Teklifim"
        : "Teklifler";

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">{listing.title}</h1>
            <p className="mt-1 text-sm text-slate-600">
              {listing.city ?? "Şehir yok"} - {listing.cat_name ?? "Kategori yok"}
            </p>
            <p className="mt-1 text-xs text-slate-500">
              İlan sahibi: {listing.owner_name ?? "Bilinmiyor"}
            </p>
          </div>
          <span className="rounded-[8px] border border-emerald-100 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
            {formatMoney(listing.budget)}
          </span>
        </div>
        <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
          <span
            className={`rounded-md border px-2 py-1 font-semibold ${listingStatusClasses(listing.status)}`}
          >
            {formatListingStatus(listing.status)}
          </span>
          <span>Oluşturulma: {formatDate(listing.created_at)}</span>
        </div>
        <p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">
          {listing.description}
        </p>
        <dl className="mt-4 grid gap-2 text-sm text-slate-600 md:grid-cols-4">
          <div>
            <dt className="font-semibold text-slate-900">Tarih</dt>
            <dd>{formatDate(listing.preferred_date)}</dd>
          </div>
          <div>
            <dt className="font-semibold text-slate-900">Saat</dt>
            <dd>{listing.preferred_time || "-"}</dd>
          </div>
          <div>
            <dt className="font-semibold text-slate-900">Oda Tipi</dt>
            <dd>{listing.room_config || "-"}</dd>
          </div>
          <div>
            <dt className="font-semibold text-slate-900">Metrekare</dt>
            <dd>{listing.sqm ? `${listing.sqm} m²` : "-"}</dd>
          </div>
        </dl>
        <div className="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
          <span className="rounded-md border border-slate-200 px-2 py-1">
            Görüntülenme: {listing.view_count ?? 0}
          </span>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">
          {offerTitle} ({offers.length})
        </h2>
        <p className="mt-1 text-sm text-slate-600">
          {offerScope === "all"
            ? "Bu ilandaki tum teklifleri goruyorsun."
            : offerScope === "mine"
              ? "Bu ilandaki kendi teklifini goruyorsun."
              : "Teklif detaylari yalnizca ilan sahibi veya teklif veren taraflarca gorunur."}
        </p>
        <div className="mt-3 flex flex-wrap gap-2">
          <span className="tb-chip">Gorunen: {offers.length}</span>
          <span className="tb-chip border-amber-200 bg-amber-50 text-amber-700">
            Beklemede: {pendingOffers}
          </span>
          <span className="tb-chip border-emerald-100 bg-emerald-50 text-emerald-700">
            Kabul: {acceptedOffers}
          </span>
          <span className="tb-chip border-rose-100 bg-rose-50 text-rose-700">
            Red: {rejectedOffers}
          </span>
        </div>
        {offers.length === 0 ? (
          <p className="tb-empty mt-3">
            {offerScope === "none"
              ? "Bu ilanin tekliflerini gormek icin ilgili surece dahil olman gerekir."
              : "Henuz teklif yok."}
          </p>
        ) : (
          <div className="mt-3 grid gap-2">
            {sortedOffers.map((offer) => (
              <article
                key={offer.id}
                className="rounded-[8px] border border-[#dbe6df] bg-white px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50/35"
              >
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div className="flex items-center gap-2">
                    {offer.worker_avatar_url ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img
                        src={offer.worker_avatar_url}
                        alt={offer.worker_name ?? "Hizmet Veren"}
                        className="h-9 w-9 rounded-full border border-[#dbe6df] object-cover"
                      />
                    ) : (
                      <span className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-xs font-bold text-emerald-700">
                        {buildInitials(offer.worker_name ?? "Hizmet Veren")}
                      </span>
                    )}
                    <p className="font-semibold text-slate-900">
                      {offer.worker_name ?? "Hizmet Veren"}
                    </p>
                  </div>
                  <span className="text-sm font-bold text-emerald-700">
                    {formatMoney(offer.price)}
                  </span>
                </div>
                <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
                  <span
                    className={`rounded-md border px-2 py-1 font-semibold ${offerStatusClasses(offer.status)}`}
                  >
                    {formatOfferStatus(offer.status)}
                  </span>
                  <span className="text-slate-500">
                    {formatDate(offer.created_at)}
                  </span>
                </div>
                <p className="mt-2 text-sm text-slate-700">
                  {offer.message || "Mesaj girilmedi."}
                </p>
                {offer.can_message && offer.message_contact_id ? (
                  <Link
                    href={`/messages?listing_id=${listingId}&user_id=${offer.message_contact_id}`}
                    className="tb-btn-secondary mt-3"
                  >
                    Mesajlas
                  </Link>
                ) : null}
              </article>
            ))}
          </div>
        )}
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={listingDetailAdSlot}
          label="Sponsorlu Alan"
          minHeight={140}
        />
      </section>

      {listing.viewer_can_message_owner && listing.owner_id ? (
        <section className="tb-surface tb-surface-pad">
          <h2 className="text-base font-bold text-slate-900">Ilan Sahibiyle Iletisim</h2>
          <p className="mt-1 text-sm text-slate-600">
            Teklif sureci icin ilan sahibiyle guvenli mesajlasmayi buradan acabilirsin.
          </p>
          <Link
            href={`/messages?listing_id=${listingId}&user_id=${listing.owner_id}`}
            className="tb-btn-primary mt-3"
          >
            Mesajlasmayi Ac
          </Link>
        </section>
      ) : null}

      <OfferForm listingId={listingId} />
    </div>
  );
}
