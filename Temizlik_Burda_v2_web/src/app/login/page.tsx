"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

import { login } from "@/lib/api";
import { setSession } from "@/lib/session";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setLoading(true);
    try {
      const result = await login(email.trim(), password);
      setSession({ token: result.token, user: result.user });
      router.push("/dashboard");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Giriş başarısız.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto grid w-full max-w-4xl gap-4 lg:grid-cols-[0.95fr_1.05fr]">
      <article className="tb-surface tb-surface-pad">
        <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">
          Hesabına Geri Dön
        </p>
        <h1 className="mt-2 text-2xl font-extrabold text-slate-900">
          Giriş Yap
        </h1>
        <p className="mt-2 text-sm leading-6 text-slate-600">
          Hesabınla giriş yapıp ilan ve teklif akışını tek panelde yönet.
        </p>
        <div className="mt-4 grid gap-2">
          <div className="tb-soft-card">
            <p className="text-xs text-slate-500">Hızlı Erişim</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              İlanlarını, tekliflerini ve ev kayıtlarını aynı hesapla takip et.
            </p>
          </div>
          <div className="tb-soft-card">
            <p className="text-xs text-slate-500">Canlı Akış</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              Yeni ilanlar ve teklif değişiklikleri anlık yansır.
            </p>
          </div>
          <div className="tb-soft-card">
            <p className="text-xs text-slate-500">Tek Hesap</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">
              Web, iOS ve Android tarafında aynı kullanıcıyla devam et.
            </p>
          </div>
        </div>
      </article>

      <article className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Hesap Bilgileri</h2>
        <form className="mt-4 space-y-3" onSubmit={onSubmit}>
          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">
              E-posta
            </span>
            <input
              type="email"
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              className="tb-input"
              placeholder="ornek@mail.com"
            />
          </label>
          <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Şifre
            </span>
            <input
              type="password"
              required
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              className="tb-input"
              placeholder="Şifren"
            />
          </label>
          {error ? <p className="tb-alert tb-alert-error">{error}</p> : null}
          <button
            type="submit"
            disabled={loading}
            className="tb-btn-primary w-full"
          >
            {loading ? "Giriş yapılıyor..." : "Giriş Yap"}
          </button>
        </form>

        <p className="mt-4 text-sm text-slate-600">
          Hesabın yok mu?{" "}
          <Link className="font-semibold text-emerald-700" href="/register">
            Kayıt ol
          </Link>
        </p>
      </article>
    </section>
  );
}
