import type { MetadataRoute } from "next";

import { getListings } from "@/lib/api";
import { CITY_TARGETS } from "@/lib/geo";

const SITE_URL = "https://temizciburada.com";

async function fetchAllListingsForSitemap() {
  const firstPage = await getListings({
    status: "all",
    sort: "newest",
    page: 1,
  }).catch(() => null);

  if (!firstPage) {
    return [];
  }

  const pages = Math.max(1, firstPage.pagination.pages || 1);
  const maxPages = Math.min(pages, 40);
  const all = [...firstPage.data];

  for (let page = 2; page <= maxPages; page += 1) {
    const payload = await getListings({
      status: "all",
      sort: "newest",
      page,
    }).catch(() => null);
    if (!payload) break;
    all.push(...payload.data);
  }

  return all;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const now = new Date();
  const staticRoutes: MetadataRoute.Sitemap = [
    {
      url: SITE_URL,
      lastModified: now,
      changeFrequency: "daily",
      priority: 1,
    },
    {
      url: `${SITE_URL}/listings`,
      lastModified: now,
      changeFrequency: "hourly",
      priority: 0.9,
    },
    {
      url: `${SITE_URL}/register`,
      lastModified: now,
      changeFrequency: "weekly",
      priority: 0.4,
    },
    {
      url: `${SITE_URL}/login`,
      lastModified: now,
      changeFrequency: "weekly",
      priority: 0.3,
    },
    {
      url: `${SITE_URL}/privacy`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.35,
    },
    {
      url: `${SITE_URL}/gizlilik-politikasi`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.35,
    },
    {
      url: `${SITE_URL}/kvkk-aydinlatma`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.35,
    },
    {
      url: `${SITE_URL}/cerez-politikasi`,
      lastModified: now,
      changeFrequency: "monthly",
      priority: 0.3,
    },
    {
      url: `${SITE_URL}/reklam-ver`,
      lastModified: now,
      changeFrequency: "weekly",
      priority: 0.55,
    },
    {
      url: `${SITE_URL}/destek`,
      lastModified: now,
      changeFrequency: "weekly",
      priority: 0.45,
    },
    {
      url: `${SITE_URL}/satici`,
      lastModified: now,
      changeFrequency: "weekly",
      priority: 0.45,
    },
  ];

  const cityRoutes: MetadataRoute.Sitemap = CITY_TARGETS.map((city) => ({
    url: `${SITE_URL}/hizmet/${city.slug}`,
    lastModified: now,
    changeFrequency: "daily",
    priority: 0.75,
  }));

  const listings = await fetchAllListingsForSitemap();
  const listingRoutes: MetadataRoute.Sitemap = listings.map((listing) => ({
    url: `${SITE_URL}/listings/${listing.id}`,
    lastModified: listing.created_at ? new Date(listing.created_at) : now,
    changeFrequency: "daily",
    priority: 0.8,
  }));

  return [...staticRoutes, ...cityRoutes, ...listingRoutes];
}
