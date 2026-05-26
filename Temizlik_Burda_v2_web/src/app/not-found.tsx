import Link from "next/link";

export default function NotFoundPage() {
  return (
    <section className="tb-surface tb-surface-pad mx-auto w-full max-w-xl text-center">
      <p className="text-xs font-semibold tracking-wide text-emerald-700">404</p>
      <h1 className="mt-2 text-2xl font-bold text-slate-900">
        Aradığın sayfayı bulamadık
      </h1>
      <p className="mt-2 text-sm text-slate-600">
        Link değişmiş olabilir veya sayfa kaldırılmış olabilir.
      </p>
      <div className="mt-4 flex justify-center gap-2">
        <Link
          href="/"
          className="tb-btn-primary"
        >
          Ana Sayfa
        </Link>
        <Link
          href="/listings"
          className="tb-btn-secondary"
        >
          İlanlar
        </Link>
      </div>
    </section>
  );
}
