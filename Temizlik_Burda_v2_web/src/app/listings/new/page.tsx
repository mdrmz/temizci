"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";

import { createListing, getCategories, getHomes } from "@/lib/api";
import { useSession } from "@/lib/use-session";
import { Category, Home } from "@/lib/types";

export default function NewListingPage() {
  const router = useRouter();
  const { ready, session } = useSession(true);

  const [categories, setCategories] = useState<Category[]>([]);
  const [homes, setHomes] = useState<Home[]>([]);
  const [homeId, setHomeId] = useState("");
  const [categorySlug, setCategorySlug] = useState("");
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [budget, setBudget] = useState("");
  const [preferredDate, setPreferredDate] = useState("");
  const [preferredTime, setPreferredTime] = useState("");
  const [propertyType, setPropertyType] = useState("daire");
  const [netSqm, setNetSqm] = useState("");
  const [floorCount, setFloorCount] = useState("");
  const [postConstructionLevel, setPostConstructionLevel] = useState("ince_is");
  const [debrisBagCount, setDebrisBagCount] = useState("");
  const [hasMaterials, setHasMaterials] = useState("unknown");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [info, setInfo] = useState("");
  const isConstructionListing = categorySlug === "insaat-temizligi";

  useEffect(() => {
    if (!session) return;
    const token = session.token;
    Promise.all([getCategories(), getHomes(token)])
      .then(([catRes, homesRes]) => {
        setCategories(catRes.data);
        setHomes(homesRes.data);
      })
      .catch((err) => {
        setError(
          err instanceof Error
            ? err.message
            : "Kategori ve ev listesi yüklenemedi.",
        );
      });
  }, [session]);

  if (!ready || !session) {
    return (
      <div className="tb-surface tb-surface-pad text-sm text-slate-600">
        Yükleniyor...
      </div>
    );
  }

  if (session.user.role !== "homeowner") {
    return (
      <div className="tb-surface tb-surface-pad">
        <p className="tb-alert tb-alert-warning">
          İlan açma ekranı yalnızca ev sahibi hesapları içindir.
        </p>
      </div>
    );
  }

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    if (!session) return;
    event.preventDefault();
    setError("");
    setInfo("");
    const token = session.token;

    if (!homeId || !categorySlug || !title.trim()) {
      setError("Ev, kategori ve başlık zorunludur.");
      return;
    }
    if (isConstructionListing && !netSqm) {
      setError("İnşaat temizliği ilanlarında net metrekare zorunludur.");
      return;
    }

    const propertyTypeLabelMap: Record<string, string> = {
      daire: "Daire",
      mustakil: "Müstakil Ev",
      ofis: "Ofis / Ticari Alan",
      villa: "Villa",
      diger: "Diğer",
    };
    const levelLabelMap: Record<string, string> = {
      kaba_insaat: "Kaba inşaat sonrası",
      ince_is: "İnce işçilik sonrası",
      teslim_oncesi: "Teslim öncesi son temizlik",
    };
    const materialLabelMap: Record<string, string> = {
      yes: "Malzemeler mevcut",
      no: "Malzeme desteği gerekiyor",
      unknown: "Durum telefonda netleşecek",
    };

    const normalizedDescription = description.trim();
    const constructionDetails = isConstructionListing
      ? [
          "Insaat Temizligi Detaylari:",
          `- Mulk tipi: ${propertyTypeLabelMap[propertyType] ?? propertyType}`,
          `- Net metrekare: ${netSqm || "-"}`,
          `- Kat sayisi: ${floorCount || "-"}`,
          `- Is seviyesi: ${levelLabelMap[postConstructionLevel] ?? postConstructionLevel}`,
          `- Moloz/cuval tahmini: ${debrisBagCount || "-"}`,
          `- Malzeme durumu: ${materialLabelMap[hasMaterials] ?? hasMaterials}`,
        ].join("\n")
      : "";

    const finalDescription = [normalizedDescription, constructionDetails]
      .filter((value) => value !== "")
      .join("\n\n");

    setLoading(true);
    try {
      const result = await createListing(token, {
        home_id: Number(homeId),
        category_slug: categorySlug,
        title: title.trim(),
        description: finalDescription,
        budget: budget ? Number(budget) : undefined,
        preferred_date: preferredDate || undefined,
        preferred_time: preferredTime || undefined,
      });
      setInfo(result.message);
      router.push(`/listings/${result.listing_id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "İlan oluşturulamadı.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="tb-surface tb-surface-pad">
      <h1 className="tb-heading">Yeni İlan Aç</h1>
      <p className="tb-subheading">
        İş detayını açık gir, daha kaliteli teklif al.
      </p>
      {homes.length === 0 ? (
        <p className="tb-alert tb-alert-warning mt-3">
          Önce en az bir ev kaydı eklemelisin.{" "}
          <Link className="font-semibold underline" href="/homes">
            Ev ekleme ekranına git
          </Link>
          .
        </p>
      ) : null}

      <form className="mt-5 grid gap-3 md:grid-cols-2" onSubmit={onSubmit}>
        <label className="md:col-span-1">
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Ev
          </span>
          <select
            value={homeId}
            onChange={(event) => setHomeId(event.target.value)}
            className="tb-select"
            required
            disabled={homes.length === 0}
          >
            <option value="">Ev seç</option>
            {homes.map((home) => (
              <option key={home.id} value={home.id}>
                {home.title} - {home.city} / {home.district}
              </option>
            ))}
          </select>
        </label>

        <label className="md:col-span-1">
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Kategori
          </span>
          <select
            value={categorySlug}
            onChange={(event) => setCategorySlug(event.target.value)}
            className="tb-select"
            required
            disabled={categories.length === 0}
          >
            <option value="">Kategori seç</option>
            {categories.map((category) => (
              <option key={category.id} value={category.slug}>
                {category.name}
              </option>
            ))}
          </select>
        </label>

        <label className="md:col-span-2">
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Başlık
          </span>
          <input
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            className="tb-input"
            required
          />
        </label>

        <label className="md:col-span-2">
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Açıklama
          </span>
          <textarea
            value={description}
            onChange={(event) => setDescription(event.target.value)}
            rows={5}
            className="tb-textarea"
          />
        </label>

        {isConstructionListing ? (
          <fieldset className="md:col-span-2 rounded-[8px] border border-[#dbe6df] bg-emerald-50/45 p-3">
            <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">
              İnşaat Temizliği Detayları
            </legend>
            <div className="mt-2 grid gap-3 md:grid-cols-2">
              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  Mülk tipi
                </span>
                <select
                  value={propertyType}
                  onChange={(event) => setPropertyType(event.target.value)}
                  className="tb-select"
                  required={isConstructionListing}
                >
                  <option value="daire">Daire</option>
                  <option value="mustakil">Müstakil Ev</option>
                  <option value="villa">Villa</option>
                  <option value="ofis">Ofis / Ticari Alan</option>
                  <option value="diger">Diğer</option>
                </select>
              </label>

              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  Net metrekare
                </span>
                <input
                  type="number"
                  min={1}
                  value={netSqm}
                  onChange={(event) => setNetSqm(event.target.value)}
                  className="tb-input"
                  required={isConstructionListing}
                />
              </label>

              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  Toplam kat sayısı
                </span>
                <input
                  type="number"
                  min={1}
                  value={floorCount}
                  onChange={(event) => setFloorCount(event.target.value)}
                  className="tb-input"
                />
              </label>

              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  İş seviyesi
                </span>
                <select
                  value={postConstructionLevel}
                  onChange={(event) => setPostConstructionLevel(event.target.value)}
                  className="tb-select"
                >
                  <option value="kaba_insaat">Kaba inşaat sonrası</option>
                  <option value="ince_is">İnce işçilik sonrası</option>
                  <option value="teslim_oncesi">Teslim öncesi son temizlik</option>
                </select>
              </label>

              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  Tahmini moloz/çuval adedi
                </span>
                <input
                  type="number"
                  min={0}
                  value={debrisBagCount}
                  onChange={(event) => setDebrisBagCount(event.target.value)}
                  className="tb-input"
                />
              </label>

              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  Temizlik malzemesi
                </span>
                <select
                  value={hasMaterials}
                  onChange={(event) => setHasMaterials(event.target.value)}
                  className="tb-select"
                >
                  <option value="unknown">Durum telefonda netleşecek</option>
                  <option value="yes">Malzemeler mevcut</option>
                  <option value="no">Malzeme desteği gerekiyor</option>
                </select>
              </label>
            </div>
          </fieldset>
        ) : null}

        <label>
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Bütçe (TL)
          </span>
          <input
            type="number"
            value={budget}
            onChange={(event) => setBudget(event.target.value)}
            className="tb-input"
          />
        </label>

        <label>
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Tercih Saati
          </span>
          <input
            type="time"
            value={preferredTime}
            onChange={(event) => setPreferredTime(event.target.value)}
            className="tb-input"
          />
        </label>

        <label>
          <span className="mb-1 block text-sm font-medium text-slate-700">
            Tercih Tarihi
          </span>
          <input
            type="date"
            value={preferredDate}
            onChange={(event) => setPreferredDate(event.target.value)}
            className="tb-input"
          />
        </label>

        <div className="md:col-span-2">
          {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}
          {info ? <p className="tb-alert tb-alert-success">{info}</p> : null}
        </div>

        <button
          type="submit"
          disabled={loading || homes.length === 0 || categories.length === 0}
          className="tb-btn-primary md:col-span-2"
        >
          {loading ? "Oluşturuluyor..." : "İlanı Oluştur"}
        </button>
      </form>
    </section>
  );
}
