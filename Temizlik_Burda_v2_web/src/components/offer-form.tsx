"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";

import { submitOffer } from "@/lib/api";
import { getSession } from "@/lib/session";

interface OfferFormProps {
  listingId: number;
}

export function OfferForm({ listingId }: OfferFormProps) {
  const session = getSession();
  const [price, setPrice] = useState("");
  const [message, setMessage] = useState("");
  const [info, setInfo] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  if (!session) {
    return (
      <section className="tb-surface tb-surface-pad mt-4">
        <h3 className="text-base font-bold text-slate-900">Teklif Ver</h3>
        <p className="mt-1 text-sm text-slate-600">
          Teklif göndermek için önce giriş yapman gerekiyor.
        </p>
        <Link
          href="/login"
          className="tb-btn-primary mt-3 inline-flex"
        >
          Giriş Yap
        </Link>
      </section>
    );
  }

  if (session.user.role !== "worker") {
    return (
      <section className="tb-surface tb-surface-pad mt-4">
        <h3 className="text-base font-bold text-amber-900">Teklif Ver</h3>
        <p className="tb-alert tb-alert-warning mt-2">
          Bu işlem yalnızca hizmet veren hesaplarla yapılabilir.
        </p>
      </section>
    );
  }

  const token = session.token;

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setInfo("");
    setError("");

    const normalizedPrice = Number(price.replace(",", "."));
    if (!Number.isFinite(normalizedPrice) || normalizedPrice <= 0) {
      setError("Geçerli bir fiyat gir.");
      return;
    }

    setLoading(true);
    try {
      await submitOffer(token, {
        listing_id: listingId,
        price: normalizedPrice,
        message: message.trim(),
      });
      setInfo("Teklif başarıyla gönderildi.");
      setPrice("");
      setMessage("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Teklif gönderilemedi.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form
      onSubmit={onSubmit}
      className="tb-surface tb-surface-pad mt-4"
    >
      <h3 className="text-base font-bold text-slate-900">Teklif Ver</h3>
      <p className="mt-1 text-sm text-slate-600">
        Hizmet bedelini ve kısa notunu yaz, ilan sahibine doğrudan iletelim.
      </p>
      <div className="mt-3 grid gap-2">
        <input
          type="number"
          min={1}
          value={price}
          onChange={(event) => setPrice(event.target.value)}
          placeholder="Fiyat (TL)"
          className="tb-input"
          required
        />
        <textarea
          value={message}
          onChange={(event) => setMessage(event.target.value)}
          rows={3}
          placeholder="Teklif notu"
          className="tb-textarea"
          maxLength={500}
        />
        <p className="text-xs text-slate-500">
          Not alanı en fazla 500 karakterdir.
        </p>
      </div>
      {error ? (
        <p className="tb-alert tb-alert-error mt-3">{error}</p>
      ) : null}
      {info ? (
        <p className="tb-alert tb-alert-success mt-3">{info}</p>
      ) : null}
      <button
        type="submit"
        disabled={loading}
        className="tb-btn-primary mt-3"
      >
        {loading ? "Gönderiliyor..." : "Teklif Gönder"}
      </button>
    </form>
  );
}
