"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";

import { AdSlot } from "@/components/ad-slot";
import {
  getMessageThreads,
  getProfile,
  removeProfileAvatar,
  sendMessage,
  updateProfile,
  uploadProfileAvatar,
} from "@/lib/api";
import { validatePasswordPolicyClient } from "@/lib/password-policy";
import { setSession } from "@/lib/session";
import { MessageThread } from "@/lib/types";
import { useSession } from "@/lib/use-session";

export default function ProfilePage() {
  const profileAdSlot =
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_PROFILE ??
    process.env.NEXT_PUBLIC_ADSENSE_SLOT_HOME_MID ??
    "";

  const { ready, session } = useSession(true);

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [city, setCity] = useState("");
  const [bio, setBio] = useState("");
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
  const [avatarSaving, setAvatarSaving] = useState(false);
  const [threads, setThreads] = useState<MessageThread[]>([]);
  const [threadsLoading, setThreadsLoading] = useState(true);
  const [selectedThreadKey, setSelectedThreadKey] = useState("");
  const [quickDraft, setQuickDraft] = useState("");
  const [quickSending, setQuickSending] = useState(false);
  const [quickError, setQuickError] = useState("");
  const [quickInfo, setQuickInfo] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [info, setInfo] = useState("");

  useEffect(() => {
    if (!session) return;
    getProfile(session.token)
      .then((res) => {
        setName(res.user.name ?? "");
        setPhone(res.user.phone ?? "");
        setCity(res.user.city ?? "");
        setBio(res.user.bio ?? "");
        setAvatarUrl(res.user.avatar_url ?? null);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Profil yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [session]);

  useEffect(() => {
    if (!session) return;
    setThreadsLoading(true);
    getMessageThreads(session.token)
      .then((res) => {
        setThreads(res.data);
        setSelectedThreadKey((prev) => {
          if (prev && res.data.some((thread) => thread.thread_key === prev)) {
            return prev;
          }
          return res.data[0]?.thread_key ?? "";
        });
      })
      .catch(() => {
        // Keep profile form usable even if messaging data fails.
      })
      .finally(() => setThreadsLoading(false));
  }, [session]);

  useEffect(() => {
    if (!avatarFile) {
      setAvatarPreview(null);
      return;
    }
    const objectUrl = URL.createObjectURL(avatarFile);
    setAvatarPreview(objectUrl);
    return () => {
      URL.revokeObjectURL(objectUrl);
    };
  }, [avatarFile]);

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
        Profil hazırlanıyor...
      </div>
    );
  }

  const completionFields = [name, session.user.email, phone, city, bio];
  const completionRatio =
    completionFields.filter((value) => Boolean(value && String(value).trim())).length /
    completionFields.length;
  const completionPercent = Math.round(completionRatio * 100);
  const selectedThread =
    threads.find((thread) => thread.thread_key === selectedThreadKey) ?? null;
  const effectiveAvatarUrl = avatarPreview ?? avatarUrl ?? session.user.avatar_url ?? null;
  const initials =
    name
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part[0]?.toUpperCase() ?? "")
      .join("") || "TB";

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    if (!session) return;
    event.preventDefault();
    setError("");
    setInfo("");
    setSaving(true);
    const token = session.token;

    const payload: Record<string, string> = {
      name: name.trim(),
      phone: phone.trim(),
      city: city.trim(),
      bio: bio.trim(),
    };

    if (currentPassword || newPassword) {
      if (currentPassword === newPassword && currentPassword.trim() !== "") {
        setError("Yeni şifre mevcut şifreyle aynı olamaz.");
        setSaving(false);
        return;
      }
      const passwordError = validatePasswordPolicyClient(newPassword, {
        email: session.user.email,
        name,
      });
      if (passwordError) {
        setError(passwordError);
        setSaving(false);
        return;
      }
      payload.current_password = currentPassword;
      payload.new_password = newPassword;
    }

    try {
      const result = await updateProfile(token, payload);
      setInfo("Profil güncellendi.");
      setCurrentPassword("");
      setNewPassword("");
      setSession({ token, user: result.user });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Güncelleme başarısız.");
    } finally {
      setSaving(false);
    }
  }

  async function onAvatarUpload() {
    if (!session || !avatarFile) return;
    setError("");
    setInfo("");
    setAvatarSaving(true);
    try {
      const result = await uploadProfileAvatar(session.token, avatarFile);
      setAvatarFile(null);
      setAvatarUrl(result.user.avatar_url ?? null);
      setSession({ token: session.token, user: result.user });
      setInfo("Profil fotografiniz guncellendi.");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Profil fotografi yuklenemedi.");
    } finally {
      setAvatarSaving(false);
    }
  }

  async function onAvatarRemove() {
    if (!session) return;
    setError("");
    setInfo("");
    setAvatarSaving(true);
    try {
      const result = await removeProfileAvatar(session.token);
      setAvatarFile(null);
      setAvatarUrl(result.user.avatar_url ?? null);
      setSession({ token: session.token, user: result.user });
      setInfo("Profil fotografi kaldirildi.");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Profil fotografi kaldirilamadi.");
    } finally {
      setAvatarSaving(false);
    }
  }

  async function onQuickSend(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!session || !selectedThread) return;
    const text = quickDraft.trim();
    if (!text) return;

    setQuickSending(true);
    setQuickError("");
    setQuickInfo("");
    try {
      const result = await sendMessage(session.token, {
        listing_id: selectedThread.listing_id,
        receiver_id: selectedThread.contact_id,
        message: text,
      });
      setQuickDraft("");
      setQuickInfo("Mesaj gonderildi.");

      setThreads((prev) => {
        const updated = prev.map((thread) =>
          thread.thread_key === selectedThread.thread_key
            ? {
                ...thread,
                last_message: text,
                last_message_at: result.message.created_at,
                last_message_from_me: true,
                unread_count: 0,
              }
            : thread,
        );
        const active = updated.find(
          (thread) => thread.thread_key === selectedThread.thread_key,
        );
        if (!active) return updated;
        return [
          active,
          ...updated.filter((thread) => thread.thread_key !== active.thread_key),
        ];
      });
    } catch (err) {
      setQuickError(err instanceof Error ? err.message : "Mesaj gonderilemedi.");
    } finally {
      setQuickSending(false);
    }
  }

  return (
    <div className="tb-page">
      <section className="tb-surface tb-surface-pad">
        <AdSlot
          slot={profileAdSlot}
          label="Sponsorlu Profil Alani"
          minHeight={120}
        />
      </section>

      <section className="tb-surface tb-surface-pad mx-auto w-full max-w-2xl">
        <h1 className="tb-heading">Profil</h1>
        <p className="tb-subheading">
          Hesap bilgilerini ve şifreni buradan güncelleyebilirsin.
        </p>
        <div className="mt-4">
          <div className="mb-2 flex items-center justify-between text-xs text-slate-600">
            <span>Profil tamamlanma</span>
            <span className="font-semibold text-slate-900">%{completionPercent}</span>
          </div>
          <div className="tb-progress">
            <div className="tb-progress-bar" style={{ width: `${completionPercent}%` }} />
          </div>
        </div>

        <div className="mt-5 rounded-[8px] border border-[#dbe6df] bg-white p-4">
          <h2 className="text-base font-bold text-slate-900">Profil Fotografi</h2>
          <div className="mt-3 flex flex-wrap items-center gap-3">
            {effectiveAvatarUrl ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={effectiveAvatarUrl}
                alt="Profil fotografi"
                className="h-16 w-16 rounded-full border border-[#dbe6df] object-cover"
              />
            ) : (
              <span className="inline-flex h-16 w-16 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-lg font-bold text-emerald-700">
                {initials}
              </span>
            )}
            <div className="flex-1">
              <input
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.heif,image/*"
                className="tb-file"
                onChange={(event) => setAvatarFile(event.target.files?.[0] ?? null)}
              />
              <div className="mt-2 flex flex-wrap gap-2">
                <button
                  type="button"
                  onClick={onAvatarUpload}
                  disabled={!avatarFile || avatarSaving}
                  className="tb-btn-primary"
                >
                  {avatarSaving ? "Yukleniyor..." : "Fotografi Yukle"}
                </button>
                <button
                  type="button"
                  onClick={onAvatarRemove}
                  disabled={avatarSaving || (!avatarFile && !avatarUrl && !session.user.avatar_url)}
                  className="tb-btn-secondary"
                >
                  Fotografi Kaldir
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="mt-5 rounded-[8px] border border-[#dbe6df] bg-white p-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="text-base font-bold text-slate-900">Profilden Hizli Mesaj</h2>
            <Link href="/messages" className="tb-btn-secondary">
              Tum Mesajlar
            </Link>
          </div>
          <p className="mt-1 text-sm text-slate-600">
            Sadece ilan surecindeki kisilerle mesajlasabilirsin.
          </p>

          {threadsLoading ? (
            <p className="mt-3 text-sm text-slate-600">Mesaj listesi yukleniyor...</p>
          ) : threads.length === 0 ? (
            <p className="tb-empty mt-3">
              Henuz aktif bir mesajlasma yok. Ilan veya teklif akisindan konusma
              baslatinca burada da hizli mesaj gonderebilirsin.
            </p>
          ) : (
            <form className="mt-3 grid gap-3" onSubmit={onQuickSend}>
              <label>
                <span className="mb-1 block text-sm font-medium text-slate-700">
                  Konusma Sec
                </span>
                <select
                  className="tb-select"
                  value={selectedThreadKey}
                  onChange={(event) => setSelectedThreadKey(event.target.value)}
                >
                  {threads.map((thread) => (
                    <option key={thread.thread_key} value={thread.thread_key}>
                      {thread.contact_name} - {thread.listing_title}
                    </option>
                  ))}
                </select>
              </label>

              {selectedThread ? (
                <p className="text-xs text-slate-500">
                  Son mesaj: {selectedThread.last_message}
                </p>
              ) : null}

              <textarea
                rows={3}
                maxLength={1200}
                value={quickDraft}
                onChange={(event) => setQuickDraft(event.target.value)}
                className="tb-textarea"
                placeholder="Mesajinizi yazin..."
              />

              {quickError ? <p className="tb-alert tb-alert-error">{quickError}</p> : null}
              {quickInfo ? <p className="tb-alert tb-alert-success">{quickInfo}</p> : null}

              <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-xs text-slate-500">{quickDraft.trim().length}/1200</p>
                <button
                  type="submit"
                  disabled={!selectedThread || quickDraft.trim() === "" || quickSending}
                  className="tb-btn-primary"
                >
                  {quickSending ? "Gonderiliyor..." : "Mesaj Gonder"}
                </button>
              </div>
            </form>
          )}
        </div>

        <form className="mt-5 grid gap-3" onSubmit={onSubmit}>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Ad Soyad
            </span>
            <input
              value={name}
              onChange={(event) => setName(event.target.value)}
              className="tb-input"
            />
          </label>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Telefon
            </span>
            <input
              value={phone}
              onChange={(event) => setPhone(event.target.value)}
              className="tb-input"
            />
          </label>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Şehir
            </span>
            <input
              value={city}
              onChange={(event) => setCity(event.target.value)}
              className="tb-input"
            />
          </label>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Bio
            </span>
            <textarea
              rows={3}
              value={bio}
              onChange={(event) => setBio(event.target.value)}
              className="tb-textarea"
            />
          </label>
          <div className="grid gap-3 md:grid-cols-2">
            <label>
              <span className="mb-1 block text-sm font-medium text-slate-700">
                Mevcut Şifre
              </span>
              <input
                type="password"
                value={currentPassword}
                onChange={(event) => setCurrentPassword(event.target.value)}
                className="tb-input"
              />
            </label>
            <label>
              <span className="mb-1 block text-sm font-medium text-slate-700">
                Yeni Şifre
              </span>
              <input
                type="password"
                value={newPassword}
                onChange={(event) => setNewPassword(event.target.value)}
                minLength={8}
                maxLength={72}
                className="tb-input"
              />
              <span className="mt-1 block text-xs text-slate-500">
                En az 8 karakter, en az 1 harf ve 1 rakam.
              </span>
            </label>
          </div>

          {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}
          {info ? <p className="tb-alert tb-alert-success">{info}</p> : null}

          <button
            type="submit"
            disabled={saving}
            className="tb-btn-primary"
          >
            {saving ? "Kaydediliyor..." : "Profili Güncelle"}
          </button>
        </form>
      </section>
    </div>
  );
}
