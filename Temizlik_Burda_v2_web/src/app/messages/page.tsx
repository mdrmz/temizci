"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { FormEvent, useCallback, useEffect, useMemo, useRef, useState } from "react";

import { AdSlot } from "@/components/ad-slot";
import { getConversation, getMessageThreads, sendMessage } from "@/lib/api";
import { ChatMessage, ConversationMeta, MessageThread } from "@/lib/types";
import { useSession } from "@/lib/use-session";

function toPositiveInt(value: string | null): number {
  if (!value) return 0;
  const parsed = Number.parseInt(value, 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}

function formatDateTime(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString("tr-TR", {
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function buildInitials(name: string): string {
  const normalized = name.trim();
  if (!normalized) return "?";
  const parts = normalized.split(/\s+/).slice(0, 2);
  return parts.map((part) => part[0]?.toUpperCase() ?? "").join("") || "?";
}

interface ActiveConversation {
  listingId: number;
  contactId: number;
}

export default function MessagesPage() {
  const messagesAdSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_MESSAGES ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    "";

  const { ready, session } = useSession(true);
  const searchParams = useSearchParams();

  const queryListingId = toPositiveInt(searchParams.get("listing_id"));
  const queryContactId = toPositiveInt(
    searchParams.get("contact_id") ?? searchParams.get("user_id"),
  );

  const [threads, setThreads] = useState<MessageThread[]>([]);
  const [active, setActive] = useState<ActiveConversation | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [meta, setMeta] = useState<ConversationMeta | null>(null);
  const [draft, setDraft] = useState("");
  const [threadQuery, setThreadQuery] = useState("");
  const [threadFilter, setThreadFilter] = useState<"all" | "unread">("all");
  const [lastSyncAt, setLastSyncAt] = useState("");
  const [loadingThreads, setLoadingThreads] = useState(true);
  const [loadingConversation, setLoadingConversation] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const messagesPaneRef = useRef<HTMLDivElement | null>(null);

  const activeKey = active ? `${active.listingId}_${active.contactId}` : "";

  const syncThreads = useCallback(async () => {
    if (!session) return;
    const response = await getMessageThreads(session.token);
    setThreads(response.data);
  }, [session]);

  const loadConversation = useCallback(
    async (target: ActiveConversation, hard = false) => {
      if (!session) return;
      if (hard) {
        setLoadingConversation(true);
      }
      try {
        const response = await getConversation(
          session.token,
          target.listingId,
          target.contactId,
        );
        setMessages(response.data);
        setMeta(response.meta);
        setError("");
        setLastSyncAt(
          new Date().toLocaleTimeString("tr-TR", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
          }),
        );

        const threadKey = `${target.listingId}_${target.contactId}`;
        setThreads((prev) =>
          prev.map((thread) =>
            thread.thread_key === threadKey
              ? { ...thread, unread_count: 0 }
              : thread,
          ),
        );
      } catch (err) {
        setError(err instanceof Error ? err.message : "Konusma yuklenemedi.");
      } finally {
        setLoadingConversation(false);
      }
    },
    [session],
  );

  useEffect(() => {
    if (!ready || !session) return;

    setLoadingThreads(true);
    syncThreads()
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Mesaj listesi alinamadi.");
      })
      .finally(() => setLoadingThreads(false));
  }, [ready, session, syncThreads]);

  useEffect(() => {
    if (!ready || !session) return;
    if (queryListingId > 0 && queryContactId > 0) {
      setActive({ listingId: queryListingId, contactId: queryContactId });
      return;
    }
    if (!active && threads.length > 0) {
      setActive({
        listingId: threads[0].listing_id,
        contactId: threads[0].contact_id,
      });
    }
  }, [
    ready,
    session,
    queryListingId,
    queryContactId,
    threads,
    active,
  ]);

  useEffect(() => {
    if (!ready || !session || !active) return;
    void loadConversation(active, true);

    const timer = setInterval(() => {
      void loadConversation(active, false);
      void syncThreads().catch(() => {
        // Ignore polling errors; user-facing errors are shown in hard loads/sends.
      });
    }, 7000);

    return () => clearInterval(timer);
  }, [ready, session, active, loadConversation, syncThreads]);

  useEffect(() => {
    const pane = messagesPaneRef.current;
    if (!pane || loadingConversation) return;
    pane.scrollTop = pane.scrollHeight;
  }, [messages, loadingConversation, activeKey]);

  const threadMap = useMemo(() => {
    const map = new Map<string, MessageThread>();
    threads.forEach((thread) => map.set(thread.thread_key, thread));
    return map;
  }, [threads]);
  const unreadTotal = useMemo(
    () => threads.reduce((sum, thread) => sum + (thread.unread_count ?? 0), 0),
    [threads],
  );
  const visibleThreads = useMemo(() => {
    const q = threadQuery.trim().toLowerCase();
    return threads.filter((thread) => {
      if (threadFilter === "unread" && (thread.unread_count ?? 0) === 0) {
        return false;
      }
      if (!q) return true;
      return (
        thread.contact_name.toLowerCase().includes(q) ||
        thread.listing_title.toLowerCase().includes(q)
      );
    });
  }, [threads, threadFilter, threadQuery]);

  const activeThread = active ? threadMap.get(activeKey) ?? null : null;
  const activeAvatarUrl =
    meta?.contact_avatar_url ?? activeThread?.contact_avatar_url ?? null;
  const listingStatusLabel = useMemo(() => {
    const status = (activeThread?.listing_status ?? "").toLowerCase();
    if (status === "closed" || status === "completed" || status === "done") {
      return "Ilan kapandi";
    }
    if (status === "in_progress") {
      return "Ilan surecte";
    }
    if (status === "cancelled") {
      return "Ilan iptal edildi";
    }
    return "Ilan acik";
  }, [activeThread?.listing_status]);
  const quickReplies = useMemo(() => {
    if (session?.user.role === "homeowner") {
      return [
        "Merhaba, teklifinizi inceledim. Uygun musunuz?",
        "Detaylari netlestirelim, musait oldugunuz saat nedir?",
        "Is kapsamini onayliyorum, baslangic tarihini paylasir misiniz?",
      ];
    }
    return [
      "Merhaba, ilani inceledim. Bugun musaitim.",
      "Isterseniz once kisa bir plan cikaralim.",
      "Temizlik sureci icin gereken ekipmanlar hazir.",
    ];
  }, [session?.user.role]);

  async function handleSend(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!session || !active) return;
    const text = draft.trim();
    if (!text) return;

    setSending(true);
    try {
      const response = await sendMessage(session.token, {
        listing_id: active.listingId,
        receiver_id: active.contactId,
        message: text,
      });
      setDraft("");
      setMessages((prev) => [...prev, response.message]);
      setMeta(response.meta);
      setError("");

      const threadKey = `${active.listingId}_${active.contactId}`;
      setThreads((prev) => {
        const existing = prev.find((item) => item.thread_key === threadKey);
        const next: MessageThread = existing
          ? {
              ...existing,
              last_message: text,
              last_message_at: response.message.created_at,
              last_message_from_me: true,
              unread_count: 0,
            }
          : {
              thread_key: threadKey,
              listing_id: active.listingId,
              listing_title:
                response.meta.listing_title || `Ilan #${active.listingId}`,
              listing_status: "",
              contact_id: active.contactId,
              contact_name:
                response.meta.contact_name || `Kullanici #${active.contactId}`,
              contact_avatar_url: response.meta.contact_avatar_url ?? null,
              last_message: text,
              last_message_at: response.message.created_at,
              last_message_from_me: true,
              unread_count: 0,
            };
        return [next, ...prev.filter((item) => item.thread_key !== threadKey)];
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Mesaj gonderilemedi.");
    } finally {
      setSending(false);
    }
  }

  if (!ready || !session) {
    return (
      <div className="tb-surface tb-surface-pad text-sm text-slate-600">
        Yukleniyor...
      </div>
    );
  }

  return (
    <section className="tb-page">
      <div className="tb-surface tb-surface-pad">
        <h1 className="tb-heading">Mesajlar</h1>
        <p className="tb-subheading">
          Ilan bazli guvenli mesajlasma: sadece ilan sahibi ve teklif veren taraflar
          gorusebilir.
        </p>
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <span className="tb-chip">Okunmamis: {unreadTotal}</span>
          <span className="tb-chip">Son senkron: {lastSyncAt || "-"}</span>
          <button
            type="button"
            className="tb-btn-secondary"
            onClick={() => {
              if (!active) {
                void syncThreads();
                return;
              }
              void loadConversation(active, true);
              void syncThreads();
            }}
          >
            Simdi yenile
          </button>
        </div>
      </div>

      <div className="tb-surface tb-surface-pad">
        <AdSlot
          slot={messagesAdSlot}
          label="Sponsorlu Mesaj Alani"
          minHeight={126}
        />
      </div>

      {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}

      <div className="grid gap-4 xl:grid-cols-[0.92fr_1.08fr]">
        <aside className="tb-surface tb-surface-pad">
          <div className="mb-3 flex items-center justify-between gap-2">
            <h2 className="text-base font-bold text-slate-900">Konusmalar</h2>
            <div className="flex gap-2">
              <span className="tb-chip">Toplam: {threads.length}</span>
              <span className="tb-chip border-emerald-100 bg-emerald-50 text-emerald-700">
                Okunmamis: {unreadTotal}
              </span>
            </div>
          </div>
          <div className="mb-3 grid gap-2">
            <input
              value={threadQuery}
              onChange={(event) => setThreadQuery(event.target.value)}
              className="tb-input"
              placeholder="Kisi veya ilan ara..."
            />
            <div className="tb-segment">
              <button
                type="button"
                className={`tb-segment-btn ${threadFilter === "all" ? "tb-segment-btn-active" : ""}`}
                onClick={() => setThreadFilter("all")}
              >
                Tumu
              </button>
              <button
                type="button"
                className={`tb-segment-btn ${threadFilter === "unread" ? "tb-segment-btn-active" : ""}`}
                onClick={() => setThreadFilter("unread")}
              >
                Okunmamis
              </button>
            </div>
          </div>

          {loadingThreads ? (
            <p className="text-sm text-slate-600">Mesaj listesi yukleniyor...</p>
          ) : threads.length === 0 ? (
            <p className="tb-empty">
              Henuz mesajlasma baslamamis. Teklif verdigin ilanlardan veya gelen
              tekliflerden konusma acabilirsin.
            </p>
          ) : visibleThreads.length === 0 ? (
            <p className="tb-empty">Bu filtrede konusma bulunamadi.</p>
          ) : (
            <div className="grid gap-2">
              {visibleThreads.map((thread) => {
                const key = thread.thread_key;
                const selected = key === activeKey;
                return (
                  <button
                    key={key}
                    type="button"
                    onClick={() =>
                      setActive({
                        listingId: thread.listing_id,
                        contactId: thread.contact_id,
                      })
                    }
                    className={`w-full rounded-[8px] border p-3 text-left transition ${
                      selected
                        ? "border-emerald-300 bg-emerald-50/45"
                        : "border-[#dbe6df] bg-white hover:border-emerald-300 hover:bg-emerald-50/25"
                    }`}
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex min-w-0 items-start gap-2">
                        {thread.contact_avatar_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img
                            src={thread.contact_avatar_url}
                            alt={thread.contact_name}
                            className="mt-0.5 h-9 w-9 rounded-full border border-[#dbe6df] object-cover"
                          />
                        ) : (
                          <span className="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-100 bg-emerald-50 text-xs font-bold text-emerald-700">
                            {buildInitials(thread.contact_name)}
                          </span>
                        )}
                        <div className="min-w-0">
                          <p className="truncate text-sm font-semibold text-slate-900">
                            {thread.contact_name}
                          </p>
                          <p className="truncate text-xs text-slate-500">
                            {thread.listing_title}
                          </p>
                        </div>
                      </div>
                      {thread.unread_count > 0 ? (
                        <span className="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                          {thread.unread_count}
                        </span>
                      ) : null}
                    </div>
                    <p className="mt-2 line-clamp-1 text-xs text-slate-600">
                      {thread.last_message}
                    </p>
                    <p className="mt-1 text-[11px] text-slate-500">
                      {formatDateTime(thread.last_message_at)}
                    </p>
                  </button>
                );
              })}
            </div>
          )}
        </aside>

        <article className="tb-surface tb-surface-pad">
          {!active ? (
            <p className="tb-empty">
              Sol taraftan bir konusma secerek devam edebilirsin.
            </p>
          ) : (
            <>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-center gap-2">
                  {activeAvatarUrl ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={activeAvatarUrl}
                      alt={
                        meta?.contact_name ??
                        activeThread?.contact_name ??
                        `Kullanici ${active.contactId}`
                      }
                      className="h-9 w-9 rounded-full border border-[#dbe6df] object-cover"
                    />
                  ) : (
                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-emerald-100 bg-emerald-50 text-xs font-bold text-emerald-700">
                      {buildInitials(
                        meta?.contact_name ??
                          activeThread?.contact_name ??
                          `Kullanici ${active.contactId}`,
                      )}
                    </span>
                  )}
                  <div>
                    <p className="text-sm font-semibold text-slate-900">
                      {meta?.contact_name ??
                        activeThread?.contact_name ??
                        `Kullanici #${active.contactId}`}
                    </p>
                    <p className="text-xs text-slate-500">
                      {meta?.listing_title ??
                        activeThread?.listing_title ??
                        `Ilan #${active.listingId}`}
                    </p>
                  </div>
                </div>
                <Link
                  href={`/listings/${active.listingId}`}
                  className="tb-btn-secondary"
                >
                  Ilana Git
                </Link>
              </div>
              <div className="mt-3">
                <span className="tb-chip">{listingStatusLabel}</span>
              </div>

              <div
                ref={messagesPaneRef}
                className="mt-4 h-[380px] overflow-y-auto rounded-[8px] border border-[#dbe6df] bg-[#f8fcfa] p-3"
              >
                {loadingConversation ? (
                  <p className="text-sm text-slate-600">Konusma yukleniyor...</p>
                ) : messages.length === 0 ? (
                  <p className="tb-empty">
                    Bu ilan icin henuz mesaj yok. Ilk mesaji sen gonderebilirsin.
                  </p>
                ) : (
                  <div className="grid gap-2">
                    {messages.map((message) => (
                      <div
                        key={message.id}
                        className={`max-w-[86%] rounded-[8px] border px-3 py-2 text-sm ${
                          message.is_mine
                            ? "ml-auto border-emerald-200 bg-emerald-50 text-emerald-900"
                            : "border-slate-200 bg-white text-slate-800"
                        }`}
                      >
                        <p className="whitespace-pre-wrap">{message.message}</p>
                        <p className="mt-1 text-[11px] text-slate-500">
                          {formatDateTime(message.created_at)}
                        </p>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <form onSubmit={handleSend} className="mt-3 grid gap-2">
                <div className="flex flex-wrap gap-2">
                  {quickReplies.map((reply) => (
                    <button
                      key={reply}
                      type="button"
                      className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-1.5 text-left text-xs font-semibold text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50"
                      onClick={() => setDraft(reply)}
                    >
                      {reply}
                    </button>
                  ))}
                </div>
                <textarea
                  value={draft}
                  onChange={(event) => setDraft(event.target.value)}
                  rows={3}
                  maxLength={1200}
                  className="tb-textarea"
                  placeholder="Mesajini yaz..."
                />
                <div className="flex items-center justify-between gap-2">
                  <p className="text-xs text-slate-500">
                    {draft.trim().length}/1200 karakter
                  </p>
                  <button
                    type="submit"
                    disabled={sending || draft.trim() === ""}
                    className="tb-btn-primary"
                  >
                    {sending ? "Gonderiliyor..." : "Mesaj Gonder"}
                  </button>
                </div>
              </form>
            </>
          )}
        </article>
      </div>
    </section>
  );
}
