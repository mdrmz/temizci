"use client";

import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { ReactNode, useEffect, useMemo, useState } from "react";

import { AdSlot } from "@/components/ad-slot";
import { getUnreadMessageCount } from "@/lib/api";
import { clearSession, getSession } from "@/lib/session";
import { Session } from "@/lib/types";

interface AppShellProps {
  children: ReactNode;
  initialEmbedded?: boolean;
}

interface NavItem {
  href: string;
  label: string;
  match: (pathname: string) => boolean;
}

function navClass(active: boolean): string {
  return active
    ? "border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.75)] font-semibold"
    : "border border-transparent text-slate-600 hover:border-slate-200 hover:bg-white hover:text-slate-900";
}

export function AppShell({ children, initialEmbedded = false }: AppShellProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [session, setSessionState] = useState<Session | null>(null);
  const [isEmbeddedApp, setIsEmbeddedApp] = useState(initialEmbedded);
  const [unreadMessages, setUnreadMessages] = useState(0);

  useEffect(() => {
    setSessionState(getSession());
  }, [pathname]);

  useEffect(() => {
    if (typeof window === "undefined") return;

    if (initialEmbedded) {
      window.sessionStorage.setItem("tb_embedded", "1");
      setIsEmbeddedApp(true);
      return;
    }

    const query = new URLSearchParams(window.location.search);
    const appFromQuery = query.get("app") === "1";
    const appFromUserAgent = window.navigator.userAgent.includes("TemizciBuradaApp");

    if (appFromQuery || appFromUserAgent) {
      window.sessionStorage.setItem("tb_embedded", "1");
      setIsEmbeddedApp(true);
      return;
    }

    const persistedMode = window.sessionStorage.getItem("tb_embedded") === "1";
    setIsEmbeddedApp(persistedMode);
  }, [initialEmbedded, pathname]);

  useEffect(() => {
    if (!session) {
      setUnreadMessages(0);
      return;
    }

    let cancelled = false;
    const fetchUnread = async () => {
      try {
        const response = await getUnreadMessageCount(session.token);
        if (!cancelled) {
          setUnreadMessages(Math.max(0, response.count ?? 0));
        }
      } catch {
        if (!cancelled) {
          setUnreadMessages(0);
        }
      }
    };

    void fetchUnread();
    const timer = setInterval(() => {
      void fetchUnread();
    }, 25000);

    return () => {
      cancelled = true;
      clearInterval(timer);
    };
  }, [session, pathname]);

  const userLinks = useMemo<NavItem[]>(
    () => [
      {
        href: "/dashboard",
        label: "Panel",
        match: (path) => path === "/dashboard",
      },
      {
        href: "/listings",
        label: "İlanlar",
        match: (path) => path === "/listings" || /^\/listings\/\d+$/.test(path),
      },
      {
        href: "/offers",
        label: "Teklifler",
        match: (path) => path === "/offers",
      },
      {
        href: "/messages",
        label: "Mesajlar",
        match: (path) => path === "/messages",
      },
      {
        href: "/homes",
        label: "Evlerim",
        match: (path) => path === "/homes",
      },
      {
        href: "/listings/new",
        label: "İlan Aç",
        match: (path) => path === "/listings/new",
      },
      {
        href: "/profile",
        label: "Profil",
        match: (path) => path === "/profile",
      },
    ],
    [],
  );

  const guestLinks = useMemo<NavItem[]>(
    () => [
      {
        href: "/",
        label: "Ana Sayfa",
        match: (path) => path === "/",
      },
      {
        href: "/listings",
        label: "İlanlar",
        match: (path) => path === "/listings" || /^\/listings\/\d+$/.test(path),
      },
      {
        href: "/reklam-ver",
        label: "Reklam",
        match: (path) => path === "/reklam-ver",
      },
      {
        href: "/register",
        label: "Kayıt",
        match: (path) => path === "/register",
      },
      {
        href: "/login",
        label: "Giriş",
        match: (path) => path === "/login",
      },
    ],
    [],
  );

  const visibleLinks = session ? userLinks : guestLinks;

  const quickAction =
    session?.user.role === "homeowner"
      ? { href: "/listings/new", label: "Yeni İlan" }
      : session?.user.role === "worker"
        ? { href: "/listings", label: "İş Bul" }
        : null;

  const roleLabel =
    session?.user.role === "homeowner"
      ? "Ev Sahibi"
      : session?.user.role === "worker"
        ? "Hizmet Veren"
        : session?.user.role === "admin"
          ? "Admin"
          : "";

  const appHref = (href: string): string => {
    if (!isEmbeddedApp || href.startsWith("http")) return href;
    if (/[?&]app=1(?:&|$)/.test(href)) return href;
    return href.includes("?") ? `${href}&app=1` : `${href}?app=1`;
  };

  const unreadBadgeLabel = unreadMessages > 99 ? "99+" : String(unreadMessages);
  const showMessageBadge = Boolean(session) && unreadMessages > 0;
  const sideLeftSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_SIDE_LEFT ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_LEFT ??
    "";
  const sideRightSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_SIDE_RIGHT ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_RIGHT ??
    "";

  return (
    <div className="flex min-h-screen flex-col bg-transparent text-slate-900">
      {!isEmbeddedApp ? (
        <header className="sticky top-0 z-30 border-b border-[#dbe6df] bg-white/90 backdrop-blur-md">
          <div className="h-px bg-gradient-to-r from-emerald-500/70 via-sky-500/60 to-amber-400/70" />
          <div className="mx-auto flex h-16 w-full max-w-[88rem] items-center justify-between gap-3 px-4 md:h-[4.3rem]">
            <Link className="group flex min-w-[200px] items-center gap-2.5" href={appHref("/")}>
              <span className="relative h-9 w-9 overflow-hidden rounded-[8px] border border-emerald-100 bg-white shadow-sm">
                <Image
                  src="/logo.png"
                  alt="TemizciBurada"
                  fill
                  sizes="36px"
                  className="object-cover"
                />
              </span>
              <span className="leading-tight">
                <span className="block text-[0.98rem] font-extrabold tracking-tight text-slate-900">
                  TemizciBurada
                </span>
                <span className="hidden text-[0.7rem] font-medium text-slate-500 sm:block">
                  Hızlı ilan, net teklif akışı
                </span>
              </span>
            </Link>

            <nav className="hidden items-center gap-2 text-sm lg:flex">
              {visibleLinks.map((item) => {
                const isMessages = item.href === "/messages";
                return (
                  <Link
                    key={item.href}
                    href={appHref(item.href)}
                    className={`rounded-[8px] px-3 py-2 transition ${navClass(item.match(pathname))}`}
                    aria-current={item.match(pathname) ? "page" : undefined}
                    aria-label={
                      isMessages && showMessageBadge
                        ? `Mesajlar, ${unreadMessages} okunmamis`
                        : undefined
                    }
                  >
                    <span className="inline-flex items-center gap-1.5">
                      <span>{item.label}</span>
                      {isMessages && showMessageBadge ? (
                        <span className="rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">
                          {unreadBadgeLabel}
                        </span>
                      ) : null}
                    </span>
                  </Link>
                );
              })}
            </nav>
            <div className="flex items-center gap-2">
              {session ? (
                <>
                  {roleLabel ? (
                    <span className="hidden rounded-[8px] border border-emerald-100 bg-emerald-50/70 px-2 py-1 text-xs font-semibold text-emerald-700 md:inline">
                      {roleLabel}
                    </span>
                  ) : null}
                  <span className="hidden text-sm font-medium text-slate-700 md:inline">
                    {session.user.name}
                  </span>
                  {quickAction ? (
                    <Link href={appHref(quickAction.href)} className="tb-btn-primary">
                      {quickAction.label}
                    </Link>
                  ) : null}
                  <button
                    type="button"
                    className="tb-btn-secondary"
                    onClick={() => {
                      clearSession();
                      setSessionState(null);
                      setUnreadMessages(0);
                      router.push("/login");
                    }}
                  >
                    Çıkış
                  </button>
                </>
              ) : (
                <>
                  <Link href={appHref("/listings/new")} className="tb-btn-primary">
                    İlan Ver
                  </Link>
                  <Link href={appHref("/login")} className="tb-btn-secondary">
                    Giriş
                  </Link>
                  <Link href={appHref("/register")} className="hidden tb-btn-secondary md:inline-flex">
                    Kayıt
                  </Link>
                </>
              )}
            </div>
          </div>

          <div className="border-t border-[#e5ece8] px-4 py-2 lg:hidden">
            <nav className="mx-auto flex w-full max-w-[88rem] gap-2 overflow-x-auto pb-1 text-sm">
              {visibleLinks.map((item) => {
                const isMessages = item.href === "/messages";
                return (
                  <Link
                    key={`mobile-${item.href}`}
                    href={appHref(item.href)}
                    className={`whitespace-nowrap rounded-[8px] px-3 py-2 transition ${navClass(item.match(pathname))}`}
                    aria-current={item.match(pathname) ? "page" : undefined}
                  >
                    <span className="inline-flex items-center gap-1.5">
                      <span>{item.label}</span>
                      {isMessages && showMessageBadge ? (
                        <span className="rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">
                          {unreadBadgeLabel}
                        </span>
                      ) : null}
                    </span>
                  </Link>
                );
              })}
            </nav>
          </div>
        </header>
      ) : null}

      <main
        className={`mx-auto w-full max-w-[88rem] flex-1 px-4 py-6 ${isEmbeddedApp ? "pb-6 lg:pb-6" : "pb-24 lg:pb-7"}`}
      >
        {isEmbeddedApp ? (
          children
        ) : (
          <div className="grid gap-4 xl:grid-cols-[11rem_minmax(0,1fr)_11rem]">
            <aside className="hidden xl:block">
              <div className="sticky top-24">
                <AdSlot
                  slot={sideLeftSlot}
                  label="Sol Reklam Alani"
                  minHeight={620}
                />
              </div>
            </aside>

            <div>{children}</div>

            <aside className="hidden xl:block">
              <div className="sticky top-24">
                <AdSlot
                  slot={sideRightSlot}
                  label="Sag Reklam Alani"
                  minHeight={620}
                />
              </div>
            </aside>
          </div>
        )}
      </main>

      {!isEmbeddedApp ? (
        <footer className="border-t border-[#dbe6df] bg-white/92">
          <div className="mx-auto flex w-full max-w-[88rem] flex-col gap-2 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-slate-500">
              TemizciBurada • Web, iOS ve Android akışları birlikte güncelleniyor.
            </p>
            <div className="flex items-center gap-2">
              <Link className="font-semibold text-slate-600 hover:text-emerald-700" href={appHref("/listings")}>
                İlanlar
              </Link>
              <span>•</span>
              <Link className="font-semibold text-slate-600 hover:text-emerald-700" href={appHref("/reklam-ver")}>
                Reklam
              </Link>
              <span>•</span>
              <Link className="font-semibold text-slate-600 hover:text-emerald-700" href={appHref("/dashboard")}>
                Panel
              </Link>
            </div>
          </div>
        </footer>
      ) : null}

      {!isEmbeddedApp ? (
        <div className="fixed inset-x-0 bottom-0 z-40 border-t border-[#dbe6df] bg-white/92 backdrop-blur-lg lg:hidden">
          <nav className={`mx-auto grid w-full max-w-[88rem] gap-2 px-4 py-2 ${session ? "grid-cols-5" : "grid-cols-4"}`}>
            <Link
              href={appHref("/")}
              className={`rounded-[8px] px-2 py-2 text-center text-xs font-semibold ${navClass(pathname === "/")}`}
            >
              Ana Sayfa
            </Link>
            <Link
              href={appHref("/listings")}
              className={`rounded-[8px] px-2 py-2 text-center text-xs font-semibold ${navClass(pathname === "/listings" || /^\/listings\/\d+$/.test(pathname))}`}
            >
              İlanlar
            </Link>
            {session ? (
              <Link
                href={appHref("/messages")}
                className={`rounded-[8px] px-2 py-2 text-center text-xs font-semibold ${navClass(pathname === "/messages")}`}
              >
                <span className="inline-flex items-center justify-center gap-1">
                  <span>Mesaj</span>
                  {showMessageBadge ? (
                    <span className="rounded-md border border-emerald-200 bg-emerald-50 px-1 py-0.5 text-[10px] font-bold text-emerald-700">
                      {unreadBadgeLabel}
                    </span>
                  ) : null}
                </span>
              </Link>
            ) : null}
            <Link
              href={appHref("/listings/new")}
              className="rounded-[8px] border border-emerald-300 bg-emerald-50 px-2 py-2 text-center text-xs font-semibold text-emerald-700"
            >
              İlan Ver
            </Link>
            <Link
              href={appHref(session ? "/dashboard" : "/login")}
              className={`rounded-[8px] px-2 py-2 text-center text-xs font-semibold ${navClass(pathname === "/dashboard" || pathname === "/login")}`}
            >
              {session ? "Panel" : "Giriş"}
            </Link>
          </nav>
        </div>
      ) : null}
    </div>
  );
}
