"use client";

import { useReportWebVitals } from "next/web-vitals";

const API_BASE = (process.env.NEXT_PUBLIC_API_BASE_URL ?? "https://temizciburada.com/api").replace(
  /\/$/,
  "",
);
const ENDPOINT = `${API_BASE}/metrics/web_vitals`;

export function WebVitals() {
  useReportWebVitals((metric) => {
    const payload = {
      id: metric.id,
      name: metric.name,
      value: metric.value,
      rating: metric.rating,
      delta: metric.delta,
      navigationType: metric.navigationType,
      path:
        typeof window !== "undefined"
          ? `${window.location.pathname}${window.location.search}`
          : "",
      userAgent: typeof navigator !== "undefined" ? navigator.userAgent : "",
      recordedAt: new Date().toISOString(),
    };

    const body = JSON.stringify(payload);

    if (typeof navigator !== "undefined" && typeof navigator.sendBeacon === "function") {
      const blob = new Blob([body], { type: "application/json" });
      navigator.sendBeacon(ENDPOINT, blob);
      return;
    }

    fetch(ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      keepalive: true,
      body,
    }).catch(() => {
      // Telemetri hatalari kullanici akisina etkimesin.
    });
  });

  return null;
}
