"use client";

import Link from "next/link";
import { FormEvent, useEffect, useMemo, useState } from "react";

import {
  AdApplication,
  AdminCategory,
  deleteAdminCategory,
  getAdApplications,
  getAdminOverview,
  getListings,
  getReports,
  getSellerApplications,
  getSupportTickets,
  ReportItem,
  ReportStatus,
  reviewSellerApplication,
  saveAdminCategory,
  SellerApplication,
  SupportTicket,
  updateAdminUser,
  updateReport,
  updateSupportTicket,
} from "@/lib/api";
import {
  formatDate,
  formatListingStatus,
  formatMoney,
  listingStatusClasses,
} from "@/lib/format";
import { Listing, User } from "@/lib/types";
import { useSession } from "@/lib/use-session";

type SupportStatus = "open" | "in_progress" | "closed";
type SupportPriority = "low" | "normal" | "high";
type SellerStatus =
  | "pending"
  | "under_review"
  | "approved"
  | "rejected"
  | "suspended";

function badgeClass(tone: "green" | "sky" | "amber" | "rose" | "slate") {
  const map = {
    green: "border-emerald-200 bg-emerald-50 text-emerald-700",
    sky: "border-sky-200 bg-sky-50 text-sky-700",
    amber: "border-amber-200 bg-amber-50 text-amber-700",
    rose: "border-rose-200 bg-rose-50 text-rose-700",
    slate: "border-slate-200 bg-slate-100 text-slate-700",
  };
  return map[tone];
}

function supportStatusLabel(status: SupportStatus) {
  if (status === "in_progress") return "Süreçte";
  if (status === "closed") return "Kapalı";
  return "Açık";
}

function priorityLabel(priority: SupportPriority) {
  if (priority === "high") return "Yüksek";
  if (priority === "low") return "Düşük";
  return "Normal";
}

function sellerStatusLabel(status: SellerStatus) {
  if (status === "approved") return "Onaylandı";
  if (status === "under_review") return "İncelemede";
  if (status === "rejected") return "Reddedildi";
  if (status === "suspended") return "Askıda";
  return "Beklemede";
}

function reportStatusLabel(status: ReportStatus) {
  if (status === "reviewing") return "İncelemede";
  if (status === "resolved") return "Çözüldü";
  if (status === "rejected") return "Reddedildi";
  return "Açık";
}

function reportReasonLabel(reason: ReportItem["reason"]) {
  if (reason === "spam") return "Spam";
  if (reason === "fraud") return "Dolandırıcılık";
  if (reason === "abuse") return "Rahatsız edici davranış";
  if (reason === "wrong_info") return "Yanlış bilgi";
  return "Diğer";
}

function adStatusLabel(status: AdApplication["status"]) {
  if (status === "contacted") return "İletişime geçildi";
  if (status === "converted") return "Dönüştü";
  if (status === "closed") return "Kapandı";
  return "Yeni";
}

interface SupportUpdateFormProps {
  ticket: SupportTicket;
  token: string;
  onSaved: (ticket: SupportTicket | null) => void;
  onError: (message: string) => void;
}

function SupportUpdateForm({
  ticket,
  token,
  onSaved,
  onError,
}: SupportUpdateFormProps) {
  const [status, setStatus] = useState<SupportStatus>(ticket.status);
  const [priority, setPriority] = useState<SupportPriority>(ticket.priority);
  const [adminNote, setAdminNote] = useState(ticket.admin_note ?? "");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setStatus(ticket.status);
    setPriority(ticket.priority);
    setAdminNote(ticket.admin_note ?? "");
  }, [ticket]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const response = await updateSupportTicket(token, {
        ticket_id: ticket.id,
        status,
        priority,
        admin_note: adminNote.trim(),
      });
      onSaved(response.ticket);
    } catch (error) {
      onError(error instanceof Error ? error.message : "Destek talebi güncellenemedi.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form className="mt-3 grid gap-2 md:grid-cols-[1fr_1fr_minmax(0,1.4fr)_auto]" onSubmit={handleSubmit}>
      <select className="tb-select" value={status} onChange={(event) => setStatus(event.target.value as SupportStatus)}>
        <option value="open">Açık</option>
        <option value="in_progress">Süreçte</option>
        <option value="closed">Kapalı</option>
      </select>
      <select className="tb-select" value={priority} onChange={(event) => setPriority(event.target.value as SupportPriority)}>
        <option value="low">Düşük</option>
        <option value="normal">Normal</option>
        <option value="high">Yüksek</option>
      </select>
      <input className="tb-input" value={adminNote} onChange={(event) => setAdminNote(event.target.value)} placeholder="Admin notu" />
      <button className="tb-btn-secondary" type="submit" disabled={saving}>
        {saving ? "Kaydediliyor" : "Kaydet"}
      </button>
    </form>
  );
}

