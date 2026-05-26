"use client";

import { FormEvent, useEffect, useState } from "react";

import { createHome, getHomes } from "@/lib/api";
import { useSession } from "@/lib/use-session";
import { Home } from "@/lib/types";

export default function HomesPage() {
  const { ready, session } = useSession(true);
  const [homes, setHomes] = useState<Home[]>([]);
  const [loadingHomes, setLoadingHomes] = useState(true);
  const [error, setError] = useState("");
  const [info, setInfo] = useState("");
  const [saving, setSaving] = useState(false);

  const [title, setTitle] = useState("Evim");
  const [address, setAddress] = useState("");
  const [city, setCity] = useState("");
  const [district, setDistrict] = useState("");
  const [roomConfig, setRoomConfig] = useState("");
  const [floor, setFloor] = useState("");
  const [bathroomCount, setBathroomCount] = useState("1");
  const [sqm, setSqm] = useState("");
  const [hasElevator, setHasElevator] = useState(false);
  const [notes, setNotes] = useState("");
  const [photo, setPhoto] = useState<File | null>(null);

  async function loadHomes(token: string) {
    setLoadingHomes(true);
    try {
      const result = await getHomes(token);
      setHomes(result.data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Evler yüklenemedi.");
    } finally {
      setLoadingHomes(false);
    }
  }

  useEffect(() => {
    if (!session) return;
    void loadHomes(session.token);
  }, [session]);

  if (!ready || !session) {
    return (
      <div className="tb-surface tb-surface-pad text-sm text-slate-600">
        Yükleniyor...
      </div>
    );
  }

  const homesWithPhoto = homes.filter((home) => Boolean(home.photo_url)).length;
  const homesWithElevator = homes.filter((home) => home.has_elevator === 1).length;

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    if (!session) return;
    event.preventDefault();
    setError("");
    setInfo("");
    setSaving(true);
    const token = session.token;

    const form = new FormData();
    form.append("title", title.trim());
    form.append("address", address.trim());
    form.append("city", city.trim());
    form.append("district", district.trim());
    form.append("room_config", roomConfig.trim());
    form.append("floor", floor ? String(Number(floor)) : "0");
    form.append("bathroom_count", String(Number(bathroomCount) || 1));
    form.append("sqm", sqm ? String(Number(sqm)) : "");
    form.append("has_elevator", hasElevator ? "1" : "0");
    form.append("notes", notes.trim());
    if (photo) {
      form.append("photo", photo);
    }

    try {
      await createHome(token, form);
      setInfo("Ev kaydı oluşturuldu.");
      setAddress("");
      setCity("");
      setDistrict("");
      setRoomConfig("");
      setFloor("");
      setSqm("");
      setNotes("");
      setPhoto(null);
      await loadHomes(token);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Ev kaydı oluşturulamadı.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
      <section className="tb-surface tb-surface-pad">
        <h1 className="tb-heading">Evlerim</h1>
        <p className="tb-subheading">
          İlan açarken kullanacağın ev kayıtlarını burada yönet.
        </p>
        <div className="mt-3 flex flex-wrap gap-2">
          <span className="tb-chip">Toplam ev: {homes.length}</span>
          <span className="tb-chip border-sky-100 bg-sky-50 text-sky-700">
            Fotoğraflı: {homesWithPhoto}
          </span>
          <span className="tb-chip border-emerald-100 bg-emerald-50 text-emerald-700">
            Asansörlü: {homesWithElevator}
          </span>
        </div>
        <div className="mt-4 grid gap-3">
          {loadingHomes ? (
            <p className="text-sm text-slate-500">Yükleniyor...</p>
          ) : homes.length === 0 ? (
            <p className="tb-empty">Henüz ev kaydı yok.</p>
          ) : (
            homes.map((home) => (
              <article
                key={home.id}
                className="rounded-[8px] border border-[#dbe6df] bg-white p-4 transition hover:border-emerald-300 hover:bg-emerald-50/35"
              >
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <p className="text-base font-semibold text-slate-900">{home.title}</p>
                  <div className="flex flex-wrap gap-1">
                    <span className="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-600">
                      {home.room_config || "-"}
                    </span>
                    {home.has_elevator === 1 ? (
                      <span className="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] text-emerald-700">
                        Asansör
                      </span>
                    ) : null}
                  </div>
                </div>
                <p className="mt-1 text-sm text-slate-600">
                  {home.city} / {home.district} - Kat: {home.floor ?? "-"}
                </p>
                <p className="mt-1 text-xs text-slate-500">
                  {home.sqm ? `${home.sqm} m²` : "-"} - Banyo:{" "}
                  {home.bathroom_count}
                </p>
                {home.notes ? (
                  <p className="mt-1 text-xs text-slate-500">{home.notes}</p>
                ) : null}
                {home.photo_url ? (
                  <a
                    href={home.photo_url}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-2 inline-flex text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                  >
                    Fotoğrafı Aç
                  </a>
                ) : null}
              </article>
            ))
          )}
        </div>
      </section>

      <section className="tb-surface tb-surface-pad">
        <h2 className="text-xl font-bold text-slate-900">Yeni Ev Ekle</h2>
        <p className="mt-1 text-sm text-slate-600">
          Adres ve oda bilgisi ne kadar net olursa gelen teklifler o kadar hızlı olur.
        </p>
        <form className="mt-4 grid gap-3" onSubmit={onSubmit}>
          <input
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            placeholder="Ev başlığı"
            className="tb-input"
          />
          <input
            value={address}
            onChange={(event) => setAddress(event.target.value)}
            placeholder="Adres"
            className="tb-input"
          />
          <div className="grid grid-cols-2 gap-2">
            <input
              value={city}
              onChange={(event) => setCity(event.target.value)}
              placeholder="Şehir"
              required
              className="tb-input"
            />
            <input
              value={district}
              onChange={(event) => setDistrict(event.target.value)}
              placeholder="İlçe"
              className="tb-input"
            />
          </div>
          <div className="grid grid-cols-2 gap-2">
            <input
              value={roomConfig}
              onChange={(event) => setRoomConfig(event.target.value)}
              placeholder="Oda yapısı (2+1)"
              required
              className="tb-input"
            />
            <input
              type="number"
              value={sqm}
              onChange={(event) => setSqm(event.target.value)}
              placeholder="Metrekare"
              className="tb-input"
            />
          </div>
          <div className="grid grid-cols-2 gap-2">
            <input
              type="number"
              value={floor}
              onChange={(event) => setFloor(event.target.value)}
              placeholder="Kat"
              className="tb-input"
            />
            <input
              type="number"
              min={1}
              value={bathroomCount}
              onChange={(event) => setBathroomCount(event.target.value)}
              placeholder="Banyo sayısı"
              className="tb-input"
            />
          </div>
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <input
              type="checkbox"
              checked={hasElevator}
              onChange={(event) => setHasElevator(event.target.checked)}
              className="h-4 w-4 accent-emerald-600"
            />
            Asansör var
          </label>
          <textarea
            rows={3}
            value={notes}
            onChange={(event) => setNotes(event.target.value)}
            placeholder="Notlar"
            className="tb-textarea"
          />
          <input
            type="file"
            accept=".jpg,.jpeg,.png,.webp"
            onChange={(event) => setPhoto(event.target.files?.[0] ?? null)}
            className="tb-file"
          />

          {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}
          {info ? <p className="tb-alert tb-alert-success">{info}</p> : null}

          <button
            type="submit"
            disabled={saving}
            className="tb-btn-primary"
          >
            {saving ? "Kaydediliyor..." : "Evi Kaydet"}
          </button>
        </form>
      </section>
    </div>
  );
}
