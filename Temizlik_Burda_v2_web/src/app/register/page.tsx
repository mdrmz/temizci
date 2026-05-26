"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

import { register } from "@/lib/api";
import { validatePasswordPolicyClient } from "@/lib/password-policy";
import { setSession } from "@/lib/session";

type Role = "homeowner" | "worker";

export default function RegisterPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [phone, setPhone] = useState("");
  const [city, setCity] = useState("");
  const [role, setRole] = useState<Role>("homeowner");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");

    if (role === "worker" && phone.trim().length < 10) {
      setError("Hizmet veren kaydı için telefon zorunludur.");
      return;
    }
    const passwordError = validatePasswordPolicyClient(password, {
      email,
      name,
    });
    if (passwordError) {
      setError(passwordError);
      return;
    }

    setLoading(true);
    try {
      const result = await register({
        name: name.trim(),
        email: email.trim(),
        password,
        role,
        phone: phone.trim(),
        city: city.trim(),
      });
      setSession({ token: result.token, user: result.user });
      router.push("/dashboard");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kayıt başarısız.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className="mx-auto grid w-full max-w-5xl gap-4 lg:grid-cols-[1.12fr_0.88fr]">
      <article className="tb-surface tb-surface-pad">
        <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">
          Yeni Başlangıç
        </p>
        <h1 className="mt-2 text-2xl font-extrabold text-slate-900">
          Yeni Hesap Oluştur
        </h1>
        <p className="mt-2 text-sm leading-6 text-slate-600">
          Ev sahibi veya hizmet veren olarak kayıt ol, ilan ve teklif akışını
          hemen kullanmaya başla.
        </p>

        <form className="mt-5 grid gap-3 md:grid-cols-2" onSubmit={onSubmit}>
          <label className="md:col-span-2">
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Rol
            </span>
            <div className="tb-segment">
              <button
                type="button"
                className={`tb-segment-btn ${role === "homeowner" ? "tb-segment-btn-active" : ""}`}
                onClick={() => setRole("homeowner")}
              >
                Ev Sahibi
              </button>
              <button
                type="button"
                className={`tb-segment-btn ${role === "worker" ? "tb-segment-btn-active" : ""}`}
                onClick={() => setRole("worker")}
              >
                Hizmet Veren
              </button>
            </div>
          </label>
          <label className="md:col-span-2">
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Ad Soyad
            </span>
            <input
              required
              value={name}
              onChange={(event) => setName(event.target.value)}
              className="tb-input"
            />
          </label>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              E-posta
            </span>
            <input
              type="email"
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              className="tb-input"
            />
          </label>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Şifre
            </span>
            <input
              type="password"
              minLength={8}
              maxLength={72}
              required
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              className="tb-input"
            />
            <span className="mt-1 block text-xs text-slate-500">
              En az 8 karakter, en az 1 harf ve 1 rakam.
            </span>
          </label>
          <label>
            <span className="mb-1 block text-sm font-medium text-slate-700">
              Telefon
            </span>
            <input
              value={phone}
              onChange={(event) => setPhone(event.target.value)}
              placeholder="05xx xxx xx xx"
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

          {error ? (
            <p className="tb-alert tb-alert-error md:col-span-2">{error}</p>
          ) : null}

          <button
            type="submit"
            disabled={loading}
            className="tb-btn-primary mt-1 w-full md:col-span-2"
          >
            {loading ? "Kayıt oluşturuluyor..." : "Kayıt Ol"}
          </button>
        </form>

        <p className="mt-4 text-sm text-slate-600">
          Hesabın var mı?{" "}
          <Link className="font-semibold text-emerald-700" href="/login">
            Giriş yap
          </Link>
        </p>
      </article>

      <article className="tb-surface tb-surface-pad">
        <h2 className="text-lg font-bold text-slate-900">Rol Seçimi</h2>
        <p className="mt-1 text-sm text-slate-600">
          Kayıt sırasında seçtiğin rol, panelde gördüğün hızlı aksiyonları belirler.
        </p>
        <div className="mt-4 grid gap-2">
          <div
            className={`tb-soft-card ${role === "homeowner" ? "border-emerald-300 bg-emerald-50/45" : ""}`}
          >
            <p className="text-sm font-semibold text-slate-900">Ev Sahibi</p>
            <p className="mt-1 text-xs leading-5 text-slate-600">
              Ev kaydı ekler, ilan açar ve gelen teklifleri yönetirsin.
            </p>
          </div>
          <div
            className={`tb-soft-card ${role === "worker" ? "border-emerald-300 bg-emerald-50/45" : ""}`}
          >
            <p className="text-sm font-semibold text-slate-900">Hizmet Veren</p>
            <p className="mt-1 text-xs leading-5 text-slate-600">
              Uygun ilanları filtreleyip teklif gönderir, iş akışını takip edersin.
            </p>
          </div>
          <div className="tb-empty">
            Telefon bilgisi özellikle hizmet veren hesaplarda eşleşme hızını artırır.
          </div>
        </div>
      </article>
    </section>
  );
}