interface SellerReviewFormProps {
  application: SellerApplication;
  token: string;
  onSaved: (application: SellerApplication) => void;
  onError: (message: string) => void;
}

function SellerReviewForm({
  application,
  token,
  onSaved,
  onError,
}: SellerReviewFormProps) {
  const [status, setStatus] = useState<SellerStatus>(application.verification_status);
  const [adminNote, setAdminNote] = useState(application.admin_note ?? "");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setStatus(application.verification_status);
    setAdminNote(application.admin_note ?? "");
  }, [application]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const response = await reviewSellerApplication(token, {
        application_id: application.id,
        status,
        admin_note: adminNote.trim(),
      });
      onSaved(response.application);
    } catch (error) {
      onError(error instanceof Error ? error.message : "Satıcı başvurusu güncellenemedi.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form className="mt-3 grid gap-2 md:grid-cols-[12rem_minmax(0,1fr)_auto]" onSubmit={handleSubmit}>
      <select className="tb-select" value={status} onChange={(event) => setStatus(event.target.value as SellerStatus)}>
        <option value="pending">Beklemede</option>
        <option value="under_review">İncelemede</option>
        <option value="approved">Onaylandı</option>
        <option value="rejected">Reddedildi</option>
        <option value="suspended">Askıda</option>
      </select>
      <input className="tb-input" value={adminNote} onChange={(event) => setAdminNote(event.target.value)} placeholder="Admin notu" />
      <button className="tb-btn-secondary" type="submit" disabled={saving}>
        {saving ? "Kaydediliyor" : "Kaydet"}
      </button>
    </form>
  );
}

interface ReportReviewFormProps {
  report: ReportItem;
  token: string;
  onSaved: (report: ReportItem) => void;
  onError: (message: string) => void;
}

function ReportReviewForm({ report, token, onSaved, onError }: ReportReviewFormProps) {
  const [status, setStatus] = useState<ReportStatus>(report.status);
  const [adminNote, setAdminNote] = useState(report.admin_note ?? "");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setStatus(report.status);
    setAdminNote(report.admin_note ?? "");
  }, [report]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const response = await updateReport(token, {
        report_id: report.id,
        status,
        admin_note: adminNote.trim(),
      });
      onSaved(response.report);
    } catch (error) {
      onError(error instanceof Error ? error.message : "Şikayet güncellenemedi.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form className="mt-3 grid gap-2 md:grid-cols-[12rem_minmax(0,1fr)_auto]" onSubmit={handleSubmit}>
      <select className="tb-select" value={status} onChange={(event) => setStatus(event.target.value as ReportStatus)}>
        <option value="open">Açık</option>
        <option value="reviewing">İncelemede</option>
        <option value="resolved">Çözüldü</option>
        <option value="rejected">Reddedildi</option>
      </select>
      <input className="tb-input" value={adminNote} onChange={(event) => setAdminNote(event.target.value)} placeholder="Admin notu" />
      <button className="tb-btn-secondary" type="submit" disabled={saving}>
        {saving ? "Kaydediliyor" : "Kaydet"}
      </button>
    </form>
  );
}

interface UserAdminFormProps {
  user: User;
  token: string;
  onSaved: (user: User) => void;
  onError: (message: string) => void;
}

