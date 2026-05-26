"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { AdSlot } from "@/components/ad-slot";
import { getMyOffers } from "@/lib/api";
import { formatDate, formatMoney, formatOfferStatus, offerStatusClasses } from "@/lib/format";
import { useSession } from "@/lib/use-session";
import { Offer } from "@/lib/types";

export default function OffersPage() {
  const offersAdSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_OFFERS ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    "";

  const { ready, session } = useSession(true);
  const [offers, setOffers] = useState<Offer[]>([]);
  const [statusFilter, setStatusFilter] = useState<
    "all" | "pending" | "accepted" | "rejected"
  >("all");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!session) return;
    getMyOffers(session.token)
      .then((res) => setOffers(res.data))
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Teklifler yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [session]);

  if (!ready || !session) {
    return (
      <div className="tb-surface tb-surface-pad text-sm text-slate-600">
        Yükleniyor...
      </div>
    );
  }

  const acceptedCount = offers.filter(
    (offer) => (offer.status ?? "").toLowerCase() === "accepted",
  ).length;
  const rejectedCount = offers.filter(
    (offer) => (offer.status ?? "").toLowerCase() === "rejected",
  ).length;
  const pendingCount = offers.filter(
    (offer) => !offer.status || offer.status.toLowerCase() === "pending",
  ).length;
  const filteredOffers = offers.filter((offer) => {
    const normalized = (offer.status ?? "pending").toLowerCase();
    if (statusFilter === "all") return true;
    return normalized === statusFilter;
  });

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <h1 className="tb-heading">Tekliflerim</h1>
        <p className="tb-subheading">
          Verdiğin teklifleri ve durumlarını buradan takip et.
        </p>
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <span className="tb-chip">Toplam: {offers.length}</span>
          <span className="tb-chip border-emerald-100 bg-emerald-50 text-emerald-700">
            Kabul: {acceptedCount}
          </span>
          <span className="tb-chip border-rose-100 bg-rose-50 text-rose-700">
            Red: {rejectedCount}
          </span>
          <span className="tb-chip border-amber-200 bg-amber-50 text-amber-700">
            Beklemede: {pendingCount}
          </span>
        </div>
        <div className="mt-3">
          <div className="tb-segment">
            <button
              type="button"
              className={`tb-segment-btn ${statusFilter === "all" ? "tb-segment-btn-active" : ""}`}
              onClick={() => setStatusFilter("all")}
            >
              Tümü
            </button>
            <button
              type="button"
              className={`tb-segment-btn ${statusFilter === "pending" ? "tb-segment-btn-active" : ""}`}
              onClick={() => setStatusFilter("pending")}
            >
              Bekleyen
            </button>
            <button
              type="button"
              className={`tb-segment-btn ${statusFilter === "accepted" ? "tb-segment-btn-active" : ""}`}
              onClick={() => setStatusFilter("accepted")}
            >
              Kabul
            </button>
            <button
              type="button"
              className={`tb-segment-btn ${statusFilter === "rejected" ? "tb-segment-btn-active" : ""}`}
              onClick={() => setStatusFilter("rejected")}
            >
              Red
            </button>
          </div>
        </div>
        {error ? (
          <p className="tb-alert tb-alert-error mt-3">{error}</p>
        ) : null}
        {loading ? (
          <p className="mt-3 text-sm text-slate-600">Yükleniyor...</p>
        ) : offers.length === 0 ? (
          <p className="tb-empty mt-3">
            Henüz teklif yok.{" "}
            <Link className="font-semibold text-emerald-700" href="/listings">
              İlanlara göz at
            </Link>
            .
          </p>
        ) : (
          <div className="mt-4 grid gap-2">
            {filteredOffers.length === 0 ? (
              <p className="tb-empty">Bu filtrede teklif bulunamadı.</p>
            ) : (
              filteredOffers.map((offer) => (
                <article
                  key={offer.id}
                  className="rounded-[8px] border border-[#dbe6df] bg-white p-4 transition hover:border-emerald-300 hover:bg-emerald-50/35"
                >
                  <div className="flex flex-wrap items-start justify-between gap-2">
                    <div>
                      <p className="font-semibold text-slate-900">
                        {offer.listing_title ?? "İlan"}
                      </p>
                      <p className="text-sm text-slate-600">
                        {offer.city ?? "-"} / {offer.district ?? "-"} -{" "}
                        {offer.homeowner_name ?? "Ev sahibi"}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="font-bold text-emerald-700">
                        {formatMoney(offer.price)}
                      </p>
                      <span
                        className={`mt-1 inline-flex rounded-md border px-2 py-1 text-xs font-semibold ${offerStatusClasses(offer.status)}`}
                      >
                        {formatOfferStatus(offer.status)}
                      </span>
                    </div>
                  </div>
                  <p className="mt-2 text-sm text-slate-700">
                    {offer.message || "Mesaj yok."}
                  </p>
                  <p className="mt-1 text-xs text-slate-500">
                    Gönderim: {formatDate(offer.created_at)}
                  </p>
                  <div className="mt-3 flex flex-wrap gap-2">
                    {offer.listing_id ? (
                      <Link
                        href={`/listings/${offer.listing_id}`}
                        className="tb-btn-secondary"
                      >
                        Ilani Ac
                      </Link>
                    ) : null}
                    {offer.listing_id && offer.homeowner_id ? (
                      <Link
                        href={`/messages?listing_id=${offer.listing_id}&user_id=${offer.homeowner_id}`}
                        className="tb-btn-primary"
                      >
                        Mesajlas
                      </Link>
                    ) : null}
                  </div>
                </article>
              ))
            )}
          </div>
        )}
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={offersAdSlot}
          label="Sponsorlu Teklif Alani"
          minHeight={126}
        />
      </section>
    </div>
  );
}
