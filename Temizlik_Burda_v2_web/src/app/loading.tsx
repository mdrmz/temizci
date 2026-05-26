export default function AppLoading() {
  return (
    <section className="tb-surface tb-surface-pad">
      <p className="text-xs font-semibold tracking-wide text-emerald-700">
        TEMIZCIBURADA
      </p>
      <h2 className="mt-1 text-lg font-bold text-slate-900">Sayfa yükleniyor...</h2>
      <p className="mt-1 text-sm text-slate-600">
        Veriler hazırlanıyor, birkaç saniye içinde ekran güncellenecek.
      </p>
      <div className="mt-4 grid gap-2 sm:grid-cols-3">
        <div className="h-20 animate-pulse rounded-[8px] border border-[#dbe6df] bg-slate-100" />
        <div className="h-20 animate-pulse rounded-[8px] border border-[#dbe6df] bg-slate-100" />
        <div className="h-20 animate-pulse rounded-[8px] border border-[#dbe6df] bg-slate-100" />
      </div>
    </section>
  );
}