function UserAdminForm({ user, token, onSaved, onError }: UserAdminFormProps) {
  const [role, setRole] = useState<"homeowner" | "worker">(
    user.role === "worker" ? "worker" : "homeowner",
  );
  const [isActive, setIsActive] = useState<0 | 1>((user.is_active ?? 1) === 1 ? 1 : 0);
  const [saving, setSaving] = useState(false);
  const isLockedAdmin = user.role === "admin";

  useEffect(() => {
    setRole(user.role === "worker" ? "worker" : "homeowner");
    setIsActive((user.is_active ?? 1) === 1 ? 1 : 0);
  }, [user]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (isLockedAdmin) return;
    setSaving(true);
    try {
      const response = await updateAdminUser(token, {
        user_id: user.id,
        role,
        is_active: isActive,
      });
      onSaved(response.user);
    } catch (error) {
      onError(error instanceof Error ? error.message : "Kullanıcı güncellenemedi.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form className="mt-3 grid gap-2 md:grid-cols-[1fr_1fr_auto]" onSubmit={handleSubmit}>
      <select className="tb-select" value={role} disabled={isLockedAdmin} onChange={(event) => setRole(event.target.value as "homeowner" | "worker")}>
        <option value="homeowner">Ev Sahibi</option>
        <option value="worker">Hizmet Veren</option>
      </select>
      <select className="tb-select" value={isActive} disabled={isLockedAdmin} onChange={(event) => setIsActive(Number(event.target.value) === 1 ? 1 : 0)}>
        <option value={1}>Aktif</option>
        <option value={0}>Pasif</option>
      </select>
      <button className="tb-btn-secondary" type="submit" disabled={saving || isLockedAdmin}>
        {isLockedAdmin ? "Kilitli" : saving ? "Kaydediliyor" : "Kaydet"}
      </button>
    </form>
  );
}

interface CategoryAdminFormProps {
  category?: AdminCategory;
  token: string;
  onSaved: (category: AdminCategory) => void;
  onDeleted?: (id: number) => void;
  onError: (message: string) => void;
}

function CategoryAdminForm({
  category,
  token,
  onSaved,
  onDeleted,
  onError,
}: CategoryAdminFormProps) {
  const [name, setName] = useState(category?.name ?? "");
  const [slug, setSlug] = useState(category?.slug ?? "");
  const [icon, setIcon] = useState(category?.icon ?? "📋");
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    setName(category?.name ?? "");
    setSlug(category?.slug ?? "");
    setIcon(category?.icon ?? "📋");
  }, [category]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const response = await saveAdminCategory(token, {
        id: category?.id,
        name: name.trim(),
        slug: slug.trim(),
        icon: icon.trim(),
      });
      onSaved(response.category);
      if (!category) {
        setName("");
        setSlug("");
        setIcon("📋");
      }
    } catch (error) {
      onError(error instanceof Error ? error.message : "Kategori kaydedilemedi.");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!category || !onDeleted) return;
    setDeleting(true);
    try {
      const response = await deleteAdminCategory(token, category.id);
      onDeleted(response.deleted_id);
    } catch (error) {
      onError(error instanceof Error ? error.message : "Kategori silinemedi.");
    } finally {
      setDeleting(false);
    }
  }

  return (
    <form className="grid gap-2 md:grid-cols-[4.5rem_minmax(0,1fr)_minmax(0,1fr)_auto_auto]" onSubmit={handleSubmit}>
      <input className="tb-input text-center" value={icon ?? ""} onChange={(event) => setIcon(event.target.value)} placeholder="İkon" />
      <input className="tb-input" value={name} onChange={(event) => setName(event.target.value)} placeholder="Kategori adı" required />
      <input className="tb-input" value={slug} onChange={(event) => setSlug(event.target.value)} placeholder="slug" required pattern="[a-z0-9-]{2,100}" />
      <button className="tb-btn-secondary" type="submit" disabled={saving}>
        {saving ? "Kaydediliyor" : category ? "Güncelle" : "Ekle"}
      </button>
      {category ? (
        <button className="tb-btn-secondary border-rose-200 text-rose-700" type="button" onClick={handleDelete} disabled={deleting}>
          {deleting ? "Siliniyor" : "Sil"}
        </button>
      ) : null}
    </form>
  );
}

