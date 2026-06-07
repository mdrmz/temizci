export interface CityTarget {
  slug: string;
  name: string;
  displayName: string;
  region: string;
  latitude: number;
  longitude: number;
}

export const CITY_TARGETS: CityTarget[] = [
  {
    slug: "istanbul",
    name: "Istanbul",
    displayName: "İstanbul",
    region: "TR-34",
    latitude: 41.0082,
    longitude: 28.9784,
  },
  {
    slug: "ankara",
    name: "Ankara",
    displayName: "Ankara",
    region: "TR-06",
    latitude: 39.9334,
    longitude: 32.8597,
  },
  {
    slug: "izmir",
    name: "Izmir",
    displayName: "İzmir",
    region: "TR-35",
    latitude: 38.4237,
    longitude: 27.1428,
  },
  {
    slug: "bursa",
    name: "Bursa",
    displayName: "Bursa",
    region: "TR-16",
    latitude: 40.1885,
    longitude: 29.061,
  },
  {
    slug: "antalya",
    name: "Antalya",
    displayName: "Antalya",
    region: "TR-07",
    latitude: 36.8969,
    longitude: 30.7133,
  },
  {
    slug: "adana",
    name: "Adana",
    displayName: "Adana",
    region: "TR-01",
    latitude: 37,
    longitude: 35.3213,
  },
  {
    slug: "konya",
    name: "Konya",
    displayName: "Konya",
    region: "TR-42",
    latitude: 37.8746,
    longitude: 32.4932,
  },
  {
    slug: "kocaeli",
    name: "Kocaeli",
    displayName: "Kocaeli",
    region: "TR-41",
    latitude: 40.7654,
    longitude: 29.9408,
  },
  {
    slug: "gaziantep",
    name: "Gaziantep",
    displayName: "Gaziantep",
    region: "TR-27",
    latitude: 37.0662,
    longitude: 37.3833,
  },
  {
    slug: "mersin",
    name: "Mersin",
    displayName: "Mersin",
    region: "TR-33",
    latitude: 36.8121,
    longitude: 34.6415,
  },
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

export function cityTargetFromSlug(slug: string): CityTarget {
  const exact = CITY_TARGETS.find((city) => city.slug === slug);
  if (exact) {
    return exact;
  }

  const name = cityNameFromSlug(slug);
  return {
    slug,
    name,
    displayName: name,
    region: "TR",
    latitude: 39,
    longitude: 35,
  };
}
