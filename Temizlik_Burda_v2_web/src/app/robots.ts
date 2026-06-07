import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: [
          "/admin",
          "/api",
          "/dashboard",
          "/messages",
          "/offers",
          "/homes",
          "/profile",
          "/listings/new",
        ],
      },
    ],
    sitemap: "https://temizciburada.com/sitemap.xml",
    host: "https://temizciburada.com",
  };
}
