export function formatMoney(
  value?: number | null,
  fallback = "Teklif usulü",
): string {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return fallback;
  }
  return `${Number(value).toLocaleString("tr-TR")} TL`;
}

export function formatDate(value?: string | null): string {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString("tr-TR");
}

export function formatListingStatus(status?: string): string {
  const normalized = (status ?? "").toLowerCase();
  if (normalized === "open" || normalized === "active") return "Açık";
  if (normalized === "in_progress") return "Devam Ediyor";
  if (normalized === "completed" || normalized === "done") return "Tamamlandı";
  if (normalized === "cancelled" || normalized === "canceled") return "İptal";
  return status?.trim() ? status : "Açık";
}

export function listingStatusClasses(status?: string): string {
  const normalized = (status ?? "").toLowerCase();
  if (normalized === "open" || normalized === "active") {
    return "border-emerald-200 bg-emerald-50 text-emerald-700";
  }
  if (normalized === "in_progress") {
    return "border-sky-200 bg-sky-50 text-sky-700";
  }
  if (normalized === "completed" || normalized === "done") {
    return "border-slate-200 bg-slate-100 text-slate-700";
  }
  if (normalized === "cancelled" || normalized === "canceled") {
    return "border-rose-200 bg-rose-50 text-rose-700";
  }
  return "border-slate-200 bg-slate-100 text-slate-700";
}

export function formatOfferStatus(status?: string): string {
  const normalized = (status ?? "").toLowerCase();
  if (normalized === "accepted") return "Kabul";
  if (normalized === "rejected") return "Reddedildi";
  return "Beklemede";
}

export function offerStatusClasses(status?: string): string {
  const normalized = (status ?? "").toLowerCase();
  if (normalized === "accepted") {
    return "border-emerald-200 bg-emerald-50 text-emerald-700";
  }
  if (normalized === "rejected") {
    return "border-rose-200 bg-rose-50 text-rose-700";
  }
  return "border-amber-200 bg-amber-50 text-amber-700";
}
