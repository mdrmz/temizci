export interface CityTarget {
  slug: string;
  name: string;
}

export const CITY_TARGETS: CityTarget[] = [
  { slug: "istanbul", name: "Istanbul" },
  { slug: "ankara", name: "Ankara" },
  { slug: "izmir", name: "Izmir" },
  { slug: "bursa", name: "Bursa" },
  { slug: "antalya", name: "Antalya" },
  { slug: "adana", name: "Adana" },
  { slug: "konya", name: "Konya" },
  { slug: "kocaeli", name: "Kocaeli" },
  { slug: "gaziantep", name: "Gaziantep" },
  { slug: "mersin", name: "Mersin" },
];

const TURKISH_CHAR_MAP: Record<string, string> = {
  ç: "c",
  Ç: "c",
  ğ: "g",
  Ğ: "g",
  ı: "i",
  İ: "i",
  ö: "o",
  Ö: "o",
  ş: "s",
  Ş: "s",
  ü: "u",
  Ü: "u",
};

export function toGeoSlug(value: string): string {
  const lowered = value
    .trim()
    .replace(/[çÇğĞıİöÖşŞüÜ]/g, (char) => TURKISH_CHAR_MAP[char] ?? char)
    .toLowerCase();

  return lowered
    .replace(/[^a-z0-9\s-]/g, "")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

export function cityNameFromSlug(slug: string): string {
  const exact = CITY_TARGETS.find((city) => city.slug === slug);
  if (exact) {
    return exact.name;
  }
  return slug
    .split("-")
    .filter((part) => part !== "")
    .map((part) => part[0]?.toUpperCase() + part.slice(1))
    .join(" ");
}