export default function AdminPage() {
  const { ready, session } = useSession(true);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [info, setInfo] = useState("");
  const [users, setUsers] = useState<User[]>([]);
  const [listings, setListings] = useState<Listing[]>([]);
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [tickets, setTickets] = useState<SupportTicket[]>([]);
  const [reports, setReports] = useState<ReportItem[]>([]);
  const [sellerApplications, setSellerApplications] = useState<SellerApplication[]>([]);
  const [adApplications, setAdApplications] = useState<AdApplication[]>([]);

  const isAdmin = session?.user.role === "admin";

  useEffect(() => {
    if (!session || !isAdmin) {
      setLoading(false);
      return;
    }

    setLoading(true);
    setError("");
    Promise.all([
      getAdminOverview(session.token),
      getListings({ page: 1, status: "all", sort: "newest" }),
      getSupportTickets(session.token, { mode: "all", limit: 80 }),
      getReports(session.token, { mode: "all" }),
      getSellerApplications(session.token, { mode: "all", limit: 80 }),
      getAdApplications(session.token, { mode: "all", limit: 120 }),
    ])
      .then(([adminRes, listingRes, supportRes, reportRes, sellerRes, adsRes]) => {
        setUsers(adminRes.users);
        setCategories(adminRes.categories);
        setListings(listingRes.data);
        setTickets(supportRes.data);
        setReports(reportRes.data);
        setSellerApplications(sellerRes.data);
        setAdApplications(adsRes.data);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Admin verileri yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [session, isAdmin]);

  const summary = useMemo(() => {
    const openListings = listings.filter((listing) => (listing.status ?? "open") === "open").length;
    const totalBudget = listings.reduce((sum, listing) => sum + (listing.budget ?? 0), 0);
    const totalOffers = listings.reduce((sum, listing) => sum + (listing.offer_count ?? 0), 0);
    return {
      users: users.length,
      activeUsers: users.filter((user) => (user.is_active ?? 1) === 1).length,
      workers: users.filter((user) => user.role === "worker").length,
      listings: listings.length,
      openListings,
      categories: categories.length,
      tickets: tickets.length,
      openTickets: tickets.filter((ticket) => ticket.status !== "closed").length,
      reports: reports.length,
      openReports: reports.filter((report) => report.status === "open").length,
      sellerPending: sellerApplications.filter((item) =>
        item.verification_status === "pending" || item.verification_status === "under_review"
      ).length,
      adNew: adApplications.filter((item) => item.status === "new").length,
      avgBudget: listings.length > 0 ? Math.round(totalBudget / listings.length) : 0,
      totalOffers,
    };
  }, [adApplications, categories, listings, reports, sellerApplications, tickets, users]);

  if (!ready || loading) {
    return <div className="tb-surface tb-surface-pad text-sm text-slate-600">Admin paneli yükleniyor...</div>;
  }

  if (!session) {
    return <div className="tb-surface tb-surface-pad text-sm text-slate-600">Giriş sayfasına yönlendiriliyorsunuz...</div>;
  }

  if (!isAdmin) {
    return (
      <div className="tb-page">
        <section className="tb-surface tb-surface-pad">
          <h1 className="tb-heading">Admin Yetkisi Gerekli</h1>
          <p className="tb-subheading">Bu sayfaya yalnızca yönetici hesabı erişebilir.</p>
          <Link href="/dashboard" className="tb-btn-secondary mt-4">
            Panele Dön
          </Link>
        </section>
      </div>
    );
  }

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="tb-heading">TypeScript Admin Merkezi</h1>
            <p className="tb-subheading">
              İlan, destek, şikayet, satıcı doğrulama ve reklam başvurularını tek TS panelinden yönetin.
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link href="/guvenlik" className="tb-btn-secondary">Güvenlik</Link>
            <Link href="/destek" className="tb-btn-secondary">Destek</Link>
            <Link href="/satici" className="tb-btn-secondary">Satıcı</Link>
          </div>
        </div>
      </section>

      {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}
      {info ? <p className="tb-alert tb-alert-success">{info}</p> : null}

      <section className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div className="tb-stat">
          <p className="tb-stat-title">Kullanıcılar</p>
          <p className="tb-stat-value">{summary.users}</p>
          <p className="mt-1 text-xs text-slate-500">{summary.activeUsers} aktif, {summary.workers} hizmet veren</p>
        </div>
        <div className="tb-stat">
          <p className="tb-stat-title">Toplam ilan</p>
          <p className="tb-stat-value">{summary.listings}</p>
          <p className="mt-1 text-xs text-slate-500">{summary.openListings} açık ilan</p>
        </div>
        <div className="tb-stat">
          <p className="tb-stat-title">Destek kuyruğu</p>
          <p className="tb-stat-value">{summary.openTickets}</p>
          <p className="mt-1 text-xs text-slate-500">{summary.tickets} toplam talep</p>
        </div>
        <div className="tb-stat">
          <p className="tb-stat-title">Güvenlik</p>
          <p className="tb-stat-value">{summary.openReports + summary.sellerPending + summary.adNew}</p>
          <p className="mt-1 text-xs text-slate-500">Bekleyen operasyon</p>
        </div>
      </section>

      <section className="grid gap-3 lg:grid-cols-4">
        <a href="#kullanicilar" className="tb-soft-card">
          <p className="text-xs font-semibold uppercase text-slate-700">Kullanıcılar</p>
          <p className="mt-1 text-sm font-bold text-slate-900">Rol ve aktiflik yönetimi.</p>
        </a>
        <a href="#ilanlar" className="tb-soft-card">
          <p className="text-xs font-semibold uppercase text-emerald-700">İlan pazarı</p>
          <p className="mt-1 text-sm font-bold text-slate-900">{summary.totalOffers} teklif, ortalama {formatMoney(summary.avgBudget)}</p>
        </a>
        <a href="#destek" className="tb-soft-card">
          <p className="text-xs font-semibold uppercase text-sky-700">Destek</p>
          <p className="mt-1 text-sm font-bold text-slate-900">Açık talepleri durum ve önceliğe göre güncelle.</p>
        </a>
        <a href="#saticilar" className="tb-soft-card">
          <p className="text-xs font-semibold uppercase text-amber-700">Satıcı doğrulama</p>
          <p className="mt-1 text-sm font-bold text-slate-900">{summary.sellerPending} başvuru inceleme bekliyor.</p>
        </a>
      </section>

      <section id="kullanicilar" className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Kullanıcı Yönetimi</h2>
        <p className="tb-subheading">Kullanıcı rolünü ve hesap aktifliğini TS panelinden yönetin.</p>
        <div className="mt-3 grid gap-3 md:grid-cols-2">
          {users.length === 0 ? (
            <p className="tb-empty md:col-span-2">Kullanıcı bulunmuyor.</p>
          ) : (
            users.slice(0, 12).map((user) => (
              <article key={user.id} className="rounded-[8px] border border-[#dbe6df] bg-white p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p className="font-bold text-slate-900">{user.name}</p>
                    <p className="mt-1 text-xs text-slate-500">{user.email} - {user.city ?? "-"} - {formatDate(user.created_at)}</p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <span className={`rounded-md border px-2 py-1 text-xs font-bold ${user.role === "admin" ? badgeClass("sky") : user.role === "worker" ? badgeClass("amber") : badgeClass("green")}`}>
                      {user.role === "admin" ? "Admin" : user.role === "worker" ? "Hizmet Veren" : "Ev Sahibi"}
                    </span>
                    <span className={`rounded-md border px-2 py-1 text-xs font-bold ${(user.is_active ?? 1) === 1 ? badgeClass("green") : badgeClass("rose")}`}>
                      {(user.is_active ?? 1) === 1 ? "Aktif" : "Pasif"}
                    </span>
                  </div>
                </div>
                <UserAdminForm
                  user={user}
                  token={session.token}
                  onSaved={(updated) => {
                    setUsers((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
                    setInfo("Kullanıcı güncellendi.");
                    setError("");
                  }}
                  onError={(message) => {
                    setError(message);
                    setInfo("");
                  }}
                />
              </article>
            ))
          )}
        </div>
      </section>

      <section id="destek" className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-slate-900">Destek Yönetimi</h2>
          <Link href="/destek" className="text-sm font-semibold text-emerald-700">Tam destek ekranı</Link>
        </div>
        <div className="mt-3 grid gap-3">
          {tickets.length === 0 ? (
            <p className="tb-empty">Destek talebi bulunmuyor.</p>
          ) : (
            tickets.slice(0, 8).map((ticket) => (
              <article key={ticket.id} className="rounded-[8px] border border-[#dbe6df] bg-white p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p className="font-bold text-slate-900">#{ticket.id} {ticket.subject}</p>
                    <p className="mt-1 text-xs text-slate-500">{ticket.user_name} - {formatDate(ticket.created_at)} - {ticket.message_count} mesaj</p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <span className={`rounded-md border px-2 py-1 text-xs font-bold ${ticket.status === "closed" ? badgeClass("slate") : ticket.status === "in_progress" ? badgeClass("sky") : badgeClass("green")}`}>
                      {supportStatusLabel(ticket.status)}
                    </span>
                    <span className={`rounded-md border px-2 py-1 text-xs font-bold ${ticket.priority === "high" ? badgeClass("rose") : ticket.priority === "low" ? badgeClass("slate") : badgeClass("amber")}`}>
                      {priorityLabel(ticket.priority)}
                    </span>
                  </div>
                </div>
                <SupportUpdateForm
                  ticket={ticket}
                  token={session.token}
                  onSaved={(updated) => {
                    if (updated) {
                      setTickets((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
                    }
                    setInfo("Destek talebi güncellendi.");
                    setError("");
                  }}
                  onError={(message) => {
                    setError(message);
                    setInfo("");
                  }}
                />
              </article>
            ))
          )}
        </div>
      </section>

      <section id="guvenlik" className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-slate-900">Güvenlik ve Şikayetler</h2>
          <Link href="/guvenlik" className="text-sm font-semibold text-emerald-700">Tam güvenlik ekranı</Link>
        </div>
        <div className="mt-3 grid gap-3 md:grid-cols-2">
          {reports.length === 0 ? (
            <p className="tb-empty md:col-span-2">Şikayet bulunmuyor.</p>
          ) : (
            reports.slice(0, 8).map((report) => (
              <article key={report.id} className="rounded-[8px] border border-[#dbe6df] bg-white p-4">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <p className="font-bold text-slate-900">#{report.id} {reportReasonLabel(report.reason)}</p>
                    <p className="mt-1 text-xs text-slate-500">{report.reporter_name} - {formatDate(report.created_at)}</p>
                  </div>
                  <span className={`rounded-md border px-2 py-1 text-xs font-bold ${report.status === "open" ? badgeClass("amber") : report.status === "reviewing" ? badgeClass("sky") : report.status === "resolved" ? badgeClass("green") : badgeClass("rose")}`}>
                    {reportStatusLabel(report.status)}
                  </span>
                </div>
                <p className="mt-3 line-clamp-3 text-sm text-slate-700">{report.message}</p>
                <ReportReviewForm
                  report={report}
                  token={session.token}
                  onSaved={(updated) => {
                    setReports((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
                    setInfo("Şikayet güncellendi.");
                    setError("");
                  }}
                  onError={(message) => {
                    setError(message);
                    setInfo("");
                  }}
                />
              </article>
            ))
          )}
        </div>
      </section>

      <section id="saticilar" className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-slate-900">Satıcı Başvuruları</h2>
          <Link href="/satici" className="text-sm font-semibold text-emerald-700">Satıcı ekranı</Link>
        </div>
        <div className="mt-3 grid gap-3">
          {sellerApplications.length === 0 ? (
            <p className="tb-empty">Satıcı başvurusu bulunmuyor.</p>
          ) : (
            sellerApplications.slice(0, 8).map((application) => (
              <article key={application.id} className="rounded-[8px] border border-[#dbe6df] bg-white p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p className="font-bold text-slate-900">{application.full_name}</p>
                    <p className="mt-1 text-xs text-slate-500">{application.city ?? "-"} - {application.email} - {formatDate(application.created_at)}</p>
                    <p className="mt-2 text-sm text-slate-600">{application.service_categories.join(", ") || "Kategori girilmemiş"}</p>
                  </div>
                  <span className={`rounded-md border px-2 py-1 text-xs font-bold ${application.verification_status === "approved" ? badgeClass("green") : application.verification_status === "rejected" || application.verification_status === "suspended" ? badgeClass("rose") : badgeClass("amber")}`}>
                    {sellerStatusLabel(application.verification_status)}
                  </span>
                </div>
                <SellerReviewForm
                  application={application}
                  token={session.token}
                  onSaved={(updated) => {
                    setSellerApplications((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
                    setInfo("Satıcı başvurusu güncellendi.");
                    setError("");
                  }}
                  onError={(message) => {
                    setError(message);
                    setInfo("");
                  }}
                />
              </article>
            ))
          )}
        </div>
      </section>

      <section id="ilanlar" className="grid gap-3 xl:grid-cols-[1.25fr_0.75fr]">
        <article className="tb-surface tb-surface-pad">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-lg font-bold text-slate-900">Son İlanlar</h2>
            <Link href="/listings?status=all" className="text-sm font-semibold text-emerald-700">Tüm ilanlar</Link>
          </div>
          <div className="mt-3 grid gap-2">
            {listings.slice(0, 10).map((listing) => (
              <Link key={listing.id} href={`/listings/${listing.id}`} className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 transition hover:border-emerald-300 hover:bg-emerald-50/30">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div>
                    <p className="font-semibold text-slate-900">{listing.title}</p>
                    <p className="mt-1 text-xs text-slate-500">{listing.city ?? "-"} - {listing.cat_name ?? "Kategori yok"} - {formatDate(listing.created_at)}</p>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-bold text-emerald-700">{formatMoney(listing.budget)}</span>
                    <span className={`rounded-md border px-2 py-1 text-xs font-bold ${listingStatusClasses(listing.status)}`}>
                      {formatListingStatus(listing.status)}
                    </span>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        </article>

        <article className="tb-surface tb-surface-pad">
          <h2 className="text-lg font-bold text-slate-900">Kategoriler</h2>
          <p className="tb-subheading">Kategori ekle, düzenle veya boş kategorileri sil.</p>
          <div className="mt-3">
            <CategoryAdminForm
              token={session.token}
              onSaved={(category) => {
                setCategories((prev) => [category, ...prev.filter((item) => item.id !== category.id)]);
                setInfo("Kategori kaydedildi.");
                setError("");
              }}
              onError={(message) => {
                setError(message);
                setInfo("");
              }}
            />
          </div>
          <div className="mt-3 grid gap-3">
            {categories.slice(0, 12).map((category) => (
              <div key={category.id} className="rounded-[8px] border border-[#dbe6df] bg-white p-3">
                <div className="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                  <Link href={`/listings?cat=${encodeURIComponent(category.slug)}`} className="font-semibold text-emerald-700">
                    {category.listing_count} ilan
                  </Link>
                  <span>#{category.id}</span>
                </div>
                <CategoryAdminForm
                  category={category}
                  token={session.token}
                  onSaved={(updated) => {
                    setCategories((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
                    setInfo("Kategori güncellendi.");
                    setError("");
                  }}
                  onDeleted={(id) => {
                    setCategories((prev) => prev.filter((item) => item.id !== id));
                    setInfo("Kategori silindi.");
                    setError("");
                  }}
                  onError={(message) => {
                    setError(message);
                    setInfo("");
                  }}
                />
              </div>
            ))}
          </div>
        </article>
      </section>

      <section id="reklamlar" className="tb-surface tb-surface-pad">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-lg font-bold text-slate-900">Reklam Başvuruları</h2>
          <Link href="/reklam-ver" className="text-sm font-semibold text-emerald-700">Reklam sayfası</Link>
        </div>
        <div className="mt-3 grid gap-3 md:grid-cols-2">
          {adApplications.length === 0 ? (
            <p className="tb-empty md:col-span-2">Reklam başvurusu bulunmuyor.</p>
          ) : (
            adApplications.slice(0, 8).map((application) => (
              <article key={application.id} className="rounded-[8px] border border-[#dbe6df] bg-white p-4">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <p className="font-bold text-slate-900">{application.company_name}</p>
                    <p className="mt-1 text-xs text-slate-500">{application.contact_name} - {application.city ?? "-"} - {formatDate(application.created_at)}</p>
                  </div>
                  <span className={`rounded-md border px-2 py-1 text-xs font-bold ${application.status === "new" ? badgeClass("amber") : application.status === "converted" ? badgeClass("green") : application.status === "closed" ? badgeClass("slate") : badgeClass("sky")}`}>
                    {adStatusLabel(application.status)}
                  </span>
                </div>
                <p className="mt-3 text-sm text-slate-700">
                  Paket: <strong>{application.package_name}</strong>
                </p>
                <p className="mt-1 text-sm text-slate-600">
                  {application.email ?? "-"} - {application.phone ?? "-"}
                </p>
              </article>
            ))
          )}
        </div>
      </section>
    </div>
  );
}
