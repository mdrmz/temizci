import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import { headers } from "next/headers";
import "./globals.css";
import { AppShell } from "@/components/app-shell";
import { WebVitals } from "@/components/web-vitals";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  metadataBase: new URL("https://temizciburada.com"),
  title: "Temizlikçi Bul | Temizci Burada",
  description:
    "Temizlikçi arayanlar için TemizciBurada: ev temizliği, inşaat temizliği ve günlük destek hizmetlerinde hızlı ilan ve güvenilir teklif akışı.",
  keywords: [
    "temizlikçi",
    "temizlikçi bul",
    "ev temizliği",
    "inşaat temizliği",
    "temizlik hizmeti",
    "temizci burada",
    "gündelikçi",
    "İstanbul temizlikçi",
    "Ankara temizlikçi",
    "İzmir temizlikçi",
  ],
  applicationName: "TemizciBurada",
  category: "local services",
  authors: [{ name: "Piksel Analitik" }],
  creator: "Piksel Analitik",
  publisher: "Piksel Analitik",
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-image-preview": "large",
      "max-snippet": -1,
      "max-video-preview": -1,
    },
  },
  alternates: {
    canonical: "/",
    languages: {
      "tr-TR": "/",
    },
  },
  openGraph: {
    type: "website",
    locale: "tr_TR",
    url: "https://temizciburada.com",
    title: "Temizlikçi Bul | Temizci Burada",
    description:
      "Ev temizliği ve yerel temizlik hizmetlerinde ilan ver, teklif al ve doğru temizlikçiyi seç.",
    siteName: "TemizciBurada",
    images: [
      {
        url: "/hero-cleaner-v2.jpg",
        width: 1200,
        height: 630,
        alt: "TemizciBurada temizlik hizmetleri",
      },
    ],
  },
  twitter: {
    card: "summary_large_image",
    title: "Temizlikçi Bul | Temizci Burada",
    description:
      "Temizlikçi arayanlar için hızlı ilan, güvenilir teklif ve mesajlaşma.",
    images: ["/hero-cleaner-v2.jpg"],
  },
  appleWebApp: {
    capable: true,
    title: "TemizciBurada",
  },
  other: {
    "geo.region": "TR",
    "geo.placename": "Türkiye",
    "geo.position": "39.0;35.0",
    ICBM: "39.0, 35.0",
  },
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const adsenseClient =
    (process.env.NEXT_PUBLIC_ADSENSE_CLIENT ?? "").trim() ||
    "ca-pub-7494296790754581";
  const requestHeaders = await headers();
  const userAgent = requestHeaders.get("user-agent")?.toLowerCase() ?? "";
  const initialEmbedded = userAgent.includes("temizciburadaapp");

  return (
    <html
      lang="tr"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <head>
        {adsenseClient !== "" ? (
          <script
            async
            src={`https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${encodeURIComponent(adsenseClient)}`}
            crossOrigin="anonymous"
          />
        ) : null}
      </head>
      <body className="min-h-full flex flex-col">
        <WebVitals />
        <AppShell initialEmbedded={initialEmbedded}>{children}</AppShell>
      </body>
    </html>
  );
}
