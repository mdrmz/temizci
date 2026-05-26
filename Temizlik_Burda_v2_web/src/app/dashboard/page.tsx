"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { AdSlot } from "@/components/ad-slot";
import {
  getHomes,
  getMessageThreads,
  getMyListings,
  getMyOffers,
  getProfile,
} from "@/lib/api";
import { useSession } from "@/lib/use-session";
import { Home, Listing, MessageThread, User } from "@/lib/types";

export default function DashboardPage() {
  const dashboardAdSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_DASHBOARD ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    "";

  const { ready, session } = useSession(true);
  const [profile, setProfile] = useState<User | null>(null);
  const [myListings, setMyListings] = useState<Listing[]>([]);
  const [homes, setHomes] = useState<Home[]>([]);
  const [myOffersCount, setMyOffersCount] = useState(0);
  const [threads, setThreads] = useState<MessageThread[]>([]);
  const [unreadMessages, setUnreadMessages] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!session) return;
    const token = session.token;

    Promise.all([
      getProfile(token),
      getMyListings(token),
      getHomes(token),
      getMyOffers(token),
      getMessageThreads(token),
    ])
      .then(([profileRes, listingsRes, homesRes, offersRes, messagesRes]) => {
        setProfile(profileRes.user);
        setMyListings(listingsRes.data);
        setHomes(homesRes.data);
        setMyOffersCount(offersRes.data.length);
        setThreads(messagesRes.data);
        setUnreadMessages(messagesRes.unread_count ?? 0);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Panel verisi alınamadı.");
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

  if (loading) {
    return (
      <div className="tb-surface tb-surface-pad text-sm text-slate-600">
        Panel hazırlanıyor...
      </div>
    );
  }

  const role = profile?.role ?? session.user.role;
  const completionFields = [
    profile?.name,
    profile?.email,
    profile?.phone,
    profile?.city,
    profile?.bio,
  ];
  const completionRatio =
    completionFields.filter((value) => Boolean(value && String(value).trim())).length /
    completionFields.length;
  const completionPercent = Math.round(completionRatio * 100);
  const openListingsCount = myListings.filter((listing) => {
    const normalized = (listing.status ?? "").toLowerCase();
    return normalized === "open" || normalized === "active" || normalized === "";
  }).length;
  const closedListingsCount = myListings.filter((listing) => {
    const normalized = (listing.status ?? "").toLowerCase();
    return normalized === "completed" || normalized === "done" || normalized === "closed";
  }).length;
  const recentListings = [...myListings]
    .sort((left, right) => {
      const leftTime = new Date(left.created_at).getTime();
      const rightTime = new Date(right.created_at).getTime();
      return rightTime - leftTime;
    })
    .slice(0, 6);
  const recentThreads = threads.slice(0, 4);
  const trustChecks = [
    Boolean(profile?.avatar_url),
    Boolean(profile?.phone && String(profile.phone).trim()),
    Boolean(profile?.city && String(profile.city).trim()),
    Boolean(profile?.bio && String(profile.bio).trim()),
    myListings.length > 0,
    myOffersCount > 0,
  ];
  const trustScore = Math.round(
    (trustChecks.filter(Boolean).length / trustChecks.length) * 100,
  );
  const trustLabel =
    trustScore >= 80
      ? "Yuksek guven"
      : trustScore >= 55
        ? "Orta guven"
        : "Profil guclendirilmeli";

  const quickLinks =
    role === "homeowner"
      ? [
          { href: "/listings/new", label: "Yeni İlan Aç" },
          { href: "/homes", label: "Evlerim" },
          { href: "/offers", label: "Tekliflerim" },
        ]
      : role === "worker"
        ? [
            { href: "/listings", label: "Uygun İlanlar" },
            { href: "/offers", label: "Tekliflerim" },
            { href: "/profile", label: "Profilim" },
          ]
        : [
            { href: "/listings", label: "İlanlar" },
            { href: "/offers", label: "Teklifler" },
            { href: "/profile", label: "Profil" },
          ];

  const roleLabel =
    role === "homeowner"
      ? "Ev Sahibi"
      : role === "worker"
        ? "Hizmet Veren"
        : "Yönetici";

  const checklist =
    role === "homeowner"
      ? [
          {
            done: homes.length > 0,
            text: "En az 1 ev kaydı ekle",
          },
          {
            done: myListings.length > 0,
            text: "İlk ilanını yayınla",
          },
          {
            done: completionPercent >= 80,
            text: "Profilini %80 ve üstüne tamamla",
          },
        ]
      : [
          {
            done: completionPercent >= 80,
            text: "Profilini tamamla",
          },
          {
            done: myOffersCount > 0,
            text: "İlk teklifini gönder",
          },
          {
            done: myListings.length > 0,
            text: "Aktif ilanları takip listene al",
          },
        ];

  return (
    <div className="tb-page">
      <section className="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <article className="tb-surface tb-surface-pad">
          <h1 className="tb-heading">Panel</h1>
          <p className="tb-subheading">
            Hoş geldin {profile?.name ?? session.user.name}. Hesap türün:{" "}
            <span className="font-semibold text-slate-900">{roleLabel}</span>
          </p>
          {error ? <p className="tb-alert tb-alert-error mt-3">{error}</p> : null}

          <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <div className="tb-stat">
              <p className="tb-stat-title">Toplam İlan</p>
              <p className="tb-stat-value">{myListings.length}</p>
            </div>
            <div className="tb-stat">
              <p className="tb-stat-title">Açık İlan</p>
              <p className="tb-stat-value text-emerald-700">{openListingsCount}</p>
            </div>
            <div className="tb-stat">
              <p className="tb-stat-title">Kapanan İş</p>
              <p className="tb-stat-value">{closedListingsCount}</p>
            </div>
            <div className="tb-stat">
              <p className="tb-stat-title">Verilen Teklif</p>
              <p className="tb-stat-value">{myOffersCount}</p>
            </div>
            <div className="tb-stat">
              <p className="tb-stat-title">Okunmamis Mesaj</p>
              <p className="tb-stat-value text-emerald-700">{unreadMessages}</p>
            </div>
          </div>

          <div className="mt-4 flex flex-wrap gap-2">
            {quickLinks.map((item, index) => (
              <Link
                key={item.href}
                href={item.href}
                className={index === 0 ? "tb-btn-primary" : "tb-btn-secondary"}
              >
                {item.label}
              </Link>
            ))}
          </div>
        </article>

        <article className="tb-surface tb-surface-pad">
          <h2 className="text-lg font-bold text-slate-900">Hesap Durumu</h2>
          <p className="mt-1 text-sm text-slate-600">
            Profil doluluk oranı ve başlangıç adımları.
          </p>
          <div className="mt-4">
            <div className="mb-2 flex items-center justify-between text-xs text-slate-600">
              <span>Profil tamamlanma</span>
              <span className="font-semibold text-slate-900">%{completionPercent}</span>
            </div>
            <div className="tb-progress">
              <div
                className="tb-progress-bar"
                style={{ width: `${Math.min(100, Math.max(0, completionPercent))}%` }}
              />
            </div>
          </div>
          <div className="mt-4 grid gap-2">
            {checklist.map((item) => (
              <div
                key={item.text}
                className={`tb-soft-card ${item.done ? "border-emerald-200 bg-emerald-50/40" : ""}`}
              >
                <p className="text-sm font-semibold text-slate-900">{item.text}</p>
                <p className="mt-1 text-xs text-slate-600">
                  {item.done ? "Tamamlandı" : "Devam ediyor"}
                </p>
              </div>
            ))}
          </div>
          <div className="mt-4">
            <span className="tb-chip">Kayıtlı Ev: {homes.length}</span>
          </div>
          <div className="mt-2">
            <span className="tb-chip">Guven Skoru: %{trustScore}</span>
            <p className="mt-1 text-xs text-slate-600">{trustLabel}</p>
          </div>
        </article>
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={dashboardAdSlot}
          label="Sponsorlu Panel Alani"
          minHeight={138}
        />
      </section>

      <section className="tb-surface tb-surface-pad">
        <div className="flex items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-slate-900">Son İlanlarım</h2>
          <Link href="/listings/new" className="tb-btn-secondary">
            Yeni İlan Aç
          </Link>
        </div>
        <div className="mt-3 grid gap-2">
          {recentListings.length === 0 ? (
            <p className="tb-empty">Henüz ilan yok.</p>
          ) : (
            recentListings.map((listing) => (
              <Link
                key={listing.id}
                href={`/listings/${listing.id}`}
                className="rounded-[8px] border border-[#dbe6df] p-3 transition hover:border-emerald-300 hover:bg-emerald-50/35"
              >
                <p className="font-semibold text-slate-900">{listing.title}</p>
                <p className="text-sm text-slate-600">
                  {listing.city ?? "-"} - Teklif: {listing.offer_count ?? 0}
                </p>
              </Link>
            ))
          )}
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <div className="flex items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-slate-900">Son Mesaj Hareketi</h2>
          <Link href="/messages" className="tb-btn-secondary">
            Tum Mesajlar
          </Link>
        </div>
        <div className="mt-3 grid gap-2">
          {recentThreads.length === 0 ? (
            <p className="tb-empty">Henuz mesajlasma yok.</p>
          ) : (
            recentThreads.map((thread) => (
              <Link
                key={thread.thread_key}
                href={`/messages?listing_id=${thread.listing_id}&contact_id=${thread.contact_id}`}
                className="rounded-[8px] border border-[#dbe6df] p-3 transition hover:border-emerald-300 hover:bg-emerald-50/35"
              >
                <div className="flex items-center justify-between gap-2">
                  <p className="truncate font-semibold text-slate-900">
                    {thread.contact_name}
                  </p>
                  {thread.unread_count > 0 ? (
                    <span className="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                      {thread.unread_count}
                    </span>
                  ) : null}
                </div>
                <p className="mt-1 truncate text-xs text-slate-500">
                  {thread.listing_title}
                </p>
                <p className="mt-1 line-clamp-1 text-sm text-slate-700">
                  {thread.last_message}
                </p>
              </Link>
            ))
          )}
        </div>
      </section>
    </div>
  );
}
