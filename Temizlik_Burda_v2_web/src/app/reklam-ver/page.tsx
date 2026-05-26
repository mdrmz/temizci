"use client";

import Link from "next/link";
import { FormEvent, useEffect, useMemo, useState } from "react";

import { AdSlot } from "@/components/ad-slot";
import { getSession } from "@/lib/session";

type PackageKey = "baslangic" | "buyume" | "premium";

const packageOptions: Array<{
  key: PackageKey;
  name: string;
  reach: string;
  duration: string;
  support: string;
}> = [
  {
    key: "baslangic",
    name: "Baslangic",
    reach: "Ana sayfa orta sponsor alani",
    duration: "7 gun",
    support: "Standart destek",
  },
  {
    key: "buyume",
    name: "Buyume",
    reach: "Ana sayfa + teklifler + profil sponsor alanlari",
    duration: "14 gun",
    support: "Oncelikli destek",
  },
  {
    key: "premium",
    name: "Premium",
    reach: "Yan reklam raylari + tum sponsor bloklari",
    duration: "30 gun",
    support: "Hizli yayina alma",
  },
];

const placements = [
  "Ana sayfa sponsor alani",
  "Masaustu sol reklam rayi",
  "Masaustu sag reklam rayi",
  "Teklifler sayfasi sponsor alani",
  "Profil sayfasi sponsor alani",
];

