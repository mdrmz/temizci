"use client";

import { useEffect, useRef } from "react";

declare global {
  interface Window {
    adsbygoogle?: unknown[];
  }
}

interface AdSlotProps {
  slot?: string;
  label?: string;
  className?: string;
  minHeight?: number;
}

const ADSENSE_CLIENT = (process.env.NEXT_PUBLIC_ADSENSE_CLIENT ?? "").trim();
const AD_CONTACT_URL = (process.env.NEXT_PUBLIC_AD_CONTACT_URL ?? "/reklam-ver").trim();
const AD_CONTACT_LABEL = (process.env.NEXT_PUBLIC_AD_CONTACT_LABEL ?? "Hemen Basvur").trim();
const HOUSE_AD_LINK_URL = (process.env.NEXT_PUBLIC_HOUSE_AD_LINK_URL ?? "/listings/new").trim();
const HOUSE_AD_IMAGE_URL = (process.env.NEXT_PUBLIC_HOUSE_AD_IMAGE_URL ?? "/logo.png").trim();
const HOUSE_AD_TITLE = (
  process.env.NEXT_PUBLIC_HOUSE_AD_TITLE ?? "TemizciBurada ile daha hizli is bul"
).trim();
const HOUSE_AD_TEXT = (
  process.env.NEXT_PUBLIC_HOUSE_AD_TEXT ??
  "Ilan ver, teklifleri karsilastir ve sureci tek panelden yonet."
).trim();
const HOUSE_AD_CTA = (process.env.NEXT_PUBLIC_HOUSE_AD_CTA ?? "Detaylari Gor").trim();

export function AdSlot({
  slot = "",
  label = "Sponsorlu Alan",
  className = "",
  minHeight = 120,
}: AdSlotProps) {
  const initializedRef = useRef(false);
  const canRenderAd = ADSENSE_CLIENT !== "" && slot.trim() !== "";
  const fallbackContactUrl = AD_CONTACT_URL || "/reklam-ver";
  const fallbackContactLabel = AD_CONTACT_LABEL || "Hemen Basvur";
  const houseAdLinkUrl = HOUSE_AD_LINK_URL || "/listings/new";
  const houseAdImageUrl = HOUSE_AD_IMAGE_URL;
  const houseAdTitle = HOUSE_AD_TITLE || "TemizciBurada";
  const houseAdText = HOUSE_AD_TEXT || "TemizciBurada reklam alani";
  const houseAdCta = HOUSE_AD_CTA || "Detaylari Gor";

  useEffect(() => {
    if (!canRenderAd || initializedRef.current) {
      return;
    }

    try {
      (window.adsbygoogle = window.adsbygoogle || []).push({});
      initializedRef.current = true;
    } catch {
      // Ad blocker veya script gecikmesi durumunda sessizce devam et.
    }
  }, [canRenderAd]);

  return (
    <section className={`tb-ad-wrap ${className}`.trim()} aria-label="Reklam alani">
      <p className="tb-ad-label">{label}</p>
      <div className="tb-ad-frame" style={{ minHeight }}>
        {canRenderAd ? (
          <ins
            className="adsbygoogle"
            style={{ display: "block", width: "100%", minHeight }}
            data-ad-client={ADSENSE_CLIENT}
            data-ad-slot={slot.trim()}
            data-ad-format="auto"
            data-full-width-responsive="true"
          />
        ) : (
          <div className="tb-ad-fallback" style={{ minHeight }}>
            <div className="tb-ad-fallback-card">
              <p className="tb-ad-fallback-title">Buraya reklam verebilirsiniz</p>
              <p className="tb-ad-fallback-text">
                TemizciBurada&apos;da binlerce kullaniciya ulasmak icin bu alanda
                markanizi one cikarabilirsiniz.
              </p>
              <p className="tb-ad-fallback-badge">Simdilik TemizciBurada tanitim alani</p>
              <a className="tb-ad-fallback-cta" href={fallbackContactUrl}>
                {fallbackContactLabel}
              </a>
              <a className="tb-house-ad" href={houseAdLinkUrl}>
                {houseAdImageUrl ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={houseAdImageUrl}
                    alt="TemizciBurada tanitim"
                    className="tb-house-ad-image"
                  />
                ) : null}
                <span className="tb-house-ad-content">
                  <span className="tb-house-ad-title">{houseAdTitle}</span>
                  <span className="tb-house-ad-text">{houseAdText}</span>
                  <span className="tb-house-ad-cta">{houseAdCta}</span>
                </span>
              </a>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