export default function AdvertisePage() {
  const previewSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_TOP ??
    "";
  const salesWhatsAppRaw = (process.env.NEXT_PUBLIC_AD_SALES_WHATSAPP ?? "").trim();
  const salesWhatsAppDigits = salesWhatsAppRaw.replace(/\D/g, "");
  const salesEmail = (process.env.NEXT_PUBLIC_AD_SALES_EMAIL ?? "reklam@temizciburada.com").trim();
  const salesPhone = (process.env.NEXT_PUBLIC_AD_SALES_PHONE ?? "").trim();

  const [selectedPackage, setSelectedPackage] = useState<PackageKey>("buyume");
  const [companyName, setCompanyName] = useState("");
  const [contactName, setContactName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [city, setCity] = useState("");
  const [monthlyBudget, setMonthlyBudget] = useState("");
  const [note, setNote] = useState("");
  const [preferredChannel, setPreferredChannel] = useState<"whatsapp" | "email">(
    "whatsapp",
  );
  const [error, setError] = useState("");
  const [info, setInfo] = useState("");
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    const stored = getSession();
    if (!stored) return;
    setContactName((prev) => prev || stored.user.name || "");
    setEmail((prev) => prev || stored.user.email || "");
    setPhone((prev) => prev || stored.user.phone || "");
    setCity((prev) => prev || stored.user.city || "");
  }, []);

  const selectedPackageMeta =
    packageOptions.find((option) => option.key === selectedPackage) ?? packageOptions[1];

  const applicationText = useMemo(() => {
    const lines = [
      "Merhaba, TemizciBurada reklam basvurusu icin yaziyorum.",
      `Paket: ${selectedPackageMeta.name}`,
      `Firma: ${companyName.trim() || "-"}`,
      `Yetkili: ${contactName.trim() || "-"}`,
      `Telefon: ${phone.trim() || "-"}`,
      `E-posta: ${email.trim() || "-"}`,
      `Sehir: ${city.trim() || "-"}`,
      `Aylik Butce: ${monthlyBudget.trim() || "-"}`,
      note.trim() ? `Not: ${note.trim()}` : "Not: -",
    ];
    return lines.join("\n");
  }, [
    city,
    companyName,
    contactName,
    email,
    monthlyBudget,
    note,
    phone,
    selectedPackageMeta.name,
  ]);

  const whatsappUrl = useMemo(() => {
    if (!salesWhatsAppDigits) return "";
    return `https://wa.me/${salesWhatsAppDigits}?text=${encodeURIComponent(applicationText)}`;
  }, [applicationText, salesWhatsAppDigits]);

  const mailtoUrl = useMemo(() => {
    if (!salesEmail) return "";
    const subject = `TemizciBurada Reklam Basvurusu - ${selectedPackageMeta.name}`;
    const body = applicationText.replace(/\n/g, "\r\n");
    return `mailto:${salesEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  }, [applicationText, salesEmail, selectedPackageMeta.name]);

  const canSubmit =
    companyName.trim() !== "" &&
    contactName.trim() !== "" &&
    (phone.trim() !== "" || email.trim() !== "");

  async function copyDraftText() {
    try {
      await navigator.clipboard.writeText(applicationText);
      setCopied(true);
      setTimeout(() => setCopied(false), 1600);
    } catch {
      setError("Metin panoya kopyalanamadi. Tarayici izinlerini kontrol edin.");
    }
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setInfo("");

    if (!canSubmit) {
      setError("Firma, yetkili ve en az bir iletisim alani zorunludur.");
      return;
    }

    if (preferredChannel === "whatsapp") {
      if (!whatsappUrl) {
        setError("WhatsApp hatti ayarlanmamis. E-posta kanalini secip devam edin.");
        return;
      }
      window.open(whatsappUrl, "_blank", "noopener,noreferrer");
      setInfo("WhatsApp basvuru penceresi acildi.");
      return;
    }

    if (!mailtoUrl) {
      setError("E-posta hatti ayarlanmamis. WhatsApp kanalini secip devam edin.");
      return;
    }

    window.location.href = mailtoUrl;
    setInfo("E-posta taslagi acildi.");
  }

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <p className="text-xs font-semibold tracking-wide text-emerald-700">
          REKLAM IS ORTAKLIGI
        </p>
        <h1 className="tb-heading mt-1">TemizciBurada Reklam Alanlari</h1>
        <p className="tb-subheading max-w-3xl">
          Markani aktif ilan akisinda, dogrudan hedef kitleye gosterebilirsin.
          Yayinlar masaustu ve mobil duzende gorunur.
        </p>
        <div className="mt-4 flex flex-wrap gap-2">
          <Link href="/register" className="tb-btn-secondary">
            Is Ortagi Hesabi Ac
          </Link>
          <Link href="/login" className="tb-btn-secondary">
            Hesabimdan Devam Et
          </Link>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Paket Secimi</h2>
        <p className="mt-1 text-sm text-slate-600">
          Ihtiyacina uygun paket secimini yapip basvuru metnini otomatik olustur.
        </p>
        <div className="mt-3 grid gap-2 md:grid-cols-3">
          {packageOptions.map((item) => (
            <button
              key={item.key}
              type="button"
              onClick={() => setSelectedPackage(item.key)}
              className={`rounded-[8px] border p-3 text-left transition ${
                selectedPackage === item.key
                  ? "border-emerald-300 bg-emerald-50/65"
                  : "border-[#dbe6df] bg-white hover:border-emerald-200"
              }`}
            >
              <p className="text-sm font-bold text-slate-900">{item.name}</p>
              <p className="mt-1 text-xs text-slate-600">{item.reach}</p>
              <p className="mt-2 text-xs text-emerald-700">Sure: {item.duration}</p>
              <p className="mt-1 text-xs text-slate-600">Destek: {item.support}</p>
            </button>
          ))}
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Basvuru Formu</h2>
        <form className="mt-3 grid gap-3" onSubmit={handleSubmit}>
          <div className="grid gap-3 md:grid-cols-2">
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-600">
                Firma Adi
              </label>
              <input
                value={companyName}
                onChange={(event) => setCompanyName(event.target.value)}
                className="tb-input"
                placeholder="Ornek Temizlik A.S."
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-600">
                Yetkili Ad Soyad
              </label>
              <input
                value={contactName}
                onChange={(event) => setContactName(event.target.value)}
                className="tb-input"
                placeholder="Ad Soyad"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-600">
                Telefon
              </label>
              <input
                value={phone}
                onChange={(event) => setPhone(event.target.value)}
                className="tb-input"
                placeholder="+90 5xx xxx xx xx"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-600">
                E-posta
              </label>
              <input
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="tb-input"
                placeholder="ad@firma.com"
                type="email"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-600">
                Sehir
              </label>
              <input
                value={city}
                onChange={(event) => setCity(event.target.value)}
                className="tb-input"
                placeholder="Istanbul"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-600">
                Aylik Butce (TL)
              </label>
              <input
                value={monthlyBudget}
                onChange={(event) => setMonthlyBudget(event.target.value)}
                className="tb-input"
                placeholder="50000"
                inputMode="numeric"
              />
            </div>
          </div>

          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">
              Ek Not
            </label>
            <textarea
              value={note}
              onChange={(event) => setNote(event.target.value)}
              className="tb-textarea"
              placeholder="Hedef sektor, kampanya tarihi veya ozel talep yazabilirsiniz."
              rows={4}
            />
          </div>

          <div>
            <p className="mb-1 text-xs font-semibold text-slate-600">Tercih Edilen Kanal</p>
            <div className="tb-segment">
              <button
                type="button"
                onClick={() => setPreferredChannel("whatsapp")}
                className={`tb-segment-btn ${
                  preferredChannel === "whatsapp" ? "tb-segment-btn-active" : ""
                }`}
              >
                WhatsApp
              </button>
              <button
                type="button"
                onClick={() => setPreferredChannel("email")}
                className={`tb-segment-btn ${
                  preferredChannel === "email" ? "tb-segment-btn-active" : ""
                }`}
              >
                E-posta
              </button>
            </div>
          </div>

          <div className="rounded-[8px] border border-[#dbe6df] bg-slate-50 p-3">
            <p className="text-xs font-semibold text-slate-700">Basvuru Ozeti</p>
            <pre className="mt-2 whitespace-pre-wrap text-xs text-slate-700">
              {applicationText}
            </pre>
          </div>

          {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}
          {info ? <p className="tb-alert tb-alert-success">{info}</p> : null}

          <div className="flex flex-wrap gap-2">
            <button type="submit" className="tb-btn-primary" disabled={!canSubmit}>
              Basvuruyu Gonder
            </button>
            <button
              type="button"
              className="tb-btn-secondary"
              onClick={copyDraftText}
            >
              {copied ? "Metin Kopyalandi" : "Metni Kopyala"}
            </button>
          </div>
        </form>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Yayin Noktalari</h2>
        <div className="mt-3 grid gap-2 sm:grid-cols-2">
          {placements.map((placement) => (
            <div
              key={placement}
              className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 text-sm text-slate-700"
            >
              {placement}
            </div>
          ))}
        </div>
        <div className="mt-3 grid gap-2 sm:grid-cols-2">
          <div className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 text-sm text-slate-700">
            WhatsApp: {salesWhatsAppDigits ? `+${salesWhatsAppDigits}` : "Ayar bekleniyor"}
          </div>
          <div className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 text-sm text-slate-700">
            E-posta: {salesEmail || "Ayar bekleniyor"}
          </div>
          <div className="rounded-[8px] border border-[#dbe6df] bg-white px-3 py-2 text-sm text-slate-700 sm:col-span-2">
            Telefon: {salesPhone || "Ayar bekleniyor"}
          </div>
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={previewSlot}
          label="Canli Sponsor Onizleme"
          minHeight={140}
        />
      </section>
    </div>
  );
}
